# Gestionale Eventi Multi-Scopo

Gestionale eventi completo in PHP vanilla con persistenza su file JSON. Tutti i file sono nella root del progetto (flat structure), con unica sottocartella `events/` dedicata agli upload per evento.

## Funzionalità principali
- Login con protezione anti-brute force (admin / admin123)
- Dashboard con statistiche e promemoria task
- Gestione eventi, clienti, fornitori
- Task e budget per evento
- Upload documenti per evento
- Export CSV/JSON/Excel

## Struttura
Tutti i file PHP/JS/CSS/JSON sono nella root. L'unica sottocartella è `events/{event_id}/uploads/`.

## Tecnologie
- PHP 8.3+
- Bootstrap 5.3 via CDN
- JavaScript ES6
- Persistenza JSON con file locking

## Accesso demo
- Username: `admin`
- Password: `admin123`

Consulta `SETUP.md` per le istruzioni di avvio.
