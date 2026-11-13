// ── Product Customization ─────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // Option buttons (size, sugar, ice)
    document.querySelectorAll('.option-group').forEach(function (group) {
        group.querySelectorAll('.option-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                group.querySelectorAll('.option-btn').forEach(function (b) {
                    b.classList.remove('selected');
                });
                btn.classList.add('selected');

                var inputName = group.dataset.input;
                if (inputName) {
                    var hidden = document.querySelector('input[name="' + inputName + '"]');
                    if (hidden) {
                        hidden.value = btn.dataset.value;
                    }
                }

                // Update price if size changes
                if (inputName === 'size') {
                    updateDetailPrice(btn.dataset.value);
                }
            });
        });
    });

    // Quantity controls on product detail
    var qtyInput = document.getElementById('qty-input');
    if (qtyInput) {
        document.getElementById('qty-minus').addEventListener('click', function () {
            var val = parseInt(qtyInput.value, 10);
            if (val > 1) qtyInput.value = val - 1;
        });
        document.getElementById('qty-plus').addEventListener('click', function () {
            var val = parseInt(qtyInput.value, 10);
            if (val < 20) qtyInput.value = val + 1;
        });
    }

    // Category filter
    document.querySelectorAll('.category-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.category-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');

            var cat = btn.dataset.category;
            document.querySelectorAll('.product-card-wrapper').forEach(function (card) {
                if (cat === 'all' || card.dataset.category === cat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Payment option selection
    document.querySelectorAll('.payment-option').forEach(function (option) {
        option.addEventListener('click', function () {
            document.querySelectorAll('.payment-option').forEach(function (o) {
                o.classList.remove('selected');
            });
            option.classList.add('selected');
            option.querySelector('input[type="radio"]').checked = true;
        });
    });

    // Auto-select first payment option
    var firstPayment = document.querySelector('.payment-option');
    if (firstPayment) {
        firstPayment.classList.add('selected');
        firstPayment.querySelector('input[type="radio"]').checked = true;
    }
});

function updateDetailPrice(size) {
    var basePrice = parseFloat(document.getElementById('base-price') ? document.getElementById('base-price').value : 0);
    if (isNaN(basePrice)) return;
    var add = 0;
    if (size === 'Large') add = 15;
    if (size === 'Extra Large') add = 25;
    var priceEl = document.getElementById('display-price');
    if (priceEl) {
        priceEl.textContent = '\u20B1' + (basePrice + add).toFixed(2);
    }
}
