/**
 * Admin Idea Management - Client-Side Validation
 * Handles validation for creating and editing ideas in admin panel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add Idea Form
    const addIdeaForm = document.getElementById('addIdeaForm');
    if (addIdeaForm) {
        addIdeaForm.setAttribute('novalidate', 'novalidate');
        addIdeaForm.addEventListener('submit', handleAddIdea);
    }
    
    // Edit Idea Form
    const editIdeaForm = document.getElementById('editIdeaForm');
    if (editIdeaForm) {
        editIdeaForm.setAttribute('novalidate', 'novalidate');
        editIdeaForm.addEventListener('submit', handleEditIdea);
    }
    
    // Real-time validation
    const titleInputs = document.querySelectorAll('#titre, #edit_titre');
    titleInputs.forEach(input => {
        input.addEventListener('input', function() {
            validateTitleRealTime(this);
        });
    });
    
    const descInputs = document.querySelectorAll('#description, #edit_description');
    descInputs.forEach(input => {
        input.addEventListener('input', function() {
            validateDescriptionRealTime(this);
        });
    });
});

/**
 * Handle Add Idea Form Submission
 */
function handleAddIdea(e) {
    e.preventDefault();
    clearFormErrors('addIdeaForm');
    
    const utilisateur_id = document.getElementById('utilisateur_id').value;
    const titre = document.getElementById('titre').value.trim();
    const description = document.getElementById('description').value.trim();
    
    let isValid = true;
    
    // Validate user selection
    if (!utilisateur_id) {
        showFieldError('utilisateur_id', 'Please select a user');
        isValid = false;
    }
    
    // Validate idea form
    if (!validateIdeaForm(titre, description, 'titre', 'description')) {
        isValid = false;
    }
    
    if (isValid) {
        submitIdea('create', { utilisateur_id, titre, description });
    }
}

/**
 * Handle Edit Idea Form Submission
 */
function handleEditIdea(e) {
    e.preventDefault();
    clearFormErrors('editIdeaForm');
    
    const ideaId = document.getElementById('edit_idea_id').value;
    const titre = document.getElementById('edit_titre').value.trim();
    const description = document.getElementById('edit_description').value.trim();
    
    if (validateIdeaForm(titre, description, 'edit_titre', 'edit_description')) {
        submitIdea('update', { idea_id: ideaId, titre, description });
    }
}

/**
 * Validate Idea Form
 */
