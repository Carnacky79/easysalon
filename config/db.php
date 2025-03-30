<?php
/**
 * Configurazione e connessione al database
 */

// Parametri di connessione al database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Da modificare in produzione
define('DB_PASS', '');         // Da modificare in produzione
define('DB_NAME', 'salon_booking');

// Connessione al database con MySQLi
function getDbConnection() {
    static $conn;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verifica la connessione
        if ($conn->connect_error) {
            die("Connessione al database fallita: " . $conn->connect_error);
        }

        // Imposta il charset a utf8
        $conn->set_charset("utf8");
    }

    return $conn;
}

// Funzione per eseguire query e gestire eventuali errori
function executeQuery($sql, $params = [], $types = "") {
    $conn = getDbConnection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Errore nella preparazione della query: " . $conn->error);
    }

    // Binding dei parametri, se presenti
    if (!empty($params)) {
        if (empty($types)) {
            // Determina automaticamente i tipi dei parametri
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_float($param)) {
                    $types .= "d";
                } elseif (is_string($param)) {
                    $types .= "s";
                } else {
                    $types .= "b"; // Blob
                }
            }
        }

        $stmt->bind_param($types, ...$params);
    }

    // Esegue la query
    if (!$stmt->execute()) {
        die("Errore nell'esecuzione della query: " . $stmt->error);
    }

    // Restituisce il risultato
    $result = $stmt->get_result();

    // Se è una query di selezione, restituisce i risultati
    if ($result) {
        return $result;
    }

    // Per query di inserimento, aggiornamento o eliminazione, restituisce l'ID inserito o il numero di righe modificate
    if ($stmt->insert_id > 0) {
        return $stmt->insert_id;
    }

    return $stmt->affected_rows;
}

// Funzione per ottenere una singola riga da una query
function fetchRow($sql, $params = [], $types = "") {
    $result = executeQuery($sql, $params, $types);

    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $result->free();
        return $row;
    }

    return null;
}

// Funzione per ottenere tutte le righe da una query
function fetchAll($sql, $params = [], $types = "") {
    $result = executeQuery($sql, $params, $types);

    if ($result instanceof mysqli_result) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        return $rows;
    }

    return [];
}

// Funzione per eseguire query di inserimento
function insert($table, $data) {
    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), "?"));

    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

    return executeQuery($sql, array_values($data));
}

// Funzione per eseguire query di aggiornamento
function update($table, $data, $where, $whereParams = []) {
    $set = [];
    foreach (array_keys($data) as $column) {
        $set[] = "$column = ?";
    }

    $sql = "UPDATE $table SET " . implode(", ", $set) . " WHERE $where";

    $params = array_merge(array_values($data), $whereParams);

    return executeQuery($sql, $params);
}

// Funzione per eseguire query di eliminazione
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";

    return executeQuery($sql, $params);
}
