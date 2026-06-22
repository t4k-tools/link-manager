<?php
declare(strict_types=1);

/**
 * @file database/import_from_mysql_dump.php
 * @layer Tooling
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Migrazione fedele dei dati delle sole tabelle link_users e link_links
 *   (perimetro ADR-001) dal dump MySQL a un database SQLite dedicato.
 *   Esegue: ricrea il file SQLite, applica lo schema, importa i dati,
 *   verifica conteggi e integrita' delle foreign key.
 *
 * USO (CLI):
 *   php database/import_from_mysql_dump.php [percorso_dump.sql]
 *   (senza argomento usa il primo file db/*.sql trovato)
 *
 * CRITICITA':
 *   - Strumento di migrazione, non codice applicativo.
 *   - Ricrea da zero il file SQLite di destinazione a ogni esecuzione.
 */

$root      = dirname(__DIR__);
$schemaSql = $root . '/database/schema_sqlite.sql';
$sqlitePath = $root . '/storage/link_manager.sqlite';

// Percorso del dump: da argomento CLI, altrimenti primo db/*.sql disponibile.
$dumpPath = $argv[1] ?? null;
if ($dumpPath === null) {
    $candidati = glob($root . '/db/*.sql') ?: [];
    $dumpPath = $candidati[0] ?? ($root . '/db/dump.sql');
}

// --- Tokenizer: estrae le righe (tuple) dalla clausola VALUES MySQL ---
// Gestisce stringhe quotate con escape MySQL (\' \" \\ \n \r \t \0) e '' doppio.
function estraiRighe(string $sql, string $tabella): array
{
    $needle = 'INSERT INTO `' . $tabella . '`';
    $pos = strpos($sql, $needle);
    if ($pos === false) {
        return [];
    }
    $valuesPos = stripos($sql, 'VALUES', $pos);
    if ($valuesPos === false) {
        return [];
    }
    $i = $valuesPos + 6;
    $n = strlen($sql);
    $ws = " \t\r\n";
    $righe = [];

    while ($i < $n) {
        while ($i < $n && (strpos($ws, $sql[$i]) !== false || $sql[$i] === ',')) {
            $i++;
        }
        if ($i >= $n || $sql[$i] === ';') {
            break;
        }
        if ($sql[$i] !== '(') {
            break;
        }
        $i++; // consuma '('
        $riga = [];

        while (true) {
            while ($i < $n && strpos($ws, $sql[$i]) !== false) {
                $i++;
            }
            $c = $sql[$i];

            if ($c === "'") {
                $i++;
                $buf = '';
                while ($i < $n) {
                    $ch = $sql[$i];
                    if ($ch === '\\') {
                        $next = $sql[$i + 1] ?? '';
                        $map = [
                            'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0",
                            '\\' => '\\', "'" => "'", '"' => '"', 'Z' => "\x1a", 'b' => "\x08",
                        ];
                        $buf .= $map[$next] ?? $next;
                        $i += 2;
                    } elseif ($ch === "'") {
                        if (($sql[$i + 1] ?? '') === "'") {
                            $buf .= "'";
                            $i += 2;
                        } else {
                            $i++;
                            break;
                        }
                    } else {
                        $buf .= $ch;
                        $i++;
                    }
                }
                $riga[] = $buf;
            } elseif (strtoupper(substr($sql, $i, 4)) === 'NULL') {
                $riga[] = null;
                $i += 4;
            } else {
                $num = '';
                while ($i < $n && strpos(",)" . $ws, $sql[$i]) === false) {
                    $num .= $sql[$i];
                    $i++;
                }
                $riga[] = $num;
            }

            while ($i < $n && strpos($ws, $sql[$i]) !== false) {
                $i++;
            }
            if ($sql[$i] === ',') {
                $i++;
                continue;
            }
            if ($sql[$i] === ')') {
                $i++;
                break;
            }
        }
        $righe[] = $riga;
    }

    return $righe;
}

// --- Avvio ---
if (!is_file($dumpPath)) {
    fwrite(STDERR, "Dump non trovato: $dumpPath\n");
    exit(1);
}
if (!is_file($schemaSql)) {
    fwrite(STDERR, "Schema non trovato: $schemaSql\n");
    exit(1);
}

$dump = file_get_contents($dumpPath);

// Ricrea da zero il file SQLite e gli eventuali file WAL/SHM.
foreach ([$sqlitePath, $sqlitePath . '-wal', $sqlitePath . '-shm'] as $f) {
    if (is_file($f)) {
        unlink($f);
    }
}

$pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec('PRAGMA foreign_keys = ON;');
$pdo->exec(file_get_contents($schemaSql));

$utenti = estraiRighe($dump, 'link_users');
$link   = estraiRighe($dump, 'link_links');

$pdo->beginTransaction();

$insUser = $pdo->prepare(
    'INSERT INTO link_users (id, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
);
foreach ($utenti as $r) {
    $insUser->execute([
        (int) $r[0],
        (string) $r[1],
        (string) $r[2],
        (string) $r[3],
        (string) $r[4],
    ]);
}

$insLink = $pdo->prepare(
    'INSERT INTO link_links (id, user_id, data, link, tipo, descrizione, visibilita, clicks)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
foreach ($link as $r) {
    // Mapping ADR-002: pubblico=1 -> 'pubblico'; pubblico=0 -> 'privato'.
    $visibilita = ((int) $r[6] === 1) ? 'pubblico' : 'privato';
    $insLink->execute([
        (int) $r[0],
        (int) $r[1],
        $r[2],                       // data: TEXT o NULL
        (string) $r[3],
        (string) $r[4],
        $r[5],                       // descrizione: TEXT o NULL
        $visibilita,
        (int) $r[7],
    ]);
}

$pdo->commit();

// --- Verifiche ---
$nUtentiSrc = count($utenti);
$nLinkSrc   = count($link);
$nUtentiDst = (int) $pdo->query('SELECT COUNT(*) FROM link_users')->fetchColumn();
$nLinkDst   = (int) $pdo->query('SELECT COUNT(*) FROM link_links')->fetchColumn();
$fkErr      = $pdo->query('PRAGMA foreign_key_check')->fetchAll();
$integrity  = $pdo->query('PRAGMA integrity_check')->fetchColumn();

echo "Import completato in: $sqlitePath\n";
echo "link_users : dump=$nUtentiSrc  sqlite=$nUtentiDst\n";
echo "link_links : dump=$nLinkSrc  sqlite=$nLinkDst\n";
echo 'foreign_key_check: ' . (count($fkErr) === 0 ? 'OK (nessun orfano)' : 'ERRORI: ' . count($fkErr)) . "\n";
echo "integrity_check: $integrity\n";

if ($nUtentiSrc !== $nUtentiDst || $nLinkSrc !== $nLinkDst || count($fkErr) !== 0 || $integrity !== 'ok') {
    fwrite(STDERR, "VERIFICA FALLITA\n");
    exit(1);
}
echo "VERIFICA OK\n";
