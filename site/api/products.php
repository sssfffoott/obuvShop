<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$db = getDB();

// Получение параметров фильтрации
$page = intval($_GET['page'] ?? 1);
$limit = intval($_GET['limit'] ?? 12);
$offset = ($page - 1) * $limit;

$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 100000);
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Строим запрос
$sql = "SELECT p.* FROM products p WHERE p.status = 'active'";
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE p.status = 'active'";
$params = [];
$types = "";

if ($category) {
    $sql .= " AND p.category = ?";
    $count_sql .= " AND p.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($brand) {
    $sql .= " AND p.brand = ?";
    $count_sql .= " AND p.brand = ?";
    $params[] = $brand;
    $types .= "s";
}

if ($min_price > 0) {
    $sql .= " AND p.price >= ?";
    $count_sql .= " AND p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price < 100000) {
    $sql .= " AND p.price <= ?";
    $count_sql .= " AND p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $count_sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Сортировка
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        // Здесь можно добавить логику сортировки по популярности
        $sql .= " ORDER BY p.created_at DESC";
        break;
    default: // newest
        $sql .= " ORDER BY p.created_at DESC";
}

// Пагинация
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Получаем общее количество
$stmt = $db->prepare($count_sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total = $total_result['total'];
$total_pages = ceil($total / $limit);

// Получаем товары
$stmt = $db->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получаем изображения для каждого товара
foreach ($products as &$product) {
    $img_stmt = $db->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
    $img_stmt->bind_param("i", $product['id']);
    $img_stmt->execute();
    $image = $img_stmt->get_result()->fetch_assoc();
    $product['image'] = $image ? $image['image_url'] : 'default.jpg';
    
    // Получаем доступные размеры
    $size_stmt = $db->prepare("SELECT size, quantity FROM product_sizes WHERE product_id = ? AND quantity > 0");
    $size_stmt->bind_param("i", $product['id']);
    $size_stmt->execute();
    $sizes = $size_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $product['sizes'] = $sizes;
}

// Формируем ответ
$response = [
    'products' => $products,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $total_pages
    ],
    'filters' => [
        'category' => $category,
        'brand' => $brand,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'search' => $search,
        'sort' => $sort
    ]
];

echo json_encode($response);
?>