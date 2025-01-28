<?php
class Loan
{
    private $conn;
    private $table_name = 'loans';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function createLoan($user_id, $book_id, $loan_days = 14)
    {
        $this->conn->beginTransaction();

        try {
            // Check for duplicate active loan
            if ($this->checkDuplicateLoan($user_id, $book_id)) {
                return ['status' => false, 'message' => 'Pengguna sudah meminjam buku ini'];
            }

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
                           (user_id, book_id, loan_date, due_date, status) 
                           VALUES (:user_id, :book_id, CURRENT_DATE, 
                                   DATE_ADD(CURRENT_DATE, INTERVAL :loan_days DAY), 'active')";

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

    public function returnBook($loan_id)
    {
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

    public function getUserLoans($user_id)
    {
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

    public function createLoans($loanData)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, book_id, loan_date, due_date, status) 
                  VALUES (:user_id, :book_id, :loan_date, :due_date, :status)";

        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':user_id', $loanData['user_id']);
            $stmt->bindParam(':book_id', $loanData['book_id']);
            $stmt->bindParam(':loan_date', $loanData['loan_date']);
            $stmt->bindParam(':due_date', $loanData['due_date']);
            $stmt->bindParam(':status', $loanData['status']);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Loan Creation Error: " . $e->getMessage());
            return false;
        }
    }

