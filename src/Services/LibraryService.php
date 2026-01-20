<?php
require_once __DIR__ . '/../Repositories/BookRepository.php';
require_once __DIR__ . '/../Repositories/MemberRepository.php';
require_once __DIR__ . '/../Repositories/BorrowRepository.php';
require_once __DIR__ . '/../Repositories/ReservationRepository.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';

class LibraryService
{
    private PDO $pdo;
    private BookRepository $books;
    private MemberRepository $members;
    private BorrowRepository $borrows;
    private ReservationRepository $reservations;
    private PaymentRepository $payments;

    function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->books = new BookRepository($pdo);
        $this->members = new MemberRepository($pdo);
        $this->borrows = new BorrowRepository($pdo);
        $this->reservations = new ReservationRepository($pdo);
        $this->payments = new PaymentRepository($pdo);
    }


    private function addDays($dateYmd, $days): string
    {
        return date('Y-m-d', strtotime("+$days days", strtotime($dateYmd)));
    }

    private function daysBetween($fromYmd, $toYmd): int
    {
        $diff = strtotime($toYmd) - strtotime($fromYmd);
        if ($diff <= 0) return 0;
        return (int)floor($diff / 86400);
    }

    function searchBooks($title = null, $author = null, $isbn = null, $category = null): array
    {
        return $this->books->search($title, $author, $isbn, $category);
    }

    function availability($bookId): array
    {
        return $this->books->availabilityByBranch($bookId);
    }

    function registerStudent($name, $email, $phone = null): int
    {
        $start = date('Y-m-d');
        $end = $this->addDays($start, 365);
        return $this->members->create('STUDENT', $name, $email, $phone, $start, $end);
    }

    function registerFaculty($name, $email, $phone = null): int
    {
        $start = date('Y-m-d');
        $end = $this->addDays($start, 365 * 3);
        return $this->members->create('FACULTY', $name, $email, $phone, $start, $end);
    }

    function renewMembership($memberId): array
    {
        $member = $this->members->findById($memberId);
        if (!$member) return ['ok' => false, 'error' => 'Member not found'];

        $today = date('Y-m-d');
        $newEnd = ($member->getType() === 'STUDENT')
            ? $this->addDays($today, 365)
            : $this->addDays($today, 365 * 3);

        $this->members->renew($memberId, $newEnd);
        return ['ok' => true, 'new_end' => $newEnd];
    }

    function borrowHistory($memberId): array
    {
        return $this->borrows->listHistory($memberId);
    }

    function borrowBook($memberId, $bookId, $branchId): array
    {

        $this->reservations->expireReadyReservations();

        $member = $this->members->findById($memberId);
        if (!$member) return ['ok' => false, 'error' => 'Member not found'];

        if (!$member->membershipValidToday()) {
            return ['ok' => false, 'error' => 'Membership expired. Renew first.'];
        }

        if ($member->getUnpaidBalance() > 10) {
            return ['ok' => false, 'error' => 'Unpaid balance > $10. Pay fines first.'];
        }

        if ($this->borrows->hasOverdue($memberId)) {
            return ['ok' => false, 'error' => 'You have overdue books. Return them first.'];
        }

        $openCount = $this->borrows->countOpenBorrows($memberId);
        if ($openCount >= $member->borrowLimit()) {
            return ['ok' => false, 'error' => 'Borrow limit reached.'];
        }

        $first = $this->reservations->firstInQueue($bookId);
        if ($first && (int)$first['member_id'] !== (int)$memberId) {
            return ['ok' => false, 'error' => 'This book is reserved for another member (waiting list).'];
        }

        $available = $this->books->availableAtBranch($bookId, $branchId);
        if ($available <= 0) {
            return ['ok' => false, 'error' => 'No available copies at this branch.'];
        }

        $borrowDate = date('Y-m-d');
        $dueDate = $this->addDays($borrowDate, $member->loanDays());


        $this->pdo->beginTransaction();
        try {
            $borrowId = $this->borrows->createBorrow($memberId, $bookId, $branchId, $borrowDate, $dueDate);
            $this->books->decreaseCopy($bookId, $branchId);


            if ($first && (int)$first['member_id'] === (int)$memberId) {
                $this->reservations->markFulfilled((int)$first['id']);
            }

            $this->pdo->commit();
            return ['ok' => true, 'borrow_id' => $borrowId, 'due_date' => $dueDate];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    function renewBorrow($memberId, $bookId): array
    {
        $member = $this->members->findById($memberId);
        if (!$member) return ['ok' => false, 'error' => 'Member not found'];

        $record = $this->borrows->findOpenByMemberAndBook($memberId, $bookId);
        if (!$record) return ['ok' => false, 'error' => 'No active borrow found for this member and book'];

        if ((int)$record['renewals_count'] >= 1) {
            return ['ok' => false, 'error' => 'You can renew only once.'];
        }

        if (strtotime(date('Y-m-d')) > strtotime($record['due_date'])) {
            return ['ok' => false, 'error' => 'Cannot renew an overdue book.'];
        }


        if ($this->reservations->someoneElseWaiting($bookId, $memberId)) {
            return ['ok' => false, 'error' => 'Cannot renew: another member reserved this book.'];
        }

        $newDue = $this->addDays($record['due_date'], $member->loanDays());

        $this->pdo->beginTransaction();
        try {
            $this->borrows->renew($record['id'], $newDue);
            $this->pdo->commit();
            return ['ok' => true, 'borrow_id' => $record['id'], 'new_due_date' => $newDue];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    function returnBook($borrowId): array
    {
        $record = $this->borrows->findById($borrowId);
        if (!$record) return ['ok' => false, 'error' => 'Borrow record not found'];

        if ($record['return_date'] !== null) {
            return ['ok' => true, 'message' => 'Already returned', 'late_fee' => 0];
        }

        $member = $this->members->findById($record['member_id']);
        if (!$member) return ['ok' => false, 'error' => 'Member not found'];

        $today = date('Y-m-d');
        $lateDays = 0;
        if (strtotime($today) > strtotime($record['due_date'])) {
            $lateDays = $this->daysBetween($record['due_date'], $today);
        }
        $lateFee = $lateDays * $member->lateFeePerDay();

        $this->pdo->beginTransaction();
        try {
            $this->borrows->setReturn($borrowId, $today, $lateFee);
            $this->books->increaseCopy($record['book_id'], $record['branch_id']);

            if ($lateFee > 0) {
                $this->members->addBalance($member->getId(), $lateFee);
            }


            $first = $this->reservations->firstInQueue($record['book_id']);
            $notify = null;
            if ($first && $first['status'] === 'WAITING') {
                $readyUntil = date('Y-m-d H:i:s', strtotime('+48 hours'));
                $this->reservations->markReady((int)$first['id'], $readyUntil);
                $notify = "Notify member_id {$first['member_id']}: book is READY until $readyUntil";
            }

            $this->pdo->commit();
            return ['ok' => true, 'late_days' => $lateDays, 'late_fee' => $lateFee, 'notification' => $notify];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    function reserveBook($memberId, $bookId): array
    {
        $member = $this->members->findById($memberId);
        if (!$member) return ['ok' => false, 'error' => 'Member not found'];

        if (!$member->membershipValidToday()) {
            return ['ok' => false, 'error' => 'Membership expired. Renew first.'];
        }

        $totalAvailable = $this->books->totalAvailable($bookId);
        if ($totalAvailable > 0) {
            return ['ok' => false, 'error' => 'Book is available now; reservation not needed.'];
        }

        try {
            $id = $this->reservations->create($memberId, $bookId);
            return ['ok' => true, 'reservation_id' => $id];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }


    function payFine($memberId, $amount, $note = null): array
    {
        if ($amount <= 0) return ['ok' => false, 'error' => 'Amount must be > 0'];

        $member = $this->members->findById($memberId);
        if (!$member) return ['ok' => false, 'error' => 'Member not found'];

        $this->pdo->beginTransaction();
        try {
            $this->payments->create($memberId, $amount, $note);
            $this->members->subtractBalance($memberId, $amount);
            $this->pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }


    function overdueReportByBranch($branchId): array
    {
        return $this->borrows->overdueByBranch($branchId);
    }

    function topBorrowedBooksThisMonth(): array
    {
        $from = date('Y-m-01');
        $to = date('Y-m-t');
        return $this->borrows->topBorrowedMonthly($from, $to, 10);
    }
}
