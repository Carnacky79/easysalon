/**
 * Script per la validazione del form di login
 */

document.addEventListener('DOMContentLoaded', function() {
    // Ottieni il form di login
    const loginForm = document.getElementById('login-form');

    if (loginForm) {
        // Aggiungi la validazione sul submit
        loginForm.addEventListener('submit', function(event) {
            // Verifica se il form è valido
            if (!validateLoginForm(this)) {
                // Se non è valido, previeni l'invio
                event.preventDefault();
            }
        });

        // Aggiungi la validazione in tempo reale sui campi
        const fields = loginForm.querySelectorAll('input');
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });
        });
    }
});

/**
 * Valida l'intero form di login
 *
 * @param {HTMLFormElement} form Form da validare
 * @returns {boolean} True se il form è valido, false altrimenti
 */
function validateLoginForm(form) {
    let isValid = true;

    // Rimuovi tutti i messaggi di errore esistenti
    const errorMessages = form.querySelectorAll('.form-error');
    errorMessages.forEach(error => error.remove());

    // Verifica che tutti i campi obbligatori siano compilati
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showError(field, 'Questo campo è obbligatorio.');
            isValid = false;
        } else {
            // Se il campo è compilato, valida il suo contenuto
            if (!validateField(field)) {
                isValid = false;
            }
        }
    });

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
    if (!field.value.trim() && !field.hasAttribute('required')) {
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

        case 'password':
            // Il campo password non richiede validazione specifica
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

    // Inserisci l'errore dopo il campo
    field.parentNode.insertBefore(errorElement, field.nextSibling);

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
    const errorElement = field.parentNode.querySelector('.form-error');
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
