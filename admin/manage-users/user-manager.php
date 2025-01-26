<?php
class UserManager {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    public function registerUser($username, $email, $password, $role = 'member') {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO users (username, email, password, role, registration_date) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$username, $email, $hashed_password, $role]);
    }

    public function authenticateUser($email, $password) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function getUserAnalytics() {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
                    COUNT(CASE WHEN role = 'librarian' THEN 1 END) as librarian_count,
                    COUNT(CASE WHEN role = 'member' THEN 1 END) as member_count,
                    (SELECT COUNT(*) FROM book_loans WHERE return_date IS NULL) as active_loans,
                    AVG(DATEDIFF(NOW(), registration_date)) as avg_membership_duration
                  FROM users";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUserStatus($user_id, $status) {
        $query = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $user_id]);
    }

    public function getUserActivityLog($user_id, $limit = 10) {
        $query = "SELECT * FROM user_activity_log 
                  WHERE user_id = ? 
                  ORDER BY timestamp DESC 
                  LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>