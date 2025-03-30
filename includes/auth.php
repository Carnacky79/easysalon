<?php
/**
 * Funzioni di autenticazione per l'applicazione Salon Booking
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Registra un nuovo utente cliente
 *
 * @param array $userData Dati dell'utente da registrare
 * @return int|false ID dell'utente registrato o false in caso di errore
 */
function registerClient($userData) {
    // Verifica se l'email è già registrata
    $existingUser = fetchRow("SELECT user_id FROM users WHERE email = ?", [$userData['email']]);

    if ($existingUser) {
        return false; // Email già registrata
    }

    // Hash della password
    $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);

    // Inserimento dell'utente nel database
    $userId = insert('users', $userData);

    return $userId;
}

/**
 * Registra un nuovo salone
 *
 * @param array $salonData Dati del salone da registrare
 * @return int|false ID del salone registrato o false in caso di errore
 */
function registerSalon($salonData) {
    // Verifica se l'email è già registrata
    $existingSalon = fetchRow("SELECT salon_id FROM salons WHERE email = ?", [$salonData['email']]);

    if ($existingSalon) {
        return false; // Email già registrata
    }

    // Inseriamo prima il salone base
    $salonFields = [
        'name' => $salonData['name'],
        'address' => $salonData['address'],
        'city' => $salonData['city'],
        'postal_code' => $salonData['postal_code'],
        'phone' => $salonData['phone'],
        'email' => $salonData['email'],
        'website' => isset($salonData['website']) ? $salonData['website'] : null,
        'logo_path' => isset($salonData['logo_path']) ? $salonData['logo_path'] : null
    ];

    // Inserimento del salone nel database
    $salonId = insert('salons', $salonFields);

    if ($salonId) {
        // Inserimento delle impostazioni predefinite
        $settingsData = [
            'salon_id' => $salonId,
            'auto_approve_appointments' => 0,
            'notification_email' => $salonData['email'],
            'notification_phone' => $salonData['phone'],
            'cancellation_deadline' => DEFAULT_CANCELLATION_DEADLINE,
            'password' => password_hash($salonData['password'], PASSWORD_DEFAULT)
        ];

        insert('salon_settings', $settingsData);

        // Inserimento degli orari predefiniti (lun-sab 9-18, dom chiuso)
        $defaultHours = [
            ['salon_id' => $salonId, 'day_of_week' => 0, 'open_time' => '00:00', 'close_time' => '00:00', 'is_closed' => 1], // Domenica chiuso
            ['salon_id' => $salonId, 'day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => 0], // Lunedì
            ['salon_id' => $salonId, 'day_of_week' => 2, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => 0], // Martedì
            ['salon_id' => $salonId, 'day_of_week' => 3, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => 0], // Mercoledì
            ['salon_id' => $salonId, 'day_of_week' => 4, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => 0], // Giovedì
            ['salon_id' => $salonId, 'day_of_week' => 5, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => 0], // Venerdì
            ['salon_id' => $salonId, 'day_of_week' => 6, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => 0]  // Sabato
        ];

        foreach ($defaultHours as $hours) {
            insert('salon_hours', $hours);
        }
    }

    return $salonId;
}

/**
 * Effettua il login di un cliente
 *
 * @param string $email Email del cliente
 * @param string $password Password del cliente
 * @return array|false Dati dell'utente o false in caso di errore
 */
function loginClient($email, $password) {
    // Ottieni i dati dell'utente
    $user = fetchRow("SELECT user_id, first_name, nickname, password, default_salon_id, city FROM users WHERE email = ?", [$email]);

    if (!$user || !password_verify($password, $user['password'])) {
        return false; // Email o password non validi
    }

    // Se l'utente non ha un salone predefinito, assegnagli uno della sua città
    if (empty($user['default_salon_id']) && !empty($user['city'])) {
        $salon = fetchRow("SELECT salon_id FROM salons WHERE city = ? LIMIT 1", [$user['city']]);
        if ($salon) {
            update('users', ['default_salon_id' => $salon['salon_id']], 'user_id = ?', [$user['user_id']]);
            $user['default_salon_id'] = $salon['salon_id'];
        }
    }

    // Imposta i dati di sessione
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = USER_TYPE_CLIENT;
    $_SESSION['user_name'] = $user['first_name'] ?: $user['nickname'];
    $_SESSION['default_salon_id'] = $user['default_salon_id'];

    return $user;
}

/**
 * Effettua il login di un salone
 *
 * @param string $email Email del salone
 * @param string $password Password del salone
 * @return array|false Dati del salone o false in caso di errore
 */
function loginSalon($email, $password) {
    // Ottieni i dati del salone
    $salon = fetchRow("
        SELECT s.salon_id, s.name, s.email, ss.password 
        FROM salons s
        JOIN salon_settings ss ON s.salon_id = ss.salon_id
        WHERE s.email = ?
    ", [$email]);

    if (!$salon || !password_verify($password, $salon['password'])) {
        return false; // Email o password non validi
    }

    // Imposta i dati di sessione
    $_SESSION['user_id'] = $salon['salon_id'];
    $_SESSION['user_type'] = USER_TYPE_SALON;
    $_SESSION['user_name'] = $salon['name'];

    return $salon;
}

/**
 * Effettua il logout dell'utente
 */
function logout() {
    // Distruggi la sessione
    session_unset();
    session_destroy();

    // Reimposta i cookie di sessione
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
}

/**
 * Verifica se l'utente è autenticato come cliente
 * Reindirizza alla pagina di login se non è autenticato
 */
function requireClientLogin() {
    if (!isLoggedIn(USER_TYPE_CLIENT)) {
        setFlashMessage('error', 'Devi effettuare il login per accedere a questa pagina.');
        redirect(APP_URL . '/client/login.php');
    }
}

/**
 * Verifica se l'utente è autenticato come salone
 * Reindirizza alla pagina di login se non è autenticato
 */
function requireSalonLogin() {
    if (!isLoggedIn(USER_TYPE_SALON)) {
        setFlashMessage('error', 'Devi effettuare il login per accedere a questa pagina.');
        redirect(APP_URL . '/salon/login.php');
    }
}

/**
 * Ottiene i dati del cliente corrente
 *
 * @return array|null Dati del cliente o null se non è autenticato
 */
function getCurrentClient() {
    if (!isLoggedIn(USER_TYPE_CLIENT)) {
        return null;
    }

    return fetchRow("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
}

/**
 * Ottiene i dati del salone corrente
 *
 * @return array|null Dati del salone o null se non è autenticato
 */
function getCurrentSalon() {
    if (!isLoggedIn(USER_TYPE_SALON)) {
        return null;
    }

    return fetchRow("
        SELECT s.*, ss.auto_approve_appointments, ss.notification_email, ss.notification_phone, ss.cancellation_deadline
        FROM salons s
        JOIN salon_settings ss ON s.salon_id = ss.salon_id
        WHERE s.salon_id = ?
    ", [$_SESSION['user_id']]);
}

/**
 * Aggiorna la password di un cliente
 *
 * @param int $userId ID del cliente
 * @param string $currentPassword Password attuale
 * @param string $newPassword Nuova password
 * @return bool True se l'aggiornamento è avvenuto con successo, false altrimenti
 */
function updateClientPassword($userId, $currentPassword, $newPassword) {
    // Ottieni la password attuale
    $user = fetchRow("SELECT password FROM users WHERE user_id = ?", [$userId]);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return false; // Password attuale non valida
    }

    // Hash della nuova password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Aggiorna la password
    return update('users', ['password' => $hashedPassword], 'user_id = ?', [$userId]) > 0;
}

/**
 * Aggiorna la password di un salone
 *
 * @param int $salonId ID del salone
 * @param string $currentPassword Password attuale
 * @param string $newPassword Nuova password
 * @return bool True se l'aggiornamento è avvenuto con successo, false altrimenti
 */
function updateSalonPassword($salonId, $currentPassword, $newPassword) {
    // Ottieni la password attuale
    $salon = fetchRow("SELECT password FROM salon_settings WHERE salon_id = ?", [$salonId]);

    if (!$salon || !password_verify($currentPassword, $salon['password'])) {
        return false; // Password attuale non valida
    }

    // Hash della nuova password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Aggiorna la password
    return update('salon_settings', ['password' => $hashedPassword], 'salon_id = ?', [$salonId]) > 0;
}

/**
 * Recupera la password di un cliente
 *
 * @param string $email Email del cliente
 * @return bool True se l'email di reset è stata inviata, false altrimenti
 */
function resetClientPassword($email) {
    // Verifica se l'email esiste
    $user = fetchRow("SELECT user_id, first_name, nickname FROM users WHERE email = ?", [$email]);

    if (!$user) {
        return false; // Email non trovata
    }

    // Genera un token di reset
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Salva il token nel database
    $resetData = [
        'user_id' => $user['user_id'],
        'token' => $token,
        'expiry' => $expiry
    ];

    insert('password_resets', $resetData);

    // Invia l'email di reset (implementazione in notifications.php)
    $userName = $user['first_name'] ?: $user['nickname'];
    $resetUrl = APP_URL . '/client/reset-password.php?token=' . $token;

    return sendPasswordResetEmail($email, $userName, $resetUrl);
}

/**
 * Imposta una nuova password per un cliente utilizzando un token di reset
 *
 * @param string $token Token di reset
 * @param string $newPassword Nuova password
 * @return bool True se la password è stata aggiornata, false altrimenti
 */
function setNewPasswordWithToken($token, $newPassword) {
    // Verifica se il token è valido e non è scaduto
    $reset = fetchRow("
        SELECT pr.user_id 
        FROM password_resets pr
        WHERE pr.token = ? AND pr.expiry > NOW()
    ", [$token]);

    if (!$reset) {
        return false; // Token non valido o scaduto
    }

    // Hash della nuova password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Aggiorna la password
    $updated = update('users', ['password' => $hashedPassword], 'user_id = ?', [$reset['user_id']]) > 0;

    if ($updated) {
        // Rimuovi il token di reset
        delete('password_resets', 'token = ?', [$token]);
    }

    return $updated;
}

/**
 * Ottiene un salone per ID
 *
 * @param int $salonId ID del salone
 * @return array|null Dati del salone o null se non esiste
 */
function getSalonById($salonId) {
    return fetchRow("SELECT * FROM salons WHERE salon_id = ?", [$salonId]);
}

/**
 * Ottiene i saloni per città
 *
 * @param string $city Nome della città
 * @return array Array di saloni nella città specificata
 */
function getSalonsByCity($city) {
    return fetchAll("SELECT * FROM salons WHERE city = ? ORDER BY name", [$city]);
}

/**
 * Imposta il salone predefinito per un cliente
 *
 * @param int $userId ID del cliente
 * @param int $salonId ID del salone
 * @return bool True se l'operazione è avvenuta con successo, false altrimenti
 */
function setDefaultSalon($userId, $salonId) {
    // Verifica che il salone esista
    $salon = getSalonById($salonId);
    if (!$salon) {
        return false;
    }

    // Aggiorna il salone predefinito dell'utente
    $updated = update('users', ['default_salon_id' => $salonId], 'user_id = ?', [$userId]) > 0;

    if ($updated) {
        $_SESSION['default_salon_id'] = $salonId;
    }

    return $updated;
}

/**
 * Aggiorna il profilo di un cliente
 *
 * @param int $userId ID del cliente
 * @param array $userData Dati aggiornati dell'utente
 * @return bool True se l'aggiornamento è avvenuto con successo, false altrimenti
 */
function updateClientProfile($userId, $userData) {
    // Se viene fornita una nuova email, verifica che non sia già in uso
    if (isset($userData['email'])) {
        $existingUser = fetchRow("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$userData['email'], $userId]);

        if ($existingUser) {
            return false; // Email già in uso da un altro utente
        }
    }

    // Aggiorna i dati dell'utente
    return update('users', $userData, 'user_id = ?', [$userId]) > 0;
}

/**
 * Aggiorna il profilo di un salone
 *
 * @param int $salonId ID del salone
 * @param array $salonData Dati aggiornati del salone
 * @return bool True se l'aggiornamento è avvenuto con successo, false altrimenti
 */
function updateSalonProfile($salonId, $salonData) {
    // Se viene fornita una nuova email, verifica che non sia già in uso
    if (isset($salonData['email'])) {
        $existingSalon = fetchRow("SELECT salon_id FROM salons WHERE email = ? AND salon_id != ?", [$salonData['email'], $salonId]);

        if ($existingSalon) {
            return false; // Email già in uso da un altro salone
        }
    }

    // Aggiorna i dati del salone
    return update('salons', $salonData, 'salon_id = ?', [$salonId]) > 0;
}
