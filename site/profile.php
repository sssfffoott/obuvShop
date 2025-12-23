<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Получаем данные пользователя
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: login.php");
    exit;
}

// Обновление профиля
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name)) {
        $message = 'Имя обязательно для заполнения';
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $phone, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $message = 'Профиль успешно обновлен';
            $user['name'] = $name;
            $user['phone'] = $phone;
        } else {
            $message = 'Ошибка при обновлении профиля';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>TODIZAD - Мой профиль</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
        }
        
        .page-header {
            text-align: center;
            margin: 40px 0;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 50px;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        .profile-sidebar {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-info h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 1.1rem;
        }
        
        .quick-links {
            margin-top: 30px;
        }
        
        .quick-links h4 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .quick-link {
            display: block;
            padding: 12px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
            transition: background 0.3s;
        }
        
        .quick-link:hover {
            background: #2980b9;
        }
        
        .profile-form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-title {
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-control:disabled {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #c0392b;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>TODIZAD - Мой профиль</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'успешно') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <!-- Левая колонка - информация -->
            <div class="profile-sidebar">
                <div class="profile-info">
                    <h3>Личная информация</h3>
                    <div class="info-item">
                        <span class="info-label">Имя:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Телефон:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'не указан'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Роль:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['role'] == 'admin' ? 'Администратор' : 'Пользователь'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Дата регистрации:</span>
                        <span class="info-value"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="quick-links">
                    <h4>Быстрые ссылки</h4>
                    <a href="orders.php" class="quick-link">Мои заказы</a>
                    <a href="cart.php" class="quick-link">Корзина</a>
                    <a href="catalog.php" class="quick-link">Каталог товаров</a>
                </div>
            </div>
            
            <!-- Правая колонка - форма редактирования -->
            <div class="profile-form-container">
                <h2 class="form-title">Редактировать профиль</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Имя *</label>
                        <input type="text" 
                               name="name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['name']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Телефон</label>
                        <input type="tel" 
                               name="phone" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               placeholder="+7 (999) 123-45-67">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email (нельзя изменить)</label>
                        <input type="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               disabled>
                    </div>
                    
                    <button type="submit" class="btn">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 TODIZAD. Все права защищены.</p>
        </div>
    </div>
</body>
</html>