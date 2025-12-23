<?php
require_once 'database.php';

class Auth {
    public static function login($email, $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                return true;
            }
        }
        return false;
    }
    
    public static function register($name, $email, $password, $phone = '') {
        $db = getDB();
        
        // Проверяем, существует ли email
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return false; // Email уже существует
        }
        
        // Регистрируем пользователя
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);
        
        if ($stmt->execute()) {
            return self::login($email, $password);
        }
        return false;
    }
    
    public static function logout() {
        session_destroy();
        header("Location: ../index.php");
        exit;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
    }
    
    public static function isManager() {
        return isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'manager' || $_SESSION['user_role'] == 'admin');
    }
    
    public static function getUserID() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>