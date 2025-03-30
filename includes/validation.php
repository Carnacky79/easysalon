<?php
/**
 * Funzioni di validazione per l'applicazione Salon Booking
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Valida i dati di registrazione di un cliente
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateClientRegistration($data) {
    $errors = [];

    // Validazione nome
    if (empty($data['first_name'])) {
        $errors['first_name'] = 'Il nome è obbligatorio.';
    } elseif (strlen($data['first_name']) > 50) {
        $errors['first_name'] = 'Il nome non può superare i 50 caratteri.';
    }

    // Validazione cognome (opzionale)
    if (!empty($data['last_name']) && strlen($data['last_name']) > 50) {
        $errors['last_name'] = 'Il cognome non può superare i 50 caratteri.';
    }

    // Validazione nickname
    if (empty($data['nickname'])) {
        $errors['nickname'] = 'Il nickname è obbligatorio.';
    } elseif (strlen($data['nickname']) > 50) {
        $errors['nickname'] = 'Il nickname non può superare i 50 caratteri.';
    }

    // Validazione email
    if (empty($data['email'])) {
        $errors['email'] = 'L\'email è obbligatoria.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'email non è valida.';
    } elseif (strlen($data['email']) > 100) {
        $errors['email'] = 'L\'email non può superare i 100 caratteri.';
    }

    // Validazione password
    if (empty($data['password'])) {
        $errors['password'] = 'La password è obbligatoria.';
    } elseif (strlen($data['password']) < 8) {
        $errors['password'] = 'La password deve contenere almeno 8 caratteri.';
    }

    // Validazione conferma password
    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'La conferma della password è obbligatoria.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Le password non coincidono.';
    }

    // Validazione telefono
    if (empty($data['phone'])) {
        $errors['phone'] = 'Il numero di telefono è obbligatorio.';
    } elseif (!preg_match('/^[0-9+\s()-]{7,20}$/', $data['phone'])) {
        $errors['phone'] = 'Il numero di telefono non è valido.';
    }

    // Validazione città
    if (empty($data['city'])) {
        $errors['city'] = 'La città è obbligatoria.';
    } elseif (strlen($data['city']) > 100) {
        $errors['city'] = 'La città non può superare i 100 caratteri.';
    }

    return $errors;
}

/**
 * Valida i dati di registrazione di un salone
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateSalonRegistration($data) {
    $errors = [];

    // Validazione nome
    if (empty($data['name'])) {
        $errors['name'] = 'Il nome del salone è obbligatorio.';
    } elseif (strlen($data['name']) > 100) {
        $errors['name'] = 'Il nome del salone non può superare i 100 caratteri.';
    }

    // Validazione indirizzo
    if (empty($data['address'])) {
        $errors['address'] = 'L\'indirizzo è obbligatorio.';
    } elseif (strlen($data['address']) > 255) {
        $errors['address'] = 'L\'indirizzo non può superare i 255 caratteri.';
    }

    // Validazione città
    if (empty($data['city'])) {
        $errors['city'] = 'La città è obbligatoria.';
    } elseif (strlen($data['city']) > 100) {
        $errors['city'] = 'La città non può superare i 100 caratteri.';
    }

    // Validazione CAP
    if (empty($data['postal_code'])) {
        $errors['postal_code'] = 'Il CAP è obbligatorio.';
    } elseif (!preg_match('/^[0-9]{5}$/', $data['postal_code'])) {
        $errors['postal_code'] = 'Il CAP non è valido (deve essere di 5 cifre).';
    }

    // Validazione telefono
    if (empty($data['phone'])) {
        $errors['phone'] = 'Il numero di telefono è obbligatorio.';
    } elseif (!preg_match('/^[0-9+\s()-]{7,20}$/', $data['phone'])) {
        $errors['phone'] = 'Il numero di telefono non è valido.';
    }

    // Validazione email
    if (empty($data['email'])) {
        $errors['email'] = 'L\'email è obbligatoria.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'email non è valida.';
    } elseif (strlen($data['email']) > 100) {
        $errors['email'] = 'L\'email non può superare i 100 caratteri.';
    }

    // Validazione password
    if (empty($data['password'])) {
        $errors['password'] = 'La password è obbligatoria.';
    } elseif (strlen($data['password']) < 8) {
        $errors['password'] = 'La password deve contenere almeno 8 caratteri.';
    }

    // Validazione conferma password
    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'La conferma della password è obbligatoria.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Le password non coincidono.';
    }

    // Validazione sito web (opzionale)
    if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Il sito web non è valido.';
    }

    return $errors;
}

/**
 * Valida i dati di login
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateLogin($data) {
    $errors = [];

    // Validazione email
    if (empty($data['email'])) {
        $errors['email'] = 'L\'email è obbligatoria.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'email non è valida.';
    }

    // Validazione password
    if (empty($data['password'])) {
        $errors['password'] = 'La password è obbligatoria.';
    }

    return $errors;
}

/**
 * Valida i dati di un servizio
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateService($data) {
    $errors = [];

    // Validazione nome
    if (empty($data['name'])) {
        $errors['name'] = 'Il nome del servizio è obbligatorio.';
    } elseif (strlen($data['name']) > 100) {
        $errors['name'] = 'Il nome del servizio non può superare i 100 caratteri.';
    }

    // Validazione prezzo
    if (empty($data['price'])) {
        $errors['price'] = 'Il prezzo è obbligatorio.';
    } elseif (!is_numeric($data['price']) || $data['price'] <= 0) {
        $errors['price'] = 'Il prezzo deve essere un numero positivo.';
    }

    // Validazione durata
    if (empty($data['duration'])) {
        $errors['duration'] = 'La durata è obbligatoria.';
    } elseif (!is_numeric($data['duration']) || $data['duration'] <= 0) {
        $errors['duration'] = 'La durata deve essere un numero positivo di minuti.';
    }

    return $errors;
}

/**
 * Valida i dati di un operatore
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateStaff($data) {
    $errors = [];

    // Validazione nome
    if (empty($data['name'])) {
        $errors['name'] = 'Il nome dell\'operatore è obbligatorio.';
    } elseif (strlen($data['name']) > 100) {
        $errors['name'] = 'Il nome dell\'operatore non può superare i 100 caratteri.';
    }

    // Validazione qualifica
    if (empty($data['title'])) {
        $errors['title'] = 'La qualifica è obbligatoria.';
    } elseif (strlen($data['title']) > 100) {
        $errors['title'] = 'La qualifica non può superare i 100 caratteri.';
    }

    return $errors;
}

/**
 * Valida i dati di un orario di disponibilità
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateAvailability($data) {
    $errors = [];

    // Validazione giorno della settimana
    if (!isset($data['day_of_week']) || !is_numeric($data['day_of_week']) || $data['day_of_week'] < 0 || $data['day_of_week'] > 6) {
        $errors['day_of_week'] = 'Il giorno della settimana non è valido.';
    }

    // Validazione ora di inizio
    if (empty($data['start_time'])) {
        $errors['start_time'] = 'L\'ora di inizio è obbligatoria.';
    } elseif (!isValidTime($data['start_time'])) {
        $errors['start_time'] = 'L\'ora di inizio non è valida (formato: HH:MM).';
    }

    // Validazione ora di fine
    if (empty($data['end_time'])) {
        $errors['end_time'] = 'L\'ora di fine è obbligatoria.';
    } elseif (!isValidTime($data['end_time'])) {
        $errors['end_time'] = 'L\'ora di fine non è valida (formato: HH:MM).';
    } elseif ($data['start_time'] >= $data['end_time']) {
        $errors['end_time'] = 'L\'ora di fine deve essere successiva all\'ora di inizio.';
    }

    return $errors;
}

/**
 * Valida i dati di un appuntamento
 *
 * @param array $data Dati da validare
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateAppointment($data) {
    $errors = [];

    // Validazione data
    if (empty($data['appointment_date'])) {
        $errors['appointment_date'] = 'La data è obbligatoria.';
    } elseif (!isValidDate($data['appointment_date'])) {
        $errors['appointment_date'] = 'La data non è valida (formato: YYYY-MM-DD).';
    }

    // Validazione ora di inizio
    if (empty($data['start_time'])) {
        $errors['start_time'] = 'L\'ora di inizio è obbligatoria.';
    } elseif (!isValidTime($data['start_time'])) {
        $errors['start_time'] = 'L\'ora di inizio non è valida (formato: HH:MM).';
    }

    // Validazione servizi
    if (empty($data['services']) || !is_array($data['services']) || count($data['services']) === 0) {
        $errors['services'] = 'Devi selezionare almeno un servizio.';
    }

    // Validazione operatore
    if (empty($data['staff_id'])) {
        $errors['staff_id'] = 'L\'operatore è obbligatorio.';
    }

    return $errors;
}

/**
 * Valida un file immagine caricato
 *
 * @param array $file File da validare ($_FILES['nome_campo'])
 * @param int $maxSize Dimensione massima in byte
 * @return array Array di errori (vuoto se non ci sono errori)
 */
