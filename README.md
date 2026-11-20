# Portale Staff Amichiamoci

[![Build Docker](https://github.com/Amichiamoci/Staff/actions/workflows/docker.yml/badge.svg)](https://github.com/Amichiamoci/Staff/actions/workflows/docker.yml)
[![Deploy FTP](https://github.com/Amichiamoci/Staff/actions/workflows/ftp-deploy.yml/badge.svg)](https://github.com/Amichiamoci/Staff/actions/workflows/ftp-deploy.yml)
[![Analisi PHP](https://github.com/Amichiamoci/Staff/actions/workflows/phpmd.yml/badge.svg)](https://github.com/Amichiamoci/Staff/actions/workflows/phpmd.yml)
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](composer.json)

Portale riservato allo staff della manifestazione [Amichiamoci](https://www.amichiamoci.it) per gestire iscrizioni, squadre, tornei e partite.

## Avvio con Docker

1. Crea un database MariaDB vuoto con codifica `utf8mb4` e un utente dedicato. L'utente deve poter creare tabelle, viste e routine nel database. Se MariaDB è in un altro container, collega i due container alla stessa rete Docker e usa il nome del servizio come `MYSQL_HOST`.
2. Prepara un file locale `.env` con i valori della tabella qui sotto. Limita i permessi del file con `chmod 600 .env`.
3. Scarica l'immagine e avvia il portale. Nell'esempio MariaDB è già raggiungibile dalla rete `amichiamoci`.

   ```sh
   docker pull amichiamoci/staff
   docker run -d --name amichiamoci-staff --network amichiamoci \
     --env-file .env --publish 127.0.0.1:8080:8080 \
     --volume amichiamoci-data:/amichiamoci/data amichiamoci/staff
   ```

4. Apri <http://localhost:8080> e accedi con l'account configurato tramite `ADMIN_USERNAME` e `ADMIN_PASSWORD`. Se il servizio è destinato all'uso in rete, pubblicalo tramite un reverse proxy HTTPS che conservi l'host pubblico e imposta `SESSION_COOKIE_SECURE=1`.

Il primo avvio importa lo schema solo in un database vuoto. Il container avvia il cron interno per le attività pianificate. Per installazioni esistenti esegui un backup del database e del volume dati prima dell'aggiornamento. Se un'importazione si interrompe, verifica lo stato dello schema prima di ripetere l'avvio.

### Configurazione

| Variabile | Uso |
| --- | --- |
| `MYSQL_DB` | Nome del database. |
| `MYSQL_HOST`, `MYSQL_PORT` | Host MariaDB e porta. Valori predefiniti `localhost` e `3306`. |
| `MYSQL_USER`, `MYSQL_PASSWORD` | Credenziali MariaDB. Il nome utente predefinito è `amichiamoci`. |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD` | Credenziali del primo amministratore. Obbligatorie per inizializzare il database. |
| `SITE_NAME`, `MAIN_SITE_URL`, `DOMAIN` | Nome del portale, URL del sito pubblico e host pubblico. Configura `DOMAIN` per generare i link di recupero password. |
| `CKEDITOR_LICENSE_KEY` | Chiave CKEditor 5 Cloud CDN per abilitare l’editor di testo ricco in `/email/send`. Senza chiave il modulo usa il campo HTML. |
| `RECAPTCHA_PUBLIC_KEY`, `RECAPTCHA_SECRET_KEY` | Coppia di chiavi per abilitare la verifica reCAPTCHA al login. |
| `MAIL_OUTPUT_ADDRESS`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASSWORD` | Configurazione della posta in uscita. La porta predefinita è `25`. La porta `465` usa TLS implicito. Sulle altre porte, la posta autenticata richiede STARTTLS. |
| `CRON_CAPTURE_OUTGOING_ADDRESS` | Destinatario di prova per le email inviate dal cron. |
| `SESSION_COOKIE_SECURE` | Imposta `1` quando TLS termina sul reverse proxy. |
| `ENABLE_API` | Imposta `1` per abilitare le route API. |
| `FILE_MAX_SIZE` | Limite applicativo per file in byte, predefinito a 10 MiB. |

Conserva password e token in un gestore di segreti o in un file locale protetto. Le variabili possono essere fornite anche tramite i corrispondenti file Docker secrets in `/run/secrets/`, impostando una variabile come `MYSQL_PASSWORD_FILE=/run/secrets/mysql_password`. I backup del volume contengono dati personali e richiedono accesso ristretto.

Per costruire l'immagine dalla copia locale usa `docker build -t amichiamoci/staff:local .` e passa quel tag a `docker run`.

## Installazione con Apache

1. Installa PHP 8.4 o successivo con `mysqli`, `gd`, `mbstring` e `fileinfo`, Composer 2 e il client `mariadb`. Usa `composer check-platform-reqs` per verificare l'ambiente.
2. Esegui `composer install --no-interaction --prefer-dist --optimize-autoloader`, dalla directory del progetto.
3. Configura un virtual host HTTPS. Attiva `mod_rewrite` e applica le regole di `docker/apache.htaccess` nella directory del progetto. Se usi un file `.htaccess`, consenti le direttive necessarie tramite `AllowOverride`. Usa un formato di access log che escluda le query string.
4. Crea `data/documenti`, `data/certificati`, `data/tmp/cron` e `data/log`, scrivibili dall'utente del servizio PHP.
5. Prepara il database e il file `.env`, poi esegui `php script/build-database.php`. Pianifica `php /percorso/del/progetto/script/cron.php` tramite il cron di sistema con l'utente del servizio PHP.

Per respingere le richieste ai percorsi della lista di blocco prima della ricerca sul filesystem, includi `docker/apache-early.conf` nel virtual host dedicato con il portale come `DocumentRoot`. Su hosting condiviso, `.htaccess` restituisce lo stesso 404 senza avviare PHP, dopo che Apache ha cercato il file di configurazione. Le richieste alle directory degli asset possono richiedere una verifica del file sul disco. Aggiungi `env=!staff_rejected` al `CustomLog` del virtual host per escludere dal log di accesso i 404 prodotti dalle regole di riscrittura. Nginx esclude già i propri 404 dal log di accesso.

I server web bloccano le richieste a file nascosti, percorsi PHP diretti, WordPress e pannelli di controllo. Il router PHP gestisce gli altri URL applicativi e restituisce 404 se il percorso non esiste. Le nuove azioni PHP sono disponibili senza modifiche alla configurazione del server web.

La gestione dei tornei e dei risultati, la modifica delle anagrafiche collegate agli account staff e la modifica o cancellazione delle iscrizioni esistenti richiedono un amministratore.

## Verifiche locali

```sh
composer validate
composer audit --locked
php tests/file-paths.php
php tests/routing.php
php tests/reset-token.php
```

I test PHP usano directory temporanee e dati sintetici. Per una prova end-to-end, avvia il portale con un database di prova e verifica accesso, caricamento e download, invio dei moduli e aggiornamento di una partita.

Il bundle locale [CodiceFiscale.js 2.4.0](https://github.com/lucavandro/CodiceFiscaleJS) è distribuito con [licenza CC BY-SA 4.0](Public/js/codicefiscale.LICENSE.txt).
