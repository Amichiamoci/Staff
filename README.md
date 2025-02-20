# Portale Staff Amichiamoci
Applicativo per la gestione di iscrizioni, partite e tornei per la 
[Manifestazione](https://www.amichiamoci.it).

## Configurazione
Impostare le seguenti variabili d'ambiente:

- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_DB`
- `MYSQL_HOST`
- `ADMIN_USERNAME`
- `ADMIN_PASSWORD`
- `SITE_NAME`: opzionale, (_Amichiamoci_ come default)

## Installazione con Docker
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