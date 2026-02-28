# Setup Gestionale Eventi

## Requisiti
- PHP 8.3+ con estensioni standard abilitate
- Server web (Apache/Nginx) oppure PHP built-in server

## Avvio rapido (PHP built-in)
```bash
php -S localhost:8000
```
Poi apri `http://localhost:8000/index.php`.

## Credenziali
- Username: `admin`
- Password: `admin123`

## Note
- Tutti i file sono nella root del progetto.
- Gli upload vengono salvati in `events/{event_id}/uploads/`.
- I file JSON sono modificati con lock per evitare corruzioni.
