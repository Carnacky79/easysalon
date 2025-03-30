<?php
/**
 * Homepage dell'applicazione Salon Booking
 */

// Includi il file di configurazione
require_once 'config/config.php';

// Includi il file di autenticazione
require_once 'includes/auth.php';

// Determina se l'utente è loggato e qual è il suo tipo
$isLoggedIn = isLoggedIn();
$userType = getCurrentUserType();

// Ottieni i dati dell'utente (se loggato)
$userData = null;
if ($isLoggedIn) {
    if ($userType === USER_TYPE_CLIENT) {
        $userData = getCurrentClient();
    } elseif ($userType === USER_TYPE_SALON) {
        $userData = getCurrentSalon();
    }
}

// Ottieni l'elenco delle città dei saloni (per il form di ricerca)
$cities = fetchAll("SELECT DISTINCT city FROM salons ORDER BY city");

// Ottieni i saloni in evidenza (esempio: i 6 saloni più recenti)
$featuredSalons = fetchAll("
    SELECT s.*, 
           (SELECT COUNT(*) FROM appointments a WHERE a.salon_id = s.salon_id) as total_appointments 
    FROM salons s 
    ORDER BY total_appointments DESC, s.created_at DESC 
    LIMIT 6
");

// Header della pagina
$pageTitle = "Home - " . APP_NAME;
include 'templates/header.php';
?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Prenota il tuo appuntamento in pochi click</h1>
                <p>Trova saloni di bellezza e parrucchieri nella tua città e prenota facilmente il tuo prossimo trattamento.</p>

                <!-- Form di ricerca -->
                <div class="search-form">
                    <form action="search.php" method="get">
                        <div class="form-group">
                            <label for="city">Città</label>
                            <select name="city" id="city" required>
                                <option value="">Seleziona una città</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city['city']); ?>">
                                        <?php echo htmlspecialchars($city['city']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="service_type">Tipo di servizio</label>
                            <select name="service_type" id="service_type">
                                <option value="">Tutti i servizi</option>
                                <option value="parrucchiere">Parrucchiere</option>
                                <option value="estetista">Estetista</option>
                                <option value="nail">Nail Artist</option>
                                <option value="makeup">Make-up Artist</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Cerca</button>
                    </form>
                </div>

                <!-- CTA per utenti non loggati -->
                <?php if (!$isLoggedIn): ?>
                    <div class="cta-buttons">
                        <a href="client/register.php" class="btn btn-secondary">Registrati come Cliente</a>
                        <a href="salon/register.php" class="btn btn-outline">Hai un salone? Registralo</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Funzionalità Principali -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Come Funziona</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Trova</h3>
                    <p>Cerca un salone nella tua città in base alle tue esigenze</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Prenota</h3>
                    <p>Scegli il giorno, l'ora e i servizi che desideri</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Conferma</h3>
                    <p>Ricevi la conferma dell'appuntamento via email o WhatsApp</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Goditi</h3>
                    <p>Goditi il tuo trattamento di bellezza senza stress</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Saloni in Evidenza -->
    <section class="featured-salons-section">
        <div class="container">
            <h2 class="section-title">Saloni in Evidenza</h2>
            <div class="salons-grid">
                <?php if (empty($featuredSalons)): ?>
                    <p class="no-results">Nessun salone disponibile al momento.</p>
                <?php else: ?>
                    <?php foreach ($featuredSalons as $salon): ?>
                        <div class="salon-card">
                            <div class="salon-image">
                                <?php if (!empty($salon['logo_path'])): ?>
                                    <img src="<?php echo LOGO_URL . htmlspecialchars($salon['logo_path']); ?>" alt="<?php echo htmlspecialchars($salon['name']); ?>">
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
                                    <?php echo htmlspecialchars($salon['city']); ?> - <?php echo htmlspecialchars($salon['address']); ?>
                                </p>
                                <a href="salon-details.php?id=<?php echo $salon['salon_id']; ?>" class="btn btn-sm btn-primary">Vedi Dettagli</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="more-salons">
                <a href="search.php" class="btn btn-outline">Vedi tutti i saloni</a>
            </div>
        </div>
    </section>

    <!-- Per i Saloni -->
    <section class="for-salons-section">
        <div class="container">
            <div class="for-salons-content">
                <div class="content-text">
                    <h2>Gestisci il tuo salone con facilità</h2>
                    <p>Utilizzando la nostra piattaforma, puoi:</p>
                    <ul>
                        <li>Gestire le prenotazioni online</li>
                        <li>Organizzare il tuo staff</li>
                        <li>Inserire i tuoi servizi con prezzi e durata</li>
                        <li>Impostare gli orari di disponibilità</li>
                        <li>Ricevere notifiche per nuove prenotazioni</li>
                    </ul>
                    <a href="salon/register.php" class="btn btn-primary">Registra il tuo salone</a>
                </div>
                <div class="content-image">
                    <img src="assets/img/salon-dashboard.jpg" alt="Dashboard per saloni">
                </div>
            </div>
        </div>
    </section>

    <!-- Richiedi App per il tuo Salone -->
    <section class="request-app-section">
        <div class="container">
            <div class="request-app-content">
                <h2>Vuoi un'app dedicata per il tuo salone?</h2>
                <p>Contattaci per avere una versione personalizzata dell'applicazione con il tuo brand.</p>
                <a href="contact.php" class="btn btn-secondary">Contattaci</a>
            </div>
        </div>
    </section>

<?php
// Footer della pagina
include 'templates/footer.php';
?>
