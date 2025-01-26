<?php
class CategoryManager {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    public function addCategory($name, $description = null) {
        $query = "INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $description]);
    }

    public function updateCategory($id, $name, $description = null) {
        $query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $description, $id]);
    }

    public function deleteCategory($id) {
        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getAllCategories($limit = null, $offset = 0) {
        $query = "SELECT c.*, 
                    COUNT(b.id) as book_count, 
                    MAX(b.created_at) as last_book_added 
                  FROM categories c
                  LEFT JOIN books b ON c.id = b.category_id
                  GROUP BY c.id
                  ORDER BY book_count DESC";
        
        if ($limit !== null) {
            $query .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit, $offset]);
        } else {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryAnalytics() {
        $query = "SELECT 
                    COUNT(*) as total_categories,
                    (SELECT COUNT(*) FROM books) as total_books,
                    (SELECT AVG(book_count) FROM (
                        SELECT COUNT(b.id) as book_count 
                        FROM categories c
                        LEFT JOIN books b ON c.id = b.category_id
                        GROUP BY c.id
                    ) as category_book_counts) as avg_books_per_category
                  FROM categories";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>