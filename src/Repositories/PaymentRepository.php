<?php
class PaymentRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($memberId, $amount, $note = null): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO payments (member_id, amount, note) VALUES (:m, :a, :n)");
        $stmt->execute(['m' => $memberId, 'a' => $amount, 'n' => $note]);
    }
}
