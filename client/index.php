<?php
/**
 * Dashboard cliente
 */

// Includi il file di configurazione
require_once '../config/config.php';

// Includi i file necessari
require_once '../includes/auth.php';

// Verifica che l'utente sia loggato come cliente
requireClientLogin();

// Ottieni i dati del cliente
$client = getCurrentClient();

// Ottieni il salone predefinito
$defaultSalon = null;
if ($client['default_salon_id']) {
    $defaultSalon = getSalonById($client['default_salon_id']);
}

// Ottieni gli appuntamenti futuri del cliente
$upcomingAppointments = fetchAll("
    SELECT a.*, s.name as salon_name, s.address, s.city, s.logo_path 
    FROM appointments a 
    JOIN salons s ON a.salon_id = s.salon_id 
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status != ?
    ORDER BY a.appointment_date ASC, a.start_time ASC
    LIMIT 3
", [$client['user_id'], APPOINTMENT_CANCELLED]);

// Ottieni gli appuntamenti recenti
$recentAppointments = fetchAll("
    SELECT a.*, s.name as salon_name 
    FROM appointments a 
    JOIN salons s ON a.salon_id = s.salon_id 
    WHERE a.user_id = ? AND (a.appointment_date < CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time < CURTIME())) 
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 3
", [$client['user_id']]);

// Ottieni i saloni della città del cliente
$citySalons = fetchAll("
    SELECT s.*, 
           (SELECT COUNT(*) FROM appointments a WHERE a.salon_id = s.salon_id) as total_appointments
    FROM salons s 
    WHERE s.city = ? 
    ORDER BY total_appointments DESC
    LIMIT 3
", [$client['city']]);

// Titolo della pagina
$pageTitle = "Dashboard - " . APP_NAME;
$extraCss = "client";
$extraJs = "client-dashboard";

// Includi l'header
include '../templates/header.php';
?>

    <section class="client-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Benvenuto, <?php echo htmlspecialchars($client['first_name'] ?: $client['nickname']); ?>!</h1>
                <p class="dashboard-subtitle">Gestisci i tuoi appuntamenti e prenota nuovi servizi.</p>
            </div>

            <!-- Sezione Quick Links -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h3 class="dashboard-card-title">Prenota</h3>
                    </div>
                    <p>Prenota un nuovo appuntamento presso il tuo salone preferito.</p>
                    <a href="<?php echo APP_URL; ?>/client/booking.php" class="btn btn-primary">Prenota Ora</a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="dashboard-card-title">Appuntamenti</h3>
                    </div>
                    <p>Visualizza, modifica o cancella i tuoi appuntamenti.</p>
                    <a href="<?php echo APP_URL; ?>/client/appointments.php" class="btn btn-primary">Gestisci</a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="dashboard-card-title">Profilo</h3>
                    </div>
                    <p>Aggiorna i tuoi dati personali e le preferenze.</p>
                    <a href="<?php echo APP_URL; ?>/client/profile.php" class="btn btn-primary">Modifica</a>
                </div>

                <?php if (!empty($defaultSalon)): ?>
                    <div class="dashboard-card cta-card">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <h3 class="dashboard-card-title">Il tuo salone</h3>
                        </div>
                        <p><?php echo htmlspecialchars($defaultSalon['name']); ?></p>
                        <p><?php echo htmlspecialchars($defaultSalon['address']); ?>, <?php echo htmlspecialchars($defaultSalon['city']); ?></p>
                        <a href="<?php echo APP_URL; ?>/salon-details.php?id=<?php echo $defaultSalon['salon_id']; ?>" class="btn btn-primary">Vai al Salone</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Prossimi Appuntamenti -->
            <div class="dashboard-section">
                <h2>Prossimi Appuntamenti</h2>

                <?php if (empty($upcomingAppointments)): ?>
                    <div class="no-data">
                        <p>Non hai appuntamenti futuri prenotati.</p>
                        <a href="<?php echo APP_URL; ?>/client/booking.php" class="btn btn-primary">Prenota Ora</a>
                    </div>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <div>
                                        <h3 class="appointment-salon"><?php echo htmlspecialchars($appointment['salon_name']); ?></h3>
                                        <div class="appointment-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo formatDate($appointment['appointment_date']); ?>
                                            alle <?php echo formatTime($appointment['start_time']); ?>
                                        </div>
                                    </div>
                                    <div class="appointment-status status-<?php echo $appointment['status']; ?>">
                                        <?php
                                        switch ($appointment['status']) {
                                            case APPOINTMENT_PENDING:
                                                echo 'In attesa';
                                                break;
                                            case APPOINTMENT_CONFIRMED:
                                                echo 'Confermato';
                                                break;
                                            case APPOINTMENT_COMPLETED:
                                                echo 'Completato';
                                                break;
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="appointment-services">
                                    <?php
                                    // Ottieni i servizi prenotati per questo appuntamento
                                    $services = fetchAll("
                                    SELECT aps.*, s.name as service_name, st.name as staff_name
                                    FROM appointment_services aps
                                    JOIN services s ON aps.service_id = s.service_id
                                    JOIN staff st ON aps.staff_id = st.staff_id
                                    WHERE aps.appointment_id = ?
                                ", [$appointment['appointment_id']]);

                                    foreach ($services as $service):
                                        ?>
                                        <div class="service-item">
                                            <div class="service-name">
                                                <?php echo htmlspecialchars($service['service_name']); ?>
                                                <span class="service-operator">con <?php echo htmlspecialchars($service['staff_name']); ?></span>
                                            </div>
                                            <div class="service-price">€<?php echo number_format($service['price'], 2); ?></div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="appointment-total">
                                        Totale: €<?php echo number_format($appointment['total_price'], 2); ?>
                                    </div>
                                </div>

                                <div class="appointment-actions">
                                    <?php if ($appointment['status'] == APPOINTMENT_PENDING || $appointment['status'] == APPOINTMENT_CONFIRMED): ?>
                                        <?php
                                        // Verifica se l'appuntamento può essere cancellato (24h prima)
                                        $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
                                        $cancellationDeadline = time() + (DEFAULT_CANCELLATION_DEADLINE * 3600);
                                        $canCancel = $appointmentDateTime > $cancellationDeadline;
                                        ?>
                                        <a href="<?php echo APP_URL; ?>/client/appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline">Dettagli</a>
                                        <?php if ($canCancel): ?>
                                            <a href="<?php echo APP_URL; ?>/client/cancel-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline cancel-btn" data-confirm="Sei sicuro di voler cancellare questo appuntamento?">Cancella</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?php echo APP_URL; ?>/client/appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline">Dettagli</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="view-all">
                            <a href="<?php echo APP_URL; ?>/client/appointments.php" class="btn btn-outline">Vedi tutti gli appuntamenti</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Saloni consigliati nella tua città -->
            <?php if (!empty($citySalons)): ?>
                <div class="dashboard-section">
                    <h2>Saloni Consigliati a <?php echo htmlspecialchars($client['city']); ?></h2>

                    <div class="salons-grid">
                        <?php foreach ($citySalons as $salon): ?>
                            <div class="salon-card">
                                <div class="salon-image">
                                    <?php if (!empty($salon['logo_path'])): ?>
                                        <img src="<?php echo APP_URL . '/uploads/logos/' . htmlspecialchars($salon['logo_path']); ?>" alt="<?php echo htmlspecialchars($salon['name']); ?>">
                                    <?php else: ?>
                                        <div class="default-logo">
                                            <i class="fas fa-spa"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="salon-info">
                                    <h3><?php echo htmlspecialchars($salon['name']); ?></h3>
                                    <p class="salon-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($salon['address']); ?>
                                    </p>
                                    <a href="<?php echo APP_URL; ?>/salon-details.php?id=<?php echo $salon['salon_id']; ?>" class="btn btn-sm btn-primary">Vedi Dettagli</a>
                                    <?php if ($client['default_salon_id'] != $salon['salon_id']): ?>
                                        <a href="<?php echo APP_URL; ?>/client/set-default-salon.php?id=<?php echo $salon['salon_id']; ?>" class="btn btn-sm btn-outline">Imposta come Predefinito</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="view-all">
                        <a href="<?php echo APP_URL; ?>/search.php?city=<?php echo urlencode($client['city']); ?>" class="btn btn-outline">Vedi tutti i saloni a <?php echo htmlspecialchars($client['city']); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php
// Includi il footer
include '../templates/footer.php';
?>
