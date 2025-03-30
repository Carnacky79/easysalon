<?php
// salone/dashboard.php - Dashboard principale per il salone di bellezza
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

// Statistiche
// 1. Appuntamenti totali di oggi
$oggi = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COUNT(*) as appuntamenti_oggi 
    FROM appuntamenti 
    WHERE salone_id = ? AND data_appuntamento = ? AND stato IN ('in attesa', 'confermato')
");
$stmt->bind_param("is", $salone_id, $oggi);
$stmt->execute();
$appuntamenti_oggi = $stmt->get_result()->fetch_assoc()['appuntamenti_oggi'];

// 2. Appuntamenti totali di questa settimana
$inizio_settimana = date('Y-m-d', strtotime('monday this week'));
$fine_settimana = date('Y-m-d', strtotime('sunday this week'));
$stmt = $conn->prepare("
    SELECT COUNT(*) as appuntamenti_settimana 
    FROM appuntamenti 
    WHERE salone_id = ? AND data_appuntamento BETWEEN ? AND ? AND stato IN ('in attesa', 'confermato')
");
$stmt->bind_param("iss", $salone_id, $inizio_settimana, $fine_settimana);
$stmt->execute();
$appuntamenti_settimana = $stmt->get_result()->fetch_assoc()['appuntamenti_settimana'];

// 3. Incasso totale di questa settimana
$stmt = $conn->prepare("
    SELECT SUM(prezzo_totale) as incasso_settimana 
    FROM appuntamenti 
    WHERE salone_id = ? AND data_appuntamento BETWEEN ? AND ? AND stato = 'completato'
");
$stmt->bind_param("iss", $salone_id, $inizio_settimana, $fine_settimana);
$stmt->execute();
$incasso_settimana = $stmt->get_result()->fetch_assoc()['incasso_settimana'] ?: 0;

// 4. Incasso totale mese corrente
$inizio_mese = date('Y-m-01');
$fine_mese = date('Y-m-t'); // t = ultimo giorno del mese
$stmt = $conn->prepare("
    SELECT SUM(prezzo_totale) as incasso_mese 
    FROM appuntamenti 
    WHERE salone_id = ? AND data_appuntamento BETWEEN ? AND ? AND stato = 'completato'
");
$stmt->bind_param("iss", $salone_id, $inizio_mese, $fine_mese);
$stmt->execute();
$incasso_mese = $stmt->get_result()->fetch_assoc()['incasso_mese'] ?: 0;

// 5. Prodotti più venduti
$stmt = $conn->prepare("
    SELECT p.nome, SUM(vpd.quantita) as quantita_venduta
    FROM vendite_prodotti_dettaglio vpd
    JOIN vendite_prodotti vp ON vpd.vendita_id = vp.id
    JOIN prodotti p ON vpd.prodotto_id = p.id
    WHERE vp.salone_id = ? AND vp.data_vendita BETWEEN ? AND ?
    GROUP BY vpd.prodotto_id
    ORDER BY quantita_venduta DESC
    LIMIT 5
");
$stmt->bind_param("iss", $salone_id, $inizio_mese, $fine_mese);
$stmt->execute();
$prodotti_top = $stmt->get_result();

// 6. Servizi più prenotati
$stmt = $conn->prepare("
    SELECT s.nome, COUNT(*) as numero_prenotazioni
    FROM appuntamenti_servizi aps
    JOIN appuntamenti a ON aps.appuntamento_id = a.id
    JOIN servizi s ON aps.servizio_id = s.id
    WHERE a.salone_id = ? AND a.data_appuntamento BETWEEN ? AND ?
    GROUP BY aps.servizio_id
    ORDER BY numero_prenotazioni DESC
    LIMIT 5
");
$stmt->bind_param("iss", $salone_id, $inizio_mese, $fine_mese);
$stmt->execute();
$servizi_top = $stmt->get_result();

// 7. Operatori più richiesti
$stmt = $conn->prepare("
    SELECT o.nome, COUNT(*) as numero_appuntamenti
    FROM appuntamenti a
    JOIN operatori o ON a.operatore_id = o.id
    WHERE a.salone_id = ? AND a.data_appuntamento BETWEEN ? AND ?
    GROUP BY a.operatore_id
    ORDER BY numero_appuntamenti DESC
");
$stmt->bind_param("iss", $salone_id, $inizio_mese, $fine_mese);
$stmt->execute();
$operatori_top = $stmt->get_result();

// 8. Ultimi 5 clienti registrati
$stmt = $conn->prepare("
    SELECT u.id, u.nome, u.cognome, u.email, u.telefono, MAX(a.data_appuntamento) as ultimo_appuntamento
    FROM utenti u
    JOIN appuntamenti a ON u.id = a.utente_id
    WHERE a.salone_id = ?
    GROUP BY u.id
    ORDER BY u.data_registrazione DESC
    LIMIT 5
");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$ultimi_clienti = $stmt->get_result();

// 9. Prossimi appuntamenti
$stmt = $conn->prepare("
    SELECT a.id, a.data_appuntamento, a.ora_inizio, a.ora_fine, 
           u.nome as utente_nome, u.cognome as utente_cognome, u.telefono,
           o.nome as operatore_nome,
           p.nome as postazione_nome,
           GROUP_CONCAT(s.nome SEPARATOR ', ') as servizi
    FROM appuntamenti a
    JOIN utenti u ON a.utente_id = u.id
    JOIN operatori o ON a.operatore_id = o.id
    LEFT JOIN postazioni p ON a.postazione_id = p.id
    LEFT JOIN appuntamenti_servizi aps ON a.id = aps.appuntamento_id
    LEFT JOIN servizi s ON aps.servizio_id = s.id
    WHERE a.salone_id = ? AND a.data_appuntamento >= ? AND a.stato IN ('in attesa', 'confermato')
    GROUP BY a.id
    ORDER BY a.data_appuntamento ASC, a.ora_inizio ASC
    LIMIT 10
");
$stmt->bind_param("is", $salone_id, $oggi);
$stmt->execute();
$prossimi_appuntamenti = $stmt->get_result();

$conn->close();

// Funzione per formattare l'importo in euro
function formatEuro($amount) {
    return '€ ' . number_format($amount, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($salone['nome']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/salone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js per i grafici -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="salone-container">
    <!-- Header del salone -->
    <?php include 'includes/header.php'; ?>

    <!-- Menu laterale -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Contenuto principale -->
    <main class="salone-content">
        <h1>Dashboard</h1>
        <p>Benvenuto, <strong><?php echo htmlspecialchars($salone['nome']); ?></strong>! Ecco un riepilogo delle attività del salone.</p>

        <!-- Card di riepilogo -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="card-content">
                    <h3>Appuntamenti Oggi</h3>
                    <p class="card-number"><?php echo $appuntamenti_oggi; ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="card-content">
                    <h3>Appuntamenti Settimana</h3>
                    <p class="card-number"><?php echo $appuntamenti_settimana; ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="card-content">
                    <h3>Incasso Settimana</h3>
                    <p class="card-number"><?php echo formatEuro($incasso_settimana); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3>Incasso Mese</h3>
                    <p class="card-number"><?php echo formatEuro($incasso_mese); ?></p>
                </div>
            </div>
        </div>

        <!-- Grafici e statistiche -->
        <div class="dashboard-charts">
            <!-- Grafico andamento appuntamenti -->
            <div class="chart-container">
                <h2>Andamento Appuntamenti</h2>
                <canvas id="appuntamentiChart"></canvas>
            </div>

            <!-- Grafico incassi -->
            <div class="chart-container">
                <h2>Andamento Incassi</h2>
                <canvas id="incassiChart"></canvas>
            </div>
        </div>

        <!-- Tabelle statistiche -->
        <div class="dashboard-tables">
            <!-- Servizi più prenotati -->
            <div class="table-container">
                <h2>Servizi Più Prenotati</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Servizio</th>
                        <th>Prenotazioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($servizi_top->num_rows > 0): ?>
                        <?php while($servizio = $servizi_top->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($servizio['nome']); ?></td>
                                <td><?php echo $servizio['numero_prenotazioni']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Nessun dato disponibile</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Prodotti più venduti -->
            <div class="table-container">
                <h2>Prodotti Più Venduti</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th>Quantità</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($prodotti_top->num_rows > 0): ?>
                        <?php while($prodotto = $prodotti_top->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prodotto['nome']); ?></td>
                                <td><?php echo $prodotto['quantita_venduta']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Nessun dato disponibile</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Operatori più richiesti -->
            <div class="table-container">
                <h2>Operatori più Richiesti</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Operatore</th>
                        <th>Appuntamenti</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($operatori_top->num_rows > 0): ?>
                        <?php while($operatore = $operatori_top->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($operatore['nome']); ?></td>
                                <td><?php echo $operatore['numero_appuntamenti']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Nessun dato disponibile</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Prossimi appuntamenti -->
        <div class="prossimi-appuntamenti">
            <h2>Prossimi Appuntamenti</h2>
            <table>
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Ora</th>
                    <th>Cliente</th>
                    <th>Telefono</th>
                    <th>Operatore</th>
                    <th>Servizi</th>
                    <th>Azioni</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($prossimi_appuntamenti->num_rows > 0): ?>
                    <?php while($appuntamento = $prossimi_appuntamenti->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($appuntamento['data_appuntamento'])); ?></td>
                            <td><?php echo date('H:i', strtotime($appuntamento['ora_inizio'])); ?> - <?php echo date('H:i', strtotime($appuntamento['ora_fine'])); ?></td>
                            <td><?php echo htmlspecialchars($appuntamento['utente_nome']) . ' ' . htmlspecialchars($appuntamento['utente_cognome']); ?></td>
                            <td><?php echo htmlspecialchars($appuntamento['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($appuntamento['operatore_nome']); ?></td>
                            <td><?php echo htmlspecialchars($appuntamento['servizi']); ?></td>
                            <td>
                                <a href="appuntamenti_dettaglio.php?id=<?php echo $appuntamento['id']; ?>" class="btn-small">Dettagli</a>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $appuntamento['telefono']); ?>?text=<?php echo urlencode('Promemoria appuntamento presso ' . $salone['nome'] . ' il ' . date('d/m/Y', strtotime($appuntamento['data_appuntamento'])) . ' alle ore ' . date('H:i', strtotime($appuntamento['ora_inizio']))); ?>" target="_blank" class="btn-small btn-green"><i class="fab fa-whatsapp"></i></a>
                                <a href="appuntamenti_modifica.php?id=<?php echo $appuntamento['id']; ?>" class="btn-small btn-blue"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nessun appuntamento in programma</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Ultimi clienti -->
        <div class="ultimi-clienti">
            <h2>Ultimi Clienti</h2>
            <div class="client-cards">
                <?php if ($ultimi_clienti->num_rows > 0): ?>
                    <?php while($cliente = $ultimi_clienti->fetch_assoc()): ?>
                        <div class="client-card">
                            <div class="client-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="client-info">
                                <h3><?php echo htmlspecialchars($cliente['nome']) . ' ' . htmlspecialchars($cliente['cognome']); ?></h3>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($cliente['telefono']); ?></p>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($cliente['email']); ?></p>
                                <?php if (!empty($cliente['ultimo_appuntamento'])): ?>
                                    <p><i class="fas fa-calendar-check"></i> Ultimo: <?php echo date('d/m/Y', strtotime($cliente['ultimo_appuntamento'])); ?></p>
                                <?php endif; ?>
                                <a href="scheda_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn-small">Visualizza Scheda</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Nessun cliente registrato</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    // Script per i grafici Chart.js
    document.addEventListener('DOMContentLoaded', function() {
        // Dati per il grafico degli appuntamenti
        const ctxAppuntamenti = document.getElementById('appuntamentiChart').getContext('2d');
        const appuntamentiChart = new Chart(ctxAppuntamenti, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
                datasets: [{
                    label: 'Appuntamenti',
                    data: [
                        <?php
                        // Recupera il numero di appuntamenti per ogni giorno della settimana corrente
                        $conn = connectDB();
                        for ($i = 0; $i < 7; $i++) {
                            $day = date('Y-m-d', strtotime("monday this week +$i days"));
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM appuntamenti WHERE salone_id = ? AND data_appuntamento = ?");
                            $stmt->bind_param("is", $salone_id, $day);
                            $stmt->execute();
                            $count = $stmt->get_result()->fetch_row()[0];
                            echo $count . ($i < 6 ? ',' : '');
                        }
                        $conn->close();
                        ?>
                    ],
                    backgroundColor: 'rgba(74, 144, 226, 0.2)',
                    borderColor: 'rgba(74, 144, 226, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Dati per il grafico degli incassi
        const ctxIncassi = document.getElementById('incassiChart').getContext('2d');
        const incassiChart = new Chart(ctxIncassi, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
                datasets: [{
                    label: 'Incassi (€)',
                    data: [
                        <?php
                        // Recupera l'incasso per ogni giorno della settimana corrente
                        $conn = connectDB();
                        for ($i = 0; $i < 7; $i++) {
                            $day = date('Y-m-d', strtotime("monday this week +$i days"));
                            $stmt = $conn->prepare("SELECT SUM(prezzo_totale) FROM appuntamenti WHERE salone_id = ? AND data_appuntamento = ? AND stato = 'completato'");
                            $stmt->bind_param("is", $salone_id, $day);
                            $stmt->execute();
                            $sum = $stmt->get_result()->fetch_row()[0] ?: 0;
                            echo round($sum, 2) . ($i < 6 ? ',' : '');
                        }
                        $conn->close();
                        ?>
                    ],
                    backgroundColor: 'rgba(126, 211, 33, 0.5)',
                    borderColor: 'rgba(126, 211, 33, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
</body>
</html>
