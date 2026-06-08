<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\ContactMessage;
use App\Domain\Repository\ContactMessageRepository;
use DateTimeImmutable;
use PDO;

final class PdoContactMessageRepository implements ContactMessageRepository
{
    public function __construct(private PDO $pdo) {}

    public function insert(ContactMessage $m): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contact_messages (id, name, email, message, user_id, ip, status, created_at)
             VALUES (:id, :name, :email, :message, :user_id, :ip, :status, :created_at)'
        );
        $stmt->execute([
            ':id'         => $m->id,
            ':name'       => $m->name,
            ':email'      => $m->email,
            ':message'    => $m->message,
            ':user_id'    => $m->userId,
            ':ip'         => $m->ip,
            ':status'     => $m->status,
            ':created_at' => $m->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function recent(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row): ContactMessage => $this->hydrate($row), $stmt->fetchAll());
    }

    public function setStatus(string $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function countOpen(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'open'")->fetchColumn();
    }

    private function hydrate(array $row): ContactMessage
    {
        return new ContactMessage(
            id: $row['id'],
            name: $row['name'],
            email: $row['email'],
            message: $row['message'],
            userId: $row['user_id'],
            ip: $row['ip'],
            status: $row['status'],
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
