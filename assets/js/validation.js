/**
 * Validation côté client - JavaScript (pas HTML5)
 * Contrôle de saisie pour toutes les pages
 */

// ==================== VALIDATION FUNCTIONS ====================

function validateRequired(value, minLength = 1, maxLength = 255) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'Ce champ est obligatoire' };
    }
    
    if (value.length < minLength) {
        return { valid: false, message: `Ce champ doit contenir au moins ${minLength} caractères` };
    }
    
    if (value.length > maxLength) {
        return { valid: false, message: `Ce champ ne peut pas dépasser ${maxLength} caractères` };
    }
    
    return { valid: true };
}

function validateName(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'Ce champ est obligatoire' };
    }
    
    if (value.length < 2) {
        return { valid: false, message: 'Ce champ doit contenir au moins 2 caractères' };
    }
    
    if (value.length > 100) {
        return { valid: false, message: 'Ce champ ne peut pas dépasser 100 caractères' };
    }
    
    const nameRegex = /^[a-zA-ZÀ-ÿ\s\-]+$/;
    if (!nameRegex.test(value)) {
        return { valid: false, message: 'Ce champ ne peut contenir que des lettres, espaces et tirets' };
    }
    
    return { valid: true };
}

function validateEmail(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'L\'email est obligatoire' };
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
        return { valid: false, message: 'Format d\'email invalide (ex: nom@domaine.com)' };
    }
    
    if (value.length > 255) {
        return { valid: false, message: 'L\'email ne peut pas dépasser 255 caractères' };
    }
    
    return { valid: true };
}

function validateTitre(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'Le titre est obligatoire' };
    }
    
    if (value.length < 3) {
        return { valid: false, message: 'Le titre doit contenir au moins 3 caractères' };
    }
    
    if (value.length > 150) {
        return { valid: false, message: 'Le titre ne peut pas dépasser 150 caractères' };
    }
    
    return { valid: true };
}

function validateDescription(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'La description est obligatoire' };
    }
    
    if (value.length < 10) {
        return { valid: false, message: 'La description doit contenir au moins 10 caractères' };
    }
    
    if (value.length > 500) {
        return { valid: false, message: 'La description ne peut pas dépasser 500 caractères' };
    }
    
    return { valid: true };
}

function validateCommentaire(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'Le commentaire est obligatoire' };
    }
    
    if (value.length < 10) {
        return { valid: false, message: 'Le commentaire doit contenir au moins 10 caractères' };
    }
    
    if (value.length > 1000) {
        return { valid: false, message: 'Le commentaire ne peut pas dépasser 1000 caractères' };
    }
    
    return { valid: true };
}

function validateDates(dateDebut, dateFin) {
    if (!dateDebut) {
        return { valid: false, message: 'La date de début est obligatoire', field: 'date_debut' };
    }
    
    if (!dateFin) {
        return { valid: false, message: 'La date de fin est obligatoire', field: 'date_fin' };
    }
    
    const debut = new Date(dateDebut);
    const fin = new Date(dateFin);
    
    if (isNaN(debut.getTime())) {
        return { valid: false, message: 'Date de début invalide', field: 'date_debut' };
    }
    
    if (isNaN(fin.getTime())) {
        return { valid: false, message: 'Date de fin invalide', field: 'date_fin' };
    }
    
    if (fin <= debut) {
        return { valid: false, message: 'La date de fin doit être postérieure à la date de début', field: 'date_fin' };
    }
    
    return { valid: true };
}

function validateSelect(value) {
    if (!value || value === '' || value === '0') {
        return { valid: false, message: 'Veuillez sélectionner une option' };
    }
    return { valid: true };
}

function validateNumber(value, min = 0, max = 999999) {
    const num = parseInt(value);
    
    if (isNaN(num)) {
        return { valid: false, message: 'Veuillez entrer un nombre valide' };
    }
    
    if (num < min) {
        return { valid: false, message: `Le nombre doit être au moins ${min}` };
    }
    
    if (num > max) {
        return { valid: false, message: `Le nombre ne peut pas dépasser ${max}` };
    }
    
    return { valid: true };
}

function validateQuestion(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'La question est obligatoire' };
    }
    
    if (value.length < 5) {
        return { valid: false, message: 'La question doit contenir au moins 5 caractères' };
    }
    
    return { valid: true };
}

function validateReponse(value) {
    value = value ? value.trim() : '';
    
    if (!value || value.length === 0) {
        return { valid: false, message: 'La réponse est obligatoire' };
    }
    
    return { valid: true };
}

function validateFile(file, maxSize = 5, allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip']) {
    if (!file) {
        return { valid: true };
    }
    
    const fileName = file.name;
    const fileSize = file.size / (1024 * 1024);
    const extension = fileName.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(extension)) {
        return { valid: false, message: `Type de fichier non autorisé. Types acceptés: ${allowedTypes.join(', ')}` };
    }
    
    if (fileSize > maxSize) {
        return { valid: false, message: `Le fichier est trop volumineux. Taille max: ${maxSize}MB` };
    }
    
    return { valid: true };
}

