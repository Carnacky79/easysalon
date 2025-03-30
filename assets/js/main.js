/**
 * Salon Booking - Script principale
 *
 * Questo file contiene le funzioni JavaScript comuni a tutte le pagine dell'applicazione
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestione menu mobile
    initMobileMenu();

    // Gestione dropdown
    initDropdowns();

    // Gestione messaggi flash
    initFlashMessages();

    // Gestione form di ricerca nella homepage
    initSearchForm();

    // Inizializza i tooltip
    initTooltips();
});

/**
 * Inizializza il menu mobile
 */
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('open');

            // Trasforma l'icona hamburger in una X
            const spans = this.querySelectorAll('span');
            if (mobileMenu.classList.contains('open')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -7px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });

        // Chiudi il menu se si clicca fuori da esso
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuToggle.contains(event.target) && mobileMenu.classList.contains('open')) {
                mobileMenu.classList.remove('open');

                const spans = mobileMenuToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }
}

/**
 * Inizializza i dropdown
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        const toggleBtn = dropdown.querySelector('.dropdown-toggle');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        if (toggleBtn && dropdownMenu) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            });

            // Chiudi il dropdown se si clicca fuori da esso
            document.addEventListener('click', function(event) {
                if (!dropdown.contains(event.target)) {
                    dropdownMenu.style.display = 'none';
                }
            });
        }
    });
}

/**
 * Inizializza i messaggi flash
 */
function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');

    flashMessages.forEach(flash => {
        const closeBtn = flash.querySelector('.close-flash');

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                flash.style.display = 'none';
            });

            // Nascondi automaticamente il messaggio dopo 5 secondi
            setTimeout(() => {
                flash.style.display = 'none';
            }, 5000);
        }
    });
}

/**
 * Inizializza il form di ricerca nella homepage
 */
function initSearchForm() {
    const searchForm = document.querySelector('.search-form');

    if (searchForm) {
        const citySelect = searchForm.querySelector('#city');

        if (citySelect) {
            // Gestisci il cambio di città
            citySelect.addEventListener('change', function() {
                // Qui potresti aggiungere una chiamata AJAX per caricare i tipi di servizio disponibili nella città selezionata
                // Per ora è solo un esempio di gestione dell'evento
                console.log('Città selezionata:', this.value);
            });
        }
    }
}

/**
 * Inizializza i tooltip
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');

    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');

            if (tooltipText) {
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip';
                tooltipEl.textContent = tooltipText;

                document.body.appendChild(tooltipEl);

                const rect = this.getBoundingClientRect();
                const tooltipRect = tooltipEl.getBoundingClientRect();

                tooltipEl.style.top = (rect.top - tooltipRect.height - 10) + 'px';
                tooltipEl.style.left = (rect.left + (rect.width / 2) - (tooltipRect.width / 2)) + 'px';
                tooltipEl.style.opacity = '1';

                this.addEventListener('mouseleave', function onMouseLeave() {
                    tooltipEl.remove();
                    this.removeEventListener('mouseleave', onMouseLeave);
                });
            }
        });
    });
}

/**
 * Funzione di utilità per effettuare chiamate AJAX
 *
 * @param {Object} options Opzioni per la chiamata AJAX
 * @param {string} options.url URL della richiesta
 * @param {string} options.method Metodo HTTP (GET, POST, etc.)
 * @param {Object|FormData} options.data Dati da inviare
 * @param {Function} options.success Callback in caso di successo
 * @param {Function} options.error Callback in caso di errore
 */
function ajax(options) {
    const xhr = new XMLHttpRequest();

    xhr.open(options.method || 'GET', options.url, true);

    if (!(options.data instanceof FormData)) {
        xhr.setRequestHeader('Content-Type', 'application/json');
    }

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            let response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (e) {
                response = xhr.responseText;
            }

            if (typeof options.success === 'function') {
                options.success(response, xhr.status, xhr);
            }
        } else {
            if (typeof options.error === 'function') {
                options.error(xhr.statusText, xhr.status, xhr);
            }
        }
    };

    xhr.onerror = function() {
        if (typeof options.error === 'function') {
            options.error('Network Error', 0, xhr);
        }
    };

    if (options.data instanceof FormData) {
        xhr.send(options.data);
    } else if (options.data) {
        xhr.send(JSON.stringify(options.data));
    } else {
        xhr.send();
    }
}

