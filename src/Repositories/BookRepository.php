<?php
require_once __DIR__ . '/../Models/Book.php';

class BookRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

   
    public function search($title, $author, $isbn, $category): array
    {
        $sql = "
            SELECT b.id, b.isbn, b.title, b.publication_year, c.name AS category_name, b.status
            FROM books b
            JOIN categories c ON c.id = b.category_id
            LEFT JOIN book_authors ba ON ba.book_id = b.id
            LEFT JOIN authors a ON a.id = ba.author_id
            WHERE 1=1
        ";

        $params = [];

        if ($title) {
            $sql .= " AND b.title LIKE :title";
            $params['title'] = '%' . $title . '%';
        }
        if ($author) {
            $sql .= " AND a.name LIKE :author";
            $params['author'] = '%' . $author . '%';
        }
        if ($isbn) {
            $sql .= " AND b.isbn = :isbn";
            $params['isbn'] = $isbn;
        }
        if ($category) {
            $sql .= " AND c.name LIKE :category";
            $params['category'] = '%' . $category . '%';
        }

        $sql .= " GROUP BY b.id ORDER BY b.title";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $books = [];
        foreach ($stmt->fetchAll() as $row) {
            $books[] = new Book(
                $row['id'],
                $row['isbn'],
                $row['title'],
                $row['publication_year'],
                $row['category_name'],
                $row['status']
            );
        }
        return $books;
    }

    public function availabilityByBranch($bookId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT br.id AS branch_id, br.name AS branch_name, br.location,
                   bi.total_copies, bi.available_copies
            FROM branch_inventory bi
            JOIN branches br ON br.id = bi.branch_id
            WHERE bi.book_id = :book_id
            ORDER BY br.name
        ");
        $stmt->execute(['book_id' => $bookId]);
        return $stmt->fetchAll();
    }

    public function availableAtBranch($bookId, $branchId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT available_copies
            FROM branch_inventory
            WHERE book_id = :book_id AND branch_id = :branch_id
        ");
        $stmt->execute(['book_id' => $bookId, 'branch_id' => $branchId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['available_copies'] : 0;
    }

    public function totalAvailable($bookId): int
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(available_copies),0) AS total FROM branch_inventory WHERE book_id = :id");
        $stmt->execute(['id' => $bookId]);
        return (int)$stmt->fetchColumn();
    }

    public function decreaseCopy($bookId, $branchId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE branch_inventory
            SET available_copies = available_copies - 1
            WHERE book_id = :book_id AND branch_id = :branch_id AND available_copies > 0
        ");
        $stmt->execute(['book_id' => $bookId, 'branch_id' => $branchId]);
    }

    public function increaseCopy($bookId, $branchId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE branch_inventory
            SET available_copies = available_copies + 1
            WHERE book_id = :book_id AND branch_id = :branch_id AND available_copies < total_copies
        ");
        $stmt->execute(['book_id' => $bookId, 'branch_id' => $branchId]);
    }
}
