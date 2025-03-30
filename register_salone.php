<?php
// register_salone.php - Pagina di registrazione per i saloni di bellezza, parrucchieri, estetisti
session_start();
require_once 'config.php';

// Se l'utente è già loggato come salone, redirect alla dashboard
if (isSalone()) {
    header("Location: salone/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        $nome = sanitizeInput($_POST['nome']);
        $indirizzo = sanitizeInput($_POST['indirizzo']);
        $citta = sanitizeInput($_POST['citta']);
        $cap = sanitizeInput($_POST['cap']);
        $telefono = sanitizeInput($_POST['telefono']);
        $email = sanitizeInput($_POST['email']);
        $sito_web = sanitizeInput($_POST['sito_web'] ?? '');
        $tipo_attivita = sanitizeInput($_POST['tipo_attivita']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validazione
        if ($password !== $confirm_password) {
            $error = "Le password non coincidono.";
        } else {
            $conn = connectDB();

            // Verifica se l'email è già in uso
            $stmt = $conn->prepare("SELECT id FROM saloni WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Email già registrata.";
            } else {
                // Gestione caricamento logo
                $logo = NULL;
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                    $upload = uploadImage($_FILES['logo'], 'uploads/saloni/');
                    if ($upload['success']) {
                        $logo = $upload['filename'];
                    } else {
                        $error = $upload['message'];
                    }
                }

                if (empty($error)) {
                    // Hash della password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Inserimento nuovo salone
                    $stmt = $conn->prepare("
                        INSERT INTO saloni (
                            nome, indirizzo, citta, cap, telefono, email, sito_web, 
                            logo, password, tipo_attivita
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param(
                        "ssssssssss",
                        $nome, $indirizzo, $citta, $cap, $telefono,
                        $email, $sito_web, $logo, $hashed_password, $tipo_attivita
                    );

                    if ($stmt->execute()) {
                        $salone_id = $conn->insert_id;

                        // Crea orari di apertura predefiniti (lun-sab, 9-19)
                        $giorni = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato'];
                        $stmt = $conn->prepare("
                            INSERT INTO orari_apertura (salone_id, giorno, aperto, ora_apertura, ora_chiusura) 
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        foreach ($giorni as $giorno) {
                            $aperto = true;
                            $ora_apertura = '09:00:00';
                            $ora_chiusura = '19:00:00';
                            $stmt->bind_param("isiss", $salone_id, $giorno, $aperto, $ora_apertura, $ora_chiusura);
                            $stmt->execute();
                        }

                        // Domenica chiuso
                        $giorno = 'domenica';
                        $aperto = false;
                        $ora_apertura = NULL;
                        $ora_chiusura = NULL;
                        $stmt->bind_param("isiss", $salone_id, $giorno, $aperto, $ora_apertura, $ora_chiusura);
                        $stmt->execute();

                        // Crea categorie di servizi predefinite in base al tipo di attività
                        $categorie = [];

                        if ($tipo_attivita == 'parrucchiere' || $tipo_attivita == 'entrambi') {
                            $categorie[] = ['nome' => 'Taglio', 'descrizione' => 'Servizi di taglio capelli', 'tipo' => 'parrucchiere', 'colore' => '#4a90e2'];
                            $categorie[] = ['nome' => 'Colore', 'descrizione' => 'Servizi di colorazione capelli', 'tipo' => 'parrucchiere', 'colore' => '#e2574a'];
                            $categorie[] = ['nome' => 'Styling', 'descrizione' => 'Servizi di piega e styling', 'tipo' => 'parrucchiere', 'colore' => '#50e3c2'];
                            $categorie[] = ['nome' => 'Trattamenti', 'descrizione' => 'Trattamenti per capelli', 'tipo' => 'parrucchiere', 'colore' => '#f5a623'];
                        }

                        if ($tipo_attivita == 'estetista' || $tipo_attivita == 'entrambi') {
                            $categorie[] = ['nome' => 'Manicure', 'descrizione' => 'Servizi per unghie delle mani', 'tipo' => 'estetica', 'colore' => '#bd10e0'];
                            $categorie[] = ['nome' => 'Pedicure', 'descrizione' => 'Servizi per unghie dei piedi', 'tipo' => 'estetica', 'colore' => '#9013fe'];
                            $categorie[] = ['nome' => 'Viso', 'descrizione' => 'Trattamenti per il viso', 'tipo' => 'estetica', 'colore' => '#50e3c2'];
                            $categorie[] = ['nome' => 'Corpo', 'descrizione' => 'Trattamenti per il corpo', 'tipo' => 'corpo', 'colore' => '#7ed321'];
                            $categorie[] = ['nome' => 'Epilazione', 'descrizione' => 'Servizi di epilazione', 'tipo' => 'corpo', 'colore' => '#f8e71c'];
                            $categorie[] = ['nome' => 'Massaggi', 'descrizione' => 'Servizi di massaggio', 'tipo' => 'corpo', 'colore' => '#4a90e2'];
                        }

                        $stmt = $conn->prepare("
                            INSERT INTO categorie_servizi (salone_id, nome, descrizione, tipo, colore, ordine) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");

                        $ordine = 0;
                        foreach ($categorie as $categoria) {
                            $ordine++;
                            $stmt->bind_param(
                                "issssi",
                                $salone_id, $categoria['nome'], $categoria['descrizione'],
                                $categoria['tipo'], $categoria['colore'], $ordine
                            );
                            $stmt->execute();
                        }

                        $success = "Registrazione completata con successo! Ora puoi accedere.";
                    } else {
                        $error = "Errore durante la registrazione: " . $stmt->error;
                    }
                }
            }

            $stmt->close();
            $conn->close();
        }
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
    <title>Registrazione Salone - BeautyBook</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="auth-form">
        <h2>Registra il tuo Salone di Bellezza</h2>

        <?php if($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
            <div class="form-links">
                <a href="login.php" class="btn-primary">Vai al login</a>
            </div>
        <?php else: ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label for="nome">Nome dell'attività:</label>
                    <input type="text" name="nome" id="nome" required>
                </div>

                <div class="form-group">
                    <label for="tipo_attivita">Tipo di attività:</label>
                    <select name="tipo_attivita" id="tipo_attivita" required>
                        <option value="parrucchiere">Parrucchiere</option>
                        <option value="estetista">Centro Estetico</option>
                        <option value="entrambi">Parrucchiere e Centro Estetico</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="indirizzo">Indirizzo:</label>
                    <input type="text" name="indirizzo" id="indirizzo" required>
                </div>

                <div class="form-group">
                    <label for="citta">Città:</label>
                    <input type="text" name="citta" id="citta" required>
                </div>

                <div class="form-group">
                    <label for="cap">CAP:</label>
                    <input type="text" name="cap" id="cap" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Telefono:</label>
                    <input type="tel" name="telefono" id="telefono" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required>
                </div>

                <div class="form-group">
                    <label for="sito_web">Sito Web (opzionale):</label>
                    <input type="url" name="sito_web" id="sito_web">
                </div>

                <div class="form-group">
                    <label for="logo">Logo (opzionale):</label>
                    <input type="file" name="logo" id="logo" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                    <small>Almeno 8 caratteri, inclusi numeri e caratteri speciali</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Conferma Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-primary">Registra Salone</button>
                </div>

                <div class="form-links">
                    <a href="login.php">Hai già un account? Accedi</a>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<script src="js/jquery.min.js"></script>
<script>
    // Script per la validazione del form
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');

        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/;

            let isValid = true;
            let errorMsg = '';

            // Validazione password
            if (!passwordRegex.test(password)) {
                errorMsg = 'La password deve contenere almeno 8 caratteri, inclusi numeri e caratteri speciali';
                isValid = false;
            }

            // Verifica che le password coincidano
            if (password !== confirmPassword) {
                errorMsg = 'Le password non coincidono';
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = errorMsg;

                // Rimuovi eventuali messaggi di errore precedenti
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }

                // Inserisci il nuovo messaggio di errore
                form.insertBefore(errorDiv, form.firstChild);
            }
        });
    });
</script>
</body>
</html>
