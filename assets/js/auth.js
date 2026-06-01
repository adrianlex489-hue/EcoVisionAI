(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginForm) initLoginForm(loginForm);
        if (registerForm) initRegisterForm(registerForm);
    });

    function initLoginForm(form) {
        const passwordInput = form.querySelector('input[name="password"]');
        const submitBtn = form.querySelector('button[type="submit"]');
        const rememberCheck = form.querySelector('input[name="remember"]');

        loadRememberedEmail(form);

        form.addEventListener('submit', function (e) {
            if (!validateLoginForm(form)) {
                e.preventDefault();
                return;
            }
            setLoadingState(submitBtn, true);
        });

        if (passwordInput) addPasswordToggle(passwordInput);

        if (rememberCheck) {
            form.querySelector('input[name="correo"]').addEventListener('change', function () {
                saveRememberedEmail(this.value, rememberCheck.checked);
            });
            rememberCheck.addEventListener('change', function () {
                const email = form.querySelector('input[name="correo"]').value;
                saveRememberedEmail(email, this.checked);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && form.contains(e.target)) {
                form.dispatchEvent(new Event('submit'));
            }
        });
    }

    function initRegisterForm(form) {
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmInput = form.querySelector('input[name="confirmar_password"]');
        const submitBtn = form.querySelector('button[type="submit"]');

        addPasswordStrengthIndicator(passwordInput);
        addPasswordToggle(passwordInput);
        addPasswordToggle(confirmInput);

        form.addEventListener('submit', function (e) {
            if (!validateRegisterForm(form)) {
                e.preventDefault();
                return;
            }
            setLoadingState(submitBtn, true);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && form.contains(e.target)) {
                form.dispatchEvent(new Event('submit'));
            }
        });
    }

    function validateLoginForm(form) {
        const email = form.querySelector('input[name="correo"]');
        const password = form.querySelector('input[name="password"]');
        let valid = true;

        clearErrors(form);

        if (!email.value.trim()) {
            showError(email, 'El correo electrónico es obligatorio.');
            valid = false;
        } else if (!isValidEmail(email.value.trim())) {
            showError(email, 'Ingresa un correo electrónico válido.');
            valid = false;
        }

        if (!password.value) {
            showError(password, 'La contraseña es obligatoria.');
            valid = false;
        }

        return valid;
    }

    function validateRegisterForm(form) {
        const nombres = form.querySelector('input[name="nombres"]');
        const apellidos = form.querySelector('input[name="apellidos"]');
        const email = form.querySelector('input[name="correo"]');
        const password = form.querySelector('input[name="password"]');
        const confirm = form.querySelector('input[name="confirmar_password"]');
        let valid = true;

        clearErrors(form);

        if (!nombres.value.trim()) {
            showError(nombres, 'Los nombres son obligatorios.');
            valid = false;
        }

        if (!apellidos.value.trim()) {
            showError(apellidos, 'Los apellidos son obligatorios.');
            valid = false;
        }

        if (!email.value.trim()) {
            showError(email, 'El correo electrónico es obligatorio.');
            valid = false;
        } else if (!isValidEmail(email.value.trim())) {
            showError(email, 'Ingresa un correo electrónico válido.');
            valid = false;
        }

        if (!password.value) {
            showError(password, 'La contraseña es obligatoria.');
            valid = false;
        } else if (password.value.length < 6) {
            showError(password, 'La contraseña debe tener al menos 6 caracteres.');
            valid = false;
        }

        if (!confirm.value) {
            showError(confirm, 'Confirma tu contraseña.');
            valid = false;
        } else if (password.value !== confirm.value) {
            showError(confirm, 'Las contraseñas no coinciden.');
            valid = false;
        }

        return valid;
    }

    function showError(input, message) {
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.classList.add('is-invalid');
        input.parentNode.appendChild(feedback);
        animateError(input);
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.remove();
        });
    }

    function animateError(input) {
        input.style.animation = 'none';
        input.offsetHeight;
        input.style.animation = 'shake 0.4s ease-in-out';
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function addPasswordToggle(input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
        btn.setAttribute('tabindex', '-1');
        wrapper.appendChild(btn);

        btn.addEventListener('click', function () {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }

    function addPasswordStrengthIndicator(input) {
        const meter = document.createElement('div');
        meter.className = 'password-strength mt-2';
        meter.innerHTML = '<div class="progress" style="height:6px;"><div class="progress-bar" role="progressbar" style="width:0%"></div></div><small class="text-muted mt-1 d-block">Fortaleza: <span>Escribe una contraseña</span></small>';
        input.parentNode.appendChild(meter);

        const bar = meter.querySelector('.progress-bar');
        const label = meter.querySelector('span');

        input.addEventListener('input', function () {
            var strength = calculatePasswordStrength(this.value);
            var pct = strength.score;
            bar.style.width = pct + '%';
            bar.className = 'progress-bar ' + strength.class;
            label.textContent = strength.label;
        });
    }

    function calculatePasswordStrength(password) {
        var score = 0;
        if (password.length >= 6) score += 20;
        if (password.length >= 10) score += 15;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 20;
        if (/\d/.test(password)) score += 20;
        if (/[^a-zA-Z0-9]/.test(password)) score += 25;

        if (score < 30) return { score: score, class: 'bg-danger', label: 'Débil' };
        if (score < 60) return { score: score, class: 'bg-warning', label: 'Media' };
        if (score < 80) return { score: score, class: 'bg-info', label: 'Buena' };
        return { score: score, class: 'bg-success', label: 'Muy fuerte' };
    }

    function setLoadingState(btn, loading) {
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Procesando...';
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
            }
        }
    }

    function saveRememberedEmail(email, remember) {
        if (remember && email) {
            localStorage.setItem('ecovision_remember_email', email);
        } else if (!remember) {
            localStorage.removeItem('ecovision_remember_email');
        }
    }

    function loadRememberedEmail(form) {
        var saved = localStorage.getItem('ecovision_remember_email');
        if (saved) {
            var emailInput = form.querySelector('input[name="correo"]');
            if (emailInput) {
                emailInput.value = saved;
                var check = form.querySelector('input[name="remember"]');
                if (check) check.checked = true;
            }
        }
    }

    var styleEl = document.createElement('style');
    styleEl.textContent = '@keyframes shake { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 50% { transform: translateX(5px); } 75% { transform: translateX(-5px); } }';
    document.head.appendChild(styleEl);

})();
