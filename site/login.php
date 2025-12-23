<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (Auth::login($email, $password)) {
        // Слияние корзин после авторизации
        if (isset($_COOKIE['cart'])) {
            $localCart = json_decode($_COOKIE['cart'], true);
            require_once 'includes/cart.php';
            Cart::mergeCarts(Auth::getUserID(), $localCart);
        }
        header("Location: index.php");
        exit;
    } else {
        $error = 'Неверный email или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; }
        .error { color: red; margin-bottom: 10px; }
        .btn { background: #333; color: white; padding: 10px; border: none; width: 100%; }
    </style>
</head>
<body>
    <h2>Вход в систему</h2>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Войти</button>
    </form>
    <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
</body>
</html>