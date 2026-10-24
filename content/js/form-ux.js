/* KulüpNet — Form UX: Inline Validation + Password Strength */
(function () {
    // --- Password Strength Meter ---
    const pwdInputs = document.querySelectorAll('input[type="password"][data-strength]');
    pwdInputs.forEach(input => {
        const wrapper = input.closest('.form-group');
        const meter = wrapper?.querySelector('.pwd-meter');
        if (!meter) return;

        input.addEventListener('input', () => {
            const val = input.value;
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const level = Math.min(score, 4);
            const labels = ['', 'Zayıf', 'Orta', 'İyi', 'Güçlü'];
            const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];

            meter.style.setProperty('--pwd-width', (level * 25) + '%');
            meter.style.setProperty('--pwd-color', colors[level] || 'transparent');
            const label = meter.querySelector('.pwd-label');
            if (label) { label.textContent = labels[level] || ''; label.style.color = colors[level]; }
        });
    });

    // --- Inline Validation on blur ---
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], input[data-validate], select[required], textarea[required]');

        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                // Clear error on typing
                const err = input.closest('.form-group')?.querySelector('.form-error');
                if (err?.classList.contains('visible')) {
                    err.classList.remove('visible');
                    input.classList.remove('input-error');
                    input.removeAttribute('aria-invalid');
                }
            });
        });

        form.addEventListener('submit', (e) => {
            let firstError = null;
            inputs.forEach(input => {
                if (!validateField(input) && !firstError) {
                    firstError = input;
                }
            });
            if (firstError) {
                e.preventDefault();
                firstError.focus();
                firstError.closest('.form-group')?.classList.add('shake');
                setTimeout(() => firstError.closest('.form-group')?.classList.remove('shake'), 500);
            }
        });
    });

    function validateField(input) {
        const group = input.closest('.form-group');
        const errEl = group?.querySelector('.form-error');
        let message = '';

        if (input.required && !input.value.trim()) {
            message = 'Bu alan zorunludur.';
        } else if (input.type === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
            message = 'Geçerli bir e-posta girin.';
        } else if (input.minLength > 0 && input.value.length < input.minLength) {
            message = `En az ${input.minLength} karakter olmalı.`;
        } else if (input.dataset.match) {
            const other = document.querySelector(input.dataset.match);
            if (other && input.value !== other.value) {
                message = 'Şifreler eşleşmiyor.';
            }
        }

        if (errEl) {
            if (message) {
                errEl.textContent = message;
                errEl.classList.add('visible');
                input.classList.add('input-error');
                input.setAttribute('aria-invalid', 'true');
            } else {
                errEl.classList.remove('visible');
                input.classList.remove('input-error');
                input.removeAttribute('aria-invalid');
            }
        }
        return !message;
    }
})();
