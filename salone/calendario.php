<?php
// salone/calendario.php - Gestione calendario degli appuntamenti
session_start();
require_once '../config.php';

// Verifica che l'utente sia autenticato come salone
if (!isSalone()) {
    header("Location: ../login.php");
    exit;
}

$salone_id = $_SESSION['salone_id'];
$conn = connectDB();

// Recupera informazioni del salone
$stmt = $conn->prepare("SELECT * FROM saloni WHERE id = ?");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$salone = $stmt->get_result()->fetch_assoc();

// Imposta la vista predefinita e gestisce il cambio di visualizzazione
$default_view = 'month'; // 'month', 'week', 'day'
$view = isset($_GET['view']) ? sanitizeInput($_GET['view']) : $default_view;

// Gestisce la navigazione tra le date
$today = date('Y-m-d');
$current_date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : $today;

// Calcola date in base alla visualizzazione
if ($view == 'month') {
    $month = date('m', strtotime($current_date));
    $year = date('Y', strtotime($current_date));
    $first_day = date('Y-m-01', strtotime($current_date));
    $days_in_month = date('t', strtotime($current_date));
    $start_day_of_week = date('N', strtotime($first_day));

    // Date per navigazione
    $prev_month = date('Y-m-d', strtotime('-1 month', strtotime($first_day)));
    $next_month = date('Y-m-d', strtotime('+1 month', strtotime($first_day)));

    $heading = date('F Y', strtotime($current_date));
} elseif ($view == 'week') {
    // Trova lunedì della settimana corrente
    $monday = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
    // Trova domenica della settimana corrente
    $sunday = date('Y-m-d', strtotime('sunday this week', strtotime($current_date)));

    // Date per navigazione
    $prev_week = date('Y-m-d', strtotime('-1 week', strtotime($monday)));
    $next_week = date('Y-m-d', strtotime('+1 week', strtotime($monday)));

    $heading = date('d M', strtotime($monday)) . ' - ' . date('d M Y', strtotime($sunday));
} else { // day view
    // Date per navigazione
    $prev_day = date('Y-m-d', strtotime('-1 day', strtotime($current_date)));
    $next_day = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));

    $heading = date('l, d F Y', strtotime($current_date));
}

