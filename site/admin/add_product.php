<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Проверка прав администратора
if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$db = getDB();
$message = '';
$errors = [];

// Получаем существующие категории и бренды
$categories_result = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$brands_result = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand");
$brands = [];
if ($brands_result) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row['brand'];
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $sizes = $_POST['sizes'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    // Валидация
    if (empty($name)) {
        $errors[] = 'Название товара обязательно';
    }
    
    if (empty($category)) {
        $errors[] = 'Категория обязательна';
    }
    
    if (empty($brand)) {
        $errors[] = 'Бренд обязателен';
    }
    
    if ($price <= 0) {
        $errors[] = 'Цена должна быть больше 0';
    }
    
    if (empty($sizes)) {
        $errors[] = 'Добавьте хотя бы один размер';
    }
    
    // Проверка размеров
    $valid_sizes = [];
    foreach ($sizes as $index => $size) {
        $size = trim($size);
        $quantity = intval($quantities[$index] ?? 0);
        
        if (!empty($size) && $quantity > 0) {
            $valid_sizes[] = [
                'size' => $size,
                'quantity' => $quantity
            ];
        }
    }
    
    if (empty($valid_sizes)) {
        $errors[] = 'Добавьте хотя бы один размер с количеством больше 0';
    }
    
    // Если нет ошибок, сохраняем товар
    if (empty($errors)) {
        $db->begin_transaction();
        
        try {
            // Добавляем товар
            $stmt = $db->prepare("INSERT INTO products (name, category, brand, price, description, material, color, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssdsss", $name, $category, $brand, $price, $description, $material, $color);
            $stmt->execute();
            
            $product_id = $db->insert_id;
            
            // Добавляем размеры
            foreach ($valid_sizes as $size_data) {
                $stmt = $db->prepare("INSERT INTO product_sizes (product_id, size, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $product_id, $size_data['size'], $size_data['quantity']);
                $stmt->execute();
            }
            
            // Обработка изображений
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../assets/images/products/';
                $thumb_dir = '../assets/images/thumbnails/';
                
                // Создаем директории если их нет
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                if (!file_exists($thumb_dir)) mkdir($thumb_dir, 0777, true);
                
                $is_main = true;
                
                foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $filename = uniqid() . '_' . basename($_FILES['images']['name'][$index]);
                        $target_file = $upload_dir . $filename;
                        
                        // Проверяем тип файла
                        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($imageFileType, $allowed_types)) {
                            // Сохраняем оригинал
                            move_uploaded_file($tmp_name, $target_file);
                            
                            // Создаем миниатюру
                            $thumb_file = $thumb_dir . $filename;
                            createThumbnail($target_file, $thumb_file, 300, 300);
                            
                            // Сохраняем в БД
                            $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, ?)");
                            $main_value = $is_main ? 1 : 0;
                            $stmt->bind_param("isi", $product_id, $filename, $main_value);
                            $stmt->execute();
                            
                            $is_main = false;
                        }
                    }
                }
            }
            
            $db->commit();
            $message = "Товар успешно добавлен! ID: " . $product_id;
            
            // Перенаправляем на редактирование для добавления доп. изображений
            header("Location: edit_product.php?id=" . $product_id . "&message=" . urlencode($message));
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Ошибка при добавлении товара: " . $e->getMessage();
        }
    }
}

