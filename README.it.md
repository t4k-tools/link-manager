# Link Manager

> *Was there really a need for yet another script to keep links organized? Forgive my presumption in wanting a public version, thinking it might be useful to someone.*

Una piccola applicazione **PHP 8 + SQLite**, senza dipendenze, per raccogliere, organizzare, cercare e condividere link. Niente framework, niente Composer, niente build: copi i file, la punti a un file SQLite e parte.

🇬🇧 English version: **[README.md](README.md)**

---

## Funzionalità

- **Autenticazione** con sessioni PHP, `password_hash()`/`password_verify()`, rigenerazione dell'ID di sessione al login.
- **Ruoli**: `admin` (vede e gestisce tutto) e `user` (gestisce solo i propri link).
- **Visibilità a tre stati** per ogni link:
  - `pubblico` — visibile a tutti, anche senza login;
  - `condiviso` — visibile agli utenti autenticati;
  - `privato` — visibile solo al proprietario (e agli admin).
- **Tag**: assegnabili ai link, cliccabili per filtrare, con tag cloud e autocomplete nei form.
- **Ricerca**: testuale su descrizione e tag, più filtro per tipo e per tag.
- **Vista a card** con paginazione, sia nell'elenco interno sia nella pagina pubblica.
- **Pagina pubblica** (`my_link.php`) con i soli link pubblici e colonna tag sticky, più una pagina indice dei tag (`tags.php`).
- **Conteggio click** con redirect sicuro (solo URL `http`/`https`).
- **Export CSV** dei link visibili.
- **Sicurezza di default**: token CSRF su ogni POST con effetti, prepared statement (PDO), escape dell'output, cookie di sessione induriti, nessun segreto nel codice, errori loggati e non mostrati in produzione.

## Stack tecnico

- PHP 8.1+ (PDO, `pdo_sqlite`)
- SQLite (un solo file, tenuto fuori dalla document root)
- Bootstrap 5 + Font Awesome (via CDN)
- JavaScript vanilla (nessun framework frontend)

## Avvio rapido

```bash
git clone git@github.com:t4k-tools/link-manager.git
cd link-manager

# 1) Configurazione locale (SQLite di default)
cp link/1/config.example.php link/1/config.local.php
#    imposta 'env' => 'dev' in config.local.php per vedere gli errori in sviluppo

# 2) Crea il database dallo schema + un primo utente admin
php database/init_db.php admin "cambia-questa-password" admin@example.com

# 3) Avvia il server PHP integrato (document root = ./link)
./start_dev.command 8080
```

Apri `http://localhost:8080/login.php` e accedi con l'admin appena creato.
La pagina pubblica è `http://localhost:8080/my_link.php` (senza login).

## Configurazione

`link/1/config.local.php` (escluso da git) contiene la configurazione d'ambiente. Esempio:

```php
<?php
declare(strict_types=1);
return [
    'env' => 'prod', // 'dev' mostra gli errori a video; 'prod' li scrive solo nel log
    'db'  => [
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/../../storage/link_manager.sqlite',
    ],
];
```

È supportato anche un driver **MySQL** legacy (`'driver' => 'mysql'` con host/dbname/username/password), utile per migrare un dataset esistente.

## Struttura del progetto

```
link/                 applicazione (document root)
  1/                  config, connessione DB, sessione, CSRF, helper, visibilità
  src/                repository (UserRepository, LinkRepository) — SQL isolato
  templates/          layout condiviso (header/footer)
  css/ js/ 2/         asset
  *.php               pagine (login, dashboard, link, add/edit/delete, utenti, pubblica...)
database/             schema_sqlite.sql, init_db.php, strumento import MySQL→SQLite
storage/              il file SQLite live (escluso da git, fuori dalla document root)
```

## Note di sicurezza

- File del database e segreti **fuori** dalla document root ed esclusi da git.
- Tutte le azioni di scrittura passano da POST con token CSRF; le cancellazioni sono solo POST.
- Autorizzazione lato server su ogni azione (ruoli + proprietà), non solo pulsanti nascosti.
- In produzione impostare `env => prod` (gli errori vanno nel log, mai nel browser).

## Licenza

Rilasciato per chi potrà trovarlo utile. Senza garanzie.
