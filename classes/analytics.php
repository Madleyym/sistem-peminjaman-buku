<?php
class DashboardAnalytics
{
    private $conn;
    private $admin_data;

    public function __construct($database_connection)
    {
        $this->conn = $database_connection;
        $this->loadAdminData(); // Load admin data saat inisialisasi
    }

    // ADMIN MANAGEMENT
    private function loadAdminData()
    {
        try {
            if (!isset($_SESSION['admin_id'])) {
                header("Location: login.php");
                exit();
            }

            $admin_id = $_SESSION['admin_id'];
            $stmt = $this->conn->prepare("SELECT id, username, name, email, nik FROM admin WHERE id = ?");
            $stmt->execute([$admin_id]);
            $this->admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$this->admin_data) {
                throw new Exception("Admin data not found");
            }
        } catch (Exception $e) {
            error_log("Error loading admin data: " . $e->getMessage());
            header("Location: login.php");
            exit();
        }
    }

    public function getAdminData()
    {
        return $this->admin_data;
    }

    // DASHBOARD SUMMARY
    public function getDashboardSummary()
    {
        try {
            return [
                'admin' => $this->getAdminData(),
                'book_loans' => $this->getBookLoanStatistics(),
                'recent_activities' => $this->getRecentActivities(),
                'user_activities' => $this->getUserActivities(),
                'daily_stats' => $this->getDailyStatistics()
            ];
        } catch (PDOException $e) {
            error_log("Dashboard Summary Error: " . $e->getMessage());
            return [
                'error' => 'Failed to fetch dashboard summary',
                'details' => $e->getMessage()
            ];
        }
    }

    // BOOK LOAN STATISTICS
    private function getBookLoanStatistics()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_loans,
                        COUNT(CASE WHEN return_date IS NULL THEN 1 END) as active_loans,
                        COUNT(CASE WHEN DATEDIFF(CURDATE(), due_date) > 0 
                            AND return_date IS NULL THEN 1 END) as overdue_loans,
                        COALESCE(AVG(DATEDIFF(IFNULL(return_date, CURDATE()), loan_date)), 0) as avg_loan_duration
                      FROM book_loans";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $result['avg_loan_duration'] = round($result['avg_loan_duration'], 1);

            return $result;
        } catch (PDOException $e) {
            error_log("Book Loan Statistics Error: " . $e->getMessage());
            return [
                'total_loans' => 0,
                'active_loans' => 0,
                'overdue_loans' => 0,
                'avg_loan_duration' => 0
            ];
        }
    }

    // ACTIVITY LOGS
    private function getRecentActivities($limit = 10)
    {
        try {
            $query = "SELECT 
                        al.*,
                        COALESCE(u.name, 'Anonymous') as name 
                      FROM activity_logs al
                      LEFT JOIN users u ON al.user_id = u.id 
                      ORDER BY al.created_at DESC 
                      LIMIT ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Recent Activities Error: " . $e->getMessage());
            return [];
        }
    }

    private function getUserActivities()
    {
        try {
            $query = "SELECT 
                        action_type,
                        COUNT(*) as count,
                        DATE(created_at) as action_date
                      FROM activity_logs
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY action_type, DATE(created_at)
                      ORDER BY action_date DESC, count DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("User Activities Error: " . $e->getMessage());
            return [];
        }
    }

    // DAILY STATISTICS
    private function getDailyStatistics()
    {
        try {
            $query = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as total_actions,
                        COUNT(DISTINCT user_id) as unique_users,
                        COUNT(DISTINCT ip_address) as unique_ips
                      FROM activity_logs
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY date DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->fillMissingDates($results);
        } catch (PDOException $e) {
            error_log("Daily Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    private function fillMissingDates($results)
    {
        $filledData = [];
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-7 days'));

        $date = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);

        while ($date <= $endDateTime) {
            $currentDate = $date->format('Y-m-d');
            $filledData[$currentDate] = [
                'date' => $currentDate,
                'total_actions' => 0,
                'unique_users' => 0,
                'unique_ips' => 0
            ];
            $date->modify('+1 day');
        }

        foreach ($results as $row) {
            if (isset($filledData[$row['date']])) {
                $filledData[$row['date']] = $row;
            }
        }

        return array_values($filledData);
    }

    // UTILITY METHODS
    public function isConnected()
    {
        try {
            $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            return false;
        }
    }
}
