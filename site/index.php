<?php
// Начинаем сессию
session_start();

// Подключаем конфигурацию
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Получаем подключение к БД
$db = getDB();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TODIZAD - Главная</title>
    <style>
        /* Базовые сбросы */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Шапка */
        .header {
            background-color: #2c3e50;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        /* Герой секция */
        .hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            text-align: center;
            padding: 80px 20px;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 20px;
            letter-spacing: 3px;
        }
        
        .hero-subtitle {
            font-size: 20px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .hero .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .hero .btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Секция товаров */
        .products-section {
            padding: 40px 0;
            flex: 1;
        }
        
        .products-section h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
            color: #2c3e50;
            position: relative;
            padding-bottom: 15px;
        }
        
        .products-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: #3498db;
            border-radius: 2px;
        }
        
        /* Сетка товаров */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Карточка товара */
        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        /* Изображение товара */
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
            border-bottom: 1px solid #eee;
            background-color: #f8f9fa;
        }
        
        /* Информация о товаре */
        .product-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-info h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
            min-height: 54px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-price {
            font-size: 22px;
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .product-info p {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        
        .product-info .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            margin-top: 15px;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        
        .product-info .btn:hover {
            background-color: #2980b9;
        }
        
        /* Подвал */
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        /* Сообщения */
        .empty-message {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            margin: 20px 0;
            grid-column: 1 / -1;
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
                <a href="cart.php">Корзина</a>
                <?php if (isset($_SESSION['user_id'])) { ?>
                    <a href="profile.php">Профиль</a>
                    <a href="index.php?logout=1">Выйти</a>
                <?php } else { ?>
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <div class="hero">
        <div class="container">
            <h1>TODIZAD</h1>
            <p class="hero-subtitle">Лучший выбор для вас</p>
            <a href="catalog.php" class="btn">Смотреть каталог</a>
        </div>
    </div>
    
    <div class="container products-section">
        <h2>Популярные товары</h2>
        <div class="products-grid">
            <?php
            // Инициализируем переменные
            $products = [];
            $has_products = false;
            
            // Проверяем есть ли таблица products
            if ($db) {
                $check_table = $db->query("SHOW TABLES LIKE 'products'");
                
                if ($check_table && $check_table->num_rows > 0) {
                    // Получаем товары с их изображениями
                    $sql = "SELECT p.*, pi.image_url 
                            FROM products p 
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                            WHERE p.status = 'active' 
                            ORDER BY p.created_at DESC 
                            LIMIT 8";
                    $result = $db->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $has_products = true;
                        while ($product = $result->fetch_assoc()) {
                            $products[] = $product;
                        }
                    }
                }
            }
            
            // Отображаем товары
            if ($has_products) {
                foreach ($products as $product) {
                    // Определяем путь к изображению
                    $image_path = 'assets/images/default.jpg';
                    
                    if (!empty($product['image_url'])) {
                        // Проверяем сначала миниатюру, потом оригинал
                        $thumb_path = 'assets/images/thumbnails/' . $product['image_url'];
                        $full_path = 'assets/images/products/' . $product['image_url'];
                        
                        if (file_exists($thumb_path)) {
                            $image_path = $thumb_path;
                        } elseif (file_exists($full_path)) {
                            $image_path = $full_path;
                        }
                    }
                    
                    echo '<div class="product-card">';
                    echo '<img src="' . $image_path . '" 
                               alt="' . htmlspecialchars($product['name']) . '" 
                               class="product-image"
                               onerror="this.src=\'assets/images/default.jpg\'">';
                    echo '<div class="product-info">';
                    echo '<h3>' . htmlspecialchars($product['name']) . '</h3>';
                    echo '<div class="product-price">' . number_format($product['price'], 0, ',', ' ') . ' ₽</div>';
                    echo '<p><strong>Бренд:</strong> ' . htmlspecialchars($product['brand']) . '</p>';
                    echo '<p><strong>Категория:</strong> ' . htmlspecialchars($product['category']) . '</p>';
                    echo '<a href="product.php?id=' . $product['id'] . '" class="btn">Подробнее</a>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="empty-message">';
                if (!$db) {
                    echo '<p>Ошибка подключения к базе данных. Проверьте файл config.php</p>';
                } else {
                    echo '<p>Товары пока не добавлены в базу данных.</p>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 TODIZAD. Все права защищены.</p>
        </div>
    </div>
    
    <?php 
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
    ?>
</body>
</html>