/**
 * Script per la validazione del form di registrazione
 */

document.addEventListener('DOMContentLoaded', function() {
    // Ottieni il form di registrazione
    const registerForm = document.getElementById('register-form');

    if (registerForm) {
        // Aggiungi la validazione sul submit
        registerForm.addEventListener('submit', function(event) {
            // Verifica se il form è valido
            if (!validateRegistrationForm(this)) {
                // Se non è valido, previeni l'invio
                event.preventDefault();
            }
        });

        // Aggiungi la validazione in tempo reale sui campi
        const fields = registerForm.querySelectorAll('input, select');
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });
        });

        // Validazione speciale per la conferma password
        const passwordField = registerForm.querySelector('#password');
        const confirmPasswordField = registerForm.querySelector('#confirm_password');

        if (passwordField && confirmPasswordField) {
            confirmPasswordField.addEventListener('input', function() {
                if (passwordField.value !== confirmPasswordField.value) {
                    showError(confirmPasswordField, 'Le password non coincidono.');
                } else {
                    removeError(confirmPasswordField);
                }
            });

            // Validazione anche quando si modifica la password principale
            passwordField.addEventListener('input', function() {
                if (confirmPasswordField.value && passwordField.value !== confirmPasswordField.value) {
                    showError(confirmPasswordField, 'Le password non coincidono.');
                } else if (confirmPasswordField.value) {
                    removeError(confirmPasswordField);
                }
            });
        }

        // Gestione del logo (solo per form di registrazione del salone)
        const logoInput = registerForm.querySelector('#logo');
        if (logoInput) {
            logoInput.addEventListener('change', function() {
                validateField(this);

                // Mostra anteprima dell'immagine se è stato caricato un file
                if (this.files && this.files[0]) {
                    // Verifica se esiste un elemento di anteprima, altrimenti crealo
                    let previewContainer = document.getElementById('logo-preview-container');
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.id = 'logo-preview-container';
                        previewContainer.className = 'logo-preview-container';
                        previewContainer.style.marginTop = '10px';
                        this.parentNode.appendChild(previewContainer);

                        const previewImg = document.createElement('img');
                        previewImg.id = 'logo-preview';
                        previewImg.style.maxWidth = '200px';
                        previewImg.style.maxHeight = '200px';
                        previewImg.style.borderRadius = '5px';
                        previewContainer.appendChild(previewImg);
                    }

                    const preview = document.getElementById('logo-preview');
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };

                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Gestione del controllo dei termini di servizio
        const termsCheckbox = registerForm.querySelector('#terms');
        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', function() {
                if (!this.checked) {
                    showError(this, 'Devi accettare i termini di servizio e la privacy policy.');
                } else {
                    removeError(this);
                }
            });
        }
    }
});

/**
 * Valida l'intero form di registrazione
 *
 * @param {HTMLFormElement} form Form da validare
 * @returns {boolean} True se il form è valido, false altrimenti
 */
function validateRegistrationForm(form) {
    let isValid = true;

    // Rimuovi tutti i messaggi di errore esistenti
    const errorMessages = form.querySelectorAll('.form-error');
    errorMessages.forEach(error => error.remove());

    // Verifica che tutti i campi obbligatori siano compilati
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        // Per i checkbox, controlla se sono selezionati
        if (field.type === 'checkbox') {
            if (!field.checked) {
                showError(field, field.id === 'terms' ?
                    'Devi accettare i termini di servizio e la privacy policy.' :
                    'Questo campo è obbligatorio.');
                isValid = false;
            }
        }
        // Per tutti gli altri campi, controlla se sono compilati
        else if (!field.value.trim()) {
            showError(field, 'Questo campo è obbligatorio.');
            isValid = false;
        } else {
            // Se il campo è compilato, valida il suo contenuto
            if (!validateField(field)) {
                isValid = false;
            }
        }
    });

    // Verifica che le password coincidano
    const passwordField = form.querySelector('#password');
    const confirmPasswordField = form.querySelector('#confirm_password');

    if (passwordField && confirmPasswordField &&
        passwordField.value && confirmPasswordField.value &&
        passwordField.value !== confirmPasswordField.value) {
        showError(confirmPasswordField, 'Le password non coincidono.');
        isValid = false;
    }

    return isValid;
}

/**
 * Valida un singolo campo del form
 *
 * @param {HTMLElement} field Campo da validare
 * @returns {boolean} True se il campo è valido, false altrimenti
 */
