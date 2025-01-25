<?php
// classes/loan.php
class Loan {
    private $conn;
    private $table_name = 'loans';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createLoan($user_id, $book_id, $loan_days = 14) {
        // Begin transaction
        $this->conn->beginTransaction();

        try {
            // Check book availability
            $book_query = "SELECT available_quantity FROM books WHERE id = :book_id AND available_quantity > 0";
            $book_stmt = $this->conn->prepare($book_query);
            $book_stmt->bindParam(':book_id', $book_id);
            $book_stmt->execute();

            if ($book_stmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Buku tidak tersedia'];
            }

            // Create loan record
            $loan_query = "INSERT INTO " . $this->table_name . " 
                           (user_id, book_id, loan_date, due_date) 
                           VALUES (:user_id, :book_id, CURRENT_DATE, 
                                   DATE_ADD(CURRENT_DATE, INTERVAL :loan_days DAY))";
            
            $loan_stmt = $this->conn->prepare($loan_query);
            $loan_stmt->bindParam(':user_id', $user_id);
            $loan_stmt->bindParam(':book_id', $book_id);
            $loan_stmt->bindParam(':loan_days', $loan_days);
            $loan_stmt->execute();

            // Reduce book availability
            $update_query = "UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :book_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':book_id', $book_id);
            $update_stmt->execute();

            // Commit transaction
            $this->conn->commit();
            return ['status' => true, 'message' => 'Peminjaman berhasil'];

        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollBack();
            return ['status' => false, 'message' => 'Gagal memproses peminjaman: ' . $e->getMessage()];
        }
    }

    public function returnBook($loan_id) {
        // Begin transaction
        $this->conn->beginTransaction();

        try {
            // Get loan details
            $loan_query = "SELECT book_id FROM " . $this->table_name . " WHERE id = :loan_id";
            $loan_stmt = $this->conn->prepare($loan_query);
            $loan_stmt->bindParam(':loan_id', $loan_id);
            $loan_stmt->execute();
            $loan = $loan_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                return ['status' => false, 'message' => 'Peminjaman tidak ditemukan'];
            }

            // Update loan status
            $return_query = "UPDATE " . $this->table_name . " 
                             SET return_date = CURRENT_DATE, 
                                 status = 'returned' 
                             WHERE id = :loan_id";
            
            $return_stmt = $this->conn->prepare($return_query);
            $return_stmt->bindParam(':loan_id', $loan_id);
            $return_stmt->execute();

            // Increase book availability
            $update_query = "UPDATE books SET available_quantity = available_quantity + 1 WHERE id = :book_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':book_id', $loan['book_id']);
            $update_stmt->execute();

            // Commit transaction
            $this->conn->commit();
            return ['status' => true, 'message' => 'Pengembalian berhasil'];

        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollBack();
            return ['status' => false, 'message' => 'Gagal memproses pengembalian: ' . $e->getMessage()];
        }
    }

    public function getUserLoans($user_id) {
        $query = "SELECT l.*, b.title, b.author 
                  FROM " . $this->table_name . " l
                  JOIN books b ON l.book_id = b.id
                  WHERE l.user_id = :user_id
                  ORDER BY l.loan_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}