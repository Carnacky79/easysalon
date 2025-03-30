<?php
// salone/servizi.php - Gestione dei servizi offerti dal salone
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

// Gestione dell'eliminazione di un servizio
$eliminato = false;
$msg_error = '';

if (isset($_GET['elimina']) && is_numeric($_GET['elimina'])) {
    $servizio_id = intval($_GET['elimina']);

    // Verifica che il servizio appartenga al salone
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM servizi WHERE id = ? AND salone_id = ?");
    $stmt->bind_param("ii", $servizio_id, $salone_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];

    if ($count > 0) {
        // Verifica se ci sono appuntamenti futuri per questo servizio
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM appuntamenti_servizi aps
            JOIN appuntamenti a ON aps.appuntamento_id = a.id
            WHERE aps.servizio_id = ? AND a.salone_id = ? AND a.data_appuntamento >= CURDATE() AND a.stato IN ('in attesa', 'confermato')
        ");
        $stmt->bind_param("ii", $servizio_id, $salone_id);
        $stmt->execute();
        $appuntamenti_futuri = $stmt->get_result()->fetch_assoc()['count'];

        if ($appuntamenti_futuri > 0) {
            $msg_error = "Impossibile eliminare il servizio perché ci sono $appuntamenti_futuri appuntamenti futuri che lo utilizzano.";
        } else {
            // Elimina il servizio
            $stmt = $conn->prepare("DELETE FROM servizi WHERE id = ? AND salone_id = ?");
            $stmt->bind_param("ii", $servizio_id, $salone_id);

            if ($stmt->execute()) {
                $eliminato = true;
            } else {
                $msg_error = "Errore durante l'eliminazione del servizio.";
            }
        }
    } else {
        $msg_error = "Servizio non trovato o non autorizzato.";
    }
}

