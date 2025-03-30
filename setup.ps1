# Crea le cartelle principali
New-Item -ItemType Directory -Path "config"
New-Item -ItemType Directory -Path "includes"
New-Item -ItemType Directory -Path "assets\css"
New-Item -ItemType Directory -Path "assets\js"
New-Item -ItemType Directory -Path "assets\img\logos"
New-Item -ItemType Directory -Path "assets\img\staff"
New-Item -ItemType Directory -Path "assets\img\users"
New-Item -ItemType Directory -Path "uploads"
New-Item -ItemType Directory -Path "salon"
New-Item -ItemType Directory -Path "client"
New-Item -ItemType Directory -Path "api\salon"
New-Item -ItemType Directory -Path "api\client"
New-Item -ItemType Directory -Path "templates\salon"
New-Item -ItemType Directory -Path "templates\client"

# Crea i file all'interno delle cartelle
# Config
New-Item -ItemType File -Path "config\config.php"
New-Item -ItemType File -Path "config\db.php"

# Includes
New-Item -ItemType File -Path "includes\functions.php"
New-Item -ItemType File -Path "includes\auth.php"
New-Item -ItemType File -Path "includes\validation.php"
New-Item -ItemType File -Path "includes\notifications.php"

# Assets CSS
New-Item -ItemType File -Path "assets\css\style.css"
New-Item -ItemType File -Path "assets\css\salon.css"
New-Item -ItemType File -Path "assets\css\client.css"

# Assets JS
New-Item -ItemType File -Path "assets\js\main.js"
New-Item -ItemType File -Path "assets\js\booking.js"
New-Item -ItemType File -Path "assets\js\salon-dashboard.js"
New-Item -ItemType File -Path "assets\js\calendar.js"

# Salon area
New-Item -ItemType File -Path "salon\index.php"
New-Item -ItemType File -Path "salon\login.php"
New-Item -ItemType File -Path "salon\register.php"
New-Item -ItemType File -Path "salon\services.php"
New-Item -ItemType File -Path "salon\staff.php"
New-Item -ItemType File -Path "salon\schedule.php"
New-Item -ItemType File -Path "salon\appointments.php"
New-Item -ItemType File -Path "salon\settings.php"

# Client area
New-Item -ItemType File -Path "client\index.php"
New-Item -ItemType File -Path "client\login.php"
New-Item -ItemType File -Path "client\register.php"
New-Item -ItemType File -Path "client\profile.php"
New-Item -ItemType File -Path "client\booking.php"
New-Item -ItemType File -Path "client\appointments.php"

# API - Salon
New-Item -ItemType File -Path "api\salon\services.php"
New-Item -ItemType File -Path "api\salon\staff.php"
New-Item -ItemType File -Path "api\salon\appointments.php"

# API - Client
New-Item -ItemType File -Path "api\client\salons.php"
New-Item -ItemType File -Path "api\client\availability.php"
New-Item -ItemType File -Path "api\client\booking.php"

# Templates - Salon
New-Item -ItemType File -Path "templates\salon\header.php"
New-Item -ItemType File -Path "templates\salon\footer.php"

# Templates - Client
New-Item -ItemType File -Path "templates\client\header.php"
New-Item -ItemType File -Path "templates\client\footer.php"

# Root files
New-Item -ItemType File -Path "index.php"
New-Item -ItemType File -Path ".htaccess"
