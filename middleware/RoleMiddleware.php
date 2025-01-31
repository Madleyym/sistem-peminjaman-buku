// middleware/RoleMiddleware.php
<?php
class RoleMiddleware
{
    public function authenticate($allowedRoles)
    {
        session_start();

        // Cek if user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: /public/auth/login.php');
            exit();
        }

        // Cek if role is allowed
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            switch ($_SESSION['role']) {
                case 'admin':
                    header('Location: /admin/index.php');
                    break;
                case 'petugas':
                    header('Location: /petugas/includes/index.php');
                    break;
                default:
                    header('Location: /public/auth/home.php');
            }
            exit();
        }

        return true;
    }
}
