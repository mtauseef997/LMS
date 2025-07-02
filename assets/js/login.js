document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.querySelector('.submit-btn');
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');

    // Password toggle functionality
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm();
        }
    });

    function validateForm() {
        let isValid = true;
        const email = document.getElementById('email');
        const password = document.getElementById('password');

        // Clear previous errors
        clearErrors();

        // Email validation
        if (!email.value.trim()) {
            showFieldError(email, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email.value.trim())) {
            showFieldError(email, 'Please enter a valid email address');
            isValid = false;
        } else {
            showFieldSuccess(email);
        }

        // Password validation
        if (!password.value) {
            showFieldError(password, 'Password is required');
            isValid = false;
        } else {
            showFieldSuccess(password);
        }

        return isValid;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function clearErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            msg.style.display = 'none';
            msg.textContent = '';
        });

        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach(group => {
            group.classList.remove('error', 'success');
        });
    }

    function showFieldError(field, message) {
        const fieldGroup = field.closest('.form-group');
        fieldGroup.classList.add('error');
        fieldGroup.classList.remove('success');
        
        const errorMessage = fieldGroup.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }
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
            }, 3000);
        }
    }

    function submitForm() {
        const formData = new FormData(form);
        
        // Add loading state
        form.classList.add('loading');
        submitBtn.innerHTML = '<span>Signing In...</span><i class="fas fa-spinner fa-spin"></i>';
        submitBtn.disabled = true;

        fetch('login.php', {
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
                
                // Redirect after 1 second
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
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
            submitBtn.innerHTML = '<span>Sign In</span><i class="fas fa-arrow-right"></i>';
            submitBtn.disabled = false;
        });
    }

    // Auto-fill demo account on demo button click (if you want to add this feature)
    const demoInfo = document.querySelector('[style*="Demo Accounts"]');
    if (demoInfo) {
        demoInfo.addEventListener('click', function() {
            document.getElementById('email').value = 'admin@lms.com';
            document.getElementById('password').value = 'admin123';
        });
    }
});
