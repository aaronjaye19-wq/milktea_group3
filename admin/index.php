<?php
$page_title = 'Sales Report';
require_once __DIR__ . '/includes/admin-header.php';

$today = new DateTimeImmutable('today');
$default_from = $today->modify('first day of this month')->format('Y-m-d');
$default_to = $today->format('Y-m-d');
$parse_date = static function ($value, $fallback) {
    if (!is_string($value) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value)) {
        return $fallback;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return $fallback;
    }

    return $value;
};
$date_from = $parse_date($_GET['from'] ?? null, $default_from);
$date_to = $parse_date($_GET['to'] ?? null, $default_to);
if ($date_from > $date_to) {
    [$date_from, $date_to] = [$date_to, $date_from];
}

$order_statuses = ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Completed', 'Cancelled'];
$status_badge = [
    'Pending' => 'badge-pending',
    'Confirmed' => 'badge-confirmed',
    'Preparing' => 'badge-preparing',
    'Out for Delivery' => 'badge-delivery',
    'Completed' => 'badge-completed',
    'Cancelled' => 'badge-cancelled',
];
$payment_methods = ['Cash on Delivery', 'GCash', 'Maya'];
$categories = $pdo->query('SELECT DISTINCT category FROM products WHERE category <> \'\' ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
$selected_status = $_GET['status'] ?? '';
$selected_status = is_string($selected_status) && in_array($selected_status, $order_statuses, true) ? $selected_status : '';
$selected_payment = $_GET['payment'] ?? '';
$selected_payment = is_string($selected_payment) && in_array($selected_payment, $payment_methods, true) ? $selected_payment : '';
$selected_category = $_GET['category'] ?? '';
$selected_category = is_string($selected_category) && in_array($selected_category, $categories, true) ? $selected_category : '';
$parse_amount = static function ($value) {
    if (!is_string($value) || trim($value) === '' || !is_numeric($value)) {
        return null;
    }

    $amount = (float)$value;
    return is_finite($amount) && $amount >= 0 ? $amount : null;
};
$min_total = $parse_amount($_GET['min_total'] ?? null);
$max_total = $parse_amount($_GET['max_total'] ?? null);
if ($min_total !== null && $max_total !== null && $min_total > $max_total) {
    [$min_total, $max_total] = [$max_total, $min_total];
}

$where = ['o.created_at >= ?', 'o.created_at < DATE_ADD(?, INTERVAL 1 DAY)'];
$params = [$date_from, $date_to];
if ($selected_status !== '') {
    $where[] = 'o.status = ?';
    $params[] = $selected_status;
}
if ($selected_payment !== '') {
    $where[] = 'o.payment_method = ?';
    $params[] = $selected_payment;
}
if ($selected_category !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM order_items category_items INNER JOIN products category_products ON category_products.id = category_items.product_id WHERE category_items.order_id = o.id AND category_products.category = ?)';
    $params[] = $selected_category;
}
if ($min_total !== null) {
    $where[] = 'o.total_amount >= ?';
    $params[] = $min_total;
}
if ($max_total !== null) {
    $where[] = 'o.total_amount <= ?';
    $params[] = $max_total;
}
$where_sql = implode(' AND ', $where);

