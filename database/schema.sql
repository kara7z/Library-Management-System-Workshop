CREATE DATABASE library_simple CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE library_simple;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    nationality VARCHAR(80) NULL,
    primary_genre VARCHAR(80) NULL
) ENGINE = InnoDB;

CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    publication_year SMALLINT NOT NULL,
    category_id INT NOT NULL,
    status ENUM(
        'AVAILABLE',
        'CHECKED_OUT',
        'RESERVED',
        'MAINTENANCE'
    ) NOT NULL DEFAULT 'AVAILABLE',
    INDEX idx_books_title (title),
    INDEX idx_books_isbn (isbn),
    FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE book_authors (
    book_id INT NOT NULL,
    author_id INT NOT NULL,
    PRIMARY KEY (book_id, author_id),
    FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    location VARCHAR(180) NOT NULL,
    operating_hours VARCHAR(120) NULL,
    contact_phone VARCHAR(30) NULL,
    contact_email VARCHAR(120) NULL
) ENGINE = InnoDB;

CREATE TABLE branch_inventory (
    branch_id INT NOT NULL,
    book_id INT NOT NULL,
    total_copies INT NOT NULL DEFAULT 0,
    available_copies INT NOT NULL DEFAULT 0,
    PRIMARY KEY (branch_id, book_id),
    FOREIGN KEY (branch_id) REFERENCES branches (id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_type ENUM('STUDENT', 'FACULTY') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    membership_start DATE NOT NULL,
    membership_end DATE NOT NULL,
    unpaid_balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00
) ENGINE = InnoDB;

CREATE TABLE borrow_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    branch_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    renewals_count TINYINT NOT NULL DEFAULT 0,
    late_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    INDEX idx_borrows_member_open (member_id, return_date),
    INDEX idx_borrows_due (due_date),
    FOREIGN KEY (member_id) REFERENCES members (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM(
        'WAITING',
        'READY',
        'FULFILLED',
        'CANCELLED',
        'EXPIRED'
    ) NOT NULL DEFAULT 'WAITING',
    reserved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notified_at DATETIME NULL,
    ready_until DATETIME NULL,
    INDEX idx_res_book_status_time (book_id, status, reserved_at),
    FOREIGN KEY (member_id) REFERENCES members (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255) NULL,
    FOREIGN KEY (member_id) REFERENCES members (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;