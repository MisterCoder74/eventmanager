# Gestionale Eventi Multi-Scopo

Gestionale eventi completo in PHP vanilla con persistenza su file JSON. Tutti i file sono nella root del progetto (flat structure), con unica sottocartella `events/` dedicata agli upload per evento.

## Funzionalità principali
- **Autenticazione**: Login con protezione anti-brute force (5 tentativi = lockout 5 min)
- **Dashboard**: Statistiche e promemoria task in scadenza
- **Gestione Eventi**: CRUD completo con dettagli, stato, note
- **Gestione Clienti**: Anagrafica clienti completa
- **Gestione Fornitori**: Anagrafica fornitori con servizi offerti
- **Gestione Task**: Task con priorità, stato, scadenza e filtri
- **Servizi per Evento**: Associazione servizi con stati (pending, confirmed, paid, cancelled)
- **Budget Duplice**:
  - Budget Cliente: limite massimo imposto dal cliente
  - Budget Preventivo: calcolato automaticamente da servizi confermati/pagati
  - Alert soglia configurabile (default 80%) con colori (verde < 80%, giallo 80-90%, rosso > 90%)
  - Progress bar visuale
- **Upload Documenti**: Documenti per evento salvati in `events/{event_id}/uploads/`
- **Export**: Export dati in CSV/JSON/Excel

## Struttura
- **Flat structure**: Tutti i file PHP/JS/CSS/JSON sono nella root
- **Sola sottocartella**: `events/{event_id}/uploads/` per i documenti degli eventi
- **Percorsi folder-agnostic**: Tutti i path PHP usano `__DIR__`

## Tecnologie
- PHP 8.3+ vanilla
- HTML5 + CSS3
- Bootstrap 5.3 via CDN
- JavaScript ES6 vanilla
- Persistenza JSON con file locking

## Accesso demo
- Username: `admin`
- Password: `admin123`

## Pagina
Le pagine principali sono:
- `index.php` - Login
- `dashboard.php` - Dashboard con statistiche
- `eventi.php` - Elenco e gestione eventi
- `event-detail.php` - Dettaglio evento con tab (Dettagli, Servizi & Budget, Task, Documenti)
- `clienti.php` - Gestione clienti
- `fornitori.php` - Gestione fornitori e servizi
- `task.php` - Gestione task completa con filtri

Consulta `SETUP.md` per le istruzioni di avvio.
