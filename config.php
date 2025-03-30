<?php
// config.php - File di configurazione per BeautyBook
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'beauty_booking');
define('SITE_URL', 'http://beautybook.local.com'); // Modificare con l'URL del tuo sito

// Connessione al database
function connectDB() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    return $conn;
}

// Funzioni utility

// Funzione per sanitizzare gli input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Funzione per generare un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Funzione per verificare un token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Funzione per verificare se l'utente è loggato
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Funzione per verificare se l'utente è un salone
function isSalone() {
    return isset($_SESSION['salone_id']);
}

// Funzione per caricare immagini
function uploadImage($file, $directory = 'uploads/') {
    // Crea la directory se non esiste
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    $target_file = $directory . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $newFileName = uniqid() . '.' . $imageFileType;
    $target_file = $directory . $newFileName;

    // Verifica se è un'immagine
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "Il file non è un'immagine."];
    }

    // Verifica la dimensione (limite a 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "Il file è troppo grande."];
    }

    // Consenti solo alcuni formati
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return ["success" => false, "message" => "Sono permessi solo file JPG, JPEG, PNG e GIF."];
    }

    // Carica il file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $newFileName];
    } else {
        return ["success" => false, "message" => "Si è verificato un errore durante il caricamento."];
    }
}

// Funzione per inviare email
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: <noreply@beautybook.com>' . "\r\n";

    return mail($to, $subject, $message, $headers);
}

// Funzione per controllare se un orario è disponibile
/**
 * Questa funzione verifica se uno slot orario è disponibile per un appuntamento.
 * Verifica tutte le fasce orarie di apertura del salone e la disponibilità dell'operatore.
 *
 * @param int $salone_id ID del salone
 * @param int $operatore_id ID dell'operatore
 * @param int|null $postazione_id ID della postazione (opzionale)
 * @param string $data Data dell'appuntamento (formato Y-m-d)
 * @param string $ora_inizio Ora di inizio dell'appuntamento (formato H:i:s)
 * @param string $ora_fine Ora di fine dell'appuntamento (formato H:i:s)
 * @param int|null $appuntamento_id ID dell'appuntamento da escludere (in caso di modifica)
 * @return bool True se lo slot è disponibile, false altrimenti
 */
