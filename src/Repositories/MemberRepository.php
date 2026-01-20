<?php
require_once __DIR__ . '/../Models/StudentMember.php';
require_once __DIR__ . '/../Models/FacultyMember.php';

class MemberRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($type, $name, $email, $phone, $start, $end): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO members (member_type, full_name, email, phone, membership_start, membership_end)
            VALUES (:t, :n, :e, :p, :s, :en)
        ");
        $stmt->execute([
            't' => $type,
            'n' => $name,
            'e' => $email,
            'p' => $phone,
            's' => $start,
            'en' => $end,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById($id): ?Member
    {
        $stmt = $this->pdo->prepare("SELECT * FROM members WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        if ($row['member_type'] === 'STUDENT') {
            return new StudentMember($row['id'], $row['member_type'], $row['full_name'], $row['email'], $row['phone'], $row['membership_start'], $row['membership_end'], $row['unpaid_balance']);
        }
        return new FacultyMember($row['id'], $row['member_type'], $row['full_name'], $row['email'], $row['phone'], $row['membership_start'], $row['membership_end'], $row['unpaid_balance']);
    }

    public function renew($id, $newEnd): void
    {
        $stmt = $this->pdo->prepare("UPDATE members SET membership_end = :end WHERE id = :id");
        $stmt->execute(['end' => $newEnd, 'id' => $id]);
    }

    public function updateContact($id, $email, $phone): void
    {
        $stmt = $this->pdo->prepare("UPDATE members SET email = :email, phone = :phone WHERE id = :id");
        $stmt->execute(['email' => $email, 'phone' => $phone, 'id' => $id]);
    }

    public function addBalance($id, $amount): void
    {
        $stmt = $this->pdo->prepare("UPDATE members SET unpaid_balance = unpaid_balance + :a WHERE id = :id");
        $stmt->execute(['a' => $amount, 'id' => $id]);
    }

    public function subtractBalance($id, $amount): void
    {
        $stmt = $this->pdo->prepare("UPDATE members SET unpaid_balance = GREATEST(unpaid_balance - :a, 0) WHERE id = :id");
        $stmt->execute(['a' => $amount, 'id' => $id]);
    }
}
