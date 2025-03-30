<!-- salone/includes/sidebar.php - Menu laterale per il salone -->
<aside class="salone-sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="calendario.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'calendario.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendario</span>
                </a>
            </li>

            <li>
                <a href="appuntamenti.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appuntamenti.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appuntamenti</span>
                </a>
            </li>

            <li>
                <a href="operatori.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'operatori.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-md"></i>
                    <span>Operatori</span>
                </a>
            </li>

            <li>
                <a href="postazioni.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'postazioni.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chair"></i>
                    <span>Postazioni</span>
                </a>
            </li>

            <li>
                <a href="servizi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'servizi.php' || basename($_SERVER['PHP_SELF']) == 'categorie_servizi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cut"></i>
                    <span>Servizi</span>
                </a>
            </li>

            <li>
                <a href="pacchetti.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pacchetti.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Pacchetti</span>
                </a>
            </li>

            <li>
                <a href="clienti.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'clienti.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Clienti</span>
                </a>
            </li>

            <li>
                <a href="schede_cliente.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schede_cliente.php' ? 'active' : ''; ?>">
                    <i class="fas fa-address-card"></i>
                    <span>Schede Cliente</span>
                </a>
            </li>

            <?php
            // Mostra sezione prodotti solo se l'attività è di tipo parrucchiere o entrambi
            if (isset($_SESSION['tipo_attivita']) && ($_SESSION['tipo_attivita'] != 'estetista')):
                ?>
                <li>
                    <a href="prodotti.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'prodotti.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-basket"></i>
                        <span>Prodotti</span>
                    </a>
                </li>

                <li>
                    <a href="vendite.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'vendite.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cash-register"></i>
                        <span>Vendite</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="fidelity.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'fidelity.php' ? 'active' : ''; ?>">
                    <i class="fas fa-award"></i>
                    <span>Fidelity Card</span>
                </a>
            </li>

            <li>
                <a href="promozioni.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promozioni.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tag"></i>
                    <span>Promozioni</span>
                </a>
            </li>

            <li>
                <a href="giftcard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'giftcard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gift"></i>
                    <span>Gift Card</span>
                </a>
            </li>

            <li>
                <a href="report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Report</span>
                </a>
            </li>

            <li>
                <a href="impostazioni.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'impostazioni.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Impostazioni</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="../" target="_blank">
            <i class="fas fa-external-link-alt"></i>
            <span>Vai al sito</span>
        </a>
    </div>
</aside>
