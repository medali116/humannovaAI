// My Investments Page Validation and Modal Management
document.addEventListener('DOMContentLoaded', function() {
    // Edit form validation
    const editForm = document.getElementById('editInvestmentForm');
    initializeForm(editForm, 'update');
});

// Form initialization
function initializeForm(form, action) {
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm(form)) {
            const formData = new FormData(form);
            formData.append('action', action);
            
            // Show loading state
            const submitBtn = form.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            fetch('../../controllers/InvestmentController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message || 'Investment updated successfully!');
                    // Refresh page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showErrorMessage(data.message || 'An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Network error. Please check your connection and try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }
    });
    
    // Real-time validation for form inputs
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearFieldValidation(this);
            validateField(this);
        });
        
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
}

// Form validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], input[type="number"]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Individual field validation
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous validation
    clearFieldValidation(field);
    
    // Required field check
    if (!value) {
        isValid = false;
        errorMessage = 'This field is required.';
    } else {
        // Specific validation based on field name
        switch (fieldName) {
            case 'montant':
                const amount = parseFloat(value);
                if (isNaN(amount) || amount < 500) {
                    isValid = false;
                    errorMessage = 'Minimum investment amount is 500 DT.';
                }
                break;
        }
    }
    
    // Show validation result
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        showFieldSuccess(field);
    }
    
    return isValid;
}

// Validation UI helpers
function showFieldError(field, message) {
    field.style.borderColor = '#ff6b6b';
    field.style.boxShadow = '0 0 0 3px rgba(255, 107, 107, 0.1)';
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.validation-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorElement = document.createElement('div');
    errorElement.className = 'validation-error';
    errorElement.style.cssText = `
        color: #ff6b6b;
        font-size: 0.8rem;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    `;
    errorElement.innerHTML = `<span>⚠️</span> ${message}`;
    field.parentNode.appendChild(errorElement);
}

function showFieldSuccess(field) {
    field.style.borderColor = '#4ade80';
    field.style.boxShadow = '0 0 0 3px rgba(74, 222, 128, 0.1)';
}

function clearFieldValidation(field) {
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    const existingError = field.parentNode.querySelector('.validation-error');
    if (existingError) {
        existingError.remove();
    }
}

// Modal management
function openEditModal(investment) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editInvestmentForm');
    
    // Populate form with investment data
    document.getElementById('edit_investment_id').value = investment.id;
    document.getElementById('edit_montant').value = investment.montant;
    
    // Populate idea details
    document.getElementById('editIdeaDetails').innerHTML = `
        <div style="background: linear-gradient(135deg, rgba(0, 255, 255, 0.05), rgba(153, 69, 255, 0.05)); 
                    border: 1px solid var(--metal-dark); border-radius: 10px; padding: 20px;">
            <h3 style="color: var(--text-primary); margin-bottom: 10px; font-size: 1.2rem;">
                ${investment.idea_title}
            </h3>
            <p style="color: var(--accent-cyan); font-weight: 600; font-size: 0.9rem; margin-bottom: 15px;">
                By ${investment.idea_author}
            </p>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                ${investment.idea_description}
            </p>
        </div>
    `;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Focus on amount input
    setTimeout(() => {
        const amountInput = document.getElementById('edit_montant');
        if (amountInput) {
            amountInput.focus();
            amountInput.select();
        }
    }, 100);
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Reset form
    const form = document.getElementById('editInvestmentForm');
    if (form) {
        form.reset();
        clearFormValidation(form);
    }
}

// Clear all form validation
function clearFormValidation(form) {
    const fields = form.querySelectorAll('input');
    fields.forEach(field => clearFieldValidation(field));
}

// Delete investment with confirmation
function deleteInvestment(investmentId) {
    // Create custom confirmation modal
    const confirmModal = createConfirmationModal(
        'Cancel Investment',
        'Are you sure you want to cancel this investment request? This action cannot be undone.',
        () => performDeleteInvestment(investmentId)
    );
    
    document.body.appendChild(confirmModal);
    confirmModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Perform actual deletion
function performDeleteInvestment(investmentId) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('investment_id', investmentId);
    
    fetch('../../controllers/InvestmentController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message || 'Investment cancelled successfully!');
            // Remove the investment card from UI immediately
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message || 'Failed to cancel investment. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Network error. Please check your connection and try again.');
    });
}

// Create confirmation modal
function createConfirmationModal(title, message, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.style.zIndex = '10000';
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>${title}</h2>
                <span class="close-modal" onclick="closeConfirmationModal(this)">&times;</span>
            </div>
            <div style="padding: 30px;">
                <p style="color: var(--text-secondary); margin-bottom: 30px; line-height: 1.6;">
                    ${message}
                </p>
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" class="btn-cancel" onclick="closeConfirmationModal(this)">Cancel</button>
                    <button type="button" class="btn-submit" onclick="confirmAction(this)" 
                            style="background: linear-gradient(135deg, #ff3333, #ff6b6b);">
                        Cancel Investment
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Store the onConfirm function
    modal.onConfirm = onConfirm;
    
    return modal;
}

// Close confirmation modal
function closeConfirmationModal(element) {
    const modal = element.closest('.modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => modal.remove(), 300);
}

// Confirm action in confirmation modal
function confirmAction(element) {
    const modal = element.closest('.modal');
    if (modal.onConfirm) {
        modal.onConfirm();
    }
    closeConfirmationModal(element);
}

// Success and error messages
function showSuccessMessage(message) {
    showMessage(message, 'success');
}

function showErrorMessage(message) {
    showMessage(message, 'error');
}

function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.toast-message');
    existingMessages.forEach(msg => msg.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10001;
        padding: 15px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        max-width: 400px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s forwards;
        ${type === 'success' 
            ? 'background: linear-gradient(135deg, #4ade80, #22c55e);' 
            : 'background: linear-gradient(135deg, #ff6b6b, #ff3333);'
        }
    `;
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.2rem;">${type === 'success' ? '✅' : '❌'}</span>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// Add required CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .modal {
        backdrop-filter: blur(5px);
    }
    
    .validation-error {
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            if (activeModal.id === 'editModal') {
                closeEditModal();
            } else {
                // For confirmation modals
                const closeBtn = activeModal.querySelector('.close-modal');
                if (closeBtn) closeBtn.click();
            }
        }
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        if (e.target.id === 'editModal') {
            closeEditModal();
        } else {
            // For confirmation modals
            const closeBtn = e.target.querySelector('.close-modal');
            if (closeBtn) closeBtn.click();
        }
    }
});