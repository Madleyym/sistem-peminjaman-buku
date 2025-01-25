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
}
