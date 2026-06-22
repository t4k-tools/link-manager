<?php
declare(strict_types=1);

/**
 * @file link/1/visibility.php
 * @layer Security
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Modello di visibilita' dei link e regole di autorizzazione (ADR-002).
 *   Funzioni pure, indipendenti da sessione e HTTP.
 */

const VISIBILITA_VALIDE = ['pubblico', 'condiviso', 'privato'];

/**
 * Etichetta leggibile per uno stato di visibilita'.
 */
function visibilita_label(string $v): string
{
    return match ($v) {
        'pubblico'  => 'Pubblico',
        'condiviso' => 'Condiviso',
        'privato'   => 'Privato',
        default     => $v,
    };
}

/**
 * Normalizza un valore di visibilita' proveniente da input utente.
 * Ritorna 'privato' (default sicuro) se il valore non e' ammesso.
 */
function visibilita_normalizza(mixed $v): string
{
    return (is_string($v) && in_array($v, VISIBILITA_VALIDE, true)) ? $v : 'privato';
}

/**
 * Frammento SQL e parametri per filtrare i link visibili a un osservatore.
 * admin: nessun filtro; user: propri link oppure link 'pubblico'/'condiviso'.
 *
 * @return array{0:string,1:array<int,int>}
 */
function visibilita_filtro_sql(?int $userId, string $role, string $alias = 'l'): array
{
    if ($role === 'admin') {
        return ['1=1', []];
    }
    return [
        "({$alias}.user_id = ? OR {$alias}.visibilita IN ('pubblico','condiviso'))",
        [(int) $userId],
    ];
}

/**
 * Indica se l'osservatore puo' modificare/eliminare un link di un dato proprietario.
 */
function puo_gestire_link(int $ownerId, ?int $userId, string $role): bool
{
    return $role === 'admin' || ($userId !== null && $ownerId === $userId);
}
