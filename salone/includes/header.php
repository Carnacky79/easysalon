<!-- salone/includes/header.php - Header per le pagine del salone -->
<header class="salone-header">
    <div class="salone-logo">
        <?php if (!empty($salone['logo'])): ?>
            <img src="../uploads/saloni/<?php echo htmlspecialchars($salone['logo']); ?>" alt="<?php echo htmlspecialchars($salone['nome']); ?>">
        <?php else: ?>
            <span class="logo-placeholder"><i class="fas fa-spa"></i></span>
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($salone['nome']); ?></h1>
    </div>

    <div class="salone-nav">
        <!-- Notifiche -->
        <div class="dropdown">
            <button class="header-btn">
                <i class="fas fa-bell"></i>
                <?php
                // Conta notifiche non lette
                $conn = connectDB();
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifiche WHERE salone_id = ? AND letto = 0");
                $stmt->bind_param("i", $salone_id);
                $stmt->execute();
                $notifiche_count = $stmt->get_result()->fetch_assoc()['count'];
                $conn->close();

                if ($notifiche_count > 0):
                    ?>
                    <span class="badge"><?php echo $notifiche_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-content">
                <h3>Notifiche</h3>
                <?php
                $conn = connectDB();
                $stmt = $conn->prepare("
                    SELECT * FROM notifiche 
                    WHERE salone_id = ? 
                    ORDER BY letto ASC, data_creazione DESC 
                    LIMIT 5
                ");
                $stmt->bind_param("i", $salone_id);
                $stmt->execute();
                $notifiche = $stmt->get_result();
                $conn->close();

                if ($notifiche->num_rows > 0):
                    while($notifica = $notifiche->fetch_assoc()):
                        ?>
                        <div class="notifica-item <?php echo $notifica['letto'] ? '' : 'non-letto'; ?>">
                            <div class="notifica-icon">
                                <?php
                                $icon = 'fas fa-info-circle';
                                switch($notifica['tipo']) {
                                    case 'appuntamento': $icon = 'fas fa-calendar-plus'; break;
                                    case 'modifica': $icon = 'fas fa-edit'; break;
                                    case 'cancellazione': $icon = 'fas fa-calendar-times'; break;
                                    case 'promemoria': $icon = 'fas fa-clock'; break;
                                    case 'compleanno': $icon = 'fas fa-birthday-cake'; break;
                                    case 'offerta': $icon = 'fas fa-tag'; break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <div class="notifica-content">
                                <p><?php echo htmlspecialchars($notifica['messaggio']); ?></p>
                                <small><?php echo date('d/m/Y H:i', strtotime($notifica['data_creazione'])); ?></small>
                            </div>
                            <?php if (!$notifica['letto']): ?>
                                <a href="mark_read.php?id=<?php echo $notifica['id']; ?>" class="mark-read">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php
                    endwhile;
                else:
                    ?>
                    <p class="empty-list">Nessuna notifica</p>
                <?php endif; ?>

                <a href="notifiche.php" class="btn-small">Vedi tutte</a>
            </div>
        </div>

        <!-- Menu utente -->
        <div class="dropdown">
            <button class="header-btn">
                <i class="fas fa-user-circle"></i>
                <span class="user-name"><?php echo $_SESSION['salone_name']; ?></span>
            </button>
            <div class="dropdown-content">
                <a href="profilo.php"><i class="fas fa-user"></i> Profilo</a>
                <a href="impostazioni.php"><i class="fas fa-cog"></i> Impostazioni</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>
