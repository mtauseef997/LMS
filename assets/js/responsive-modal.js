/* Universal Responsive Modal JavaScript for LMS Admin Panel */

class ResponsiveModal {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.modalContent = this.modal?.querySelector('.modal-content');
        this.closeBtn = this.modal?.querySelector('.close');
        this.form = this.modal?.querySelector('form');

        this.init();
    }

    init() {
        if (!this.modal) return;

        // Add event listeners
        this.addEventListeners();

        // Add keyboard support
        this.addKeyboardSupport();

        // Add touch support
        this.addTouchSupport();

        // Add orientation change support
        this.addOrientationSupport();
    }

    addEventListeners() {
        // Close button
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }

        // Click outside to close
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Form submission - disabled for admin pages to handle manually
        // if (this.form) {
        //     this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        // }
    }

    addKeyboardSupport() {
        document.addEventListener('keydown', (e) => {
            if (!this.isOpen()) return;

            // Close on Escape
            if (e.key === 'Escape') {
                this.close();
                return;
            }

            // Trap focus within modal
            if (e.key === 'Tab') {
                this.trapFocus(e);
            }
        });
    }

    addTouchSupport() {
        // Touch outside to close
        this.modal.addEventListener('touchstart', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
    }

    addOrientationSupport() {
        window.addEventListener('orientationchange', () => {
            if (this.isOpen()) {
                setTimeout(() => {
                    this.adjustPosition();
                }, 100);
            }
        });

        window.addEventListener('resize', () => {
            if (this.isOpen()) {
                this.adjustPosition();
            }
        });
    }

    open() {
        if (!this.modal) return;

        // Show modal
        this.modal.style.display = 'flex';

        // Add show class for animation
        setTimeout(() => {
            this.modal.classList.add('show');
        }, 10);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Focus first input
        setTimeout(() => {
            this.focusFirstInput();
        }, 100);

        // Adjust position
        this.adjustPosition();
    }

    close() {
        if (!this.modal) return;

        // Remove show class
        this.modal.classList.remove('show');

        // Hide modal after animation
        setTimeout(() => {
            this.modal.style.display = 'none';
        }, 300);

        // Restore body scroll
        document.body.style.overflow = '';

        // Reset form if exists
        if (this.form) {
            this.form.reset();
        }

        // Hide any preview sections
        const previews = this.modal.querySelectorAll('.preview-section');
        previews.forEach(preview => {
            preview.style.display = 'none';
        });
    }

    isOpen() {
        return this.modal && this.modal.style.display === 'flex';
    }

    focusFirstInput() {
        const firstInput = this.modal.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }

    trapFocus(e) {
        const focusableElements = this.modal.querySelectorAll(
            'input, select, textarea, button, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    }

    adjustPosition() {
        if (!this.modalContent) return;

        // Reset scroll position
        this.modalContent.scrollTop = 0;

        // Adjust for mobile keyboards
        if (window.innerHeight < 500) {
            this.modal.style.alignItems = 'flex-start';
            this.modal.style.paddingTop = '1rem';
        } else {
            this.modal.style.alignItems = 'center';
            this.modal.style.paddingTop = '';
        }
    }

    handleFormSubmit(e) {
        e.preventDefault();

        const submitBtn = this.form.querySelector('[type="submit"]');
        const originalText = submitBtn?.innerHTML;

        // Show loading state
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
        }

        // Get form data
        const formData = new FormData(this.form);

        // Submit form
        fetch(window.location.href, {
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
                    this.showNotification(data.message, 'success');
                    this.close();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('An error occurred while processing the request', 'error');
            })
            .finally(() => {
                // Restore button state
                if (submitBtn && originalText) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
    }

    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 2000;
            max-width: 300px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
            ${type === 'success' ?
                'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' :
                'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);'
            }
        `;

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
}

// Notification animations CSS
const notificationStyle = document.createElement('style');
notificationStyle.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @media (max-width: 480px) {
        .notification {
            top: 10px !important;
            right: 10px !important;
            left: 10px !important;
            max-width: none !important;
        }
    }
`;
document.head.appendChild(notificationStyle);

// Global modal utilities
window.ModalUtils = {
    create: (modalId) => new ResponsiveModal(modalId),

    // Legacy support functions
    openModal: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            const instance = new ResponsiveModal(modalId);
            instance.open();
        }
    },

    closeModal: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            const instance = new ResponsiveModal(modalId);
            instance.close();
        }
    }
};
