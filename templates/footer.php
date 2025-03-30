</main>

<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="<?php echo APP_URL; ?>/assets/img/logo-white.png" alt="<?php echo APP_NAME; ?>">
                <p>La piattaforma ideale per prenotare appuntamenti presso saloni di bellezza e parrucchieri.</p>
            </div>

            <div class="footer-links">
                <div class="footer-column">
                    <h4>Naviga</h4>
                    <ul>
                        <li><a href="<?php echo APP_URL; ?>/">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/search.php">Cerca Saloni</a></li>
                        <li><a href="<?php echo APP_URL; ?>/how-it-works.php">Come Funziona</a></li>
                        <li><a href="<?php echo APP_URL; ?>/contact.php">Contatti</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Per i Clienti</h4>
                    <ul>
                        <li><a href="<?php echo APP_URL; ?>/client/register.php">Registrati</a></li>
                        <li><a href="<?php echo APP_URL; ?>/client/login.php">Accedi</a></li>
                        <li><a href="<?php echo APP_URL; ?>/faq.php">FAQ</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Per i Saloni</h4>
                    <ul>
                        <li><a href="<?php echo APP_URL; ?>/salon/register.php">Registra il tuo salone</a></li>
                        <li><a href="<?php echo APP_URL; ?>/salon/login.php">Area Riservata</a></li>
                        <li><a href="<?php echo APP_URL; ?>/salon/features.php">Funzionalità</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Contatti</h4>
                    <ul class="contact-list">
                        <li><i class="fas fa-envelope"></i> info@salonbooking.it</li>
                        <li><i class="fas fa-phone"></i> +39 123 456 7890</li>
                        <li><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</li>
                    </ul>
                    <div class="social-links">
                        <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tutti i diritti riservati.
            </div>
            <div class="footer-legal">
                <a href="<?php echo APP_URL; ?>/privacy-policy.php">Privacy Policy</a>
                <a href="<?php echo APP_URL; ?>/terms-of-service.php">Termini di Servizio</a>
                <a href="<?php echo APP_URL; ?>/cookie-policy.php">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript principale -->
<script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>

<!-- JavaScript aggiuntivo se necessario -->
<?php if (isset($extraJs)): ?>
    <script src="<?php echo APP_URL; ?>/assets/js/<?php echo $extraJs; ?>.js"></script>
<?php endif; ?>
</body>
</html>
