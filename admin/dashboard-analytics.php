<?php
class DashboardAnalytics {
    private $conn;
    private $categoryManager;
    private $userManager;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->categoryManager = new CategoryManager($database_connection);
        $this->userManager = new UserManager($database_connection);
    }

    public function getDashboardSummary() {
        $categoryAnalytics = $this->categoryManager->getCategoryAnalytics();
        $userAnalytics = $this->userManager->getUserAnalytics();

        $bookLoans = $this->getBookLoanStatistics();
        $recentActivities = $this->getRecentSystemActivities();

        return [
            'categories' => $categoryAnalytics,
            'users' => $userAnalytics,
            'book_loans' => $bookLoans,
            'recent_activities' => $recentActivities
        ];
    }

    private function getBookLoanStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_loans,
                    COUNT(CASE WHEN return_date IS NULL THEN 1 END) as active_loans,
                    COUNT(CASE WHEN DATEDIFF(CURDATE(), due_date) > 0 THEN 1 END) as overdue_loans,
                    AVG(DATEDIFF(return_date, loan_date)) as avg_loan_duration
                  FROM book_loans";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getRecentSystemActivities($limit = 10) {
        $query = "SELECT * FROM system_activity_log 
                  ORDER BY timestamp DESC 
                  LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>