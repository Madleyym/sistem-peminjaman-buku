<?php
class Category
{
    private $conn;
    private $table_name = 'categories';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function countCategories()
    {
        try {
            // First try to count from categories table
            $query = "SELECT COUNT(DISTINCT id) as total FROM {$this->table_name}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['total'] > 0) {
                return $result['total'];
            }

            // If no categories table or no results, count distinct categories from books
            $query = "SELECT COUNT(DISTINCT category) as total FROM books WHERE category IS NOT NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Count Categories Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getAllCategories()
    {
        try {
            // First try to get from categories table
            $query = "SELECT * FROM {$this->table_name} ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                return $results;
            }

            // If no categories table or no results, get distinct categories from books
            $query = "SELECT DISTINCT category as name FROM books 
                     WHERE category IS NOT NULL 
                     ORDER BY category";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get All Categories Error: " . $e->getMessage());
            return [];
        }
    }
}
