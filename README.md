# Portale Staff Amichiamoci

[![Docker image build and push](https://github.com/Amichiamoci/Staff/actions/workflows/docker.yml/badge.svg)](https://github.com/Amichiamoci/Staff/actions/workflows/docker.yml)

[![FTP deploy on push](https://github.com/Amichiamoci/Staff/actions/workflows/ftp-deploy.yml/badge.svg)](https://github.com/Amichiamoci/Staff/actions/workflows/ftp-deploy.yml)

Applicativo per la gestione di iscrizioni, partite e tornei per la 
[Manifestazione](https://www.amichiamoci.it).

## Configurazione
Impostare le seguenti variabili d'ambiente:

- `MYSQL_USER`: obbligatoria
- `MYSQL_PASSWORD`: opzionale
- `MYSQL_DB`: obbligatoria
- `MYSQL_HOST`: obbligatoria
- `MYSQL_PORT`: opzionale, default `3306`
- `ADMIN_USERNAME`: opzionale, ha effetto solo al primo avvio
- `ADMIN_PASSWORD`: opzionale, ha effetto solo al primo avvio
- `SITE_NAME`: opzionale, default `Amichiamoci`
- `RECAPTCHA_PUBLIC_KEY`: opzionale, chiavi Recaptcha v3 per proteggere la agina di login
- `RECAPTCHA_SECRET_KEY`: opzionale
- `DOMAIN`: opzionale, l'host al quale sarà accessibile il portale
- `POWERED_BY`: opzionale, link che compare nel footer, default <https://github.com/Amichiamoci/Staff>
- `MAIL_OUTPUT_ADDRESS`: opzionale, se non impostato non sarà possibile inviare email
- `SMTP_HOST`: opzionale, se non impostato non sarà possibile inviare email
- `SMTP_PORT`: opzionale, default `25`
- `SMTP_USER`: opzionale, se non impostato non sarà possibile inviare email
- `SMTP_PASSWORD`: opzionale


## Installazione con Docker
```bash
docker pull ghcr.io/amichiamoci/staff:latest
```

Avviare il contenitore e impostare le variabili d'ambiente allo stesso. all'avvio genererà il databse se non ne trova uno (il controllo è effettuato sull'esistenza di almeno un utente, non cancellarli mai tutti).

### Porte e utenti
Il contenitore espone solo la porta `80`, ed il processo principale è eseguito dall'utente `www-data`

### Volumi
Un solo volume è richiesto, e va montato a `/var/www/html/Uploads`. In esso sarà aggiunto un file `.htaccess` che farà in modo che i file non siano accessibili senza login: non cancellarlo!

### Segreti e dati sensibili
I docker secrets sono supportati e consigliati per le variabili più importanti (come password o token)

### Costruire l'immagine in locale
Effettuare un pull della repo:
```bash
git clone https://github.com/Amichiamoci/Staff && cd ./Staff
```
Costrure l'immagine
```bash
docker build . --tag 'amichiamoci-staff'
```

## Installazione su Apache (sconsigliata)
Assicurarsi di avere [composer](https://getcomposer.org "Vai al sito") installato sul proprio sistema.

Caricare le librerie con
```bash
composer update --no-interaction --no-progress
```
Impostare le variabili d'ambiente nel file `.env` (nella cartella principale dell'applicativo)

Generare il file `.sql` per l'inizializzazione del database con
```bash
chmod +x ./build-starting-db.sh
```
Questo genererà un file `starting-db.tmp.sql`.

Adesso eseguire lo script per generare effettivamente il database
```bash
php ./build-database.php
```
Lo script cercherà di lanciare `mariadb` (MariaDB client per comunicare col server), se non si dispone di tale pacchetto o non si dispone dei permessi sarà necessario eseguire il file `starting-db.tmp.sql` a mano. Fare ciò non genererà il primo utente (`admin`): anche quello andrà aggiunto a mano.