if ($selected_category !== '') {
    $summary_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) AS matching_orders,
        COALESCE(SUM(CASE WHEN o.status <> 'Cancelled' THEN oi.subtotal ELSE 0 END), 0) AS total_sales
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.id
        INNER JOIN products p ON p.id = oi.product_id
        WHERE $where_sql AND p.category = ?");
    $summary_stmt->execute(array_merge($params, [$selected_category]));
} else {
    $summary_stmt = $pdo->prepare("SELECT COUNT(*) AS matching_orders,
        COALESCE(SUM(CASE WHEN o.status <> 'Cancelled' THEN o.total_amount ELSE 0 END), 0) AS total_sales
        FROM orders o WHERE $where_sql");
    $summary_stmt->execute($params);
}
$report_summary = $summary_stmt->fetch();
$total_sales = (float)$report_summary['total_sales'];
$matching_orders = (int)$report_summary['matching_orders'];

$report_per_page = 15;
$report_page = max(1, (int)($_GET['page'] ?? 1));
$report_pages = max(1, (int)ceil($matching_orders / $report_per_page));
$report_page = min($report_page, $report_pages);
$report_stmt = $pdo->prepare("SELECT o.id, o.order_reference, o.customer_name, o.status, o.payment_method, o.total_amount, o.created_at,
    (SELECT GROUP_CONCAT(DISTINCT p.category ORDER BY p.category SEPARATOR ', ')
     FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = o.id) AS product_categories
    FROM orders o WHERE $where_sql ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
foreach ($params as $index => $value) {
    $report_stmt->bindValue($index + 1, $value);
}
$report_stmt->bindValue(count($params) + 1, $report_per_page, PDO::PARAM_INT);
$report_stmt->bindValue(count($params) + 2, ($report_page - 1) * $report_per_page, PDO::PARAM_INT);
$report_stmt->execute();
$report_orders = $report_stmt->fetchAll();
$report_filters = [
    'from' => $date_from,
    'to' => $date_to,
    'status' => $selected_status,
    'payment' => $selected_payment,
    'min_total' => $min_total === null ? '' : number_format($min_total, 2, '.', ''),
    'max_total' => $max_total === null ? '' : number_format($max_total, 2, '.', ''),
    'category' => $selected_category,
];
$report_page_url = static fn($page) => app_url('admin/index.php?' . http_build_query($report_filters + ['page' => $page]));
?>

<section class="analytics-section" aria-labelledby="sales-report-title">
    <div class="analytics-toolbar">
        <div>
            <p class="analytics-eyebrow">SALES PERFORMANCE</p>
            <h3 id="sales-report-title">Sales report</h3>
            <p class="analytics-caption">Choose a date range first, then narrow the report with optional filters. Cancelled orders do not contribute to Total Sales.</p>
        </div>
    </div>

    <form class="sales-report-filters" method="get" action="<?= app_url('admin/index.php') ?>">
        <fieldset class="report-primary-filter">
            <legend>Date range</legend>
            <div class="report-filter-fields">
                <div class="analytics-date-field"><label for="report-from">From</label><input id="report-from" type="date" name="from" value="<?= sanitize($date_from) ?>" required></div>
                <div class="analytics-date-field"><label for="report-to">To</label><input id="report-to" type="date" name="to" value="<?= sanitize($date_to) ?>" required></div>
            </div>
        </fieldset>
        <fieldset class="report-additional-filters">
            <legend>Additional filters</legend>
            <div class="report-filter-fields">
                <div class="analytics-date-field"><label for="report-status">Order status</label><select id="report-status" name="status"><option value="">All statuses</option><?php foreach ($order_statuses as $status): ?><option value="<?= sanitize($status) ?>" <?= $selected_status === $status ? 'selected' : '' ?>><?= sanitize($status) ?></option><?php endforeach; ?></select></div>
                <div class="analytics-date-field"><label for="report-payment">Payment method</label><select id="report-payment" name="payment"><option value="">All payment methods</option><?php foreach ($payment_methods as $method): ?><option value="<?= sanitize($method) ?>" <?= $selected_payment === $method ? 'selected' : '' ?>><?= sanitize($method) ?></option><?php endforeach; ?></select></div>
                <div class="analytics-date-field"><label for="report-category">Product category</label><select id="report-category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= sanitize($category) ?>" <?= $selected_category === $category ? 'selected' : '' ?>><?= sanitize($category) ?></option><?php endforeach; ?></select></div>
                <div class="analytics-date-field"><label for="report-min-total">Min. order total</label><input id="report-min-total" type="number" name="min_total" min="0" step="0.01" inputmode="decimal" value="<?= $min_total === null ? '' : sanitize(number_format($min_total, 2, '.', '')) ?>" placeholder="Any"></div>
                <div class="analytics-date-field"><label for="report-max-total">Max. order total</label><input id="report-max-total" type="number" name="max_total" min="0" step="0.01" inputmode="decimal" value="<?= $max_total === null ? '' : sanitize(number_format($max_total, 2, '.', '')) ?>" placeholder="Any"></div>
            </div>
        </fieldset>
        <div class="report-filter-actions"><button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-secondary" href="<?= app_url('admin/index.php') ?>">Reset filters</a></div>
    </form>

    <div class="report-summary-row">
        <div class="stat-card report-total-sales"><div class="stat-label">Total Sales</div><div class="stat-value"><?= format_price($total_sales) ?></div><div class="stat-sub"><?= number_format($matching_orders) ?> matching orders · cancelled orders excluded from sales</div></div>
        <p class="report-result-count" role="status"><?= number_format($matching_orders) ?> matching <?= $matching_orders === 1 ? 'order' : 'orders' ?></p>
    </div>

    <div class="admin-card report-table-card">
        <div class="admin-card-header"><span class="admin-card-title">Filtered orders</span><span class="report-range-label"><?= sanitize(date('M j, Y', strtotime($date_from))) ?> – <?= sanitize(date('M j, Y', strtotime($date_to))) ?></span></div>
        <div class="report-table-scroll">
            <table class="admin-table report-table">
                <thead><tr><th>Order</th><th>Customer</th><th>Product categories</th><th>Order status</th><th>Payment</th><th>Total sales</th><th>Date</th><th>Details</th></tr></thead>
                <tbody>
                    <?php if (empty($report_orders)): ?>
                    <tr><td colspan="8" class="report-empty">No orders match these filters.</td></tr>
                    <?php else: ?>
                    <?php foreach ($report_orders as $order): ?>
                    <tr>
                        <td class="report-order-reference">#<?= sanitize($order['order_reference'] ?: str_pad($order['id'], 6, '0', STR_PAD_LEFT)) ?></td>
                        <td><?= sanitize($order['customer_name']) ?></td>
                        <td><?= sanitize($order['product_categories'] ?: '—') ?></td>
                        <td><span class="badge <?= $status_badge[$order['status']] ?? 'badge-pending' ?>"><?= sanitize($order['status']) ?></span></td>
                        <td><?= sanitize($order['payment_method']) ?></td>
                        <td><?= format_price($order['total_amount']) ?></td>
                        <td><?= sanitize(date('M j, Y', strtotime($order['created_at']))) ?></td>
                        <td><a href="<?= app_url('admin/order-detail.php?id=' . (int)$order['id']) ?>" class="btn btn-secondary btn-sm">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($report_pages > 1): ?>
        <nav class="pagination" aria-label="Sales report pagination">
            <?php if ($report_page > 1): ?><a href="<?= sanitize($report_page_url($report_page - 1)) ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
            <?php for ($page = 1; $page <= $report_pages; $page++): ?><a class="<?= $page === $report_page ? 'current' : '' ?>" href="<?= sanitize($report_page_url($page)) ?>" <?= $page === $report_page ? 'aria-current="page"' : '' ?>><?= $page ?></a><?php endfor; ?>
            <?php if ($report_page < $report_pages): ?><a href="<?= sanitize($report_page_url($report_page + 1)) ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
