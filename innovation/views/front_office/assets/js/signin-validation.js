/**
 * Sign-In Form Validation (Client-Side)
 * Pure JavaScript validation - NO HTML5 validation
 */

document.addEventListener('DOMContentLoaded', function() {
    const signInForm = document.getElementById('signInForm');
    
    if (signInForm) {
        // Remove HTML5 validation
        signInForm.setAttribute('novalidate', 'novalidate');
        
        signInForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            clearErrors();
            
            // Get form data
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const email = emailField.value.trim();
            const password = passwordField.value;
            
            // Validate inputs
            let isValid = true;
            
            // Email validation - Check for @ symbol
            if (!email) {
                showError('email', 'Email is required');
                isValid = false;
            } else if (!email.includes('@')) {
                showError('email', 'Email must contain @ symbol');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showError('email', 'Please enter a valid email address (e.g., user@example.com)');
                isValid = false;
            }
            
            // Password validation - Must be at least 8 characters
            if (!password) {
                showError('password', 'Password is required');
                isValid = false;
            } else if (password.length < 8) {
                showError('password', 'Password must be at least 8 characters long');
                isValid = false;
            }
            
            // If validation passes, submit via AJAX
            if (isValid) {
                console.log('Validation passed, submitting form...');
                submitSignIn(email, password);
            } else {
                console.log('Validation failed');
            }
        });
        
        // Real-time validation on input
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value.trim();
            if (email && !email.includes('@')) {
                showError('email', 'Email must contain @ symbol');
            } else {
                clearFieldError('email');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            if (password && password.length < 8) {
                showError('password', 'Password must be at least 8 characters long');
            } else {
                clearFieldError('password');
            }
        });
    }
});

/**
 * Validate email format (must have @ and domain)
 */
function isValidEmail(email) {
    // Must contain @ and have text before and after it
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Show error message for a field
 */
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const formGroup = field.closest('.form-group');
    
    // Add error class to input
    field.style.borderColor = 'var(--accent-red)';
    field.style.boxShadow = '0 0 10px rgba(255, 51, 51, 0.3)';
    
    // Create or update error message
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
 * Clear error for specific field
 */
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const formGroup = field.closest('.form-group');
    
    field.style.borderColor = 'var(--metal-dark)';
    field.style.boxShadow = 'none';
    
    const errorDiv = formGroup.querySelector('.error-message-field');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Clear all error messages
 */
function clearErrors() {
    // Clear field errors
    document.querySelectorAll('.error-message-field').forEach(el => el.remove());
    
    // Reset field borders
    document.querySelectorAll('.form-group input').forEach(input => {
        input.style.borderColor = 'var(--metal-dark)';
        input.style.boxShadow = 'none';
    });
    
    // Clear general error message
    const errorContainer = document.getElementById('errorContainer');
    if (errorContainer) {
        errorContainer.style.display = 'none';
        errorContainer.innerHTML = '';
    }
    
    // Clear success message
    const successContainer = document.getElementById('successContainer');
    if (successContainer) {
        successContainer.style.display = 'none';
        successContainer.innerHTML = '';
    }
}

/**
 * Display general error message
 */
function displayGeneralError(errors) {
    let errorContainer = document.getElementById('errorContainer');
    
    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.id = 'errorContainer';
        errorContainer.className = 'error-message';
        errorContainer.style.cssText = `
            background: rgba(255, 51, 51, 0.1);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 500;
        `;
        
        const form = document.getElementById('signInForm');
        form.parentNode.insertBefore(errorContainer, form);
    }
    
    errorContainer.style.display = 'block';
    
    if (Array.isArray(errors)) {
        errorContainer.innerHTML = '❌ ' + errors.join('<br>❌ ');
    } else {
        errorContainer.textContent = '❌ ' + errors;
    }
}

/**
 * Display success message
 */
function displaySuccess(message) {
    let successContainer = document.getElementById('successContainer');
    
    if (!successContainer) {
        successContainer = document.createElement('div');
        successContainer.id = 'successContainer';
        successContainer.className = 'success-message';
        successContainer.style.cssText = `
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 500;
        `;
        
        const form = document.getElementById('signInForm');
        form.parentNode.insertBefore(successContainer, form);
    }
    
    successContainer.style.display = 'block';
    successContainer.textContent = '✅ ' + message;
}

/**
 * Submit sign-in via AJAX
 */
function submitSignIn(email, password) {
    const submitBtn = document.querySelector('.signin-btn');
    const originalText = submitBtn.textContent;
    
    console.log('Submitting sign-in for:', email);
    
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing In...';
    submitBtn.style.opacity = '0.7';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'signin');
    formData.append('email', email);
    formData.append('password', password);
    
    console.log('Sending request to AuthController...');
    
    // Send AJAX request
    fetch('../../controllers/AuthController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        
        if (data.success) {
            // Show success message
            displaySuccess(data.message);
            
            // Redirect after 1 second
            console.log('Redirecting to:', data.redirect);
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            // Show errors
            console.log('Login failed:', data.errors);
            displayGeneralError(data.errors);
            
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            submitBtn.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        displayGeneralError('Network error. Please check your connection and try again.');
        
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        submitBtn.style.opacity = '1';
    });
}

/**
 * Toggle password visibility
 */
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle-btn');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = '👁️';
    }
}
