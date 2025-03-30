<?php
// login.php - Pagina di login con selezione tramite immagini
session_start();
require_once 'config.php';

// Se l'utente è già loggato, redirect alla dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
} elseif (isSalone()) {
    header("Location: salone/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $tipo = sanitizeInput($_POST['tipo']); // 'utente' o 'salone'

        $conn = connectDB();

        if ($tipo === 'utente') {
            $stmt = $conn->prepare("SELECT id, nome, nickname, password, salone_default FROM utenti WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT id, nome, email, password, tipo_attivita FROM saloni WHERE email = ?");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                if ($tipo === 'utente') {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['nickname'];
                    $_SESSION['salone_default'] = $row['salone_default'];
                    header("Location: dashboard.php");
                } else {
                    $_SESSION['salone_id'] = $row['id'];
                    $_SESSION['salone_name'] = $row['nome'];
                    $_SESSION['tipo_attivita'] = $row['tipo_attivita'];
                    header("Location: salone/dashboard.php");
                }
                exit;
            } else {
                $error = "Password non valida.";
            }
        } else {
            $error = "Nessun account trovato con questa email.";
        }

        $stmt->close();
        $conn->close();
    } else {
        $error = "Token CSRF non valido.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BeautyBook</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .user-type-selection {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .type-option {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.6;
        }

        .type-option.selected {
            opacity: 1;
            transform: scale(1.05);
        }

        .type-option img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .type-option.selected img {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(0, 102, 204, 0.3);
        }

        .type-option h3 {
            font-size: 1.2rem;
            margin: 0;
        }

        #login-form {
            display: none; /* Nascondi il form all'inizio */
        }

        .selection-prompt {
            text-align: center;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .form-links {
            margin-top: 1rem;
        }

        .back-button {
            display: inline-block;
            margin-right: 10px;
            color: var(--primary-color);
            cursor: pointer;
        }

        .back-button:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="auth-form" style="display:flex;flex-direction:column;align-items:center;">
        <h2>Accedi a BeautyBook</h2>

        <?php if($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <p class="selection-prompt">Seleziona il tipo di account:</p>

        <div class="user-type-selection">
            <div class="type-option" data-type="utente">
                <img src="img/user-icon.svg" alt="Cliente">
                <h3>Cliente</h3>
            </div>
            <div class="type-option" data-type="salone">
                <img src="img/salon-icon.svg" alt="Salone di Bellezza">
                <h3>Salone di Bellezza</h3>
            </div>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="login-form" style="width:100%; flex-direction:column;align-items:center;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="tipo" id="tipo-input" value="utente">

            <span class="back-button" id="back-to-selection">← Torna alla selezione</span>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-primary">Accedi</button>
            </div>

            <div class="form-links" id="form-links">
                <a href="register.php" id="register-link">Non hai un account? Registrati</a>
                <a href="reset_password.php">Password dimenticata?</a>
            </div>
        </form>
    </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/script.js"></script>
<script>
    // Script per gestire la selezione del tipo di utente
    document.addEventListener('DOMContentLoaded', function() {
        const typeOptions = document.querySelectorAll('.type-option');
        const tipoInput = document.getElementById('tipo-input');
        const loginForm = document.getElementById('login-form');
        const userTypeSelection = document.querySelector('.user-type-selection');
        const selectionPrompt = document.querySelector('.selection-prompt');
        const backButton = document.getElementById('back-to-selection');
        const registerLink = document.getElementById('register-link');

        typeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Rimuovi la classe selected da tutte le opzioni
                typeOptions.forEach(el => el.classList.remove('selected'));

                // Aggiungi la classe selected all'opzione selezionata
                this.classList.add('selected');

                // Nascondi la selezione e mostra il form
                userTypeSelection.style.display = 'none';
                selectionPrompt.style.display = 'none';
                loginForm.style.display = 'flex';

                // Aggiorna il valore dell'input hidden
                const userType = this.getAttribute('data-type');
                tipoInput.value = userType;

                // Aggiorna il link di registrazione in base al tipo
                if (userType === 'salone') {
                    registerLink.href = 'register_salone.php';
                } else {
                    registerLink.href = 'register.php';
                }
            });
        });

        // Gestisci il clic sul pulsante "Torna alla selezione"
        backButton.addEventListener('click', function() {
            loginForm.style.display = 'none';
            userTypeSelection.style.display = 'flex';
            selectionPrompt.style.display = 'flex';
        });
    });
</script>
</body>
</html>
