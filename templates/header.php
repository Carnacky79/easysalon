<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : APP_NAME; ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/favicon.ico" type="image/x-icon">

    <!-- Font Awesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- CSS principale -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">

    <!-- CSS specifico per area client o salon se necessario -->
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/<?php echo $extraCss; ?>.css">
    <?php endif; ?>

    <!-- jQuery (slim version) -->
    <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
</head>
<body>
<!-- Header -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo APP_URL; ?>/">
                    <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="<?php echo APP_NAME; ?>">
                </a>
            </div>

            <!-- Menu principale -->
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="<?php echo APP_URL; ?>/">Home</a></li>
                    <li><a href="<?php echo APP_URL; ?>/search.php">Cerca Saloni</a></li>
                    <li><a href="<?php echo APP_URL; ?>/how-it-works.php">Come Funziona</a></li>
                    <li><a href="<?php echo APP_URL; ?>/contact.php">Contatti</a></li>
                </ul>
            </nav>

            <!-- Menu utente -->
            <div class="user-menu">
                <?php if (isLoggedIn()): ?>
                    <?php if (getCurrentUserType() === USER_TYPE_CLIENT): ?>
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo APP_URL; ?>/client/index.php">Dashboard</a></li>
                                <li><a href="<?php echo APP_URL; ?>/client/appointments.php">I miei Appuntamenti</a></li>
                                <li><a href="<?php echo APP_URL; ?>/client/profile.php">Profilo</a></li>
                                <li><a href="<?php echo APP_URL; ?>/client/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php elseif (getCurrentUserType() === USER_TYPE_SALON): ?>
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo APP_URL; ?>/salon/index.php">Dashboard</a></li>
                                <li><a href="<?php echo APP_URL; ?>/salon/appointments.php">Appuntamenti</a></li>
                                <li><a href="<?php echo APP_URL; ?>/salon/services.php">Servizi</a></li>
                                <li><a href="<?php echo APP_URL; ?>/salon/staff.php">Staff</a></li>
                                <li><a href="<?php echo APP_URL; ?>/salon/settings.php">Impostazioni</a></li>
                                <li><a href="<?php echo APP_URL; ?>/salon/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/client/login.php" class="btn btn-sm btn-primary">Accedi</a>
                    <a href="<?php echo APP_URL; ?>/client/register.php" class="btn btn-sm btn-outline">Registrati</a>
                <?php endif; ?>
            </div>

            <!-- Menu hamburger per mobile -->
            <div class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</header>

<!-- Menu mobile (visibile solo su mobile) -->
<div class="mobile-menu">
    <ul class="nav-list">
        <li><a href="<?php echo APP_URL; ?>/">Home</a></li>
        <li><a href="<?php echo APP_URL; ?>/search.php">Cerca Saloni</a></li>
        <li><a href="<?php echo APP_URL; ?>/how-it-works.php">Come Funziona</a></li>
        <li><a href="<?php echo APP_URL; ?>/contact.php">Contatti</a></li>

        <?php if (!isLoggedIn()): ?>
            <li><a href="<?php echo APP_URL; ?>/client/login.php">Accedi</a></li>
            <li><a href="<?php echo APP_URL; ?>/client/register.php">Registrati</a></li>
        <?php else: ?>
            <?php if (getCurrentUserType() === USER_TYPE_CLIENT): ?>
                <li><a href="<?php echo APP_URL; ?>/client/index.php">Dashboard</a></li>
                <li><a href="<?php echo APP_URL; ?>/client/appointments.php">I miei Appuntamenti</a></li>
                <li><a href="<?php echo APP_URL; ?>/client/profile.php">Profilo</a></li>
                <li><a href="<?php echo APP_URL; ?>/client/logout.php">Logout</a></li>
            <?php elseif (getCurrentUserType() === USER_TYPE_SALON): ?>
                <li><a href="<?php echo APP_URL; ?>/salon/index.php">Dashboard</a></li>
                <li><a href="<?php echo APP_URL; ?>/salon/appointments.php">Appuntamenti</a></li>
                <li><a href="<?php echo APP_URL; ?>/salon/services.php">Servizi</a></li>
                <li><a href="<?php echo APP_URL; ?>/salon/staff.php">Staff</a></li>
                <li><a href="<?php echo APP_URL; ?>/salon/settings.php">Impostazioni</a></li>
                <li><a href="<?php echo APP_URL; ?>/salon/logout.php">Logout</a></li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>
</div>

<!-- Messaggi Flash -->
<?php
$flashMessage = getFlashMessage();
if ($flashMessage):
    ?>
    <div class="flash-message flash-<?php echo $flashMessage['type']; ?>">
        <div class="container">
            <p><?php echo htmlspecialchars($flashMessage['message']); ?></p>
            <button class="close-flash"><i class="fas fa-times"></i></button>
        </div>
    </div>
<?php endif; ?>

<!-- Contenuto principale -->
<main>
