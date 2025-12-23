<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$response = [];

switch ($action) {
    case 'add':
        $product_id = intval($input['product_id'] ?? 0);
        $size_id = intval($input['size_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        
        if (addToCart($user_id, $product_id, $size_id, $quantity)) {
            $response['success'] = true;
            $response['cart_count'] = getCartCount();
        } else {
            $response['error'] = 'Не удалось добавить товар в корзину';
        }
        break;
        
    case 'update':
        $items = $input['items'] ?? [];
        foreach ($items as $item) {
            updateCartQuantity($user_id, $item['id'], $item['quantity']);
        }
        $response['success'] = true;
        $response['cart_count'] = getCartCount();
        break;
        
    case 'remove':
        $item_id = intval($input['item_id'] ?? 0);
        if (removeFromCart($user_id, $item_id)) {
            $response['success'] = true;
            $response['cart_count'] = getCartCount();
        } else {
            $response['error'] = 'Не удалось удалить товар';
        }
        break;
        
    case 'clear':
        if (clearCart($user_id)) {
            $response['success'] = true;
            $response['cart_count'] = 0;
        }
        break;
        
    case 'get':
        $cart_items = getCartItems($user_id);
        $response['items'] = $cart_items;
        $response['total'] = getCartTotal($user_id);
        $response['count'] = getCartCount();
        break;
        
    default:
        $response['error'] = 'Неизвестное действие';
}

echo json_encode($response);
?>