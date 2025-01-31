<?php

class Borrowing
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Menghitung jumlah peminjaman aktif
    public function countActiveLoans()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM loans WHERE status = 'borrowed'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        } catch (PDOException $e) {
            error_log("Error counting active loans: " . $e->getMessage());
            return 0;
        }
    }

    // Menghitung jumlah peminjaman yang terlambat
    public function countOverdueLoans()
    {
        try {
            $query = "SELECT COUNT(*) as total 
                     FROM loans 
                     WHERE status = 'borrowed' 
                     AND due_date < CURRENT_DATE()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        } catch (PDOException $e) {
            error_log("Error counting overdue loans: " . $e->getMessage());
            return 0;
        }
    }

    // Membuat peminjaman baru
    public function createLoan($data)
    {
        try {
            $query = "INSERT INTO loans (
                user_id, 
                book_id, 
                loan_date, 
                due_date, 
                status,
                created_at
            ) VALUES (
                :user_id,
                :book_id,
                :loan_date,
                :due_date,
                'borrowed',
                NOW()
            )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':book_id', $data['book_id']);
            $stmt->bindParam(':loan_date', $data['loan_date']);
            $stmt->bindParam(':due_date', $data['due_date']);

            if ($stmt->execute()) {
                // Update stok buku
                $this->updateBookStock($data['book_id'], -1);
                return [
                    'status' => true,
                    'message' => 'Peminjaman berhasil dicatat'
                ];
            }

            return [
                'status' => false,
                'message' => 'Gagal mencatat peminjaman'
            ];
        } catch (PDOException $e) {
            error_log("Error creating loan: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat mencatat peminjaman'
            ];
        }
    }

    // Mencatat pengembalian buku
    public function returnBook($loan_id, $fine_amount = 0)
    {
        try {
            // Dapatkan book_id sebelum update
            $query = "SELECT book_id FROM loans WHERE id = :loan_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':loan_id', $loan_id);
            $stmt->execute();
            $loan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                return [
                    'status' => false,
                    'message' => 'Data peminjaman tidak ditemukan'
                ];
            }

            // Update status peminjaman
            $query = "UPDATE loans 
                     SET return_date = CURRENT_DATE(),
                         status = 'returned',
                         fine_amount = :fine_amount
                     WHERE id = :loan_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fine_amount', $fine_amount);
            $stmt->bindParam(':loan_id', $loan_id);

            if ($stmt->execute()) {
                // Update stok buku
                $this->updateBookStock($loan['book_id'], 1);
                return [
                    'status' => true,
                    'message' => 'Pengembalian berhasil dicatat'
                ];
            }

            return [
                'status' => false,
                'message' => 'Gagal mencatat pengembalian'
            ];
        } catch (PDOException $e) {
            error_log("Error recording return: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat mencatat pengembalian'
            ];
        }
    }

    // Update stok buku
    private function updateBookStock($book_id, $quantity_change)
    {
        try {
            $query = "UPDATE books 
                     SET stock = stock + :quantity_change
                     WHERE id = :book_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quantity_change', $quantity_change);
            $stmt->bindParam(':book_id', $book_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating book stock: " . $e->getMessage());
            return false;
        }
    }

    // Mendapatkan daftar peminjaman aktif
    public function getActiveLoans($limit = 10)
    {
        try {
            $query = "SELECT 
                        l.*,
                        b.title as book_title,
                        u.name as user_name
                     FROM loans l
                     JOIN books b ON l.book_id = b.id
                     JOIN users u ON l.user_id = u.id
                     WHERE l.status = 'borrowed'
                     ORDER BY l.due_date ASC
                     LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active loans: " . $e->getMessage());
            return [];
        }
    }

    // Mendapatkan daftar peminjaman yang terlambat
    public function getOverdueLoans()
    {
        try {
            $query = "SELECT 
                        l.*,
                        b.title as book_title,
                        u.name as user_name,
                        DATEDIFF(CURRENT_DATE, l.due_date) as days_overdue
                     FROM loans l
                     JOIN books b ON l.book_id = b.id
                     JOIN users u ON l.user_id = u.id
                     WHERE l.status = 'borrowed'
                     AND l.due_date < CURRENT_DATE
                     ORDER BY l.due_date ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting overdue loans: " . $e->getMessage());
            return [];
        }
    }

    // Mendapatkan statistik peminjaman untuk dashboard
    public function getLoanStatistics()
    {
        try {
            $stats = [];

            // Total peminjaman aktif
            $stats['active_loans'] = $this->countActiveLoans();

            // Total peminjaman terlambat
            $stats['overdue_loans'] = $this->countOverdueLoans();

            // Total denda
            $query = "SELECT SUM(fine_amount) as total_fines 
                     FROM loans 
                     WHERE status = 'returned' 
                     AND fine_amount > 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $fines = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_fines'] = $fines['total_fines'] ?? 0;

            // Buku yang sering dipinjam
            $query = "SELECT 
                        b.title,
                        COUNT(*) as borrow_count
                     FROM loans l
                     JOIN books b ON l.book_id = b.id
                     GROUP BY b.id
                     ORDER BY borrow_count DESC
                     LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['popular_books'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (PDOException $e) {
            error_log("Error getting loan statistics: " . $e->getMessage());
            return null;
        }
    }
}
