<?php
class Book
{
    private int $id;
    private string $isbn;
    private string $title;
    private int $year;
    private string $category;
    private string $status;

    public function __construct($id, $isbn, $title, $year, $category, $status)
    {
        $this->id = (int)$id;
        $this->isbn = $isbn;
        $this->title = $title;
        $this->year = (int)$year;
        $this->category = $category;
        $this->status = $status;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getIsbn()
    {
        return $this->isbn;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getYear()
    {
        return $this->year;
    }
    public function getCategory()
    {
        return $this->category;
    }
    public function getStatus()
    {
        return $this->status;
    }
}
