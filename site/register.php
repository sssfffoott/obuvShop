<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Если пользователь уже авторизован, перенаправляем
if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree = isset($_POST['agree']);
    
    // Валидация
    if (empty($name)) {
        $errors[] = 'Имя обязательно';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Имя должно содержать минимум 2 символа';
    }
    
    if (empty($email)) {
        $errors[] = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }
    
    if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone)) {
        $errors[] = 'Некорректный номер телефона';
    }
    
    if (empty($password)) {
        $errors[] = 'Пароль обязателен';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }
    
    if (!$agree) {
        $errors[] = 'Необходимо согласиться с правилами';
    }
    
    if (empty($errors)) {
        $result = registerUser([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ]);
        
        if ($result['success']) {
            $success = true;
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Магазин обуви</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #666;
        }
        
        .btn {
            background: #e74c3c;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #c0392b;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .auth-footer a {
            color: #3498db;
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>
        
        <?php if ($errors): ?>
            <div class="error-message">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                Регистрация прошла успешно! Вы будете перенаправлены...
            </div>
        <?php endif; ?>
        
        <form method="POST" id="register-form">
            <div class="form-group">
                <label for="name">Имя и фамилия *</label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                       placeholder="Иван Иванов" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                       placeholder="example@mail.ru" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                       placeholder="+7 (999) 123-45-67">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль *</label>
                <input type="password" id="password" name="password" 
                       placeholder="Минимум 6 символов" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Подтверждение пароля *</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Повторите пароль" required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="agree" id="agree" required>
                    <span>Я согласен с правилами обработки персональных данных и пользовательским соглашением</span>
                </label>
            </div>
            
            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>
        
        <div class="auth-footer">
            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </div>
    
    <script>
    // Простая валидация формы
    document.getElementById('register-form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        let errors = [];
        
        if (password.length < 6) {
            errors.push('Пароль должен содержать минимум 6 символов');
        }
        
        if (password !== confirmPassword) {
            errors.push('Пароли не совпадают');
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert(errors.join('\n'));
        }
    });
    
    // Маска для телефона
    document.getElementById('phone')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            if (value.length <= 1) {
                e.target.value = '+7 (' + value;
            } else if (value.length <= 4) {
                e.target.value = '+7 (' + value.substring(1, 4);
            } else if (value.length <= 7) {
                e.target.value = '+7 (' + value.substring(1, 4) + ') ' + value.substring(4, 7);
            } else if (value.length <= 9) {
                e.target.value = '+7 (' + value.substring(1, 4) + ') ' + value.substring(4, 7) + '-' + value.substring(7, 9);
            } else {
                e.target.value = '+7 (' + value.substring(1, 4) + ') ' + value.substring(4, 7) + '-' + value.substring(7, 9) + '-' + value.substring(9, 11);
            }
        }
    });
    </script>
</body>
</html>