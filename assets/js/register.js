document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    const inputs = form.querySelectorAll('input, select');
    const submitBtn = document.getElementById('submitBtn');

    // Real-time validation
    inputs.forEach(input => {
        input.addEventListener('blur', function () {
            validateField(this);
        });

        input.addEventListener('input', function () {
            clearFieldError(this);
        });
    });

    // Form submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (validateForm()) {
            submitForm();
        }
    });

    function validateField(field) {
        const fieldGroup = field.closest('.form-group');
        const fieldName = field.name;
        const fieldValue = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Clear previous validation
        clearFieldError(field);

        switch (fieldName) {
            case 'first_name':
            case 'last_name':
                if (fieldValue.length < 2) {
                    isValid = false;
                    errorMessage = 'Name must be at least 2 characters long';
                } else if (!/^[a-zA-Z\s]+$/.test(fieldValue)) {
                    isValid = false;
                    errorMessage = 'Name can only contain letters and spaces';
                }
                break;

            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(fieldValue)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                break;

            case 'password':
                if (fieldValue.length < 6) {
                    isValid = false;
                    errorMessage = 'Password must be at least 6 characters long';
                } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(fieldValue)) {
                    isValid = false;
                    errorMessage = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
                }
                break;

            case 'confirm_password':
                const password = document.getElementById('password').value;
                if (fieldValue !== password) {
                    isValid = false;
                    errorMessage = 'Passwords do not match';
                }
                break;

            case 'phone':
                const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
                if (fieldValue && !phoneRegex.test(fieldValue.replace(/[\s\-\(\)]/g, ''))) {
                    isValid = false;
                    errorMessage = 'Please enter a valid phone number';
                }
                break;

            case 'role':
                if (!fieldValue) {
                    isValid = false;
                    errorMessage = 'Please select a role';
                }
                break;
        }

        if (!isValid) {
            showFieldError(field, errorMessage);
        } else {
            showFieldSuccess(field);
        }

        return isValid;
    }

    function validateForm() {
        let isFormValid = true;

        inputs.forEach(input => {
            if (!validateField(input)) {
                isFormValid = false;
            }
        });

        // Check if role is selected
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const roleSelected = Array.from(roleInputs).some(input => input.checked);

        if (!roleSelected) {
            showAlert('Please select a role', 'error');
            isFormValid = false;
        }

        return isFormValid;
    }

    function clearFieldError(field) {
        const fieldGroup = field.closest('.form-group');
        fieldGroup.classList.remove('error', 'success');

        const errorMessage = fieldGroup.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }

    function showFieldError(field, message) {
        const fieldGroup = field.closest('.form-group');
        fieldGroup.classList.add('error');
        fieldGroup.classList.remove('success');

        let errorMessage = fieldGroup.querySelector('.error-message');
        if (!errorMessage) {
            errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            fieldGroup.appendChild(errorMessage);
        }

        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
    }

    function showFieldSuccess(field) {
        const fieldGroup = field.closest('.form-group');
        fieldGroup.classList.add('success');
        fieldGroup.classList.remove('error');

        const errorMessage = fieldGroup.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }

    function showAlert(message, type) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        const container = document.querySelector('.register-container');
        container.insertBefore(alert, container.firstChild);

        // Auto-hide success alerts
        if (type === 'success') {
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    }

    function submitForm() {
        const formData = new FormData(form);

        // Add loading state
        form.classList.add('loading');
        submitBtn.textContent = 'Creating Account...';
        submitBtn.disabled = true;

        fetch('register.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    form.reset();

                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Remove loading state
                form.classList.remove('loading');
                submitBtn.textContent = 'Create Account';
                submitBtn.disabled = false;
            });
    }

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            updatePasswordStrength(this.value);
        });
    }

    function updatePasswordStrength(password) {
        const strengthIndicator = document.getElementById('passwordStrength');
        if (!strengthIndicator) return;

        let strength = 0;
        let strengthText = '';
        let strengthClass = '';

        if (password.length >= 6) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z\d]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                strengthText = 'Very Weak';
                strengthClass = 'very-weak';
                break;
            case 2:
                strengthText = 'Weak';
                strengthClass = 'weak';
                break;
            case 3:
                strengthText = 'Fair';
                strengthClass = 'fair';
                break;
            case 4:
                strengthText = 'Good';
                strengthClass = 'good';
                break;
            case 5:
                strengthText = 'Strong';
                strengthClass = 'strong';
                break;
        }

        strengthIndicator.textContent = strengthText;
        strengthIndicator.className = `password-strength ${strengthClass}`;
    }
});