/**
 * Funzione di utilità per la validazione dei form
 *
 * @param {HTMLFormElement} form Elemento form da validare
 * @param {Object} rules Regole di validazione
 * @returns {boolean} True se il form è valido, false altrimenti
 */
function validateForm(form, rules) {
    let isValid = true;

    // Rimuovi i messaggi di errore precedenti
    form.querySelectorAll('.form-error').forEach(error => {
        error.remove();
    });

    // Controlla ogni campo
    for (const field in rules) {
        const input = form.querySelector(`[name="${field}"]`);

        if (input) {
            const value = input.value.trim();
            const rule = rules[field];

            // Campo richiesto
            if (rule.required && value === '') {
                showError(input, rule.required === true ? 'Questo campo è obbligatorio.' : rule.required);
                isValid = false;
                continue;
            }

            // Lunghezza minima
            if (rule.minLength && value.length < rule.minLength) {
                showError(input, `Questo campo deve contenere almeno ${rule.minLength} caratteri.`);
                isValid = false;
                continue;
            }

            // Lunghezza massima
            if (rule.maxLength && value.length > rule.maxLength) {
                showError(input, `Questo campo non può superare ${rule.maxLength} caratteri.`);
                isValid = false;
                continue;
            }

            // Email
            if (rule.email && value !== '' && !isValidEmail(value)) {
                showError(input, 'Inserisci un indirizzo email valido.');
                isValid = false;
                continue;
            }

            // Numerico
            if (rule.numeric && value !== '' && isNaN(value)) {
                showError(input, 'Questo campo deve essere un numero.');
                isValid = false;
                continue;
            }

            // Intero positivo
            if (rule.positiveInteger && value !== '' && (!Number.isInteger(Number(value)) || Number(value) <= 0)) {
                showError(input, 'Questo campo deve essere un numero intero positivo.');
                isValid = false;
                continue;
            }

            // Pattern regex
            if (rule.pattern && value !== '' && !rule.pattern.test(value)) {
                showError(input, rule.message || 'Questo campo non è valido.');
                isValid = false;
                continue;
            }

            // Conferma password
            if (rule.match) {
                const matchInput = form.querySelector(`[name="${rule.match}"]`);
                if (matchInput && value !== matchInput.value) {
                    showError(input, 'I campi non coincidono.');
                    isValid = false;
                    continue;
                }
            }

            // Funzione di validazione personalizzata
            if (rule.validate && typeof rule.validate === 'function') {
                const result = rule.validate(value, input, form);
                if (result !== true) {
                    showError(input, result || 'Questo campo non è valido.');
                    isValid = false;
                    continue;
                }
            }
        }
    }

    return isValid;
}

/**
 * Mostra un messaggio di errore per un campo del form
 *
 * @param {HTMLElement} input Campo di input
 * @param {string} message Messaggio di errore
 */
function showError(input, message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'form-error';
    errorElement.textContent = message;

    // Inserisci il messaggio di errore dopo il campo
    input.parentNode.insertBefore(errorElement, input.nextSibling);

    // Aggiungi la classe di errore al campo
    input.classList.add('is-invalid');
}

/**
 * Controlla se una stringa è un indirizzo email valido
 *
 * @param {string} email Indirizzo email da controllare
 * @returns {boolean} True se l'email è valida, false altrimenti
 */
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Formatta una data in formato leggibile
 *
 * @param {string|Date} date Data da formattare
 * @param {string} format Formato desiderato (default: dd/mm/yyyy)
 * @returns {string} Data formattata
 */
