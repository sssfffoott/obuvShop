<?php
// Общие вспомогательные функции

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['user_role'] === $role || 
           ($role === 'manager' && $_SESSION['user_role'] === 'admin');
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('HTTP/1.0 403 Forbidden');
        die('Доступ запрещен');
    }
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ₽';
}

function getCartCount() {
    if (isLoggedIn()) {
        $db = getDB();
        $user_id = $_SESSION['user_id'];
        $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    } else {
        return 0;
    }
}

function getCategories() {
    $db = getDB();
    $result = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

function getBrands() {
    $db = getDB();
    $result = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand");
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row['brand'];
    }
    return $brands;
}

function getProduct($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getProductSizes($product_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM product_sizes WHERE product_id = ? AND quantity > 0 ORDER BY size");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sizes = [];
    while ($row = $result->fetch_assoc()) {
        $sizes[] = $row;
    }
    return $sizes;
}

function getProductImages($product_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    return $images;
}

function addToCart($user_id, $product_id, $size, $quantity = 1) {
    $db = getDB();
    
    // Проверяем наличие товара на складе
    $stmt = $db->prepare("SELECT quantity FROM product_sizes WHERE product_id = ? AND size = ?");
    $stmt->bind_param("id", $product_id, $size);
    $stmt->execute();
    $stock = $stmt->get_result()->fetch_assoc();
    
    if (!$stock || $stock['quantity'] < $quantity) {
        return false;
    }
    
    // Проверяем, есть ли уже такой товар в корзине
    $stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND size = ?");
    $stmt->bind_param("iid", $user_id, $product_id, $size);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Обновляем количество
        $new_quantity = $existing['quantity'] + $quantity;
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $existing['id']);
        return $stmt->execute();
    } else {
        // Добавляем новый товар
        $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, size, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iidi", $user_id, $product_id, $size, $quantity);
        return $stmt->execute();
    }
}

function getCartItems($user_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            ci.id as cart_item_id,
            ci.quantity,
            ci.size,
            p.id as product_id,
            p.name,
            p.price,
            p.brand,
            pi.image_url
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE ci.user_id = ?
        ORDER BY ci.id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getCartTotal($user_id) {
    $items = getCartItems($user_id);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function removeFromCart($user_id, $cart_item_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND id = ?");
    $stmt->bind_param("ii", $user_id, $cart_item_id);
    return $stmt->execute();
}

function updateCartQuantity($user_id, $cart_item_id, $quantity) {
    if ($quantity <= 0) {
        return removeFromCart($user_id, $cart_item_id);
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $cart_item_id);
    return $stmt->execute();
}

function clearCart($user_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

function createOrder($user_id, $address, $payment_method) {
    $db = getDB();
    
    // Начинаем транзакцию
    $db->begin_transaction();
    
    try {
        // Получаем товары из корзины
        $cart_items = getCartItems($user_id);
        
        if (empty($cart_items)) {
            throw new Exception("Корзина пуста");
        }
        
        // Проверяем наличие товаров на складе
        foreach ($cart_items as $item) {
            $stmt = $db->prepare("SELECT quantity FROM product_sizes WHERE product_id = ? AND size = ?");
            $stmt->bind_param("id", $item['product_id'], $item['size']);
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc();
            
            if ($stock['quantity'] < $item['quantity']) {
                throw new Exception("Недостаточно товара на складе: " . $item['name']);
            }
        }
        
        // Считаем общую сумму
        $total = getCartTotal($user_id);
        
        // Создаем заказ
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("idss", $user_id, $total, $address, $payment_method);
        $stmt->execute();
        $order_id = $db->insert_id;
        
        // Добавляем товары в заказ и обновляем остатки
        foreach ($cart_items as $item) {
            // Добавляем в заказ
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, size, quantity, price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidid", $order_id, $item['product_id'], $item['size'], $item['quantity'], $item['price']);
            $stmt->execute();
            
            // Обновляем остатки на складе
            $stmt = $db->prepare("
                UPDATE product_sizes 
                SET quantity = quantity - ? 
                WHERE product_id = ? AND size = ?
            ");
            $stmt->bind_param("iid", $item['quantity'], $item['product_id'], $item['size']);
            $stmt->execute();
        }
        
        // Очищаем корзину
        clearCart($user_id);
        
        // Подтверждаем транзакцию
        $db->commit();
        return $order_id;
        
    } catch (Exception $e) {
        // Откатываем транзакцию при ошибке
        $db->rollback();
        throw $e;
    }
}

function getUserOrders($user_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderItems($order_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT oi.*, p.name, p.brand
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getStats() {
    $db = getDB();
    
    $stats = [];
    
    // Общее количество товаров
    $result = $db->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $result->fetch_assoc()['count'];
    
    // Общее количество заказов
    $result = $db->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $result->fetch_assoc()['count'];
    
    // Общая выручка
    $result = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
    $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Количество пользователей
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

// Функция для регистрации пользователя
function registerUser($data) {
    $db = getDB();
    
    // Проверяем, существует ли email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'error' => 'Пользователь с таким email уже зарегистрирован'];
    }
    
    // Хэшируем пароль
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Добавляем пользователя
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $data['name'], $data['email'], $hashed_password, $data['phone']);
    
    if ($stmt->execute()) {
        $user_id = $db->insert_id;
        
        // Автоматически логиним пользователя
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];
        $_SESSION['user_role'] = 'user';
        
        return ['success' => true, 'user_id' => $user_id];
    }
    
    return ['success' => false, 'error' => 'Ошибка при регистрации пользователя'];
}
?>