<?php
declare(strict_types=1);

/**
 * @file link/src/UserRepository.php
 * @layer Repository
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Accesso dati per la tabella link_users. Isola le query SQL dalle pagine.
 *   Non conosce HTTP né sessione.
 */
final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo
            ->query('SELECT id, username, email, role FROM link_users ORDER BY username')
            ->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role FROM link_users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM link_users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsUsernameOrEmail(string $username, string $email, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM link_users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
        } else {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM link_users WHERE (username = ? OR email = ?) AND id != ?');
            $stmt->execute([$username, $email, $exceptId]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $username, string $email, string $passwordHash, string $role): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO link_users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $email, $passwordHash, $role]);
    }

    public function update(int $id, string $username, string $email, string $role, ?string $passwordHash = null): void
    {
        if ($passwordHash !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE link_users SET username = ?, email = ?, password_hash = ?, role = ? WHERE id = ?'
            );
            $stmt->execute([$username, $email, $passwordHash, $role, $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE link_users SET username = ?, email = ?, role = ? WHERE id = ?'
            );
            $stmt->execute([$username, $email, $role, $id]);
        }
    }
}