function formatDate(date, format = 'dd/mm/yyyy') {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();

    switch (format) {
        case 'dd/mm/yyyy':
            return `${day}/${month}/${year}`;
        case 'mm/dd/yyyy':
            return `${month}/${day}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return `${day}/${month}/${year}`;
    }
}

/**
 * Formatta un'ora in formato leggibile
 *
 * @param {string} time Ora in formato HH:MM
 * @param {boolean} includeMeridiem Se includere AM/PM
 * @returns {string} Ora formattata
 */
function formatTime(time, includeMeridiem = false) {
    if (!time) return '';

    const [hours, minutes] = time.split(':');

    if (!includeMeridiem) {
        return `${hours}:${minutes}`;
    }

    const h = parseInt(hours, 10);
    return `${h % 12 || 12}:${minutes} ${h < 12 ? 'AM' : 'PM'}`;
}

/**
 * Calcola la differenza tra due ore in minuti
 *
 * @param {string} startTime Ora di inizio (formato HH:MM)
 * @param {string} endTime Ora di fine (formato HH:MM)
 * @returns {number} Differenza in minuti
 */
function getTimeDifference(startTime, endTime) {
    const [startHours, startMinutes] = startTime.split(':').map(Number);
    const [endHours, endMinutes] = endTime.split(':').map(Number);

    // Converti le ore in minuti
    const startTotalMinutes = (startHours * 60) + startMinutes;
    const endTotalMinutes = (endHours * 60) + endMinutes;

    return endTotalMinutes - startTotalMinutes;
}

/**
 * Converte i minuti in formato ore:minuti
 *
 * @param {number} minutes Minuti da convertire
 * @returns {string} Tempo in formato HH:MM
 */
function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

/**
 * Aggiunge minuti a un'ora
 *
 * @param {string} time Ora di partenza (formato HH:MM)
 * @param {number} minutes Minuti da aggiungere
 * @returns {string} Ora risultante (formato HH:MM)
 */
function addMinutesToTime(time, minutes) {
    const [hours, mins] = time.split(':').map(Number);

    // Converti l'ora in minuti
    let totalMinutes = (hours * 60) + mins + minutes;

    // Gestisci il caso in cui totalMinutes sia negativo
    if (totalMinutes < 0) {
        totalMinutes = 0;
    }

    const newHours = Math.floor(totalMinutes / 60) % 24;
    const newMinutes = totalMinutes % 60;

    return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`;
}

/**
 * Controlla se un'ora è compresa tra due orari
 *
 * @param {string} time Ora da controllare (formato HH:MM)
 * @param {string} startTime Ora di inizio (formato HH:MM)
 * @param {string} endTime Ora di fine (formato HH:MM)
 * @returns {boolean} True se l'ora è compresa, false altrimenti
 */
function isTimeBetween(time, startTime, endTime) {
    const [h, m] = time.split(':').map(Number);
    const [startH, startM] = startTime.split(':').map(Number);
    const [endH, endM] = endTime.split(':').map(Number);

    // Converti in minuti
    const timeMinutes = (h * 60) + m;
    const startMinutes = (startH * 60) + startM;
    const endMinutes = (endH * 60) + endM;

    return timeMinutes >= startMinutes && timeMinutes <= endMinutes;
}

/**
 * Formatta un numero come prezzo in euro
 *
 * @param {number} amount Importo da formattare
 * @returns {string} Importo formattato
 */
function formatPrice(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

/**
 * Carica le opzioni in un select da un array di oggetti
 *
 * @param {HTMLSelectElement} selectElement Elemento select
 * @param {Array} options Array di opzioni
 * @param {string} valueKey Chiave per il valore
 * @param {string} textKey Chiave per il testo
 * @param {string} defaultText Testo per l'opzione predefinita
 * @param {*} selectedValue Valore da selezionare
 */
function populateSelect(selectElement, options, valueKey, textKey, defaultText = null, selectedValue = null) {
    // Rimuovi tutte le opzioni esistenti
    selectElement.innerHTML = '';

    // Aggiungi l'opzione predefinita se necessario
    if (defaultText) {
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = defaultText;
        selectElement.appendChild(defaultOption);
    }

    // Aggiungi le nuove opzioni
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option[valueKey];
        optionElement.textContent = option[textKey];

        if (selectedValue !== null && option[valueKey] == selectedValue) {
            optionElement.selected = true;
        }

        selectElement.appendChild(optionElement);
    });
}

/**
 * Crea un elemento HTML con attributi e contenuto
 *
 * @param {string} tag Nome del tag
 * @param {Object} attributes Attributi dell'elemento
 * @param {string|HTMLElement|Array} content Contenuto dell'elemento
 * @returns {HTMLElement} Elemento creato
 */
function createElement(tag, attributes = {}, content = null) {
    const element = document.createElement(tag);

    // Aggiungi gli attributi
    for (const key in attributes) {
        if (key === 'classList' && Array.isArray(attributes[key])) {
            element.classList.add(...attributes[key]);
        } else {
            element.setAttribute(key, attributes[key]);
        }
    }

    // Aggiungi il contenuto
    if (content !== null) {
        if (typeof content === 'string') {
            element.textContent = content;
        } else if (content instanceof HTMLElement) {
            element.appendChild(content);
        } else if (Array.isArray(content)) {
            content.forEach(item => {
                if (typeof item === 'string') {
                    element.innerHTML += item;
                } else if (item instanceof HTMLElement) {
                    element.appendChild(item);
                }
            });
        }
    }

    return element;
}
