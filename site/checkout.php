<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_to'] = 'checkout.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);

if (empty($cart_items)) {
    redirect('cart.php');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($address)) {
        $error = 'Введите адрес доставки';
    } elseif (empty($payment_method)) {
        $error = 'Выберите способ оплаты';
    } else {
        try {
            $order_id = createOrder($user_id, $address, $payment_method);
            $success = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оформление заказа</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="success-message">
                <h2>Заказ успешно оформлен!</h2>
                <p>Номер вашего заказа: <strong>#<?= $order_id ?></strong></p>
                <p>Мы свяжемся с вами для подтверждения заказа.</p>
                <a href="orders.php" class="btn">Мои заказы</a>
                <a href="catalog.php" class="btn">Продолжить покупки</a>
            </div>
        <?php else: ?>
            <h1>Оформление заказа</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
                <div class="form-container">
                    <form method="POST">
                        <div class="form-group">
                            <label>Адрес доставки *</label>
                            <textarea name="address" rows="4" required><?= $_POST['address'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Способ оплаты *</label>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" name="payment_method" value="cash" required>
                                    <span>Наличными при получении</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" name="payment_method" value="card" required>
                                    <span>Банковской картой онлайн</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Подтвердить заказ</button>
                    </form>
                </div>
                
                <div>
                    <h3>Ваш заказ</h3>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px;">
                        <?php foreach ($cart_items as $item): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                <div>
                                    <p><?= htmlspecialchars($item['name']) ?></p>
                                    <small style="color: #7f8c8d;">Размер: <?= $item['size'] ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <p><?= formatPrice($item['price']) ?></p>
                                    <small style="color: #7f8c8d;">x<?= $item['quantity'] ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #333;">
                            <strong>Итого:</strong>
                            <strong><?= formatPrice($cart_total) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>