function validateField(field) {
    // Se il campo è vuoto e non è obbligatorio, è valido
    if (field.type !== 'checkbox' && !field.value.trim() && !field.hasAttribute('required')) {
        removeError(field);
        return true;
    }

    // Valida in base al tipo di campo
    let isValid = true;
    let errorMessage = '';

    switch (field.id) {
        case 'email':
            if (!isValidEmail(field.value)) {
                errorMessage = 'Inserisci un indirizzo email valido.';
                isValid = false;
            }
            break;

        case 'phone':
            if (!isValidPhone(field.value)) {
                errorMessage = 'Inserisci un numero di telefono valido.';
                isValid = false;
            }
            break;

        case 'password':
            if (field.value.length < 8) {
                errorMessage = 'La password deve contenere almeno 8 caratteri.';
                isValid = false;
            }
            break;

        case 'postal_code':
            if (!/^\d{5}$/.test(field.value)) {
                errorMessage = 'Il CAP deve essere di 5 cifre.';
                isValid = false;
            }
            break;

        case 'website':
            if (field.value && !isValidUrl(field.value)) {
                errorMessage = 'Inserisci un URL valido (es. https://www.example.com).';
                isValid = false;
            }
            break;

        case 'logo':
            if (field.files.length > 0) {
                const file = field.files[0];

                // Verifica il tipo di file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    errorMessage = 'Formato non supportato. Usa JPG, PNG o GIF.';
                    isValid = false;
                }

                // Verifica la dimensione del file (max 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    errorMessage = 'Il file è troppo grande. La dimensione massima è 2 MB.';
                    isValid = false;
                }
            }
            break;

        case 'terms':
            if (field.type === 'checkbox' && !field.checked) {
                errorMessage = 'Devi accettare i termini di servizio e la privacy policy.';
                isValid = false;
            }
            break;

        case 'first_name':
        case 'last_name':
        case 'nickname':
        case 'name': // Nome del salone
            if (field.value.length > 100) {
                errorMessage = 'Questo campo non può superare i 100 caratteri.';
                isValid = false;
            }
            break;

        case 'address':
            if (field.value.length > 255) {
                errorMessage = 'L\'indirizzo non può superare i 255 caratteri.';
                isValid = false;
            }
            break;
    }

    // Mostra o rimuovi l'errore
    if (!isValid) {
        showError(field, errorMessage);
    } else {
        removeError(field);
    }

    return isValid;
}

/**
 * Mostra un messaggio di errore per un campo
 *
 * @param {HTMLElement} field Campo con errore
 * @param {string} message Messaggio di errore
 */
function showError(field, message) {
    // Rimuovi eventuali errori esistenti
    removeError(field);

    // Crea il messaggio di errore
    const errorElement = document.createElement('div');
    errorElement.className = 'form-error';
    errorElement.textContent = message;

    // Inserisci l'errore dopo il campo o dopo il suo label per i checkbox
    if (field.type === 'checkbox') {
        const label = field.parentNode.querySelector('label');
        label.parentNode.insertBefore(errorElement, label.nextSibling);
    } else {
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }

    // Aggiungi la classe di errore al campo
    field.classList.add('is-invalid');
}

/**
 * Rimuove il messaggio di errore da un campo
 *
 * @param {HTMLElement} field Campo da cui rimuovere l'errore
 */
function removeError(field) {
    // Rimuovi eventuali messaggi di errore esistenti
    let parentNode = field.parentNode;

    // Per i checkbox, cerca l'errore nel contenitore padre
    if (field.type === 'checkbox') {
        parentNode = field.closest('.checkbox-group') || parentNode;
    }

    const errorElement = parentNode.querySelector('.form-error');
    if (errorElement) {
        errorElement.remove();
    }

    // Rimuovi la classe di errore
    field.classList.remove('is-invalid');
}

/**
 * Controlla se un'email è valida
 *
 * @param {string} email Email da controllare
 * @returns {boolean} True se l'email è valida, false altrimenti
 */
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Controlla se un numero di telefono è valido
 *
 * @param {string} phone Numero di telefono da controllare
 * @returns {boolean} True se il numero è valido, false altrimenti
 */
function isValidPhone(phone) {
    // Accetta numeri di telefono italiani in vari formati
    const re = /^[0-9+\s()-]{7,20}$/;
    return re.test(phone);
}

/**
 * Controlla se un URL è valido
 *
 * @param {string} url URL da controllare
 * @returns {boolean} True se l'URL è valido, false altrimenti
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Mostra in anteprima l'immagine selezionata
 *
 * @param {HTMLInputElement} input Input file
 * @param {string} previewId ID dell'elemento per l'anteprima
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);

    if (!preview) {
        return;
    }

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}
