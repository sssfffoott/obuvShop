<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    $_SESSION['redirect_to'] = 'cart.php';
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Инициализируем переменные
$message = '';
$cart_items = [];
$cart_total = 0;

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        // Проверяем наличие массива quantity
        if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $item_id => $quantity) {
                $item_id = intval($item_id);
                $quantity = intval($quantity);
                
                if ($quantity > 0) {
                    updateCartQuantity($user_id, $item_id, $quantity);
                } else {
                    removeFromCart($user_id, $item_id);
                }
            }
        }
        $message = "Корзина обновлена";
    } elseif ($action === 'remove') {
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            removeFromCart($user_id, $item_id);
            $message = "Товар удален из корзины";
        }
    } elseif ($action === 'clear') {
        clearCart($user_id);
        $message = "Корзина очищена";
    }
}

// Получаем данные корзины
$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>TODIZAD - Корзина</title>
    <style>
        /* Базовые стили */
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
        
        /* Шапка (такая же как в index.php) */
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
        
        /* Основной контент */
        .main-content {
            flex: 1;
            padding: 40px 0;
        }
        
        .page-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        /* Сообщения */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Пустая корзина */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .empty-cart h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        .empty-cart .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .empty-cart .btn:hover {
            background-color: #2980b9;
        }
        
        /* Таблица корзины */
        .cart-table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .cart-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .cart-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .cart-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Строка товара */
        .product-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        
        .product-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .product-details p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        /* Количество */
        .quantity-input {
            width: 70px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Кнопки */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        /* Действия корзины */
        .cart-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .cart-actions .btn {
            flex: 1;
            min-width: 200px;
        }
        
        /* Итого */
        .cart-total {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: right;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .cart-total h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .cart-total .btn {
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
        }
        
        /* Подвал */
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .cart-actions {
                flex-direction: column;
            }
            
            .cart-actions .btn {
                width: 100%;
            }
            
            .product-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
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
    
    <div class="main-content">
        <div class="container">
            <h1 class="page-title">TODIZAD - Корзина покупок</h1>
            
            <?php if (!empty($message)): ?>
                <div class="message success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <h2>Ваша корзина пуста</h2>
                    <p>Добавьте товары из каталога</p>
                    <a href="catalog.php" class="btn">Перейти в каталог</a>
                </div>
            <?php else: ?>
                <form method="POST" id="cart-form">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="cart-table-container">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th>Цена</th>
                                    <th>Размер</th>
                                    <th>Количество</th>
                                    <th>Итого</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <?php if (is_array($item) && isset($item['cart_item_id'])): ?>
                                <tr>
                                    <td>
                                        <div class="product-row">
                                            <?php 
                                            $image_path = 'assets/images/default.jpg';
                                            if (!empty($item['image_url'])) {
                                                $full_path = 'assets/images/products/' . $item['image_url'];
                                                $thumb_path = 'assets/images/thumbnails/' . $item['image_url'];
                                                
                                                if (file_exists($thumb_path)) {
                                                    $image_path = $thumb_path;
                                                } elseif (file_exists($full_path)) {
                                                    $image_path = $full_path;
                                                }
                                            }
                                            ?>
                                            <img src="<?= $image_path ?>" 
                                                 alt="<?= htmlspecialchars($item['name'] ?? 'Товар') ?>" 
                                                 class="product-image"
                                                 onerror="this.src='assets/images/default.jpg'">
                                            <div class="product-details">
                                                <h4><?= htmlspecialchars($item['name'] ?? 'Товар') ?></h4>
                                                <p><?= htmlspecialchars($item['brand'] ?? '') ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= isset($item['price']) ? number_format($item['price'], 0, ',', ' ') . ' ₽' : '0 ₽' ?></td>
                                    <td><?= htmlspecialchars($item['size'] ?? '') ?></td>
                                    <td>
                                        <input type="number" 
                                               name="quantity[<?= $item['cart_item_id'] ?>]" 
                                               value="<?= $item['quantity'] ?? 1 ?>" 
                                               min="1" 
                                               max="10"
                                               class="quantity-input">
                                    </td>
                                    <td>
                                        <?php 
                                        $item_total = 0;
                                        if (isset($item['price']) && isset($item['quantity'])) {
                                            $item_total = $item['price'] * $item['quantity'];
                                        }
                                        echo number_format($item_total, 0, ',', ' ') . ' ₽';
                                        ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="item_id" value="<?= $item['cart_item_id'] ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Удалить этот товар?')">
                                                Удалить
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="cart-actions">
                        <button type="submit" class="btn">Обновить корзину</button>
                        
                        <form method="POST" style="display: inline; flex: 1;">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-secondary" 
                                    onclick="return confirm('Очистить всю корзину?')">
                                Очистить корзину
                            </button>
                        </form>
                    </div>
                </form>
                
                <div class="cart-total">
                    <h3>Итого: <?= number_format($cart_total, 0, ',', ' ') ?> ₽</h3>
                    <a href="checkout.php" class="btn">Оформить заказ</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 TODIZAD. Все права защищены.</p>
        </div>
    </div>
</body>
</html>