function validateImage($file, $maxSize = MAX_PHOTO_SIZE) {
    $errors = [];

    // Verifica se è stato caricato un file
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $errors; // Nessun file, nessun errore (il file potrebbe essere opzionale)
    }

    // Verifica se ci sono errori di caricamento
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Errore durante il caricamento del file.';
        return $errors;
    }

    // Verifica la dimensione
    if ($file['size'] > $maxSize) {
        $errors[] = 'Il file è troppo grande. La dimensione massima è ' . ($maxSize / 1024 / 1024) . ' MB.';
    }

    // Verifica il tipo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = 'Il tipo di file non è valido. I tipi consentiti sono: JPG, PNG e GIF.';
    }

    return $errors;
}

/**
 * Valida una data e verifica che non sia nel passato
 *
 * @param string $date Data da validare (formato YYYY-MM-DD)
 * @return bool True se la data è valida e non è nel passato, false altrimenti
 */
function validateFutureDate($date) {
    if (!isValidDate($date)) {
        return false;
    }

    $today = date('Y-m-d');
    return $date >= $today;
}

/**
 * Valida che un orario sia all'interno dell'orario di apertura di un salone
 *
 * @param int $salonId ID del salone
 * @param string $date Data (formato YYYY-MM-DD)
 * @param string $time Orario (formato HH:MM)
 * @return bool True se l'orario è valido, false altrimenti
 */
function validateTimeWithinBusinessHours($salonId, $date, $time) {
    // Ottieni il giorno della settimana (0 = Domenica, 1 = Lunedì, ...)
    $dayOfWeek = date('w', strtotime($date));

    // Verifica se il salone è aperto in quel giorno
    $businessHours = fetchRow("
        SELECT open_time, close_time, is_closed 
        FROM salon_hours 
        WHERE salon_id = ? AND day_of_week = ?
    ", [$salonId, $dayOfWeek]);

    if (!$businessHours || $businessHours['is_closed']) {
        return false; // Salone chiuso in quel giorno
    }

    // Verifica se è un giorno di chiusura speciale
    $specialClosure = fetchRow("
        SELECT 1 
        FROM salon_closures 
        WHERE salon_id = ? AND date = ?
    ", [$salonId, $date]);

    if ($specialClosure) {
        return false; // Giorno di chiusura speciale
    }

    // Verifica se l'orario è all'interno dell'orario di apertura
    $openTime = $businessHours['open_time'];
    $closeTime = $businessHours['close_time'];

    return $time >= $openTime && $time <= $closeTime;
}
