<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Получаем подключение к БД
$db = getDB();

// Инициализируем переменные
$products = [];
$has_products = false;
$search_query = '';

// Обработка поиска
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TODIZAD - Каталог</title>
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
        
        /* Основной контент */
        .main-content {
            flex: 1;
            padding: 40px 0;
        }
        
        /* Заголовок страницы */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 36px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 18px;
        }
        
        /* Поиск */
        .search-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            text-align: center;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .search-button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            white-space: nowrap;
        }
        
        .search-button:hover {
            background-color: #2980b9;
        }
        
        /* Сетка товаров - ФИКСИРОВАННАЯ СТРУКТУРА */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
            opacity: 1;
            transition: opacity 0.3s;
        }
        
        /* Предзагрузчик (скрыт по умолчанию) */
        .products-grid.loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* Карточка товара - ФИКСИРОВАННЫЕ РАЗМЕРЫ */
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 450px; /* ФИКСИРОВАННАЯ ВЫСОТА */
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        /* Изображение товара - ФИКСИРОВАННЫЙ РАЗМЕР */
        .product-image-container {
            width: 100%;
            height: 250px; /* ФИКСИРОВАННАЯ ВЫСОТА */
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover; /* ОБРЕЗАЕМ ИЗОБРАЖЕНИЕ ПО РАЗМЕРУ */
            display: block;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        /* Плейсхолдер если нет изображения */
        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 14px;
            text-align: center;
            padding: 20px;
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
            line-height: 1.4;
            height: 50px; /* ФИКСИРОВАННАЯ ВЫСОТА ДЛЯ НАЗВАНИЯ */
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
        
        .product-meta {
            margin: 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .product-meta p {
            margin: 5px 0;
        }
        
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            margin-top: auto; /* Толкает кнопку вниз */
            transition: background-color 0.3s;
            font-size: 14px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        /* Сообщения */
        .empty-message {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            grid-column: 1 / -1;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .empty-message h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .empty-message p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Счетчик товаров */
        .products-count {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        /* Подвал */
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        /* Скелетон-загрузчик (опционально) */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Адаптивность */
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header h1 {
                font-size: 32px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-button {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .nav {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .product-card {
                height: 420px;
            }
            
            .product-image-container {
                height: 220px;
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
            <div class="page-header">
                <h1>TODIZAD</h1>
                <p>Каталог обуви</p>
            </div>
            
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Поиск по названию, бренду или категории..." 
                           value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit" class="search-button">Поиск</button>
                </form>
            </div>
            
            <div class="products-grid" id="products-grid">
                <?php
                // Получаем товары из БД
                if ($db) {
                    $sql = "SELECT p.*, pi.image_url 
                            FROM products p 
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                            WHERE p.status = 'active'";
                    
                    if (!empty($search_query)) {
                        $search = $db->real_escape_string($search_query);
                        $sql .= " AND (p.name LIKE '%$search%' 
                                OR p.description LIKE '%$search%' 
                                OR p.brand LIKE '%$search%'
                                OR p.category LIKE '%$search%')";
                    }
                    
                    $sql .= " ORDER BY p.created_at DESC";
                    $result = $db->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $has_products = true;
                        $count = 0;
                        
                        while ($product = $result->fetch_assoc()) {
                            $count++;
                            // Определяем путь к изображению
                            $image_path = '';
                            $has_image = false;
                            
                            if (!empty($product['image_url'])) {
                                // Проверяем сначала миниатюру, потом оригинал
                                $thumb_path = 'assets/images/thumbnails/' . $product['image_url'];
                                $full_path = 'assets/images/products/' . $product['image_url'];
                                
                                if (file_exists($thumb_path)) {
                                    $image_path = $thumb_path;
                                    $has_image = true;
                                } elseif (file_exists($full_path)) {
                                    $image_path = $full_path;
                                    $has_image = true;
                                }
                            }
                            ?>
                            
                            <div class="product-card" data-id="<?= $product['id'] ?>">
                                <div class="product-image-container">
                                    <?php if ($has_image): ?>
                                        <img src="<?= $image_path ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="product-image"
                                             loading="lazy" <!-- Ленивая загрузка -->
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk5OSI+Tm8gaW1hZ2U8L3RleHQ+PC9zdmc+'">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <?= htmlspecialchars($product['brand'] ?? 'Без изображения') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                                    <div class="product-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</div>
                                    <div class="product-meta">
                                        <p><strong>Бренд:</strong> <?= htmlspecialchars($product['brand']) ?></p>
                                        <p><strong>Категория:</strong> <?= htmlspecialchars($product['category']) ?></p>
                                    </div>
                                    <a href="product.php?id=<?= $product['id'] ?>" class="btn">Подробнее</a>
                                </div>
                            </div>
                            
                            <?php
                        }
                        
                        // Выводим счетчик
                        echo '<div class="products-count">Найдено товаров: ' . $count . '</div>';
                        
                    } else {
                        $has_products = false;
                    }
                } else {
                    $has_products = false;
                }
                
                // Если товаров нет
                if (!$has_products): ?>
                    <div class="empty-message">
                        <h3>Товары не найдены</h3>
                        <p><?= !empty($search_query) ? 'По запросу "' . htmlspecialchars($search_query) . '" ничего не найдено.' : 'В каталоге пока нет товаров.' ?></p>
                        <?php if (!empty($search_query)): ?>
                            <a href="catalog.php" class="btn" style="max-width: 200px; margin: 0 auto;">Показать все товары</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 TODIZAD. Все права защищены.</p>
        </div>
    </div>
    
    <script>
        // Предотвращаем дергание интерфейса
        document.addEventListener('DOMContentLoaded', function() {
            const productsGrid = document.getElementById('products-grid');
            
            // Предзагрузка изображений
            const images = productsGrid.querySelectorAll('img');
            let loadedImages = 0;
            const totalImages = images.length;
            
            if (totalImages > 0) {
                images.forEach(img => {
                    if (img.complete) {
                        imageLoaded();
                    } else {
                        img.addEventListener('load', imageLoaded);
                        img.addEventListener('error', imageLoaded);
                    }
                });
            }
            
            function imageLoaded() {
                loadedImages++;
                if (loadedImages === totalImages) {
                    // Все изображения загружены
                    productsGrid.style.opacity = '1';
                }
            }
            
            // Предотвращаем изменение размеров при загрузке
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                // Фиксируем размеры
                const width = card.offsetWidth;
                const height = card.offsetHeight;
                card.style.width = width + 'px';
                card.style.height = height + 'px';
            });
            
            // Плавная загрузка
            setTimeout(() => {
                productsGrid.style.opacity = '1';
            }, 100);
        });
        
        // Предотвращаем дергание при изменении размера окна
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const productsGrid = document.getElementById('products-grid');
                productsGrid.style.opacity = '0.5';
                
                setTimeout(() => {
                    productsGrid.style.opacity = '1';
                }, 300);
            }, 250);
        });
    </script>
    
    <?php 
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
    ?>
</body>
</html>