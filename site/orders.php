<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$orders = getUserOrders($user_id);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои заказы</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .order-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .status-created { background: #f39c12; color: white; }
        .status-paid { background: #3498db; color: white; }
        .status-processing { background: #9b59b6; color: white; }
        .status-shipped { background: #1abc9c; color: white; }
        .status-delivered { background: #27ae60; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Мои заказы</h1>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h2>У вас пока нет заказов</h2>
                <p>Сделайте свой первый заказ!</p>
                <a href="catalog.php" class="btn">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div style="background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div>
                                <h3>Заказ #<?= $order['id'] ?></h3>
                                <p style="color: #7f8c8d;"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div>
                                <span class="order-status status-<?= $order['status'] ?>">
                                    <?php
                                    $status_labels = [
                                        'created' => 'Оформлен',
                                        'paid' => 'Оплачен',
                                        'processing' => 'В обработке',
                                        'shipped' => 'Отправлен',
                                        'delivered' => 'Доставлен',
                                        'cancelled' => 'Отменен'
                                    ];
                                    echo $status_labels[$order['status']] ?? $order['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <p><strong>Адрес доставки:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                            <p><strong>Способ оплаты:</strong> 
                                <?= $order['payment_method'] == 'cash' ? 'Наличными при получении' : 'Банковской картой онлайн' ?>
                            </p>
                        </div>
                        
                        <?php $order_items = getOrderItems($order['id']); ?>
                        <?php if (!empty($order_items)): ?>
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 15px;">
                                <h4>Товары:</h4>
                                <?php foreach ($order_items as $item): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee;">
                                        <div>
                                            <p><?= htmlspecialchars($item['name']) ?></p>
                                            <small style="color: #7f8c8d;">Размер: <?= $item['size'] ?>, Количество: <?= $item['quantity'] ?></small>
                                        </div>
                                        <div>
                                            <p><?= formatPrice($item['price'] * $item['quantity']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="text-align: right;">
                            <h3>Итого: <?= formatPrice($order['total_amount']) ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>