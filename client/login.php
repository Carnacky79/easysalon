<?php
/**
 * Pagina di login per i clienti
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
$email = '';
$errors = [];

// Gestisci il form di login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ottieni e pulisci i dati del form
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Valida i dati
    $errors = validateLogin([
        'email' => $email,
        'password' => $password
    ]);

    // Se non ci sono errori, prova a effettuare il login
    if (empty($errors)) {
        $user = loginClient($email, $password);

        if ($user) {
            // Login riuscito, reindirizza alla dashboard
            redirect(APP_URL . '/client/index.php');
        } else {
            // Login fallito
            $errors['login'] = 'Email o password non validi.';
        }
    }
}

// Titolo della pagina
$pageTitle = "Accedi - " . APP_NAME;
$extraCss = "client";

// Includi l'header
include '../templates/header.php';
?>

    <section class="login-section">
        <div class="container">
            <div class="form-container">
                <h2>Accedi al tuo account</h2>

                <?php if (isset($errors['login'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errors['login']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" id="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                        >
                        <?php if (isset($errors['password'])): ?>
                            <div class="form-error"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Ricordami</label>
                        </div>
                        <a href="<?php echo APP_URL; ?>/client/forgot-password.php" class="forgot-password">Password dimenticata?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Accedi</button>
                </form>

                <div class="register-link">
                    Non hai ancora un account? <a href="<?php echo APP_URL; ?>/client/register.php">Registrati</a>
                </div>

                <div class="login-alternate">
                    <span>oppure accedi come</span>
                    <a href="<?php echo APP_URL; ?>/salon/login.php" class="btn btn-outline btn-block">Salone</a>
                </div>
            </div>
        </div>
    </section>

<?php
// JavaScript per validazione lato client
$extraJs = "login";

// Includi il footer
include '../templates/footer.php';
?>
