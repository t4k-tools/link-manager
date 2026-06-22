<?php
declare(strict_types=1);

require_once __DIR__ . '/../1/visibility.php';

/**
 * @file link/src/LinkRepository.php
 * @layer Repository
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Accesso dati per la tabella link_links, con applicazione del filtro di
 *   visibilità (ADR-002). Isola le query SQL dalle pagine.
 *   Non conosce HTTP né sessione.
 */
final class LinkRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Elenco dei link visibili a un osservatore, con filtri opzionali.
     *
     * @return array<int, array<string, mixed>>
     */
    public function visibleList(int $userId, string $role, string $tipo = '', string $search = '', string $tag = ''): array
    {
        $sql = 'SELECT l.id, l.user_id, l.data, l.link, l.tipo, l.descrizione, l.visibilita, l.tags, l.clicks, u.username
                FROM link_links l
                JOIN link_users u ON l.user_id = u.id
                WHERE 1=1';
        [$frag, $params] = visibilita_filtro_sql($userId, $role, 'l');
        $sql .= ' AND ' . $frag;

        if ($tipo !== '') {
            $sql .= ' AND l.tipo = ?';
            $params[] = $tipo;
        }
        if ($search !== '') {
            // La ricerca testuale copre descrizione e tag.
            $sql .= ' AND (l.descrizione LIKE ? OR l.tags LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($tag !== '') {
            // Filtro per tag esatto (formato memorizzato: ,tag1,tag2,).
            $sql .= " AND l.tags LIKE '%,' || ? || ',%'";
            $params[] = strtolower($tag);
        }
        $sql .= ' ORDER BY l.data DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Link pubblici (pagina pubblica anonima).
     *
     * @return array<int, array<string, mixed>>
     */
    public function publicList(): array
    {
        return $this->pdo
            ->query("SELECT id, data, link, descrizione, clicks FROM link_links WHERE visibilita = 'pubblico' ORDER BY clicks DESC")
            ->fetchAll();
    }

    /**
     * Link pubblici con ricerca (descrizione/tag) e filtro per tag.
     *
     * @return array<int, array<string, mixed>>
     */
    public function publicSearch(string $search = '', string $tag = ''): array
    {
        $sql = "SELECT id, data, link, tipo, descrizione, tags, clicks
                FROM link_links WHERE visibilita = 'pubblico'";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (descrizione LIKE ? OR tags LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($tag !== '') {
            $sql .= " AND tags LIKE '%,' || ? || ',%'";
            $params[] = strtolower($tag);
        }
        $sql .= ' ORDER BY data DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conteggio tag tra i soli link pubblici (non ordinato).
     *
     * @return array<string, int>
     */
    public function publicTagCounts(): array
    {
        $stmt = $this->pdo->query("SELECT tags FROM link_links WHERE visibilita = 'pubblico' AND tags <> ''");
        $counts = [];
        foreach ($stmt as $r) {
            foreach (array_filter(explode(',', trim((string) $r['tags'], ','))) as $t) {
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        }
        return $counts;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM link_links WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findUrl(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT link FROM link_links WHERE id = ?');
        $stmt->execute([$id]);
        $url = $stmt->fetchColumn();
        return ($url !== false) ? (string) $url : null;
    }

    public function create(int $userId, string $link, string $tipo, string $descrizione, string $visibilita, string $tags = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO link_links (user_id, link, tipo, descrizione, visibilita, tags) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $link, $tipo, $descrizione, $visibilita, $tags]);
    }

    public function update(int $id, string $link, string $tipo, string $descrizione, string $visibilita, string $tags = ''): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE link_links SET link = ?, tipo = ?, descrizione = ?, visibilita = ?, tags = ? WHERE id = ?'
        );
        $stmt->execute([$link, $tipo, $descrizione, $visibilita, $tags, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM link_links WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function incrementClicks(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE link_links SET clicks = clicks + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Conteggi per stato di visibilità entro lo scope dell'osservatore.
     *
     * @return array{pubblico:int, condiviso:int, privato:int}
     */
    public function statsByVisibility(int $userId, string $role): array
    {
        [$frag, $params] = visibilita_filtro_sql($userId, $role, 'l');
        $stmt = $this->pdo->prepare("SELECT l.visibilita, COUNT(*) AS c FROM link_links l WHERE $frag GROUP BY l.visibilita");
        $stmt->execute($params);
        $out = ['pubblico' => 0, 'condiviso' => 0, 'privato' => 0];
        foreach ($stmt as $r) {
            $out[$r['visibilita']] = (int) $r['c'];
        }
        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public function countPerUser(int $userId, string $role): array
    {
        [$frag, $params] = visibilita_filtro_sql($userId, $role, 'l');
        $stmt = $this->pdo->prepare(
            "SELECT u.username, COUNT(l.id) AS total
             FROM link_users u
             LEFT JOIN link_links l ON u.id = l.user_id AND $frag
             GROUP BY u.username"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conteggio dei tag presenti nei link visibili all'osservatore.
     *
     * @return array<string, int> mappa tag => numero, ordinata per nome
     */
    public function tagCounts(int $userId, string $role): array
    {
        [$frag, $params] = visibilita_filtro_sql($userId, $role, 'l');
        $stmt = $this->pdo->prepare("SELECT l.tags FROM link_links l WHERE $frag AND l.tags <> ''");
        $stmt->execute($params);

        $counts = [];
        foreach ($stmt as $r) {
            foreach (array_filter(explode(',', trim((string) $r['tags'], ','))) as $t) {
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        }
        ksort($counts);
        return $counts;
    }

    /** @return array<int, array<string, mixed>> */
    public function clicksList(int $userId, string $role): array
    {
        [$frag, $params] = visibilita_filtro_sql($userId, $role, 'l');
        $stmt = $this->pdo->prepare("SELECT l.link, l.clicks FROM link_links l WHERE $frag ORDER BY l.clicks DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
