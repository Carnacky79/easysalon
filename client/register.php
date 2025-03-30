<?php
/**
 * Pagina di registrazione per i clienti
 */

// Includi il file di configurazione
require_once '../config/config.php';

// Includi i file necessari
require_once '../includes/auth.php';
require_once '../includes/validation.php';

// Se l'utente è già loggato, reindirizzalo alla dashboard
if (isLoggedIn(USER_TYPE_CLIENT)) {
    redirect(APP_URL . '/client/index.php');
}

// Inizializza le variabili
$userData = [
    'first_name' => '',
    'last_name' => '',
    'nickname' => '',
    'email' => '',
    'phone' => '',
    'city' => ''
];
$errors = [];

// Gestisci il form di registrazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ottieni e pulisci i dati del form
    $userData = [
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'nickname' => sanitize($_POST['nickname'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'phone' => sanitize($_POST['phone'] ?? ''),
        'city' => sanitize($_POST['city'] ?? '')
    ];

    // Valida i dati
    $errors = validateClientRegistration($userData);

    // Se non ci sono errori, prova a registrare l'utente
    if (empty($errors)) {
        // Rimuovi la conferma password (non va salvata nel database)
        unset($userData['confirm_password']);

        // Registra l'utente
        $userId = registerClient($userData);

        if ($userId) {
            // Registrazione riuscita, effettua il login automatico
            loginClient($userData['email'], $_POST['password']);

            // Imposta un messaggio di successo
            setFlashMessage('success', 'Registrazione completata con successo! Benvenuto/a su ' . APP_NAME);

            // Reindirizza alla dashboard
            redirect(APP_URL . '/client/index.php');
        } else {
            // Registrazione fallita
            $errors['register'] = 'Si è verificato un errore durante la registrazione. L\'email potrebbe essere già in uso.';
        }
    }
}

// Ottieni l'elenco delle città con saloni (per il form di registrazione)
$cities = fetchAll("SELECT DISTINCT city FROM salons ORDER BY city");

// Titolo della pagina
$pageTitle = "Registrazione Cliente - " . APP_NAME;
$extraCss = "client";

// Includi l'header
include '../templates/header.php';
?>

    <section class="register-section">
        <div class="container">
            <div class="form-container">
                <h2>Registrati come cliente</h2>
                <p>Crea un account per prenotare i tuoi appuntamenti preferiti.</p>

                <?php if (isset($errors['register'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errors['register']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" id="register-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Nome *</label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="<?php echo htmlspecialchars($userData['first_name']); ?>"
                                required
                            >
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Cognome</label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="<?php echo htmlspecialchars($userData['last_name']); ?>"
                            >
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nickname">Nickname *</label>
                        <input
                            type="text"
                            id="nickname"
                            name="nickname"
                            value="<?php echo htmlspecialchars($userData['nickname']); ?>"
                            required
                        >
                        <?php if (isset($errors['nickname'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['nickname']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($userData['email']); ?>"
                            required
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                            >
                            <div class="form-hint">Almeno 8 caratteri.</div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Conferma Password *</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                required
                            >
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Telefono *</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($userData['phone']); ?>"
                                required
                            >
                            <?php if (isset($errors['phone'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['phone']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="city">Città *</label>
                            <select id="city" name="city" required>
                                <option value="">Seleziona la tua città</option>
                                <?php foreach ($cities as $city): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($city['city']); ?>"
                                        <?php echo ($userData['city'] === $city['city']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($city['city']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['city'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['city']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            Accetto i <a href="<?php echo APP_URL; ?>/terms-of-service.php" target="_blank">Termini di Servizio</a> e la <a href="<?php echo APP_URL; ?>/privacy-policy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Registrati</button>
                </form>

                <div class="login-link">
                    Hai già un account? <a href="<?php echo APP_URL; ?>/client/login.php">Accedi</a>
                </div>

                <div class="register-alternate">
                    <span>oppure registrati come</span>
                    <a href="<?php echo APP_URL; ?>/salon/register.php" class="btn btn-outline btn-block">Salone</a>
                </div>
            </div>
        </div>
    </section>

<?php
// JavaScript per validazione lato client
$extraJs = "register";

// Includi il footer
include '../templates/footer.php';
?>
