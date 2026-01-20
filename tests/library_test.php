<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Services/LibraryService.php';

$pdo = Database::connect();
$service = new LibraryService($pdo);




echo " Search books in 'Computer Science'\n";
$books = $service->searchBooks(null, null, null, 'Computer Science');
foreach ($books as $b) {
    echo "- [{$b->getId()}] {$b->getTitle()} ({$b->getIsbn()})\n";
}
echo "\n";