// Функция для создания миниатюры
function createThumbnail($source, $dest, $width, $height) {
    list($src_width, $src_height, $type) = getimagesize($source);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    $ratio = min($width/$src_width, $height/$src_height);
    $new_width = round($src_width * $ratio);
    $new_height = round($src_height * $ratio);
    
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    // Сохраняем прозрачность для PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
    }
    
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $dest, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $dest);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $dest);
            break;
    }
    
    imagedestroy($src);
    imagedestroy($thumb);
    
    return true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TODIZAD - Добавить товар</title>
    <style>
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .sizes-container {
            margin-top: 20px;
        }
        
        .size-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .size-row input {
            flex: 1;
        }
        
        .size-row input[type="number"] {
            width: 100px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .image-upload {
            border: 2px dashed #ddd;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .image-upload:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .image-upload i {
            font-size: 48px;
            color: #95a5a6;
            margin-bottom: 10px;
        }
        
        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        
        .preview-item {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .preview-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Сайдбар -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>TODIZAD</h2>
                <small>Панель администратора</small>
            </div>
            
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a></li>
                <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Товары</a></li>
                <li><a href="categories.php"><i class="fas fa-list"></i> Категории</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Пользователи</a></li>
                <li><a href="../index.php"><i class="fas fa-external-link-alt"></i> На сайт</a></li>
                <li><a href="../index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- Основное содержимое -->
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-plus"></i> Добавить товар</h1>
                <div class="user-info">
                    <span>Администратор: <?php echo $_SESSION['user_name'] ?? 'Админ'; ?></span>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="form-container">
                <!-- Основная информация -->
                <div class="form-group">
                    <h3>Основная информация</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Название товара *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Категория *</label>
                        <input type="text" id="category" name="category" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" 
                               list="categories" required>
                        <datalist id="categories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="brand">Бренд *</label>
                        <input type="text" id="brand" name="brand" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>" 
                               list="brands" required>
                        <datalist id="brands">
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo htmlspecialchars($brand); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Цена (₽) *</label>
                        <input type="number" id="price" name="price" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                               step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="material">Материал</label>
                        <input type="text" id="material" name="material" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['material'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Цвет</label>
                        <input type="text" id="color" name="color" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Размеры -->
                <div class="form-group">
                    <h3>Размеры и количество</h3>
                    <div class="sizes-container" id="sizes-container">
                        <div class="size-row">
                            <input type="text" name="sizes[]" placeholder="Размер (например: 42)" class="form-control">
                            <input type="number" name="quantities[]" placeholder="Количество" min="0" class="form-control">
                            <button type="button" class="btn btn-small" onclick="addSizeRow()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Изображения -->
                <div class="form-group">
                    <h3>Изображения</h3>
                    <div class="image-upload" onclick="document.getElementById('images').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Нажмите для загрузки изображений</p>
                        <p><small>Первое изображение будет основным</small></p>
                    </div>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" 
                           style="display: none;" onchange="previewImages(this)">
                    
                    <div class="image-preview" id="image-preview"></div>
                </div>
                
                <!-- Кнопки -->
                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Сохранить товар
                    </button>
                    <a href="products.php" class="btn" style="background: #95a5a6;">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let sizeRowCount = 1;
        
        function addSizeRow() {
            const container = document.getElementById('sizes-container');
            const newRow = document.createElement('div');
            newRow.className = 'size-row';
            newRow.innerHTML = `
                <input type="text" name="sizes[]" placeholder="Размер" class="form-control">
                <input type="number" name="quantities[]" placeholder="Количество" min="0" class="form-control">
                <button type="button" class="btn btn-small btn-danger" onclick="removeSizeRow(this)">
                    <i class="fas fa-minus"></i>
                </button>
            `;
            container.appendChild(newRow);
            sizeRowCount++;
        }
        
        function removeSizeRow(button) {
            if (sizeRowCount > 1) {
                button.parentElement.remove();
                sizeRowCount--;
            }
        }
        
        function previewImages(input) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (input.files) {
                for (let i = 0; i < input.files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeImage(${i})">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(input.files[i]);
                }
            }
        }
        
        function removeImage(index) {
            // TODO: Реализовать удаление выбранного файла
            alert('Для удаления изображения отмените выбор файла');
        }
        
        // Автозаполнение категорий и брендов
        document.addEventListener('DOMContentLoaded', function() {
            const categoryInput = document.getElementById('category');
            const brandInput = document.getElementById('brand');
            
            // При вводе новой категории/бренда добавляем в список
            categoryInput.addEventListener('change', function() {
                if (this.value && !document.querySelector('#categories option[value="' + this.value + '"]')) {
                    const option = document.createElement('option');
                    option.value = this.value;
                    document.getElementById('categories').appendChild(option);
                }
            });
            
            brandInput.addEventListener('change', function() {
                if (this.value && !document.querySelector('#brands option[value="' + this.value + '"]')) {
                    const option = document.createElement('option');
                    option.value = this.value;
                    document.getElementById('brands').appendChild(option);
                }
            });
        });
    </script>
</body>
</html>