<?php
class Book
{
    private $conn;
    private $table_name = 'books';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAllBooks($limit = 10, $offset = 0, $searchQuery = '', $categoryFilter = '')
    {
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";

        if (!empty($searchQuery)) {
            $query .= " AND (title LIKE :search OR author LIKE :search OR isbn LIKE :search)";
        }

        if (!empty($categoryFilter)) {
            $query .= " AND category = :category";
        }

        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        if (!empty($searchQuery)) {
            $searchParam = "%{$searchQuery}%";
            $stmt->bindParam(':search', $searchParam);
        }

        if (!empty($categoryFilter)) {
            $stmt->bindParam(':category', $categoryFilter);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotalBooks($searchQuery = '', $categoryFilter = '')
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table_name} WHERE 1=1";

        if (!empty($searchQuery)) {
            $query .= " AND (title LIKE :search OR author LIKE :search OR isbn LIKE :search)";
        }

        if (!empty($categoryFilter)) {
            $query .= " AND category = :category";
        }

        $stmt = $this->conn->prepare($query);

        if (!empty($searchQuery)) {
            $searchParam = "%{$searchQuery}%";
            $stmt->bindParam(':search', $searchParam);
        }

        if (!empty($categoryFilter)) {
            $stmt->bindParam(':category', $categoryFilter);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function addBook($bookData)
    {
        $query = "INSERT INTO {$this->table_name} 
                  (title, author, publisher, year_published, isbn, category, 
                   total_quantity, available_quantity, cover_image, description, shelf_location) 
                  VALUES 
                  (:title, :author, :publisher, :year_published, :isbn, :category, 
                   :total_quantity, :available_quantity, :cover_image, :description, :shelf_location)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':title', $bookData['title']);
        $stmt->bindParam(':author', $bookData['author']);
        $stmt->bindParam(':publisher', $bookData['publisher'] ?? '');
        $stmt->bindParam(':year_published', $bookData['publication_year']);
        $stmt->bindParam(':isbn', $bookData['isbn']);
        $stmt->bindParam(':category', $bookData['category_id']);
        $stmt->bindParam(':total_quantity', $bookData['total_copies']);
        $stmt->bindParam(':available_quantity', $bookData['total_copies']);
        $stmt->bindParam(':cover_image', $bookData['book_cover'] ?? null);
        $stmt->bindParam(':description', $bookData['description']);
        $stmt->bindParam(':shelf_location', $bookData['shelf_location'] ?? '');

        return $stmt->execute();
    }

    public function deleteBook($bookId)
    {
        try {
            // Validate book ID
            $bookId = filter_var($bookId, FILTER_VALIDATE_INT);
            if ($bookId === false) {
                throw new Exception("Invalid Book ID");
            }

            // Begin transaction
            $this->conn->beginTransaction();

            // Check if book exists
            $checkStmt = $this->conn->prepare("SELECT id FROM {$this->table_name} WHERE id = ?");
            $checkStmt->execute([$bookId]);
            if ($checkStmt->rowCount() === 0) {
                throw new Exception("Book not found");
            }

            // Prepare and execute delete statement
            $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id = ?");
            $result = $stmt->execute([$bookId]);

            if (!$result) {
                throw new Exception("Failed to delete book");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Delete Book Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateBook($bookId, $bookData)
    {
        try {
            // Validate book ID
            $bookId = filter_var($bookId, FILTER_VALIDATE_INT);
            if ($bookId === false) {
                throw new Exception("Invalid Book ID");
            }

            $sql = "UPDATE {$this->table_name} SET 
                    title = :title,
                    author = :author,
                    publisher = :publisher,
                    year_published = :year_published,
                    isbn = :isbn,
                    category = :category,
                    total_quantity = :total_quantity,
                    available_quantity = :available_quantity,
                    description = :description,
                    shelf_location = :shelf_location";

            // Add cover image update if provided
            if (isset($bookData['book_cover'])) {
                $sql .= ", cover_image = :cover_image";
            }

            $sql .= " WHERE id = :book_id";

            $stmt = $this->conn->prepare($sql);

            // Bind parameters
            $params = [
                ':title' => $bookData['title'],
                ':author' => $bookData['author'],
                ':publisher' => $bookData['publisher'] ?? '',
                ':year_published' => $bookData['publication_year'],
                ':isbn' => $bookData['isbn'],
                ':category' => $bookData['category_id'],
                ':total_quantity' => $bookData['total_copies'],
                ':available_quantity' => $bookData['total_copies'], // Set available same as total for update
                ':description' => $bookData['description'],
                ':shelf_location' => $bookData['shelf_location'] ?? '',
                ':book_id' => $bookId
            ];

            // Add cover image parameter if provided
            if (isset($bookData['book_cover'])) {
                $params[':cover_image'] = $bookData['book_cover'];
            }

            // Execute with all parameters
            $result = $stmt->execute($params);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database Error: " . $errorInfo[2]);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Update Book Error: " . $e->getMessage());
            return false;
        }
    }

    public function getBookById($bookId)
    {
        try {
            // Validate book ID
            $bookId = filter_var($bookId, FILTER_VALIDATE_INT);
            if ($bookId === false) {
                throw new Exception("Invalid Book ID");
            }

            // Prepare and execute select statement
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            return $book ?: null;
        } catch (Exception $e) {
            error_log("Get Book Error: " . $e->getMessage());
            return null;
        }
    }

    public function validateSession($user_id, $session_token)
    {
        $query = "SELECT * FROM users WHERE id = :user_id AND session_token = :session_token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':session_token', $session_token);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function countLowStockBooks($threshold = 3)
    {
        $query = "SELECT COUNT(*) as low_stock FROM {$this->table_name} WHERE available_quantity <= :threshold";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['low_stock'];
    }

    public function getRecentlyAddedBooks($limit = 5)
    {
        $query = "SELECT title, year_published FROM {$this->table_name} ORDER BY id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBorrowedBooksByUserId($user_id)
    {
        $query = "SELECT l.*, b.* FROM loans l
                JOIN books b ON l.book_id = b.id
                WHERE l.user_id = :user_id AND l.status = 'active'
                ORDER BY l.loan_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $query = "INSERT INTO {$this->table_name} 
                  (title, author, publisher, isbn, category, 
                   year_published, total_quantity, available_quantity, 
                   description, shelf_location, language)
                  VALUES (:title, :author, :publisher, :isbn, :category, 
                          :year, :total, :available, :description, 
                          :shelf, :language)";

        $stmt = $this->conn->prepare($query);

        // Sanitize and validate input
        $data = $this->sanitizeBookData($data);

        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':author', $data['author']);
        $stmt->bindParam(':publisher', $data['publisher']);
        $stmt->bindParam(':isbn', $data['isbn']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':year', $data['year_published']);
        $stmt->bindParam(':total', $data['total_quantity']);
        $stmt->bindParam(':available', $data['available_quantity']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':shelf', $data['shelf_location']);
        $stmt->bindParam(':language', $data['language']);

        return $stmt->execute();
    }

    public function getNewBooks($limit = 6)
    {
        $query = "SELECT * FROM {$this->table_name} 
                  ORDER BY year_published DESC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookCoverImage($book_id)
    {
        $query = "SELECT cover_image FROM {$this->table_name} WHERE id = :book_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':book_id', $book_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['cover_image'] ?? '../assets/images/default-book-cover.jpg';
    }

    public function searchBooks($keyword, $filters = [])
    {
        $query = "SELECT * FROM {$this->table_name} 
                  WHERE (title LIKE :keyword 
                  OR author LIKE :keyword 
                  OR isbn LIKE :keyword)";

        if (!empty($filters['category'])) {
            $query .= " AND category = :category";
        }
        if (!empty($filters['min_year'])) {
            $query .= " AND year_published >= :min_year";
        }
        if (!empty($filters['max_year'])) {
            $query .= " AND year_published <= :max_year";
        }
        if (!empty($filters['language'])) {
            $query .= " AND language = :language";
        }

        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);

        if (!empty($filters['category'])) {
            $stmt->bindParam(':category', $filters['category']);
        }
        if (!empty($filters['min_year'])) {
            $stmt->bindParam(':min_year', $filters['min_year']);
        }
        if (!empty($filters['max_year'])) {
            $stmt->bindParam(':max_year', $filters['max_year']);
        }
        if (!empty($filters['language'])) {
            $stmt->bindParam(':language', $filters['language']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sanitizeBookData($data)
    {
        return [
            'title' => htmlspecialchars(trim($data['title'])),
            'author' => htmlspecialchars(trim($data['author'])),
            'publisher' => htmlspecialchars(trim($data['publisher'])),
            'isbn' => preg_replace('/[^0-9]/', '', $data['isbn']),
            'category' => htmlspecialchars(trim($data['category'])),
            'year_published' => intval($data['year_published']),
            'total_quantity' => intval($data['total_quantity']),
            'available_quantity' => intval($data['available_quantity']),
            'description' => htmlspecialchars(trim($data['description'])),
            'shelf_location' => htmlspecialchars(trim($data['shelf_location'])),
            'language' => htmlspecialchars(trim($data['language']))
        ];
    }
    // Tambahkan method ini di akhir class Book, sebelum curly brace penutup }
    public function getAvailableBooks()
    {
        try {
            $query = "SELECT b.*, c.name as category_name 
                  FROM {$this->table_name} b
                  LEFT JOIN categories c ON b.category = c.id
                  WHERE b.available_quantity > 0
                  ORDER BY b.title ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Available Books Error: " . $e->getMessage());
            return [];
        }
    }
}
