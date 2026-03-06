document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const reqLength = document.getElementById('req-length');
    const reqNumber = document.getElementById('req-number');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
        });
    }

    if (passwordInput && reqLength && reqNumber) {
        passwordInput.addEventListener('input', function () {
            const val = passwordInput.value;

            reqLength.classList.toggle('valid', val.length >= 8);

            reqNumber.classList.toggle('valid', /[0-9]/.test(val));
        });
    }
});
