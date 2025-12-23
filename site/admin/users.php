<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$db = getDB();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'update_role':
            $role = $_POST['role'];
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $role, $user_id);
            $stmt->execute();
            $message = "Роль пользователя обновлена";
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $message = "Пользователь удален";
            break;
    }
}

// Получение списка пользователей
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if ($role_filter) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        function confirmDelete(userId, userName) {
            if (confirm(`Удалить пользователя "${userName}"?`)) {
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-header">
        <div class="container">
            <h1>Управление пользователями</h1>
            <div class="admin-menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="products.php">Товары</a>
                <a href="users.php" class="active">Пользователи</a>
                <a href="../index.php">На сайт</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($message)): ?>
            <div class="success-message"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Поиск по имени или email" value="<?= htmlspecialchars($search) ?>">
                <select name="role">
                    <option value="">Все роли</option>
                    <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>Пользователь</option>
                    <option value="manager" <?= $role_filter == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                    <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Администратор</option>
                </select>
                <button type="submit" class="btn">Фильтровать</button>
            </form>
        </div>
        
        <table class="cart-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>Пользователь</option>
                                <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Администратор</option>
                            </select>
                        </form>
                    </td>
                    <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['role'] != 'admin'): ?>
                            <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')" 
                                    class="btn" style="background: #e74c3c; padding: 5px 10px;">
                                Удалить
                            </button>
                        <?php else: ?>
                            <span style="color: #7f8c8d;">Нельзя удалить</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <form id="deleteForm" method="POST" style="display: none;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="delete_user_id">
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>