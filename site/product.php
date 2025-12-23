<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: catalog.php");
    exit;
}

$db = getDB();
$product_id = intval($_GET['id']);

// Получаем информацию о товаре с изображениями
$sql = "SELECT p.*, 
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
        FROM products p 
        WHERE p.id = ? AND p.status = 'active'";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Товар не найден");
}

// Получаем все изображения товара
$images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC";
$images_stmt = $db->prepare($images_sql);
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$images = [];
while ($row = $images_result->fetch_assoc()) {
    $images[] = $row;
}

// Определяем основное изображение
$main_image = 'assets/images/default.jpg';
if (!empty($images)) {
    $main_image_path = 'assets/images/products/' . $images[0]['image_url'];
    $main_image_thumb = 'assets/images/thumbnails/' . $images[0]['image_url'];
    
    if (file_exists($main_image_thumb)) {
        $main_image = $main_image_thumb;
    } elseif (file_exists($main_image_path)) {
        $main_image = $main_image_path;
    }
}

// Получаем доступные размеры с количеством
$sizes_sql = "SELECT size, quantity FROM product_sizes WHERE product_id = ? AND quantity > 0 ORDER BY size";
$size_stmt = $db->prepare($sizes_sql);
$size_stmt->bind_param("i", $product_id);
$size_stmt->execute();
$sizes_result = $size_stmt->get_result();
$available_sizes = [];
while ($size_row = $sizes_result->fetch_assoc()) {
    $available_sizes[$size_row['size']] = $size_row['quantity'];
}

