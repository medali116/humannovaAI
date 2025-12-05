// Admin Investment Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search and filter functionality
    initializeSearchAndFilter();
    
    // Initialize form validation
    const editForm = document.getElementById('editInvestmentForm');
    if (editForm) {
        initializeEditForm(editForm);
    }
});

// Search and filter functionality
function initializeSearchAndFilter() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.getElementById('investmentTableBody');
    const rows = tableBody.querySelectorAll('tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusFilter_value = statusFilter.value;

        rows.forEach(row => {
            if (row.querySelector('.no-investments')) return; // Skip "no data" row

            const investor = row.cells[1]?.textContent.toLowerCase() || '';
            const ideaTitle = row.cells[2]?.textContent.toLowerCase() || '';
            const ideaOwner = row.cells[3]?.textContent.toLowerCase() || '';
            const amount = row.cells[4]?.textContent.toLowerCase() || '';
            const status = row.dataset.status || '';

            const matchesSearch = investor.includes(searchTerm) || 
                                ideaTitle.includes(searchTerm) || 
                                ideaOwner.includes(searchTerm) ||
                                amount.includes(searchTerm);
            
            const matchesStatus = !statusFilter_value || status === statusFilter_value;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Add event listeners for real-time filtering
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', filterTable);
    }
}

// Edit form initialization
function initializeEditForm(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateEditForm()) {
            const formData = new FormData(form);
            formData.append('action', 'admin_update');
            
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
                    closeEditModal();
                    // Refresh page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showErrorMessage(data.message || 'Failed to update investment.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Network error. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }
    });
    
    // Real-time validation for amount input
    const amountInput = document.getElementById('edit_montant');
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            validateAmountField(this);
        });
    }
}

// Form validation
function validateEditForm() {
    const montant = document.getElementById('edit_montant');
    const statut = document.getElementById('edit_statut');
    let isValid = true;

    // Validate amount
    if (!validateAmountField(montant)) {
        isValid = false;
    }

    // Validate status
    if (!statut.value) {
        showFieldError(statut, 'Status is required.');
        isValid = false;
    } else {
        showFieldSuccess(statut);
    }

    return isValid;
}

// Amount field validation
function validateAmountField(field) {
    const amount = parseFloat(field.value);
    
    clearFieldValidation(field);
    
    if (!field.value || field.value.trim() === '') {
        showFieldError(field, 'Investment amount is required.');
        return false;
    }
    
    if (isNaN(amount) || amount <= 0) {
        showFieldError(field, 'Please enter a valid amount.');
        return false;
    }
    
    if (amount < 500) {
        showFieldError(field, 'Minimum investment amount is 500 DT.');
        return false;
    }
    
    showFieldSuccess(field);
    return true;
}

