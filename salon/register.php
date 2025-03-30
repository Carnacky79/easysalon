<?php
/**
 * Pagina di registrazione per i saloni
 */

// Includi il file di configurazione
require_once '../config/config.php';

// Includi i file necessari
require_once '../includes/auth.php';
require_once '../includes/validation.php';

// Se l'utente è già loggato come salone, reindirizzalo alla dashboard
if (isLoggedIn(USER_TYPE_SALON)) {
    redirect(APP_URL . '/salon/index.php');
}

// Inizializza le variabili
$salonData = [
    'name' => '',
    'address' => '',
    'city' => '',
    'postal_code' => '',
    'phone' => '',
    'email' => '',
    'website' => ''
];
$errors = [];

// Gestisci il form di registrazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ottieni e pulisci i dati del form
    $salonData = [
        'name' => sanitize($_POST['name'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'postal_code' => sanitize($_POST['postal_code'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'website' => sanitize($_POST['website'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];

    // Valida i dati
    $errors = validateSalonRegistration($salonData);

    // Gestisci il caricamento del logo
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logoErrors = validateImage($_FILES['logo'], MAX_LOGO_SIZE);

        if (!empty($logoErrors)) {
            $errors['logo'] = $logoErrors[0];
        } else {
            // Genera un nome file unico
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logoFilename = uniqid('logo_') . '.' . $extension;
            $targetPath = LOGO_UPLOAD_DIR . $logoFilename;

            // Crea la directory se non esiste
            if (!file_exists(LOGO_UPLOAD_DIR)) {
                mkdir(LOGO_UPLOAD_DIR, 0755, true);
            }

            // Sposta il file caricato
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                $logoPath = $logoFilename;
            } else {
                $errors['logo'] = 'Errore nel caricamento del logo. Riprova.';
            }
        }
    }

    // Se non ci sono errori, prova a registrare il salone
    if (empty($errors)) {
        // Aggiungi il path del logo (se presente)
        if ($logoPath) {
            $salonData['logo_path'] = $logoPath;
        }

        // Rimuovi la conferma password (non va salvata nel database)
        unset($salonData['confirm_password']);

        // Registra il salone
        $salonId = registerSalon($salonData);

        if ($salonId) {
            // Registrazione riuscita, effettua il login automatico
            loginSalon($salonData['email'], $_POST['password']);

            // Imposta un messaggio di successo
            setFlashMessage('success', 'Registrazione completata con successo! Benvenuto/a su ' . APP_NAME);

            // Reindirizza alla dashboard
            redirect(APP_URL . '/salon/index.php');
        } else {
            // Registrazione fallita
            $errors['register'] = 'Si è verificato un errore durante la registrazione. L\'email potrebbe essere già in uso.';

            // Rimuovi il logo caricato se c'è stato un errore
            if ($logoPath && file_exists(LOGO_UPLOAD_DIR . $logoPath)) {
                unlink(LOGO_UPLOAD_DIR . $logoPath);
            }
        }
    }
}

// Titolo della pagina
$pageTitle = "Registrazione Salone - " . APP_NAME;
$extraCss = "salon";

// Includi l'header
include '../templates/header.php';
?>

    <section class="register-section salon-register">
        <div class="container">
            <div class="form-container">
                <h2>Registra il tuo salone</h2>
                <p>Crea un account per gestire il tuo salone e ricevere prenotazioni online.</p>

                <?php if (isset($errors['register'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errors['register']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" id="register-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Nome del salone *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($salonData['name']); ?>"
                            required
                        >
                        <?php if (isset($errors['name'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="address">Indirizzo *</label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            value="<?php echo htmlspecialchars($salonData['address']); ?>"
                            required
                        >
                        <?php if (isset($errors['address'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['address']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">Città *</label>
                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?php echo htmlspecialchars($salonData['city']); ?>"
                                required
                            >
                            <?php if (isset($errors['city'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['city']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="postal_code">CAP *</label>
                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                value="<?php echo htmlspecialchars($salonData['postal_code']); ?>"
                                required
                                pattern="[0-9]{5}"
                                maxlength="5"
                            >
                            <?php if (isset($errors['postal_code'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['postal_code']); ?></div>
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
                                value="<?php echo htmlspecialchars($salonData['phone']); ?>"
                                required
                            >
                            <?php if (isset($errors['phone'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['phone']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($salonData['email']); ?>"
                                required
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="form-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="website">Sito web</label>
                        <input
                            type="url"
                            id="website"
                            name="website"
                            value="<?php echo htmlspecialchars($salonData['website']); ?>"
                            placeholder="https://www.example.com"
                        >
                        <?php if (isset($errors['website'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['website']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo del salone</label>
                        <input
                            type="file"
                            id="logo"
                            name="logo"
                            accept="image/jpeg,image/png,image/gif"
                        >
                        <div class="form-hint">Formati accettati: JPG, PNG, GIF. Dimensione massima: 2 MB.</div>
                        <?php if (isset($errors['logo'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['logo']); ?></div>
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

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            Accetto i <a href="<?php echo APP_URL; ?>/terms-of-service.php" target="_blank">Termini di Servizio</a> e la <a href="<?php echo APP_URL; ?>/privacy-policy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Registra il tuo salone</button>
                </form>

                <div class="login-link">
                    Hai già un account? <a href="<?php echo APP_URL; ?>/salon/login.php">Accedi</a>
                </div>

                <div class="register-alternate">
                    <span>oppure registrati come</span>
                    <a href="<?php echo APP_URL; ?>/client/register.php" class="btn btn-outline btn-block">Cliente</a>
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