function isTimeSlotAvailable($salone_id, $operatore_id, $data, $ora_inizio, $ora_fine, $postazione_id = null, $appuntamento_id = null) {
    $salone_id = intval($salone_id);
    $operatore_id = intval($operatore_id);
    $postazione_id = $postazione_id ? intval($postazione_id) : null;

    // Assicurati che data e orari siano nel formato corretto
    $data = date('Y-m-d', strtotime($data));
    $ora_inizio = date('H:i:s', strtotime($ora_inizio));
    $ora_fine = date('H:i:s', strtotime($ora_fine));

    $conn = connectDB();

    // Debug - rimuovere o commentare in produzione
    // error_log("isTimeSlotAvailable: salone=$salone_id, operatore=$operatore_id, data=$data, inizio=$ora_inizio, fine=$ora_fine, postazione=$postazione_id");

    $giorno_settimana = strtolower(date('l', strtotime($data)));
    $giorni_it = [
        'monday' => 'lunedi',
        'tuesday' => 'martedi',
        'wednesday' => 'mercoledi',
        'thursday' => 'giovedi',
        'friday' => 'venerdi',
        'saturday' => 'sabato',
        'sunday' => 'domenica'
    ];
    $giorno = $giorni_it[$giorno_settimana];

    // Verifica che il giorno sia aperto
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM orari_apertura 
        WHERE salone_id = ? AND giorno = ? AND aperto = 1
    ");
    $stmt->bind_param("is", $salone_id, $giorno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row['count'] == 0) {
        error_log("Il salone è chiuso in questo giorno");
        $conn->close();
        return false;
    }

    // Verifica se è un giorno di chiusura speciale
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM giorni_chiusura 
        WHERE salone_id = ? AND data_chiusura = ?
    ");
    $stmt->bind_param("is", $salone_id, $data);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row['count'] > 0) {
        error_log("È un giorno di chiusura speciale");
        $conn->close();
        return false;
    }

    // Verifica che lo slot rientri in almeno una fascia oraria
    // Converte gli orari in timestamp per un confronto corretto
    $inizio_timestamp = strtotime("1970-01-01 " . $ora_inizio);
    $fine_timestamp = strtotime("1970-01-01 " . $ora_fine);

    $slot_in_fascia = false;
    $stmt = $conn->prepare("
        SELECT ora_apertura, ora_chiusura FROM orari_apertura 
        WHERE salone_id = ? AND giorno = ? AND aperto = 1
    ");
    $stmt->bind_param("is", $salone_id, $giorno);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $apertura_timestamp = strtotime("1970-01-01 " . $row['ora_apertura']);
        $chiusura_timestamp = strtotime("1970-01-01 " . $row['ora_chiusura']);
        if ($inizio_timestamp >= $apertura_timestamp && $fine_timestamp <= $chiusura_timestamp) {
            $slot_in_fascia = true;
            break;
        }
    }

    if (!$slot_in_fascia) {
        error_log("Lo slot non rientra in nessuna fascia oraria");
        $conn->close();
        return false;
    }

    // Verifica la disponibilità dell'operatore
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM orari_operatori 
        WHERE operatore_id = ? AND giorno = ? AND 
              ora_inizio <= ? AND ora_fine >= ?
    ");
    $stmt->bind_param("isss", $operatore_id, $giorno, $ora_inizio, $ora_fine);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        error_log("L'operatore non è disponibile in questo orario");
        $conn->close();
        return false;
    }

    // Verifica la disponibilità della postazione (se specificata)
    if ($postazione_id) {
        $query = "
            SELECT COUNT(*) as count FROM appuntamenti 
            WHERE salone_id = ? AND postazione_id = ? AND data_appuntamento = ? 
            AND stato IN ('in attesa', 'confermato')
            AND (
                (? > ora_inizio AND ? < ora_fine) OR
                (? > ora_inizio AND ? < ora_fine) OR
                (? <= ora_inizio AND ? >= ora_fine)
            )
        ";

        $params = [
            $salone_id,
            $postazione_id,
            $data,
            $ora_inizio,
            $ora_inizio,
            $ora_fine,
            $ora_fine,
            $ora_inizio,
            $ora_fine
        ];
        $types = "iisssssss";

        if ($appuntamento_id) {
            $query .= " AND id != ?";
            $params[] = $appuntamento_id;
            $types .= "i";
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            error_log("La postazione è già occupata in questo orario");
            $conn->close();
            return false;
        }
    }

    // Verifica sovrapposizioni con altri appuntamenti dell'operatore
    $query = "
        SELECT COUNT(*) as count FROM appuntamenti 
        WHERE salone_id = ? AND operatore_id = ? AND data_appuntamento = ? 
        AND stato IN ('in attesa', 'confermato')
        AND (
            (? > ora_inizio AND ? < ora_fine) OR
            (? > ora_inizio AND ? < ora_fine) OR
            (? <= ora_inizio AND ? >= ora_fine)
        )
    ";

    $params = [
        $salone_id,
        $operatore_id,
        $data,
        $ora_inizio,
        $ora_inizio,
        $ora_fine,
        $ora_fine,
        $ora_inizio,
        $ora_fine
    ];
    $types = "iisssssss";

    if ($appuntamento_id) {
        $query .= " AND id != ?";
        $params[] = $appuntamento_id;
        $types .= "i";
    }

    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Errore SQL in isTimeSlotAvailable: " . $e->getMessage());
        $conn->close();
        return false;
    }

    $is_available = ($row['count'] == 0);

    if (!$is_available) {
        error_log("C'è una sovrapposizione con un altro appuntamento");
    }

    $conn->close();
    return $is_available;
}