function validateImage(file) {
    if (!file) {
        return { valid: true };
    }
    
    const allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const extension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(extension)) {
        return { valid: false, message: 'Format d\'image non supporté. Utilisez: JPG, PNG, GIF ou WEBP' };
    }
    
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        return { valid: false, message: 'L\'image est trop volumineuse. Taille max: 5MB' };
    }
    
    return { valid: true };
}

// ==================== UI FUNCTIONS ====================

function showError(input, message) {
    removeError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `<span style="margin-right: 5px;">⚠️</span>${message}`;
    
    input.parentElement.appendChild(errorDiv);
    input.classList.add('error');
    input.style.borderColor = '#ff3333';
    
    errorDiv.style.animation = 'fadeIn 0.3s ease';
}

function removeError(input) {
    const parent = input.parentElement;
    const errorDiv = parent.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
    input.classList.remove('error');
    input.style.borderColor = '';
}

function clearAllErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.remove());
    document.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
        el.style.borderColor = '';
    });
}

function validateField(input, validatorFunction, ...args) {
    removeError(input);
    
    const result = validatorFunction(input.value, ...args);
    if (!result.valid) {
        showError(input, result.message);
        return false;
    }
    
    input.style.borderColor = '#00ff88';
    return true;
}

// ==================== FORM VALIDATION ====================

function validateEventForm() {
    clearAllErrors();
    let isValid = true;
    let firstError = null;
    
    const typeSelect = document.getElementById('eventType');
    if (typeSelect) {
        const typeResult = validateSelect(typeSelect.value);
        if (!typeResult.valid) {
            showError(typeSelect, typeResult.message);
            if (!firstError) firstError = typeSelect;
            isValid = false;
        }
    }
    
    const titreInput = document.getElementById('eventTitre');
    if (titreInput) {
        const titreResult = validateTitre(titreInput.value);
        if (!titreResult.valid) {
            showError(titreInput, titreResult.message);
            if (!firstError) firstError = titreInput;
            isValid = false;
        }
    }
    
    const descInput = document.getElementById('eventDescription');
    if (descInput) {
        const descResult = validateDescription(descInput.value);
        if (!descResult.valid) {
            showError(descInput, descResult.message);
            if (!firstError) firstError = descInput;
            isValid = false;
        }
    }
    
    const dateDebutInput = document.getElementById('eventDateDebut');
    const dateFinInput = document.getElementById('eventDateFin');
    if (dateDebutInput && dateFinInput) {
        const datesResult = validateDates(dateDebutInput.value, dateFinInput.value);
        if (!datesResult.valid) {
            const targetInput = datesResult.field === 'date_debut' ? dateDebutInput : dateFinInput;
            showError(targetInput, datesResult.message);
            if (!firstError) firstError = targetInput;
            isValid = false;
        }
    }
    
    if (firstError) {
        firstError.focus();
    }
    
    return isValid;
}

function validateParticipationForm() {
    clearAllErrors();
    let isValid = true;
    let firstError = null;
    
    const nomInput = document.getElementById('partNom');
    if (nomInput) {
        const nomResult = validateName(nomInput.value);
        if (!nomResult.valid) {
            showError(nomInput, nomResult.message);
            if (!firstError) firstError = nomInput;
            isValid = false;
        }
    }
    
    const prenomInput = document.getElementById('partPrenom');
    if (prenomInput) {
        const prenomResult = validateName(prenomInput.value);
        if (!prenomResult.valid) {
            showError(prenomInput, prenomResult.message);
            if (!firstError) firstError = prenomInput;
            isValid = false;
        }
    }
    
    const emailInput = document.getElementById('partEmail');
    if (emailInput) {
        const emailResult = validateEmail(emailInput.value);
        if (!emailResult.valid) {
            showError(emailInput, emailResult.message);
            if (!firstError) firstError = emailInput;
            isValid = false;
        }
    }
    
    const commentaireInput = document.getElementById('partCommentaire');
    if (commentaireInput) {
        const commentaireResult = validateCommentaire(commentaireInput.value);
        if (!commentaireResult.valid) {
            showError(commentaireInput, commentaireResult.message);
            if (!firstError) firstError = commentaireInput;
            isValid = false;
        }
    }
    
    if (firstError) {
        firstError.focus();
    }
    
    return isValid;
}

