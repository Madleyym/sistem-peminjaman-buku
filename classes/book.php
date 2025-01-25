<?php
class Book
{
    private $conn;
    private $table_name = 'books';


    public function getAllBooks($limit = 10, $offset = 0, $searchQuery = '', $categoryFilter = '')
    {
        $query = "SELECT * FROM books WHERE 1=1";

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

    // Keep this method
    public function countTotalBooks($searchQuery = '', $categoryFilter = '')
    {
        $query = "SELECT COUNT(*) as total FROM books WHERE 1=1";

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
        $query = "INSERT INTO books 
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

    public function updateBook($bookId, $bookData)
    {
        $query = "UPDATE books 
              SET title = :title, 
                  author = :author, 
                  publisher = :publisher,
                  year_published = :year_published, 
                  isbn = :isbn, 
                  category = :category, 
                  total_quantity = :total_quantity,
                  available_quantity = :available_quantity,
                  description = :description";

        if (isset($bookData['book_cover'])) {
            $query .= ", cover_image = :cover_image";
        }

        $query .= " WHERE id = :book_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':title', $bookData['title']);
        $stmt->bindParam(':author', $bookData['author']);
        $stmt->bindParam(':publisher', $bookData['publisher'] ?? '');
        $stmt->bindParam(':year_published', $bookData['publication_year']);
        $stmt->bindParam(':isbn', $bookData['isbn']);
        $stmt->bindParam(':category', $bookData['category_id']);
        $stmt->bindParam(':total_quantity', $bookData['total_copies']);
        $stmt->bindParam(':available_quantity', $bookData['total_copies']);
        $stmt->bindParam(':description', $bookData['description']);
        $stmt->bindParam(':book_id', $bookId);

        if (isset($bookData['book_cover'])) {
            $stmt->bindParam(':cover_image', $bookData['book_cover']);
        }

        return $stmt->execute();
    }

    public function deleteBook($bookId)
    {
        $query = "DELETE FROM books WHERE id = :book_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':book_id', $bookId);
        return $stmt->execute();
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
    /*************  ✨ new methods ⭐  *************/


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

    public function __construct($db)
    {
        $this->conn = $db;
    }
    public function getBorrowedBooksByUserId($user_id)
    {
        $query = "SELECT b.* FROM books b 
                  JOIN borrowings br ON b.id = br.book_id 
                  WHERE br.user_id = :user_id";
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

    // Add a method to get cover image
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

        // Add optional filters
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

        // Bind additional filter parameters
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

    public function getBookById($book_id)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE id = :book_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':book_id', $book_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