// Funzione per sostituire i segnaposto nei messaggi
function formatMessage($template, $appuntamento) {
    $replacements = [
        '{nome}' => $appuntamento['utente_nome'] ?? 'Cliente',
        '{servizio}' => $appuntamento['servizio_nome'] ?? 'servizio',
        '{data}' => isset($appuntamento['data_appuntamento']) ? date('d/m/Y', strtotime($appuntamento['data_appuntamento'])) : 'data',
        '{ora}' => isset($appuntamento['ora_inizio']) ? date('H:i', strtotime($appuntamento['ora_inizio'])) : 'ora',
        '{operatore}' => $appuntamento['operatore_nome'] ?? 'operatore',
        '{salone}' => $appuntamento['salone_nome'] ?? 'salone',
        '{totale}' => isset($appuntamento['prezzo_totale']) ? number_format($appuntamento['prezzo_totale'], 2) . '€' : '0.00€'
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

// Funzione per generare il link WhatsApp
function generateWhatsAppLink($telefono, $messaggio) {
    // Rimuovi spazi e caratteri non numerici dal telefono
    $telefono = preg_replace('/[^0-9]/', '', $telefono);

    // Aggiungi prefisso internazionale se non presente
    if (substr($telefono, 0, 1) !== '+' && substr($telefono, 0, 2) !== '00') {
        $telefono = '39' . $telefono; // Prefisso Italia
    }

    // Codifica il messaggio per URL
    $messaggio = urlencode($messaggio);

    return "https://wa.me/{$telefono}?text={$messaggio}";
}

// Funzione per calcolare il totale dei servizi di un appuntamento
function calcolaTotaleServizi($conn, $appuntamento_id) {
    $stmt = $conn->prepare("
        SELECT SUM(prezzo) as totale 
        FROM appuntamenti_servizi
        WHERE appuntamento_id = ?
    ");
    $stmt->bind_param("i", $appuntamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['totale'] ?: 0;
}

// Funzione per ottenere tutti i servizi di un appuntamento
function getServiziAppuntamento($conn, $appuntamento_id) {
    $stmt = $conn->prepare("
        SELECT as.*, s.nome, s.descrizione, s.durata_minuti, s.tempo_posa_minuti
        FROM appuntamenti_servizi as
        JOIN servizi s ON as.servizio_id = s.id
        WHERE as.appuntamento_id = ?
        ORDER BY as.sequenza
    ");
    $stmt->bind_param("i", $appuntamento_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $servizi = [];
    while ($row = $result->fetch_assoc()) {
        $servizi[] = $row;
    }

    return $servizi;
}

// Funzione per controllare se un utente ha una scheda cliente
function hasSchedaCliente($conn, $utente_id, $salone_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM schede_cliente
        WHERE utente_id = ? AND salone_id = ?
    ");
    $stmt->bind_param("ii", $utente_id, $salone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['count'] > 0;
}

// Funzione per ottenere la scheda cliente
function getSchedaCliente($conn, $utente_id, $salone_id) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM schede_cliente
        WHERE utente_id = ? AND salone_id = ?
    ");
    $stmt->bind_param("ii", $utente_id, $salone_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Funzione per aggiungere punti alla fidelity card di un cliente
function aggiungiPunti($conn, $utente_id, $salone_id, $punti, $descrizione, $riferimento_id = null) {
    // Verifica se l'utente ha una fidelity card
    $stmt = $conn->prepare("
        SELECT id
        FROM fidelity_card
        WHERE utente_id = ? AND salone_id = ? AND attiva = 1
    ");
    $stmt->bind_param("ii", $utente_id, $salone_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Crea una nuova fidelity card
        $codice = uniqid('FID');
        $stmt = $conn->prepare("
            INSERT INTO fidelity_card (utente_id, salone_id, codice, punti_accumulati)
            VALUES (?, ?, ?, 0)
        ");
        $stmt->bind_param("iis", $utente_id, $salone_id, $codice);
        $stmt->execute();
        $fidelity_id = $conn->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $fidelity_id = $row['id'];
    }

    // Aggiorna i punti della fidelity card
    $stmt = $conn->prepare("
        UPDATE fidelity_card 
        SET punti_accumulati = punti_accumulati + ?
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $punti, $fidelity_id);
    $stmt->execute();

    // Registra il movimento
    $tipo = $punti >= 0 ? 'accredito' : 'addebito';
    $punti_abs = abs($punti);

    $stmt = $conn->prepare("
        INSERT INTO movimenti_punti (fidelity_id, punti, tipo, descrizione, riferimento_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissi", $fidelity_id, $punti_abs, $tipo, $descrizione, $riferimento_id);

    return $stmt->execute();
}

// Funzione per verificare l'applicabilità di una promozione
function verificaPromozione($conn, $promozione_id, $servizi_ids = [], $utente_id = null) {
    // Ottieni i dettagli della promozione
    $stmt = $conn->prepare("
        SELECT * FROM promozioni
        WHERE id = ? AND attiva = 1
        AND data_inizio <= CURDATE() AND data_fine >= CURDATE()
    ");
    $stmt->bind_param("i", $promozione_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ['applicabile' => false, 'messaggio' => 'Promozione non valida o scaduta'];
    }

    $promozione = $result->fetch_assoc();

    // Verifica servizi inclusi (se specificati)
    if (!empty($promozione['servizi_inclusi']) && !empty($servizi_ids)) {
        $servizi_inclusi = json_decode($promozione['servizi_inclusi'], true);
        $servizi_validi = false;

        foreach ($servizi_ids as $servizio_id) {
            if (in_array($servizio_id, $servizi_inclusi)) {
                $servizi_validi = true;
                break;
            }
        }

        if (!$servizi_validi) {
            return ['applicabile' => false, 'messaggio' => 'Promozione non applicabile ai servizi selezionati'];
        }
    }

    // Verifica categorie incluse (se specificate)
    if (!empty($promozione['categorie_incluse']) && !empty($servizi_ids)) {
        $categorie_incluse = json_decode($promozione['categorie_incluse'], true);

        // Ottieni le categorie dei servizi selezionati
        $query = "SELECT DISTINCT categoria_id FROM servizi WHERE id IN (" . implode(',', array_fill(0, count($servizi_ids), '?')) . ")";
        $stmt = $conn->prepare($query);

        $types = str_repeat('i', count($servizi_ids));
        $stmt->bind_param($types, ...$servizi_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $categorie_servizi = [];
        while ($row = $result->fetch_assoc()) {
            $categorie_servizi[] = $row['categoria_id'];
        }

        $categorie_valide = false;
        foreach ($categorie_servizi as $categoria_id) {
            if (in_array($categoria_id, $categorie_incluse)) {
                $categorie_valide = true;
                break;
            }
        }

        if (!$categorie_valide) {
            return ['applicabile' => false, 'messaggio' => 'Promozione non applicabile alle categorie dei servizi selezionati'];
        }
    }

    return [
        'applicabile' => true,
        'promozione' => $promozione,
        'sconto_percentuale' => $promozione['sconto_percentuale'] ?? 0,
        'sconto_fisso' => $promozione['sconto_fisso'] ?? 0
    ];
}

// Funzione per applicare uno sconto basato su una promozione
function applicaSconto($prezzo, $promozione) {
    $prezzo_scontato = $prezzo;

    if (!empty($promozione['sconto_percentuale'])) {
        $sconto = $prezzo * ($promozione['sconto_percentuale'] / 100);
        $prezzo_scontato -= $sconto;
    }

    if (!empty($promozione['sconto_fisso'])) {
        $prezzo_scontato -= $promozione['sconto_fisso'];
    }

    return max(0, $prezzo_scontato);
}

// Funzione per generare un codice gift card
function generaGiftCardCodice() {
    $caratteri = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codice = '';

    for ($i = 0; $i < 8; $i++) {
        $indice = rand(0, strlen($caratteri) - 1);
        $codice .= $caratteri[$indice];
    }

    return 'GC-' . $codice;
}

// Funzione per creare una nuova gift card
function creaGiftCard($conn, $salone_id, $valore, $data_scadenza = null, $utente_acquirente_id = null, $utente_beneficiario_id = null) {
    $codice = generaGiftCardCodice();

    if ($data_scadenza === null) {
        // Imposta la scadenza a 1 anno dalla data corrente
        $data_scadenza = date('Y-m-d', strtotime('+1 year'));
    }

    $stmt = $conn->prepare("
        INSERT INTO gift_card (salone_id, codice, valore_originale, valore_residuo, data_scadenza, utente_acquirente_id, utente_beneficiario_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isddiii", $salone_id, $codice, $valore, $valore, $data_scadenza, $utente_acquirente_id, $utente_beneficiario_id);

    if ($stmt->execute()) {
        return [
            'success' => true,
            'gift_card_id' => $conn->insert_id,
            'codice' => $codice
        ];
    }

    return [
        'success' => false,
        'message' => 'Errore nella creazione della gift card'
    ];
}

// Funzione per verificare la validità di una gift card
function verificaGiftCard($conn, $codice, $salone_id) {
    $stmt = $conn->prepare("
        SELECT * FROM gift_card
        WHERE codice = ? AND salone_id = ? AND stato = 'attiva' AND valore_residuo > 0
        AND (data_scadenza IS NULL OR data_scadenza >= CURDATE())
    ");
    $stmt->bind_param("si", $codice, $salone_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return [
            'valida' => false,
            'messaggio' => 'Gift card non valida, scaduta o senza credito residuo'
        ];
    }

    return [
        'valida' => true,
        'gift_card' => $result->fetch_assoc()
    ];
}

// Funzione per utilizzare una gift card
function utilizzaGiftCard($conn, $gift_card_id, $importo) {
    // Verifica la gift card
    $stmt = $conn->prepare("
        SELECT * FROM gift_card
        WHERE id = ? AND stato = 'attiva' AND valore_residuo >= ?
        AND (data_scadenza IS NULL OR data_scadenza >= CURDATE())
    ");
    $stmt->bind_param("id", $gift_card_id, $importo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return [
            'success' => false,
            'message' => 'Gift card non valida o credito insufficiente'
        ];
    }

    $gift_card = $result->fetch_assoc();
    $nuovo_valore = $gift_card['valore_residuo'] - $importo;

    // Aggiorna il valore residuo
    $stmt = $conn->prepare("
        UPDATE gift_card 
        SET valore_residuo = ?,
            stato = CASE WHEN ? <= 0 THEN 'utilizzata' ELSE stato END
        WHERE id = ?
    ");
    $stmt->bind_param("ddi", $nuovo_valore, $nuovo_valore, $gift_card_id);

    if ($stmt->execute()) {
        return [
            'success' => true,
            'valore_residuo' => $nuovo_valore,
            'gift_card' => $gift_card
        ];
    }

    return [
        'success' => false,
        'message' => 'Errore nell\'aggiornamento della gift card'
    ];
}