// Recupera categorie di servizi
$stmt = $conn->prepare("
    SELECT * FROM categorie_servizi 
    WHERE salone_id = ? 
    ORDER BY ordine
");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$categorie = $stmt->get_result();
$categorie_list = [];

while ($categoria = $categorie->fetch_assoc()) {
    $categorie_list[$categoria['id']] = $categoria;
}

// Recupera servizi raggruppati per categoria
$stmt = $conn->prepare("
    SELECT s.*, cs.nome as categoria_nome, cs.colore as categoria_colore
    FROM servizi s
    JOIN categorie_servizi cs ON s.categoria_id = cs.id
    WHERE s.salone_id = ?
    ORDER BY cs.ordine, s.nome
");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$result = $stmt->get_result();

$servizi_per_categoria = [];
while ($servizio = $result->fetch_assoc()) {
    $categoria_id = $servizio['categoria_id'];
    if (!isset($servizi_per_categoria[$categoria_id])) {
        $servizi_per_categoria[$categoria_id] = [];
    }
    $servizi_per_categoria[$categoria_id][] = $servizio;
}

// Recupera operatori
$stmt = $conn->prepare("
    SELECT id, nome, qualifica, specializzazione
    FROM operatori
    WHERE salone_id = ? AND attivo = 1
    ORDER BY nome
");
$stmt->bind_param("i", $salone_id);
$stmt->execute();
$operatori = $stmt->get_result();
$operatori_list = [];

while ($operatore = $operatori->fetch_assoc()) {
    $operatori_list[$operatore['id']] = $operatore;
}

// Recupera per ogni servizio l'elenco degli operatori che lo possono eseguire
$servizi_operatori = [];
$stmt = $conn->prepare("
    SELECT servizio_id, operatore_id 
    FROM operatori_servizi
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $servizio_id = $row['servizio_id'];
    $operatore_id = $row['operatore_id'];

    if (!isset($servizi_operatori[$servizio_id])) {
        $servizi_operatori[$servizio_id] = [];
    }

    $servizi_operatori[$servizio_id][] = $operatore_id;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Servizi - <?php echo htmlspecialchars($salone['nome']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/salone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="salone-container">
    <!-- Header del salone -->
    <?php include 'includes/header.php'; ?>

    <!-- Menu laterale -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Contenuto principale -->
    <main class="salone-content">
        <div class="page-header">
            <h1>Gestione Servizi</h1>
            <div class="page-actions">
                <a href="servizio_nuovo.php" class="btn-primary"><i class="fas fa-plus"></i> Nuovo Servizio</a>
                <a href="categorie_servizi.php" class="btn-outline"><i class="fas fa-tags"></i> Gestisci Categorie</a>
            </div>
        </div>

        <?php if ($eliminato): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Servizio eliminato con successo.
                <span class="alert-close">&times;</span>
            </div>
        <?php endif; ?>

        <?php if ($msg_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $msg_error; ?>
                <span class="alert-close">&times;</span>
            </div>
        <?php endif; ?>

        <div class="services-container">
            <?php if (empty($categorie_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags empty-icon"></i>
                    <h2>Nessuna categoria di servizi</h2>
                    <p>Prima di aggiungere i servizi, è necessario creare almeno una categoria.</p>
                    <a href="categorie_servizi.php" class="btn-primary">Crea Categoria</a>
                </div>
            <?php else: ?>
                <?php foreach ($categorie_list as $categoria_id => $categoria): ?>
                    <div class="service-category" style="--category-color: <?php echo $categoria['colore']; ?>">
                        <div class="category-header">
                            <h2><i class="fas fa-tag"></i> <?php echo htmlspecialchars($categoria['nome']); ?></h2>
                            <div class="category-actions">
                                <a href="servizio_nuovo.php?categoria=<?php echo $categoria_id; ?>" class="btn-small btn-outline"><i class="fas fa-plus"></i> Aggiungi Servizio</a>
                                <a href="categoria_modifica.php?id=<?php echo $categoria_id; ?>" class="btn-small btn-outline"><i class="fas fa-edit"></i> Modifica Categoria</a>
                            </div>
                        </div>

                        <?php if (isset($servizi_per_categoria[$categoria_id]) && !empty($servizi_per_categoria[$categoria_id])): ?>
                            <table class="services-table">
                                <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrizione</th>
                                    <th>Prezzo</th>
                                    <th>Durata</th>
                                    <th>Operatori</th>
                                    <th>Azioni</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($servizi_per_categoria[$categoria_id] as $servizio): ?>
                                    <tr>
                                        <td>
                                            <div class="service-name">
                                                <span class="color-dot" style="background-color: <?php echo $servizio['colore']; ?>"></span>
                                                <?php echo htmlspecialchars($servizio['nome']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo !empty($servizio['descrizione']) ? htmlspecialchars($servizio['descrizione']) : '<em>Nessuna descrizione</em>'; ?>
                                        </td>
                                        <td><?php echo number_format($servizio['prezzo'], 2, ',', '.'); ?> €</td>
                                        <td>
                                            <?php echo $servizio['durata_minuti']; ?> min
                                            <?php if ($servizio['tempo_posa_minuti'] > 0): ?>
                                                <span class="badge-info" title="Tempo di posa">(+<?php echo $servizio['tempo_posa_minuti']; ?> min)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if (isset($servizi_operatori[$servizio['id']])) {
                                                $op_count = count($servizi_operatori[$servizio['id']]);
                                                echo $op_count . ' ';
                                                echo $op_count == 1 ? 'operatore' : 'operatori';
                                            } else {
                                                echo '<span class="badge-warning">Nessun operatore</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="servizio_modifica.php?id=<?php echo $servizio['id']; ?>" class="btn-icon" title="Modifica"><i class="fas fa-edit"></i></a>
                                            <a href="servizio_operatori.php?id=<?php echo $servizio['id']; ?>" class="btn-icon" title="Gestisci operatori"><i class="fas fa-users"></i></a>
                                            <a href="#" class="btn-icon btn-danger delete-btn" data-id="<?php echo $servizio['id']; ?>" title="Elimina"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-category">
                                <p>Nessun servizio in questa categoria.</p>
                                <a href="servizio_nuovo.php?categoria=<?php echo $categoria_id; ?>" class="btn-small">Aggiungi Servizio</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal di conferma eliminazione -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Conferma Eliminazione</h2>
        <p>Sei sicuro di voler eliminare questo servizio? Questa azione non può essere annullata.</p>
        <div class="modal-actions">
            <button id="cancelDelete" class="btn-outline">Annulla</button>
            <a href="#" id="confirmDelete" class="btn-danger">Elimina</a>
        </div>
    </div>
</div>

<script src="../js/jquery.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione del modal di conferma eliminazione
        const modal = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const closeBtn = modal.querySelector('.close');
        const cancelBtn = document.getElementById('cancelDelete');
        const deleteBtns = document.querySelectorAll('.delete-btn');

        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const serviceId = this.getAttribute('data-id');
                confirmDeleteBtn.href = 'servizi.php?elimina=' + serviceId;
                modal.style.display = 'block';
            });
        });

        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Gestione chiusura alert
        const alertCloseBtn = document.querySelectorAll('.alert-close');
        alertCloseBtn.forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });
    });
</script>

<style>
    /* Stili specifici per la pagina servizi */
    .services-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .service-category {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border-top: 4px solid var(--category-color, var(--primary-color));
    }

    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid var(--gray-lighter);
    }

    .category-header h2 {
        margin: 0;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
    }

    .category-header h2 i {
        margin-right: 10px;
        color: var(--category-color, var(--primary-color));
    }

    .category-actions {
        display: flex;
        gap: 10px;
    }

    .services-table {
        width: 100%;
        border-collapse: collapse;
    }

    .services-table th,
    .services-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--gray-lighter);
    }

    .services-table th {
        background-color: var(--background-color);
        font-weight: 500;
        color: var(--gray);
    }

    .service-name {
        display: flex;
        align-items: center;
        font-weight: 500;
    }

    .color-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }

    .badge-info,
    .badge-warning {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: normal;
    }

    .badge-info {
        background-color: rgba(74, 144, 226, 0.1);
        color: var(--primary-color);
    }

    .badge-warning {
        background-color: rgba(245, 166, 35, 0.1);
        color: var(--warning-color);
    }

    .actions-cell {
        white-space: nowrap;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 4px;
        background-color: var(--background-color);
        color: var(--gray);
        margin-right: 5px;
        transition: all 0.3s;
    }

    .btn-icon:hover {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-icon.btn-danger:hover {
        background-color: var(--danger-color);
    }

    .empty-category {
        padding: 30px;
        text-align: center;
        color: var(--gray);
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .empty-icon {
        font-size: 48px;
        color: var(--gray-light);
        margin-bottom: 20px;
    }

    .empty-state h2 {
        margin-bottom: 10px;
        color: var(--gray-dark);
    }

    .empty-state p {
        margin-bottom: 20px;
        color: var(--gray);
        max-width: 500px;
        text-align: center;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .page-actions {
        display: flex;
        gap: 10px;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: white;
        width: 90%;
        max-width: 500px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        padding: 20px;
        position: relative;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 20px;
        cursor: pointer;
        color: var(--gray);
    }

    .modal h2 {
        margin-top: 0;
        margin-bottom: 15px;
        color: var(--gray-dark);
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-danger {
        background-color: var(--danger-color);
        color: white;
    }

    .btn-danger:hover {
        background-color: darken(var(--danger-color), 10%);
    }
</style>
</body>
</html>