// Обработка добавления в корзину
$cart_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_to'] = 'product.php?id=' . $product_id;
        header("Location: login.php");
        exit;
    }
    
    $selected_size = $_POST['size'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (empty($selected_size)) {
        $cart_message = '<div class="error-message">Выберите размер</div>';
    } elseif ($quantity < 1 || $quantity > 10) {
        $cart_message = '<div class="error-message">Количество должно быть от 1 до 10</div>';
    } else {
        // Проверяем доступность размера
        if (!isset($available_sizes[$selected_size]) || $available_sizes[$selected_size] < $quantity) {
            $cart_message = '<div class="error-message">Выбранный размер недоступен в нужном количестве</div>';
        } else {
            // Добавляем в корзину
            $user_id = $_SESSION['user_id'];
            
            // Проверяем, есть ли уже этот товар в корзине
            $check_sql = "SELECT ci.id, ci.quantity FROM cart_items ci 
                         JOIN products p ON ci.product_id = p.id 
                         WHERE ci.user_id = ? AND ci.product_id = ? AND ci.size = ?";
            $check_stmt = $db->prepare($check_sql);
            $check_stmt->bind_param("iis", $user_id, $product_id, $selected_size);
            $check_stmt->execute();
            $existing_item = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing_item) {
                // Обновляем количество
                $new_quantity = $existing_item['quantity'] + $quantity;
                $update_sql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->bind_param("ii", $new_quantity, $existing_item['id']);
                $update_stmt->execute();
            } else {
                // Добавляем новый товар
                $insert_sql = "INSERT INTO cart_items (user_id, product_id, size, quantity) VALUES (?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_sql);
                $insert_stmt->bind_param("iisi", $user_id, $product_id, $selected_size, $quantity);
                $insert_stmt->execute();
            }
            
            $cart_message = '<div class="success-message">Товар добавлен в корзину!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Todizad</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            background: #2c3e50;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: white;
            text-decoration: none;
            text-transform: uppercase;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #3498db;
        }
        
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
        }
        
        .product-gallery {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .main-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        
        .thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            overflow-x: auto;
            padding: 10px 0;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .thumbnail:hover {
            border-color: #3498db;
        }
        
        .thumbnail.active {
            border-color: #2ecc71;
        }
        
        .product-info h1 {
            margin-top: 0;
            font-size: 2.2rem;
            color: #333;
        }
        
        .product-price {
            font-size: 2rem;
            color: #e74c3c;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .product-meta {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .meta-item i {
            color: #3498db;
            width: 20px;
        }
        
        .size-selector {
            margin: 30px 0;
        }
        
        .size-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .size-option {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .size-option:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .size-option.selected {
            border-color: #2ecc71;
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .size-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        .quantity-selector {
            margin: 20px 0;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ddd;
            background: white;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .quantity-input {
            width: 80px;
            height: 40px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin: 30px 0;
        }
        
        .btn-primary {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            width: 60px;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        
        .availability-info {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .product-description {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .product-features {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .product-features ul {
            list-style: none;
            padding: 0;
        }
        
        .product-features li {
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .main-image img {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container nav">
            <a href="index.php" class="logo">Todizad</a>
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
    
    <div class="container">
        <nav style="margin: 20px 0; font-size: 0.9rem; color: #666;">
            <a href="index.php" style="color: #3498db;">Главная</a> &gt; 
            <a href="catalog.php" style="color: #3498db;">Каталог</a> &gt; 
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
        
        <?php echo $cart_message; ?>
        
        <div class="product-detail">
            <div class="product-gallery">
                <!-- Главное изображение -->
                <div class="main-image">
                    <img id="main-image" src="<?php echo $main_image; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='assets/images/default.jpg'">
                </div>
                
                <!-- Миниатюры -->
                <?php if (!empty($images)): ?>
                <div class="thumbnails">
                    <?php foreach ($images as $index => $image): 
                        $thumb_path = 'assets/images/thumbnails/' . $image['image_url'];
                        $full_path = 'assets/images/products/' . $image['image_url'];
                        
                        $thumb_url = file_exists($thumb_path) ? $thumb_path : 
                                    (file_exists($full_path) ? $full_path : 'assets/images/default.jpg');
                    ?>
                        <img class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                             src="<?= $thumb_url ?>"
                             data-full="<?= $full_path ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             onerror="this.src='assets/images/default.jpg'">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-price">
                    <?php echo number_format($product['price'], 0, ',', ' '); ?> ₽
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span><strong>Бренд:</strong> <?php echo htmlspecialchars($product['brand']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-palette"></i>
                        <span><strong>Цвет:</strong> <?php echo htmlspecialchars($product['color'] ?? 'Не указан'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-layer-group"></i>
                        <span><strong>Материал:</strong> <?php echo htmlspecialchars($product['material'] ?? 'Не указан'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span><strong>Категория:</strong> <?php echo htmlspecialchars($product['category'] ?? 'Не указана'); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="add_to_cart" value="1">
                    
                    <div class="size-selector">
                        <h3>Выберите размер</h3>
                        <div class="size-options">
                            <?php
                            if (!empty($available_sizes)) {
                                foreach ($available_sizes as $size => $quantity) {
                                    $disabled = $quantity == 0;
                                    $selected = isset($_POST['size']) && $_POST['size'] == $size ? 'selected' : '';
                                    echo '<div class="size-option ' . ($disabled ? 'disabled' : '') . ' ' . $selected . '" 
                                          data-size="' . $size . '" 
                                          data-quantity="' . $quantity . '"
                                          onclick="' . ($disabled ? '' : 'selectSize(this, ' . $quantity . ')') . '">
                                          ' . $size . '
                                          </div>';
                                }
                            } else {
                                echo '<p>Размеры временно недоступны</p>';
                            }
                            ?>
                        </div>
                        <div class="availability-info" id="availability-info">
                            <?php if (!empty($available_sizes)): ?>
                                Выберите размер, чтобы увидеть доступное количество
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="size" id="selected_size" value="<?php echo $_POST['size'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="quantity-selector">
                        <h3>Количество</h3>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" name="quantity" id="quantity" class="quantity-input" value="<?php echo $_POST['quantity'] ?? 1; ?>" min="1" max="10" readonly>
                            <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <div class="product-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-cart-plus"></i> Добавить в корзину
                        </button>
                        <button type="button" class="btn-secondary" onclick="addToWishlist()">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </form>
                
                <div class="product-description">
                    <h3>Описание товара</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Описание отсутствует')); ?></p>
                </div>
                
                <div class="product-features">
                    <h3>Преимущества</h3>
                    <ul>
                        <li><i class="fas fa-check" style="color: #27ae60;"></i> Официальная гарантия</li>
                        <li><i class="fas fa-check" style="color: #27ae60;"></i> Бесплатная доставка от 5000 ₽</li>
                        <li><i class="fas fa-check" style="color: #27ae60;"></i> Возврат в течение 30 дней</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 Обувной магазин Todizad. Все права защищены.</p>
        </div>
    </div>
    
    <script>
        let selectedSize = null;
        let maxQuantity = 10;
        
        function selectSize(element, availableQuantity) {
            // Снимаем выделение со всех размеров
            document.querySelectorAll('.size-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Выделяем выбранный размер
            element.classList.add('selected');
            selectedSize = element.dataset.size;
            document.getElementById('selected_size').value = selectedSize;
            
            // Обновляем информацию о доступности
            const infoElement = document.getElementById('availability-info');
            infoElement.innerHTML = `В наличии: ${availableQuantity} шт.`;
            
            // Устанавливаем максимальное количество
            maxQuantity = Math.min(10, availableQuantity);
            const quantityInput = document.getElementById('quantity');
            if (parseInt(quantityInput.value) > maxQuantity) {
                quantityInput.value = maxQuantity;
            }
            quantityInput.max = maxQuantity;
        }
        
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let quantity = parseInt(quantityInput.value);
            quantity += change;
            
            if (quantity < 1) quantity = 1;
            if (quantity > maxQuantity) quantity = maxQuantity;
            
            quantityInput.value = quantity;
        }
        
        function addToWishlist() {
            alert('Товар добавлен в избранное!');
        }
        
        // Обработка миниатюр
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Снимаем активный класс со всех миниатюр
                document.querySelectorAll('.thumbnail').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Добавляем активный класс текущей миниатюре
                this.classList.add('active');
                
                // Обновляем главное изображение
                const mainImage = document.getElementById('main-image');
                mainImage.src = this.dataset.full;
            });
        });
        
        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            // Если есть выбранный размер из POST запроса, выделяем его
            const selectedSizeValue = document.getElementById('selected_size').value;
            if (selectedSizeValue) {
                const sizeElement = document.querySelector(`.size-option[data-size="${selectedSizeValue}"]`);
                if (sizeElement && !sizeElement.classList.contains('disabled')) {
                    const availableQuantity = sizeElement.dataset.quantity;
                    selectSize(sizeElement, parseInt(availableQuantity));
                }
            }
            
            // Обработка формы
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                if (!selectedSize) {
                    e.preventDefault();
                    alert('Пожалуйста, выберите размер');
                    return false;
                }
                
                const quantity = parseInt(document.getElementById('quantity').value);
                if (quantity < 1 || quantity > maxQuantity) {
                    e.preventDefault();
                    alert(`Количество должно быть от 1 до ${maxQuantity}`);
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>