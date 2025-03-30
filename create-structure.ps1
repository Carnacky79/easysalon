# Script PowerShell per creare la struttura del progetto BeautyBook
# Da eseguire dall'interno della cartella beautybook già esistente

# Crea i file PHP nella radice
$rootFiles = @(
    "index.php",
    "login.php",
    "logout.php",
    "register.php",
    "register_salone.php",
    "reset_password.php",
    "dashboard.php",
    "prenota.php",
    "appuntamenti.php",
    "profilo.php",
    "ricerca.php",
    "config.php"
)

foreach ($file in $rootFiles) {
    New-Item -Path $file -ItemType File -Force | Out-Null
}

# Crea la cartella api e i suoi file
New-Item -Path "api" -ItemType Directory | Out-Null
$apiFiles = @(
    "orari_disponibili.php",
    "servizi.php",
    "operatori.php",
    "prenotazione.php"
)

foreach ($file in $apiFiles) {
    New-Item -Path "api\$file" -ItemType File -Force | Out-Null
}

# Crea la cartella css e i suoi file
New-Item -Path "css" -ItemType Directory | Out-Null
$cssFiles = @(
    "style.css",
    "salone.css",
    "responsive.css"
)

foreach ($file in $cssFiles) {
    New-Item -Path "css\$file" -ItemType File -Force | Out-Null
}

# Crea la cartella js e i suoi file
New-Item -Path "js" -ItemType Directory | Out-Null
$jsFiles = @(
    "jquery.min.js",
    "script.js",
    "calendario.js",
    "prenotazione.js"
)

foreach ($file in $jsFiles) {
    New-Item -Path "js\$file" -ItemType File -Force | Out-Null
}

# Crea la cartella img e le sue sottocartelle
New-Item -Path "img" -ItemType Directory | Out-Null
New-Item -Path "img\servizi" -ItemType Directory | Out-Null
$imgFiles = @(
    "logo.svg",
    "user-icon.svg",
    "salon-icon.svg"
)

foreach ($file in $imgFiles) {
    New-Item -Path "img\$file" -ItemType File -Force | Out-Null
}

# Crea la cartella uploads e le sue sottocartelle
New-Item -Path "uploads" -ItemType Directory | Out-Null
New-Item -Path "uploads\saloni" -ItemType Directory | Out-Null
New-Item -Path "uploads\operatori" -ItemType Directory | Out-Null
New-Item -Path "uploads\utenti" -ItemType Directory | Out-Null

# Crea la cartella salone e i suoi file
New-Item -Path "salone" -ItemType Directory | Out-Null
$saloneFiles = @(
    "dashboard.php",
    "calendario.php",
    "appuntamenti.php",
    "appuntamenti_dettaglio.php",
    "appuntamenti_nuovo.php",
    "appuntamenti_modifica.php",
    "servizi.php",
    "servizio_nuovo.php",
    "servizio_modifica.php",
    "servizio_operatori.php",
    "categorie_servizi.php",
    "categoria_nuova.php",
    "categoria_modifica.php",
    "operatori.php",
    "operatore_nuovo.php",
    "operatore_modifica.php",
    "postazioni.php",
    "postazione_nuova.php",
    "postazione_modifica.php",
    "clienti.php",
    "cliente_nuovo.php",
    "cliente_modifica.php",
    "schede_cliente.php",
    "scheda_cliente.php",
    "scheda_cliente_nuova.php",
    "scheda_cliente_modifica.php",
    "prodotti.php",
    "prodotto_nuovo.php",
    "prodotto_modifica.php",
    "vendite.php",
    "vendita_nuova.php",
    "vendita_dettaglio.php",
    "fidelity.php",
    "fidelity_nuova.php",
    "fidelity_movimenti.php",
    "promozioni.php",
    "promozione_nuova.php",
    "promozione_modifica.php",
    "giftcard.php",
    "giftcard_nuova.php",
    "giftcard_dettaglio.php",
    "orari.php",
    "chiusure.php",
    "notifiche.php",
    "mark_read.php",
    "report.php",
    "profilo.php",
    "impostazioni.php",
    "messaggi.php"
)

foreach ($file in $saloneFiles) {
    New-Item -Path "salone\$file" -ItemType File -Force | Out-Null
}

# Crea la sottocartella includes di salone e i suoi file
New-Item -Path "salone\includes" -ItemType Directory | Out-Null
$saloneIncludesFiles = @(
    "header.php",
    "sidebar.php",
    "footer.php",
    "functions.php"
)

foreach ($file in $saloneIncludesFiles) {
    New-Item -Path "salone\includes\$file" -ItemType File -Force | Out-Null
}

# Crea la cartella includes e i suoi file
New-Item -Path "includes" -ItemType Directory | Out-Null
$includesFiles = @(
    "header.php",
    "footer.php",
    "sidebar.php",
    "functions.php"
)

foreach ($file in $includesFiles) {
    New-Item -Path "includes\$file" -ItemType File -Force | Out-Null
}

Write-Host "La struttura del progetto BeautyBook è stata creata con successo!" -ForegroundColor Green
