document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle)
    if (!input) return
    const show = input.type === 'password'
    input.type = show ? 'text' : 'password'
    button.textContent = show ? 'Hide' : 'Show'
    button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${input.labels?.[0]?.textContent?.toLowerCase() || 'password'}`)
  })
})

document.querySelectorAll('form[data-single-submit]').forEach((form) => {
  form.addEventListener('submit', () => {
    if (!form.reportValidity()) return
    const submit = form.querySelector('button[type="submit"]')
    if (submit) {
      submit.disabled = true
      submit.setAttribute('aria-busy', 'true')
      submit.textContent = 'Please wait…'
    }
  })
})