function validateIdeaForm(titre, description, titreFieldId, descFieldId) {
    let isValid = true;
    
    // Validate title
    if (!titre) {
        showFieldError(titreFieldId, 'Title is required');
        isValid = false;
    } else {
        // Check for numbers
        if (/\d/.test(titre)) {
            showFieldError(titreFieldId, 'Title must not contain numbers');
            isValid = false;
        }
        // Check for symbols (only letters, spaces, hyphens, apostrophes allowed)
        else if (/[^a-zA-Z\s'\-àáâãäåèéêëìíîïòóôõöùúûüçñÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÇÑ]/.test(titre)) {
            showFieldError(titreFieldId, 'Title must not contain symbols or special characters');
            isValid = false;
        }
        // Check word count (minimum 3 words)
        else if (countWords(titre) < 3) {
            showFieldError(titreFieldId, 'Title must contain at least 3 words');
            isValid = false;
        }
    }
    
    // Validate description
    if (!description) {
        showFieldError(descFieldId, 'Description is required');
        isValid = false;
    } else if (countWords(description) < 10) {
        showFieldError(descFieldId, 'Description must contain at least 10 words');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Real-time title validation
 */
function validateTitleRealTime(input) {
    const titre = input.value.trim();
    const fieldId = input.id;
    
    clearFieldError(fieldId);
    
    if (titre) {
        if (/\d/.test(titre)) {
            showFieldError(fieldId, 'Title must not contain numbers');
        } else if (/[^a-zA-Z\s'\-àáâãäåèéêëìíîïòóôõöùúûüçñÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÇÑ]/.test(titre)) {
            showFieldError(fieldId, 'Title must not contain symbols');
        } else if (countWords(titre) < 3) {
            showFieldError(fieldId, `${countWords(titre)}/3 words minimum`);
        }
    }
}

/**
 * Real-time description validation
 */
function validateDescriptionRealTime(input) {
    const description = input.value.trim();
    const fieldId = input.id;
    
    clearFieldError(fieldId);
    
    if (description && countWords(description) < 10) {
        showFieldError(fieldId, `${countWords(description)}/10 words minimum`);
    }
}

/**
 * Count words in a string
 */
function countWords(str) {
    return str.split(/\s+/).filter(word => word.length > 0).length;
}

/**
 * Submit Idea via AJAX
 */
function submitIdea(action, data) {
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = action === 'create' ? 'Creating...' : 'Updating...';
    submitBtn.style.opacity = '0.7';
    
    const formData = new FormData();
    formData.append('action', action);
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    
    fetch('../../controllers/IdeaController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage(result.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(result.errors);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            submitBtn.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage(['An error occurred. Please try again.']);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        submitBtn.style.opacity = '1';
    });
}

/**
 * Delete Idea via AJAX
 */
function deleteIdeaAjax(ideaId) {
    if (!confirm('Are you sure you want to delete this idea? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('idea_id', ideaId);
    
    fetch('../../controllers/IdeaController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage(result.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(result.errors);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage(['An error occurred. Please try again.']);
    });
}

/**
 * Show field error
 */
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const formGroup = field.closest('.form-group');
    field.style.borderColor = 'var(--accent-red)';
    
    let errorDiv = formGroup.querySelector('.error-message-field');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message-field';
        errorDiv.style.cssText = 'color: var(--accent-red); font-size: 0.85rem; margin-top: 8px; font-weight: 500;';
        formGroup.appendChild(errorDiv);
    }
    errorDiv.textContent = '⚠️ ' + message;
}

/**
 * Clear field error
 */
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const formGroup = field.closest('.form-group');
    field.style.borderColor = 'var(--metal-dark)';
    
    const errorDiv = formGroup.querySelector('.error-message-field');
    if (errorDiv) errorDiv.remove();
}

/**
 * Clear all form errors
 */
function clearFormErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.querySelectorAll('.error-message-field').forEach(el => el.remove());
    form.querySelectorAll('input, textarea, select').forEach(input => {
        input.style.borderColor = 'var(--metal-dark)';
    });
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'alert-message success';
    msgDiv.textContent = '✅ ' + message;
    msgDiv.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: rgba(0, 255, 136, 0.1); border: 1px solid var(--accent-green);
        color: var(--accent-green); padding: 15px 25px; border-radius: 10px;
        font-weight: 500; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    `;
    document.body.appendChild(msgDiv);
    setTimeout(() => msgDiv.remove(), 3000);
}

/**
 * Show error message
 */
function showErrorMessage(errors) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'alert-message error';
    msgDiv.innerHTML = '❌ ' + (Array.isArray(errors) ? errors.join('<br>❌ ') : errors);
    msgDiv.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: rgba(255, 51, 51, 0.1); border: 1px solid var(--accent-red);
        color: var(--accent-red); padding: 15px 25px; border-radius: 10px;
        font-weight: 500; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    `;
    document.body.appendChild(msgDiv);
    setTimeout(() => msgDiv.remove(), 4000);
}

/**
 * Open Add Modal
 */
function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}

/**
 * Close Add Modal
 */
function closeAddModal() {
    document.getElementById('addModal').classList.remove('active');
    clearFormErrors('addIdeaForm');
    document.getElementById('addIdeaForm').reset();
}

/**
 * Open Edit Modal
 */
function editIdea(idea) {
    document.getElementById('edit_idea_id').value = idea.id;
    document.getElementById('edit_titre').value = idea.titre;
    document.getElementById('edit_description').value = idea.description;
    document.getElementById('editModal').classList.add('active');
}

/**
 * Close Edit Modal
 */
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
    clearFormErrors('editIdeaForm');
}

/**
 * Open View Modal
 */
function viewIdea(idea) {
    document.getElementById('view_id').textContent = '#' + idea.id;
    document.getElementById('view_titre').textContent = idea.titre;
    document.getElementById('view_description').textContent = idea.description;
    document.getElementById('view_author').textContent = idea.fullname + ' (@' + idea.username + ')';
    document.getElementById('view_date').textContent = new Date(idea.date_creation).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    document.getElementById('viewModal').classList.add('active');
}

/**
 * Close View Modal
 */
function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
