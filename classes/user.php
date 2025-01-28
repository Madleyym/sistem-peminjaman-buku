<?php
// classes/user.php
class User
{
    private $conn;
    private $table_name = 'users';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function adminRegister($data)
    {
        // Check if username already exists
        $checkUsernameQuery = "SELECT * FROM admin WHERE username = :username";
        $checkUsernameStmt = $this->conn->prepare($checkUsernameQuery);
        $checkUsernameStmt->bindParam(':username', $data['username']);
        $checkUsernameStmt->execute();

        if ($checkUsernameStmt->rowCount() > 0) {
            return [
                'status' => false,
                'message' => 'Username sudah terdaftar'
            ];
        }

        // Check if email already exists
        $checkQuery = "SELECT * FROM admin WHERE email = :email";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $data['email']);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return [
                'status' => false,
                'message' => 'Email sudah terdaftar'
            ];
        }

        // Check if NIK already exists
        $checkNIKQuery = "SELECT * FROM admin WHERE nik = :nik";
        $checkNIKStmt = $this->conn->prepare($checkNIKQuery);
        $checkNIKStmt->bindParam(':nik', $data['nik']);
        $checkNIKStmt->execute();

        if ($checkNIKStmt->rowCount() > 0) {
            return [
                'status' => false,
                'message' => 'NIK sudah terdaftar'
            ];
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert new admin
        $query = "INSERT INTO admin (username, email, password, nik, name, created_at) 
                  VALUES (:username, :email, :password, :nik, :name, NOW())";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':nik', $data['nik']);
        $stmt->bindParam(':name', $data['name']); // Assuming you'll add name in the registration form

        if ($stmt->execute()) {
            return [
                'status' => true,
                'message' => 'Registrasi berhasil'
            ];
        }

        return [
            'status' => false,
            'message' => 'Registrasi gagal'
        ];
    }

    public function adminLogin($email, $password)
    {
        $query = "SELECT * FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Update last_login
            $updateQuery = "UPDATE admin SET last_login = NOW() WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':id', $admin['id']);
            $updateStmt->execute();

            // Remove sensitive information
            unset($admin['password']);
            return [
                'status' => true,
                'user' => $admin
            ];
        }

        return [
            'status' => false,
            'message' => 'Email atau password salah'
        ];
    }

    public function adminLoginWithNIK($email, $nik, $password)
    {
        $query = "SELECT * FROM admin WHERE email = :email AND nik = :nik";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':nik', $nik);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Update last_login
            $updateQuery = "UPDATE admin SET last_login = NOW() WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':id', $admin['id']);
            $updateStmt->execute();

            // Remove sensitive information
            unset($admin['password']);
            return [
                'status' => true,
                'user' => $admin
            ];
        }

        return [
            'status' => false,
            'message' => 'Email, NIK, atau password salah'
        ];
    }
    /*************  ✨ adminLoginWithNIK ⭐  *************/

    public function updateProfile($userId, $name, $email, $phone, $address, $profileImage)
    {
        $query = "UPDATE users SET 
                name = :name, 
                email = :email, 
                phone_number = :phone, 
                address = :address, 
                profile_image = :profile_image 
              WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':profile_image', $profileImage);
        $stmt->bindParam(':id', $userId);

        return $stmt->execute();
    }

    public function getUserById($userId)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function register($name, $email, $password, $phone = '', $address = '')
    {
        if ($this->emailExists($email)) {
            return ['status' => false, 'message' => 'Email sudah terdaftar'];
        }

        $hashed_password = hashPassword($password);
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, phone_number, address) 
                  VALUES (:name, :email, :password, :phone, :address)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            return ['status' => true, 'message' => 'Registrasi berhasil'];
        }

        return ['status' => false, 'message' => 'Registrasi gagal'];
    }

    public function login($email, $password)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && verifyPassword($password, $user['password'])) {
            return ['status' => true, 'user' => $user];
        }

        return ['status' => false, 'message' => 'Login gagal'];
    }

    private function emailExists($email)
    {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }
    // Get all users (including admins if needed)
    public function getAllUsers()
    {
        // Ensure both queries have the same number of columns
        $query = "
             SELECT id, name, email, 'user' as role FROM " . $this->table_name . " 
             UNION 
             SELECT id, name, email, 'admin' as role FROM admin 
             ORDER BY name
         ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update user (either admin or regular user)
    public function updateUser($id, $name, $email, $role)
    {
        $table = ($role == 'admin') ? 'admin' : $this->table_name;
        $query = "UPDATE " . $table . " SET name = :name, email = :email WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // Delete user (either admin or regular user)
    public function deleteUser($id, $role)
    {
        $table = ($role == 'admin') ? 'admin' : $this->table_name;
        $query = "DELETE FROM " . $table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
    // Di class User
    public function getAllBorrowers()
    {
        try {
            $query = "SELECT * FROM users WHERE role = 'borrower' ORDER BY name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get All Borrowers Error: " . $e->getMessage());
            return [];
        }
    }
    // Di class User.php

    public function getAllAdmins()
    {
        try {
            $query = "SELECT * FROM admin ORDER BY username";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get All Admins Error: " . $e->getMessage());
            return [];
        }
    }

    public function updateAdmin($id, $data)
    {
        try {
            $query = "UPDATE admin 
                  SET username = :username, 
                      email = :email, 
                      name = :name, 
                      nik = :nik 
                  WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':nik', $data['nik']);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Admin Error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAdmin($id)
    {
        try {
            $query = "DELETE FROM admin WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete Admin Error: " . $e->getMessage());
            return false;
        }
    }

    public function searchAdmins($search)
    {
        try {
            $query = "SELECT * FROM admin 
                  WHERE username LIKE :search 
                  OR email LIKE :search 
                  OR name LIKE :search 
                  OR nik LIKE :search 
                  ORDER BY username";

            $searchTerm = "%$search%";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':search', $searchTerm);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search Admins Error: " . $e->getMessage());
            return [];
        }
    }
    // Di dalam class User (User.php):
    public function getUserStatistics($userId)
    {
        try {
            // Get total books borrowed
            $queryTotal = "SELECT COUNT(*) as total_books FROM book_loans WHERE user_id = ?";
            $stmtTotal = $this->conn->prepare($queryTotal);
            $stmtTotal->execute([$userId]);
            $totalBooks = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_books'];

            // Get active loans
            $queryActive = "SELECT COUNT(*) as active_loans FROM book_loans 
                       WHERE user_id = ? AND status = 'Active'";
            $stmtActive = $this->conn->prepare($queryActive);
            $stmtActive->execute([$userId]);
            $activeLoans = $stmtActive->fetch(PDO::FETCH_ASSOC)['active_loans'];

            return [
                'total_books' => $totalBooks,
                'active_loans' => $activeLoans
            ];
        } catch (PDOException $e) {
            error_log("Error in getUserStatistics: " . $e->getMessage());
            return [
                'total_books' => 0,
                'active_loans' => 0
            ];
        }
    }
}
