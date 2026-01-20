USE library_simple;

INSERT INTO categories (name) VALUES
  ('Computer Science'), ('Literature'), ('Science'), ('History')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO authors (name, nationality, primary_genre) VALUES
  ('Robert C. Martin', 'USA', 'Software Engineering'),
  ('Andrew Hunt', 'USA', 'Software Engineering'),
  ('David Thomas', 'USA', 'Software Engineering'),
  ('Jane Austen', 'UK', 'Novel');

INSERT INTO books (isbn, title, publication_year, category_id, status) VALUES
  ('9780132350884', 'Clean Code', 2008, (SELECT id FROM categories WHERE name='Computer Science'), 'AVAILABLE'),
  ('9780201616224', 'The Pragmatic Programmer', 1999, (SELECT id FROM categories WHERE name='Computer Science'), 'AVAILABLE'),
  ('9780141439518', 'Pride and Prejudice', 1813, (SELECT id FROM categories WHERE name='Literature'), 'AVAILABLE');

INSERT INTO book_authors (book_id, author_id) VALUES
  ((SELECT id FROM books WHERE isbn='9780132350884'), (SELECT id FROM authors WHERE name='Robert C. Martin')),
  ((SELECT id FROM books WHERE isbn='9780201616224'), (SELECT id FROM authors WHERE name='Andrew Hunt')),
  ((SELECT id FROM books WHERE isbn='9780201616224'), (SELECT id FROM authors WHERE name='David Thomas')),
  ((SELECT id FROM books WHERE isbn='9780141439518'), (SELECT id FROM authors WHERE name='Jane Austen'));

INSERT INTO branches (name, location, operating_hours, contact_phone, contact_email) VALUES
  ('Main Library', 'Campus Center', 'Mon-Fri 08:00-18:00', '+1-555-0100', 'main@techcity.edu'),
  ('Engineering Branch', 'Engineering Building', 'Mon-Fri 09:00-17:00', '+1-555-0101', 'eng@techcity.edu'),
  ('Science Branch', 'Science Complex', 'Mon-Fri 09:00-17:00', '+1-555-0102', 'science@techcity.edu'),
  ('Arts Branch', 'Arts Hall', 'Mon-Fri 10:00-16:00', '+1-555-0103', 'arts@techcity.edu'),
  ('Research Branch', 'Research Center', 'Mon-Fri 08:00-20:00', '+1-555-0104', 'research@techcity.edu');

-- Inventory
INSERT INTO branch_inventory (branch_id, book_id, total_copies, available_copies) VALUES
  ((SELECT id FROM branches WHERE name='Main Library'), (SELECT id FROM books WHERE isbn='9780132350884'), 3, 3),
  ((SELECT id FROM branches WHERE name='Engineering Branch'), (SELECT id FROM books WHERE isbn='9780132350884'), 2, 2),
  ((SELECT id FROM branches WHERE name='Main Library'), (SELECT id FROM books WHERE isbn='9780201616224'), 2, 2),
  ((SELECT id FROM branches WHERE name='Research Branch'), (SELECT id FROM books WHERE isbn='9780201616224'), 1, 1),
  ((SELECT id FROM branches WHERE name='Arts Branch'), (SELECT id FROM books WHERE isbn='9780141439518'), 2, 2);

-- Members
INSERT INTO members (member_type, full_name, email, phone, membership_start, membership_end, unpaid_balance) VALUES
  ('STUDENT', 'Alice Student', 'alice.student@techcity.edu', '+1-555-0200', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0.00),
  ('FACULTY', 'Dr. Bob Faculty', 'bob.faculty@techcity.edu', '+1-555-0201', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 YEAR), 0.00);