// Modal functions
function viewInvestment(investment) {
    const modal = document.getElementById('viewModal');
    const modalBody = document.getElementById('viewModalBody');
    
    // Format status display
    let statusDisplay = '';
    switch(investment.statut) {
        case 'en_attente':
            statusDisplay = '<span class="status-badge status-pending">⏳ Pending</span>';
            break;
        case 'accepte':
            statusDisplay = '<span class="status-badge status-accepted">✅ Accepted</span>';
            break;
        case 'refuse':
            statusDisplay = '<span class="status-badge status-refused">❌ Refused</span>';
            break;
    }
    
    modalBody.innerHTML = `
        <div style="display: grid; gap: 20px;">
            <div class="form-group">
                <label>Investment ID</label>
                <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--text-primary);">
                    #${investment.id}
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Investor</label>
                    <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--accent-cyan);">
                        ${investment.investor_name}
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Investor Email</label>
                    <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--text-secondary);">
                        ${investment.investor_email}
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Idea Title</label>
                <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--accent-cyan);">
                    ${investment.idea_title}
                </div>
            </div>
            
            <div class="form-group">
                <label>Idea Owner</label>
                <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--text-primary);">
                    ${investment.idea_owner}
                </div>
            </div>
            
            <div class="form-group">
                <label>Investment Amount</label>
                <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--accent-purple); font-weight: bold; font-size: 1.2rem;">
                    ${parseFloat(investment.montant).toLocaleString()} DT
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Status</label>
                    <div style="padding: 10px;">
                        ${statusDisplay}
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date Submitted</label>
                    <div style="padding: 10px; background: var(--carbon-dark); border-radius: 8px; color: var(--text-secondary);">
                        ${investment.formatted_date}
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Idea Description</label>
                <div style="padding: 15px; background: var(--carbon-dark); border-radius: 8px; color: var(--text-secondary); line-height: 1.6; max-height: 150px; overflow-y: auto;">
                    ${investment.idea_description}
                </div>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeViewModal() {
    const modal = document.getElementById('viewModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function editInvestment(investment) {
    const modal = document.getElementById('editModal');
    
    // Populate form fields
    document.getElementById('edit_investment_id').value = investment.id;
    document.getElementById('edit_montant').value = investment.montant;
    document.getElementById('edit_statut').value = investment.statut;
    
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

function deleteInvestment(investmentId) {
    const confirmModal = createConfirmationModal(
        'Delete Investment',
        'Are you sure you want to permanently delete this investment? This action cannot be undone.',
        () => performDeleteInvestment(investmentId)
    );
    
    document.body.appendChild(confirmModal);
    confirmModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function performDeleteInvestment(investmentId) {
    const formData = new FormData();
    formData.append('action', 'admin_delete');
    formData.append('investment_id', investmentId);
    
    fetch('../../controllers/InvestmentController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message || 'Investment deleted successfully!');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message || 'Failed to delete investment.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Network error. Please try again.');
    });
}

// Validation helper functions
function showFieldError(field, message) {
    field.style.borderColor = '#ff6b6b';
    field.style.boxShadow = '0 0 0 3px rgba(255, 107, 107, 0.1)';
    
    const existingError = field.parentNode.querySelector('.validation-error');
    if (existingError) existingError.remove();
    
    const errorElement = document.createElement('div');
    errorElement.className = 'validation-error';
    errorElement.style.cssText = `
        color: #ff6b6b;
        font-size: 0.8rem;
        margin-top: 5px;
    `;
    errorElement.textContent = message;
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
    if (existingError) existingError.remove();
}

function clearFormValidation(form) {
    const fields = form.querySelectorAll('input, select');
    fields.forEach(field => clearFieldValidation(field));
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
                        Delete Investment
                    </button>
                </div>
            </div>
        </div>
    `;
    
    modal.onConfirm = onConfirm;
    return modal;
}

function closeConfirmationModal(element) {
    const modal = element.closest('.modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => modal.remove(), 300);
}

function confirmAction(element) {
    const modal = element.closest('.modal');
    if (modal.onConfirm) {
        modal.onConfirm();
    }
    closeConfirmationModal(element);
}

// Message functions
function showSuccessMessage(message) {
    showMessage(message, 'success');
}

function showErrorMessage(message) {
    showMessage(message, 'error');
}

function showMessage(message, type) {
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
        ${type === 'success' 
            ? 'background: linear-gradient(135deg, #4ade80, #22c55e);' 
            : 'background: linear-gradient(135deg, #ff6b6b, #ff3333);'
        }
    `;
    
    toast.innerHTML = `${type === 'success' ? '✅' : '❌'} ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) toast.remove();
    }, 3000);
}

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            if (activeModal.id === 'viewModal') {
                closeViewModal();
            } else if (activeModal.id === 'editModal') {
                closeEditModal();
            } else {
                const closeBtn = activeModal.querySelector('.close-modal');
                if (closeBtn) closeBtn.click();
            }
        }
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        if (e.target.id === 'viewModal') {
            closeViewModal();
        } else if (e.target.id === 'editModal') {
            closeEditModal();
        } else {
            const closeBtn = e.target.querySelector('.close-modal');
            if (closeBtn) closeBtn.click();
        }
    }
});

// Debug function to check session
function debugSession() {
    const formData = new FormData();
    formData.append('action', 'debug_session');
    
    fetch('../../controllers/InvestmentController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Session Debug:', data);
        alert('Session Debug (check console): ' + JSON.stringify(data, null, 2));
    })
    .catch(error => {
        console.error('Debug Error:', error);
        alert('Debug Error: ' + error.message);
    });
}