function validateQuizForm() {
    clearAllErrors();
    let isValid = true;
    let firstError = null;
    
    const nomInput = document.getElementById('quizNom');
    if (nomInput) {
        const nomResult = validateName(nomInput.value);
        if (!nomResult.valid) {
            showError(nomInput, nomResult.message);
            if (!firstError) firstError = nomInput;
            isValid = false;
        }
    }
    
    const prenomInput = document.getElementById('quizPrenom');
    if (prenomInput) {
        const prenomResult = validateName(prenomInput.value);
        if (!prenomResult.valid) {
            showError(prenomInput, prenomResult.message);
            if (!firstError) firstError = prenomInput;
            isValid = false;
        }
    }
    
    const emailInput = document.getElementById('quizEmail');
    if (emailInput) {
        const emailResult = validateEmail(emailInput.value);
        if (!emailResult.valid) {
            showError(emailInput, emailResult.message);
            if (!firstError) firstError = emailInput;
            isValid = false;
        }
    }
    
    // Vérifier les réponses aux questions
    const questionCards = document.querySelectorAll('.question-card');
    let unansweredCount = 0;
    
    questionCards.forEach((card) => {
        const selected = card.querySelector('input[type="radio"]:checked, input[type="checkbox"]:checked');
        if (!selected) {
            unansweredCount++;
            card.style.borderColor = '#ff3333';
            card.style.boxShadow = '0 0 15px rgba(255, 51, 51, 0.3)';
            if (!firstError) firstError = card;
        } else {
            card.style.borderColor = '#00ff88';
            card.style.boxShadow = '0 0 15px rgba(0, 255, 136, 0.2)';
        }
    });
    
    if (unansweredCount > 0) {
        alert(`Veuillez répondre à toutes les questions. Il reste ${unansweredCount} question(s) sans réponse.`);
        isValid = false;
    }
    
    if (firstError && firstError.scrollIntoView) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    return isValid;
}

function validateQuizQuestions() {
    let isValid = true;
    const questionBlocks = document.querySelectorAll('.question-block');
    
    if (questionBlocks.length === 0) {
        alert('Veuillez ajouter au moins une question');
        return false;
    }
    
    questionBlocks.forEach((block, index) => {
        const questionInput = block.querySelector('.question-texte');
        if (questionInput) {
            const questionResult = validateQuestion(questionInput.value);
            if (!questionResult.valid) {
                showError(questionInput, questionResult.message);
                isValid = false;
            }
        }
        
        const reponseInputs = block.querySelectorAll('.reponse-texte');
        if (reponseInputs.length < 2) {
            alert(`La question ${index + 1} doit avoir au moins 2 réponses`);
            isValid = false;
        }
        
        reponseInputs.forEach((repInput) => {
            const repResult = validateReponse(repInput.value);
            if (!repResult.valid) {
                showError(repInput, repResult.message);
                isValid = false;
            }
        });
        
        const correcteChecked = block.querySelector('input[name^="reponse_correcte"]:checked');
        if (!correcteChecked) {
            alert(`Veuillez sélectionner la réponse correcte pour la question ${index + 1}`);
            isValid = false;
        }
    });
    
    return isValid;
}

// ==================== REAL-TIME VALIDATION ====================

function setupRealTimeValidation() {
    document.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(input => {
        input.addEventListener('blur', function() {
            const fieldName = (this.id || this.name || '').toLowerCase();
            
            if (fieldName.includes('nom') || fieldName.includes('prenom')) {
                validateField(this, validateName);
            } else if (fieldName.includes('email')) {
                validateField(this, validateEmail);
            } else if (fieldName.includes('titre')) {
                validateField(this, validateTitre);
            } else if (fieldName.includes('description')) {
                validateField(this, validateDescription);
            } else if (fieldName.includes('commentaire')) {
                validateField(this, validateCommentaire);
            }
        });
        
        input.addEventListener('input', function() {
            removeError(this);
        });
    });
    
    document.querySelectorAll('[maxlength]').forEach(input => {
        const maxLength = input.getAttribute('maxlength');
        const counter = input.parentElement.querySelector('small');
        
        if (counter) {
            input.addEventListener('input', function() {
                counter.textContent = `${this.value.length}/${maxLength}`;
                
                if (this.value.length >= maxLength * 0.9) {
                    counter.style.color = '#ff9500';
                } else {
                    counter.style.color = '';
                }
            });
        }
    });
}

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', function() {
    setupRealTimeValidation();
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
});

// ==================== EXPORTS ====================

window.validateRequired = validateRequired;
window.validateName = validateName;
window.validateEmail = validateEmail;
window.validateTitre = validateTitre;
window.validateDescription = validateDescription;
window.validateCommentaire = validateCommentaire;
window.validateDates = validateDates;
window.validateSelect = validateSelect;
window.validateNumber = validateNumber;
window.validateQuestion = validateQuestion;
window.validateReponse = validateReponse;
window.validateFile = validateFile;
window.validateImage = validateImage;
window.validateField = validateField;
window.showError = showError;
window.removeError = removeError;
window.clearAllErrors = clearAllErrors;
window.validateEventForm = validateEventForm;
window.validateParticipationForm = validateParticipationForm;
window.validateQuizForm = validateQuizForm;
window.validateQuizQuestions = validateQuizQuestions;
