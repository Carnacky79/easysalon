<?php
/**
 * Configurazione generale dell'applicazione
 */

// Configurazione dell'applicazione
define('APP_NAME', 'Salon Booking');
define('APP_URL', 'http://localhost/salon-booking'); // Modificare in produzione

// Timezone
date_default_timezone_set('Europe/Rome');

// Configurazione sessione
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Percorsi di upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LOGO_UPLOAD_DIR', UPLOAD_DIR . 'logos/');
define('STAFF_UPLOAD_DIR', UPLOAD_DIR . 'staff/');
define('USER_UPLOAD_DIR', UPLOAD_DIR . 'users/');

// Dimensioni massime per le immagini
define('MAX_LOGO_SIZE', 2 * 1024 * 1024); // 2MB
define('MAX_PHOTO_SIZE', 1 * 1024 * 1024); // 1MB

// Percorsi URL per le immagini
define('LOGO_URL', APP_URL . '/uploads/logos/');
define('STAFF_URL', APP_URL . '/uploads/staff/');
define('USER_URL', APP_URL . '/uploads/users/');

// Impostazioni per le prenotazioni
define('DEFAULT_CANCELLATION_DEADLINE', 24); // Ore

// Tipi di utenti
define('USER_TYPE_CLIENT', 'client');
define('USER_TYPE_SALON', 'salon');

// Stato delle prenotazioni
define('APPOINTMENT_PENDING', 'pending');
define('APPOINTMENT_CONFIRMED', 'confirmed');
define('APPOINTMENT_COMPLETED', 'completed');
define('APPOINTMENT_CANCELLED', 'cancelled');

// Funzione per reindirizzare
function redirect($url) {
    header("Location: $url");
    exit;
}

// Funzione per visualizzare messaggi flash
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Funzione per ottenere e rimuovere un messaggio flash
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Funzione per pulire l'input
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }

    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Funzione per verificare se l'utente è loggato
function isLoggedIn($userType = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }

    if ($userType !== null && $_SESSION['user_type'] !== $userType) {
        return false;
    }

    return true;
}

// Funzione per ottenere l'ID dell'utente corrente
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Funzione per ottenere il tipo di utente corrente
function getCurrentUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

// Funzione per ottenere l'ID del salone corrente (se l'utente è un salone)
function getCurrentSalonId() {
    if (isLoggedIn(USER_TYPE_SALON)) {
        return $_SESSION['user_id'];
    } elseif (isLoggedIn(USER_TYPE_CLIENT) && isset($_SESSION['default_salon_id'])) {
        return $_SESSION['default_salon_id'];
    }

    return null;
}

// Funzione per verificare se una stringa è una data valida
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Funzione per verificare se una stringa è un'ora valida
function isValidTime($time, $format = 'H:i') {
    $t = DateTime::createFromFormat($format, $time);
    return $t && $t->format($format) === $time;
}

// Funzione per formattare una data
function formatDate($date, $format = 'd/m/Y') {
    $d = new DateTime($date);
    return $d->format($format);
}

// Funzione per formattare un'ora
function formatTime($time, $format = 'H:i') {
    $t = new DateTime($time);
    return $t->format($format);
}

// Funzione per convertire minuti in formato ore:minuti
function minutesToHoursMinutes($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    return sprintf('%02d:%02d', $hours, $mins);
}

// Funzione per aggiungere minuti a un'ora
function addMinutesToTime($time, $minutes) {
    $t = new DateTime($time);
    $t->add(new DateInterval('PT' . $minutes . 'M'));
    return $t->format('H:i');
}

// Funzione per calcolare la durata tra due ore (in minuti)
function getTimeDifference($startTime, $endTime) {
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $diff = $end->diff($start);

    return ($diff->h * 60) + $diff->i;
}

// Include il file di configurazione del database
require_once __DIR__ . '/db.php';