    public function getLoansByUser($user_id)
    {
        $query = "SELECT l.*, b.title, b.author 
                  FROM " . $this->table_name . " l
                  JOIN books b ON l.book_id = b.id
                  WHERE l.user_id = :user_id
                  ORDER BY l.loan_date DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Loans Error: " . $e->getMessage());
            return [];
        }
    }

    public function returnBooks($loan_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'returned', 
                      return_date = CURRENT_DATE 
                  WHERE id = :loan_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':loan_id', $loan_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Book Return Error: " . $e->getMessage());
            return false;
        }
    }

    public function checkOverdueLoans()
    {
        $query = "SELECT l.*, u.email, b.title 
                  FROM " . $this->table_name . " l
                  JOIN users u ON l.user_id = u.id
                  JOIN books b ON l.book_id = b.id
                  WHERE l.status = 'active' AND l.due_date < CURRENT_DATE";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Overdue Loans Check Error: " . $e->getMessage());
            return [];
        }
    }
    // Tambahkan metode-metode berikut ke dalam class Loan

    // Hitung total peminjaman
    public function countTotalLoans()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Hitung peminjaman aktif
    public function countActiveLoans()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Hitung total denda
    public function getTotalFines()
    {
        $query = "SELECT SUM(fine_amount) as total FROM " . $this->table_name . " WHERE fine_amount > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    // Ambil peminjaman terbaru dengan detail lengkap
    public function getRecentLoans($limit = 5)
    {
        $query = "SELECT l.*, 
                        u.name as borrower_name, 
                        u.email as borrower_email,
                        b.title as book_title, 
                        b.isbn,
                        b.author
                 FROM " . $this->table_name . " l
                 LEFT JOIN users u ON l.user_id = u.id
                 LEFT JOIN books b ON l.book_id = b.id
                 ORDER BY l.created_at DESC 
                 LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua peminjaman dengan pagination
    public function getAllLoans($page = 1, $limit = 10, $search = '', $status = '')
    {
        $offset = ($page - 1) * $limit;

        $query = "SELECT l.*, 
                        u.name as borrower_name, 
                        u.email as borrower_email,
                        b.title as book_title, 
                        b.isbn as book_isbn,
                        b.author
                 FROM " . $this->table_name . " l
                 LEFT JOIN users u ON l.user_id = u.id
                 LEFT JOIN books b ON l.book_id = b.id
                 WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (b.title LIKE :search 
                           OR b.isbn LIKE :search 
                           OR u.name LIKE :search 
                           OR u.email LIKE :search)";
        }

        if (!empty($status)) {
            $query .= " AND l.status = :status";
        }

        $query .= " ORDER BY l.created_at DESC";

        // Tambahkan LIMIT dan OFFSET ke query
        if ($limit > 0) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        try {
            $stmt = $this->conn->prepare($query);

            if (!empty($search)) {
                $searchTerm = "%$search%";
                $stmt->bindParam(':search', $searchTerm);
            }

            if (!empty($status)) {
                $stmt->bindParam(':status', $status);
            }

            if ($limit > 0) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get All Loans Error: " . $e->getMessage());
            return [];
        }
    }
    // Hitung total pages untuk pagination
    public function getTotalPages($limit = 10, $search = '', $status = '')
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " l
                 LEFT JOIN users u ON l.user_id = u.id
                 LEFT JOIN books b ON l.book_id = b.id
                 WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (b.title LIKE :search 
                           OR b.isbn LIKE :search 
                           OR u.name LIKE :search 
                           OR u.email LIKE :search)";
        }

        if (!empty($status)) {
            $query .= " AND l.status = :status";
        }

        $stmt = $this->conn->prepare($query);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm);
        }

        if (!empty($status)) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        return ceil($total / $limit);
    }

    // Dapatkan statistik peminjaman
    public function getLoanStatistics()
    {
        return [
            'total_loans' => $this->countTotalLoans(),
            'active_loans' => $this->countActiveLoans(),
            'overdue_loans' => count($this->checkOverdueLoans()),
            'total_fines' => $this->getTotalFines()
        ];
    }

    // Dapatkan buku yang paling sering dipinjam
    public function getMostBorrowedBooks($limit = 5)
    {
        $query = "SELECT b.title, b.author, COUNT(l.id) as loan_count
                 FROM books b
                 LEFT JOIN " . $this->table_name . " l ON b.id = l.book_id
                 GROUP BY b.id
                 ORDER BY loan_count DESC
                 LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update status dan denda peminjaman
    public function updateLoanStatus($loan_id, $status, $fine_amount = null)
    {
        $query = "UPDATE " . $this->table_name . "
                 SET status = :status";

        if ($fine_amount !== null) {
            $query .= ", fine_amount = :fine_amount";
        }

        $query .= " WHERE id = :loan_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':loan_id', $loan_id);

        if ($fine_amount !== null) {
            $stmt->bindParam(':fine_amount', $fine_amount);
        }

        return $stmt->execute();
    }

    // Hitung denda berdasarkan keterlambatan
    public function calculateFine($loan_id)
    {
        try {
            // Ambil detail peminjaman
            $query = "SELECT l.*, 
                        DATEDIFF(COALESCE(l.return_date, CURRENT_DATE), l.due_date) as days_late
                 FROM " . $this->table_name . " l
                 WHERE l.id = :loan_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':loan_id', $loan_id);
            $stmt->execute();

            $loan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                return 0;
            }

            // Jika belum terlambat atau sudah dikembalikan tepat waktu
            if ($loan['days_late'] <= 0) {
                return 0;
            }

            // Hitung denda (Rp 1.000 per hari keterlambatan)
            $finePerDay = 1000;
            $totalFine = $loan['days_late'] * $finePerDay;

            // Update denda di database
            $updateQuery = "UPDATE " . $this->table_name . "
                      SET fine_amount = :fine_amount,
                          status = CASE 
                            WHEN status = 'active' THEN 'overdue'
                            ELSE status
                          END
                      WHERE id = :loan_id";

            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':fine_amount', $totalFine);
            $updateStmt->bindParam(':loan_id', $loan_id);
            $updateStmt->execute();

            return $totalFine;
        } catch (PDOException $e) {
            error_log("Calculate Fine Error: " . $e->getMessage());
            return 0;
        }
    }

    // Method untuk memperbarui denda untuk semua peminjaman yang aktif
    public function updateAllFines()
    {
        try {
            // Ambil semua peminjaman aktif yang telah melewati due_date
            $query = "SELECT id FROM " . $this->table_name . "
                 WHERE (status = 'active' OR status = 'overdue')
                 AND due_date < CURRENT_DATE";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $overdueLoans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($overdueLoans as $loan) {
                $this->calculateFine($loan['id']);
            }

            return true;
        } catch (PDOException $e) {
            error_log("Update All Fines Error: " . $e->getMessage());
            return false;
        }
    }
    // Tambahkan method ini di class Loan
    public function getTotalLoans($search = '', $status = '')
    {
        $query = "SELECT COUNT(*) as total 
              FROM " . $this->table_name . " l
              LEFT JOIN users u ON l.user_id = u.id
              LEFT JOIN books b ON l.book_id = b.id
              WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (b.title LIKE :search 
                       OR b.isbn LIKE :search 
                       OR u.name LIKE :search 
                       OR u.email LIKE :search)";
        }

        if (!empty($status)) {
            $query .= " AND l.status = :status";
        }

        try {
            $stmt = $this->conn->prepare($query);

            if (!empty($search)) {
                $searchTerm = "%$search%";
                $stmt->bindParam(':search', $searchTerm);
            }

            if (!empty($status)) {
                $stmt->bindParam(':status', $status);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Get Total Loans Error: " . $e->getMessage());
            return 0;
        }
    }
    public function checkDuplicateLoan($user_id, $book_id)
    {
        $query = "SELECT COUNT(*) as count 
              FROM " . $this->table_name . "
              WHERE user_id = :user_id 
              AND book_id = :book_id 
              AND status = 'active'";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':book_id', $book_id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Check Duplicate Loan Error: " . $e->getMessage());
            return false;
        }
    }

    public function createLoanWithAdminInfo($loanData)
    {
        $this->conn->beginTransaction();

        try {
            // Check for duplicate active loan
            if ($this->checkDuplicateLoan($loanData['borrower_id'], $loanData['book_id'])) {
                return ['status' => false, 'message' => 'Pengguna sudah meminjam buku ini'];
            }

            // Check book availability
            $book_query = "SELECT available_quantity FROM books WHERE id = :book_id AND available_quantity > 0";
            $book_stmt = $this->conn->prepare($book_query);
            $book_stmt->bindParam(':book_id', $loanData['book_id']);
            $book_stmt->execute();

            if ($book_stmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Buku tidak tersedia'];
            }

            // Create loan record dengan audit trail
            $loan_query = "INSERT INTO " . $this->table_name . " 
                      (user_id, book_id, loan_date, due_date, status, created_by, created_at) 
                      VALUES (:borrower_id, :book_id, :loan_date, :due_date, :status, :created_by, :created_at)";

            $loan_stmt = $this->conn->prepare($loan_query);

            // Bind parameters
            $loan_stmt->bindParam(':borrower_id', $loanData['borrower_id']);
            $loan_stmt->bindParam(':book_id', $loanData['book_id']);
            $loan_stmt->bindParam(':loan_date', $loanData['loan_date']);
            $loan_stmt->bindParam(':due_date', $loanData['due_date']);
            $loan_stmt->bindParam(':status', $loanData['status']);
            $loan_stmt->bindParam(':created_by', $loanData['created_by']);
            $loan_stmt->bindParam(':created_at', $loanData['created_at']);

            $loan_stmt->execute();

            // Reduce book availability
            $update_query = "UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :book_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':book_id', $loanData['book_id']);
            $update_stmt->execute();

            // Commit transaction
            $this->conn->commit();
            return ['status' => true, 'message' => 'Peminjaman berhasil'];
        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Create Loan Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal memproses peminjaman: ' . $e->getMessage()];
        }
    }
}
