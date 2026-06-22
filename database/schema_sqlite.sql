-- schema_sqlite.sql | Versione 2.0 | Ultima modifica: 2026-06-22
-- Schema SQLite per Link Manager (perimetro ADR-001: solo link_users, link_links).
-- Visibilita' a tre stati secondo ADR-002 (sostituisce il flag legacy `pubblico`).

PRAGMA foreign_keys = ON;

-- --- Utenti ---
CREATE TABLE IF NOT EXISTS link_users (
    id            INTEGER PRIMARY KEY,
    username      TEXT NOT NULL UNIQUE,
    email         TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'user'))
);

-- --- Link ---
CREATE TABLE IF NOT EXISTS link_links (
    id          INTEGER PRIMARY KEY,
    user_id     INTEGER NOT NULL,
    data        TEXT DEFAULT CURRENT_TIMESTAMP,
    link        TEXT NOT NULL,
    tipo        TEXT NOT NULL CHECK (tipo IN ('risorse', 'myweb', 'siti', 'portali', 'blog', 'giornali')),
    descrizione TEXT,
    visibilita  TEXT NOT NULL DEFAULT 'privato' CHECK (visibilita IN ('pubblico', 'condiviso', 'privato')),
    tags        TEXT NOT NULL DEFAULT '',
    clicks      INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES link_users (id) ON DELETE CASCADE
);

-- Indice sulla foreign key (usata nelle JOIN).
CREATE INDEX IF NOT EXISTS idx_link_links_user_id ON link_links (user_id);