// Recupera gli operatori
$stmt = $conn->prepare("
    SELECT id, nome, qualifica, specializzazione, colore
    FROM operatori 
    WHERE salone_id = ? AND attivo = 1
    ORDER BY nome
");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$operatori = $stmt->get_result();
$operatori_list = [];
while ($op = $operatori->fetch_assoc()) {
    $operatori_list[$op['id']] = $op;
}

// Recupera le postazioni
$stmt = $conn->prepare("
    SELECT id, nome, tipo
    FROM postazioni 
    WHERE salone_id = ? AND attiva = 1
    ORDER BY nome
");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$postazioni = $stmt->get_result();
$postazioni_list = [];
while ($pos = $postazioni->fetch_assoc()) {
    $postazioni_list[$pos['id']] = $pos;
}

// Funzione per recuperare gli appuntamenti
function getAppointments($conn, $salone_id, $start_date, $end_date = null) {
    if ($end_date === null) {
        $end_date = $start_date;
    }

    $stmt = $conn->prepare("
        SELECT a.id, a.data_appuntamento, a.ora_inizio, a.ora_fine, a.stato, 
               a.operatore_id, a.postazione_id, a.prezzo_totale,
               u.nome as utente_nome, u.cognome as utente_cognome,
               GROUP_CONCAT(s.nome SEPARATOR ', ') as servizi_nomi,
               GROUP_CONCAT(s.id SEPARATOR ',') as servizi_ids,
               GROUP_CONCAT(cs.colore SEPARATOR ',') as servizi_colori
        FROM appuntamenti a
        JOIN utenti u ON a.utente_id = u.id
        LEFT JOIN appuntamenti_servizi aps ON a.id = aps.appuntamento_id
        LEFT JOIN servizi s ON aps.servizio_id = s.id
        LEFT JOIN categorie_servizi cs ON s.categoria_id = cs.id
        WHERE a.salone_id = ? 
        AND a.data_appuntamento BETWEEN ? AND ?
        AND a.stato != 'cancellato'
        GROUP BY a.id
        ORDER BY a.data_appuntamento, a.ora_inizio
    ");
    $stmt->bind_param("iss", $salone_id, $start_date, $end_date);
    $stmt->execute();

    $appointments = [];
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $date = $row['data_appuntamento'];
        if (!isset($appointments[$date])) {
            $appointments[$date] = [];
        }

        // Gestione colori dei servizi per la visualizzazione
        $colors = explode(',', $row['servizi_colori']);
        $main_color = !empty($colors) ? $colors[0] : '#4a90e2';

        $row['main_color'] = $main_color;
        $appointments[$date][] = $row;
    }

    return $appointments;
}

// Recupera appuntamenti in base alla visualizzazione
if ($view == 'month') {
    // Trova il primo giorno della griglia del calendario (potrebbe essere del mese precedente)
    $calendar_start = date('Y-m-d', strtotime(($start_day_of_week - 1) . ' days ago', strtotime($first_day)));
    // Trova l'ultimo giorno della griglia del calendario (potrebbe essere del mese successivo)
    $last_day_of_month = date('Y-m-t', strtotime($current_date));
    $end_day_of_week = date('N', strtotime($last_day_of_month));
    $days_to_add = 7 - $end_day_of_week;
    $calendar_end = date('Y-m-d', strtotime('+' . $days_to_add . ' days', strtotime($last_day_of_month)));

    $appointments = getAppointments($conn, $salone_id, $calendar_start, $calendar_end);
} elseif ($view == 'week') {
    $appointments = getAppointments($conn, $salone_id, $monday, $sunday);
} else { // day view
    $appointments = getAppointments($conn, $salone_id, $current_date);
}

// Recupera gli orari di apertura per la vista giornaliera
$orari_apertura = [];
if ($view == 'day') {
    $giorno_settimana = strtolower(date('l', strtotime($current_date)));
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

    $stmt = $conn->prepare("
        SELECT ora_apertura, ora_chiusura, aperto 
        FROM orari_apertura 
        WHERE salone_id = ? AND giorno = ?
    ");
    $stmt->bind_param("is", $salone_id, $giorno);
    $stmt->execute();
    $orari = $stmt->get_result()->fetch_assoc();

    if ($orari && $orari['aperto']) {
        $orari_apertura = [
            'apertura' => $orari['ora_apertura'],
            'chiusura' => $orari['ora_chiusura']
        ];
    }

    // Controlla anche se è un giorno di chiusura speciale
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM giorni_chiusura 
        WHERE salone_id = ? AND data_chiusura = ?
    ");
    $stmt->bind_param("is", $salone_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        $orari_apertura = [];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - <?php echo htmlspecialchars($salone['nome']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/salone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- FullCalendar per vista avanzata -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/it.js"></script>
</head>
<body>
<div class="salone-container">
    <!-- Header del salone -->
    <?php include 'includes/header.php'; ?>

    <!-- Menu laterale -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Contenuto principale -->
    <main class="salone-content">
        <h1>Calendario Appuntamenti</h1>

        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <?php if ($view == 'month'): ?>
                        <a href="?view=month&date=<?php echo $prev_month; ?>" class="btn-small"><i class="fas fa-chevron-left"></i></a>
                        <span><?php echo $heading; ?></span>
                        <a href="?view=month&date=<?php echo $next_month; ?>" class="btn-small"><i class="fas fa-chevron-right"></i></a>
                    <?php elseif ($view == 'week'): ?>
                        <a href="?view=week&date=<?php echo $prev_week; ?>" class="btn-small"><i class="fas fa-chevron-left"></i></a>
                        <span><?php echo $heading; ?></span>
                        <a href="?view=week&date=<?php echo $next_week; ?>" class="btn-small"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <a href="?view=day&date=<?php echo $prev_day; ?>" class="btn-small"><i class="fas fa-chevron-left"></i></a>
                        <span><?php echo $heading; ?></span>
                        <a href="?view=day&date=<?php echo $next_day; ?>" class="btn-small"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>

                <div class="calendar-view-options">
                    <a href="?view=day&date=<?php echo $current_date; ?>" class="btn-small <?php echo $view == 'day' ? 'btn-primary' : 'btn-outline'; ?>">Giorno</a>
                    <a href="?view=week&date=<?php echo $current_date; ?>" class="btn-small <?php echo $view == 'week' ? 'btn-primary' : 'btn-outline'; ?>">Settimana</a>
                    <a href="?view=month&date=<?php echo $current_date; ?>" class="btn-small <?php echo $view == 'month' ? 'btn-primary' : 'btn-outline'; ?>">Mese</a>
                </div>

                <div class="calendar-actions">
                    <a href="?view=<?php echo $view; ?>&date=<?php echo $today; ?>" class="btn-small btn-outline">Oggi</a>
                    <a href="appuntamenti_nuovo.php" class="btn-small btn-primary"><i class="fas fa-plus"></i> Nuovo</a>
                </div>
            </div>

            <div class="calendar-filters">
                <div class="filter-group">
                    <label>Filtra per operatore:</label>
                    <select id="operatore-filter">
                        <option value="all">Tutti gli operatori</option>
                        <?php foreach ($operatori_list as $op): ?>
                            <option value="<?php echo $op['id']; ?>"><?php echo htmlspecialchars($op['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Filtra per postazione:</label>
                    <select id="postazione-filter">
                        <option value="all">Tutte le postazioni</option>
                        <?php foreach ($postazioni_list as $pos): ?>
                            <option value="<?php echo $pos['id']; ?>"><?php echo htmlspecialchars($pos['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($view == 'month'): ?>
                <!-- Vista mensile -->
                <div class="calendar month-view">
                    <!-- Intestazioni giorni della settimana -->
                    <div class="calendar-day-header">Lun</div>
                    <div class="calendar-day-header">Mar</div>
                    <div class="calendar-day-header">Mer</div>
                    <div class="calendar-day-header">Gio</div>
                    <div class="calendar-day-header">Ven</div>
                    <div class="calendar-day-header">Sab</div>
                    <div class="calendar-day-header">Dom</div>

                    <?php
                    // Genera la griglia del calendario
                    $current_day = new DateTime($calendar_start);
                    $last_day = new DateTime($calendar_end);

                    while ($current_day <= $last_day) {
                        $day_string = $current_day->format('Y-m-d');
                        $is_today = ($day_string == $today);
                        $is_current_month = ($current_day->format('m') == $month);

                        echo '<div class="calendar-day ' .
                            ($is_today ? 'today ' : '') .
                            (!$is_current_month ? 'other-month' : '') . '">';

                        echo '<div class="calendar-day-number">' . $current_day->format('j') . '</div>';

                        // Visualizza gli appuntamenti per questo giorno
                        if (isset($appointments[$day_string])) {
                            foreach ($appointments[$day_string] as $appointment) {
                                $time = date('H:i', strtotime($appointment['ora_inizio']));
                                $class = '';

                                // Determina la classe CSS in base al servizio (per il colore)
                                if (!empty($appointment['servizi_ids'])) {
                                    $first_service_id = explode(',', $appointment['servizi_ids'])[0];
                                    $class = 'event-service-' . $first_service_id;
                                }

                                echo '<a href="appuntamenti_dettaglio.php?id=' . $appointment['id'] . '" ';
                                echo 'class="calendar-event ' . $class . '" ';
                                echo 'style="background-color: ' . $appointment['main_color'] . ';">';
                                echo $time . ' - ' . htmlspecialchars($appointment['utente_nome'] . ' ' . $appointment['utente_cognome']);
                                echo '</a>';
                            }
                        }

                        echo '</div>';

                        $current_day->modify('+1 day');
                    }
                    ?>
                </div>
            <?php elseif ($view == 'week'): ?>
                <!-- Vista settimanale -->
                <div id="calendar-week" class="fullcalendar-container"></div>
            <?php else: ?>
                <!-- Vista giornaliera -->
                <div id="calendar-day" class="fullcalendar-container"></div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../js/jquery.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione filtri
        const operatoreFilter = document.getElementById('operatore-filter');
        const postazioneFilter = document.getElementById('postazione-filter');

        function applyFilters() {
            const operatoreValue = operatoreFilter.value;
            const postazioneValue = postazioneFilter.value;

            const events = document.querySelectorAll('.calendar-event');
            events.forEach(event => {
                const eventOperatore = event.getAttribute('data-operatore');
                const eventPostazione = event.getAttribute('data-postazione');

                let showEvent = true;

                if (operatoreValue !== 'all' && eventOperatore !== operatoreValue) {
                    showEvent = false;
                }

                if (postazioneValue !== 'all' && eventPostazione !== postazioneValue) {
                    showEvent = false;
                }

                event.style.display = showEvent ? 'block' : 'none';
            });
        }

        if (operatoreFilter) operatoreFilter.addEventListener('change', applyFilters);
        if (postazioneFilter) postazioneFilter.addEventListener('change', applyFilters);

        <?php if ($view == 'week' || $view == 'day'): ?>
        // Inizializzazione FullCalendar
        const calendarEl = document.getElementById('calendar-<?php echo $view; ?>');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'it',
            initialView: '<?php echo $view == 'week' ? 'timeGridWeek' : 'timeGridDay'; ?>',
            initialDate: '<?php echo $current_date; ?>',
            headerToolbar: false, // Usiamo il nostro header personalizzato
            allDaySlot: false,
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            slotDuration: '00:15:00',
            height: 'auto',
            events: [
                <?php
                // Genera gli eventi per FullCalendar
                $events = [];

                if ($view == 'week') {
                    $start_date = $monday;
                    $end_date = $sunday;
                } else {
                    $start_date = $current_date;
                    $end_date = $current_date;
                }

                $current = new DateTime($start_date);
                $last = new DateTime($end_date);

                while ($current <= $last) {
                    $date_string = $current->format('Y-m-d');

                    if (isset($appointments[$date_string])) {
                        foreach ($appointments[$date_string] as $appointment) {
                            $start = $date_string . 'T' . $appointment['ora_inizio'];
                            $end = $date_string . 'T' . $appointment['ora_fine'];

                            echo '{';
                            echo 'id: "' . $appointment['id'] . '",';
                            echo 'title: "' . addslashes($appointment['utente_nome'] . ' ' . $appointment['utente_cognome']) . '",';
                            echo 'start: "' . $start . '",';
                            echo 'end: "' . $end . '",';
                            echo 'extendedProps: {';
                            echo 'servizi: "' . addslashes($appointment['servizi_nomi']) . '",';
                            echo 'operatore: "' . $appointment['operatore_id'] . '",';
                            echo 'operatore_nome: "' . addslashes($operatori_list[$appointment['operatore_id']]['nome']) . '",';

                            if (!empty($appointment['postazione_id'])) {
                                echo 'postazione: "' . $appointment['postazione_id'] . '",';
                                echo 'postazione_nome: "' . addslashes($postazioni_list[$appointment['postazione_id']]['nome']) . '",';
                            }

                            echo 'prezzo: "' . $appointment['prezzo_totale'] . '"';
                            echo '},';
                            echo 'backgroundColor: "' . $appointment['main_color'] . '",';
                            echo 'borderColor: "' . $appointment['main_color'] . '",';
                            echo 'url: "appuntamenti_dettaglio.php?id=' . $appointment['id'] . '"';
                            echo '},';
                        }
                    }

                    $current->modify('+1 day');
                }
                ?>
            ],
            eventContent: function(arg) {
                return {
                    html: `
                    <div class="fc-event-main-frame">
                        <div class="fc-event-time">${arg.timeText}</div>
                        <div class="fc-event-title-container">
                            <div class="fc-event-title">${arg.event.title}</div>
                            <div class="fc-event-desc">${arg.event.extendedProps.servizi}</div>
                            <div class="fc-event-op">${arg.event.extendedProps.operatore_nome}</div>
                        </div>
                    </div>
                `
                };
            }
        });

        calendar.render();
        <?php endif; ?>
    });
</script>
</body>
</html>
