<?php
class ReservationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($memberId, $bookId): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO reservations (member_id, book_id, status) VALUES (:m, :b, 'WAITING')");
        $stmt->execute(['m' => $memberId, 'b' => $bookId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function firstInQueue($bookId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM reservations
            WHERE book_id = :b AND status IN ('WAITING','READY')
            ORDER BY reserved_at ASC
            LIMIT 1
        ");
        $stmt->execute(['b' => $bookId]);
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public function markReady($reservationId, $readyUntil): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE reservations
            SET status='READY', notified_at = NOW(), ready_until = :ru
            WHERE id = :id
        ");
        $stmt->execute(['ru' => $readyUntil, 'id' => $reservationId]);
    }

    public function markFulfilled($reservationId): void
    {
        $stmt = $this->pdo->prepare("UPDATE reservations SET status='FULFILLED' WHERE id = :id");
        $stmt->execute(['id' => $reservationId]);
    }

    public function expireReadyReservations(): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            UPDATE reservations
            SET status='EXPIRED'
            WHERE status='READY' AND ready_until IS NOT NULL AND ready_until < :now
        ");
        $stmt->execute(['now' => $now]);
    }

    public function someoneElseWaiting($bookId, $memberId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM reservations
            WHERE book_id = :b
              AND status IN ('WAITING','READY')
              AND member_id <> :m
        ");
        $stmt->execute(['b' => $bookId, 'm' => $memberId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
