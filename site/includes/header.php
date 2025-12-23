<?php
// Проверяем, запущена ли сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем необходимые файлы
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TODIZAD</title>
    <style>
        .header {
            background: #2c3e50;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a, .nav-links span {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .welcome-user {
            color: #3498db !important;
            font-weight: bold;
        }
        
        .cart-count {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container nav">
            <a href="index.php" class="logo">TODIZAD</a>
            <div class="nav-links">
                <a href="index.php">Главная</a>
                <a href="catalog.php">Каталог</a>
                <a href="cart.php">
                    Корзина 
                    <?php 
                    if (isLoggedIn()) { 
                        $cart_count = getCartCount();
                        if ($cart_count > 0) { 
                    ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php 
                        }
                    } 
                    ?>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <span class="welcome-user">Привет, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Пользователь'); ?>!</span>
                    <a href="profile.php">Профиль</a>
                    <a href="orders.php">Заказы</a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                        <a href="admin/dashboard.php">Админка</a>
                    <?php endif; ?>
                    <a href="logout.php">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </div>