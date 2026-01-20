<?php
class BorrowRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countOpenBorrows($memberId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE member_id = :id AND return_date IS NULL");
        $stmt->execute(['id' => $memberId]);
        return (int)$stmt->fetchColumn();
    }

    public function hasOverdue($memberId): bool
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM borrow_records
            WHERE member_id = :id
              AND return_date IS NULL
              AND due_date < :today
        ");
        $stmt->execute(['id' => $memberId, 'today' => $today]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createBorrow($memberId, $bookId, $branchId, $borrowDate, $dueDate): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO borrow_records (member_id, book_id, branch_id, borrow_date, due_date)
            VALUES (:m, :b, :br, :bd, :dd)
        ");
        $stmt->execute([
            'm' => $memberId,
            'b' => $bookId,
            'br' => $branchId,
            'bd' => $borrowDate,
            'dd' => $dueDate,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findOpenByMemberAndBook($memberId, $bookId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM borrow_records
            WHERE member_id = :m AND book_id = :b AND return_date IS NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['m' => $memberId, 'b' => $bookId]);
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public function findById($borrowId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM borrow_records WHERE id = :id");
        $stmt->execute(['id' => $borrowId]);
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public function setReturn($borrowId, $returnDate, $lateFee): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE borrow_records
            SET return_date = :rd, late_fee = :lf
            WHERE id = :id
        ");
        $stmt->execute(['rd' => $returnDate, 'lf' => $lateFee, 'id' => $borrowId]);
    }

    public function renew($borrowId, $newDueDate): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE borrow_records
            SET due_date = :dd,
                renewals_count = renewals_count + 1
            WHERE id = :id
        ");
        $stmt->execute(['dd' => $newDueDate, 'id' => $borrowId]);
    }

    public function listHistory($memberId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT br.*, b.title, b.isbn
            FROM borrow_records br
            JOIN books b ON b.id = br.book_id
            WHERE br.member_id = :m
            ORDER BY br.id DESC
        ");
        $stmt->execute(['m' => $memberId]);
        return $stmt->fetchAll();
    }

    public function overdueByBranch($branchId): array
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            SELECT br.id, br.member_id, m.full_name, b.title, br.borrow_date, br.due_date
            FROM borrow_records br
            JOIN members m ON m.id = br.member_id
            JOIN books b ON b.id = br.book_id
            WHERE br.branch_id = :br
              AND br.return_date IS NULL
              AND br.due_date < :today
            ORDER BY br.due_date ASC
        ");
        $stmt->execute(['br' => $branchId, 'today' => $today]);
        return $stmt->fetchAll();
    }

    public function topBorrowedMonthly($from, $to, $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.title, b.isbn, COUNT(*) AS borrow_count
            FROM borrow_records br
            JOIN books b ON b.id = br.book_id
            WHERE br.borrow_date BETWEEN :f AND :t
            GROUP BY b.id
            ORDER BY borrow_count DESC
            LIMIT $limit
        ");
        $stmt->execute(['f' => $from, 't' => $to]);
        return $stmt->fetchAll();
    }
}
