<?php
class Category
{
    private $conn;
    private $table_name = 'categories';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAllCategories()
    {
        $query = "SELECT * FROM {$this->table_name}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add more methods as needed, like:
    public function getCategoryById($category_id)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE id = :category_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}