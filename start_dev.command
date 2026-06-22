#!/usr/bin/env bash
# start_dev.command
# Avvia il server PHP built-in per Link Manager su http://localhost:<PORT>
# Document root: cartella interna ./link (unita' applicativa, vedi ADR-001).
# Uso: doppio click oppure ./start_dev.command [porta] da terminale

set -e

DIR="$(cd "$(dirname "$0")" && pwd)"
DOCROOT="$DIR/link"
PORT="${1:-8080}"

if [ ! -f "$DOCROOT/index.php" ]; then
    echo "ERRORE: docroot non valida, manca $DOCROOT/index.php"
    exit 1
fi

if [ ! -f "$DOCROOT/1/config.local.php" ]; then
    echo "ATTENZIONE: $DOCROOT/1/config.local.php non trovato."
    echo "Senza configurazione il login restituira' un errore di connessione DB."
    echo
fi

echo "==============================================="
echo " Link Manager - Dev Server"
echo " Docroot: $DOCROOT"
echo " URL:     http://localhost:${PORT}/"
echo " Login:   http://localhost:${PORT}/login.php"
echo "==============================================="
echo "Premi Ctrl+C per fermare il server."
echo

# Lanciato dalla docroot: gli include relativi (1/connect.php, 1/session.php) si risolvono.
cd "$DOCROOT"
exec php -S "localhost:${PORT}" -t "$DOCROOT"
