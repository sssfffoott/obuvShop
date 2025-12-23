<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$stats = getStats();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-header">
        <div class="container">
            <h1>Панель администратора</h1>
            <div class="admin-menu">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="products.php">Товары</a>
                <a href="users.php">Пользователи</a>
                <a href="../index.php">На сайт</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <h2>Статистика</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Всего товаров</h3>
                <div class="number"><?= $stats['total_products'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Всего заказов</h3>
                <div class="number"><?= $stats['total_orders'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Общая выручка</h3>
                <div class="number"><?= formatPrice($stats['total_revenue']) ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Пользователи</h3>
                <div class="number"><?= $stats['total_users'] ?></div>
            </div>
        </div>
        
        <div class="recent-orders">
            <h3>Последние заказы</h3>
            <?php
            $db = getDB();
            $result = $db->query("
                SELECT o.*, u.name as user_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT 10
            ");
            
            if ($result->num_rows > 0):
            ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['user_name']) ?></td>
                        <td><?= formatPrice($order['total_amount']) ?></td>
                        <td><?= $order['status'] ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Заказов пока нет.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>