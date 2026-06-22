<?php
declare(strict_types=1);

/**
 * @file link/1/helpers.php
 * @layer Support
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Helper di supporto condivisi. Per ora l'escape dell'output HTML.
 */

/**
 * Escape sicuro per output HTML.
 */
function e(?string $valore): string
{
    return htmlspecialchars((string) $valore, ENT_QUOTES, 'UTF-8');
}

/**
 * Normalizza una stringa di tag inserita dall'utente in forma canonica.
 * Esempio: " PHP, sqlite , php " -> ",php,sqlite,". Vuoto -> "".
 * Il formato con virgole iniziali/finali permette ricerche precise per tag.
 */
function tags_normalize(?string $input): string
{
    $parts = array_map(
        static fn(string $t): string => strtolower(trim($t)),
        explode(',', (string) $input)
    );
    $parts = array_values(array_unique(array_filter($parts, static fn(string $t): bool => $t !== '')));
    return $parts ? ',' . implode(',', $parts) . ',' : '';
}

/**
 * Converte il valore tags memorizzato in un elenco di tag per la visualizzazione.
 *
 * @return array<int, string>
 */
function tags_to_array(?string $tags): array
{
    $t = trim((string) $tags, ',');
    return $t === '' ? [] : explode(',', $t);
}
