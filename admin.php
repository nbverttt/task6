<?php

$db_user = 'u82591';
$db_pass = '2762718';
$db_name = 'u82591';

try {
    $db = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к БД');
}

$stmt = $db->query("SELECT login, password_hash FROM admins LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $default_login = 'admin';
    $default_password = 'admin123';
    $password_hash = md5($default_password);
    
    $db->exec("INSERT INTO admins (login, password_hash) VALUES ('$default_login', '$password_hash')");
    $admin = ['login' => $default_login, 'password_hash' => $password_hash];
}

if (
    empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $admin['login'] ||
    md5($_SERVER['PHP_AUTH_PW']) !== $admin['password_hash']
) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin panel"');
    echo '<h1>401 Требуется авторизация</h1>';
    echo '<p>Доступ запрещён. Введите логин и пароль администратора.</p>';
    exit();
}

$message = '';
$message_type = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $db->beginTransaction();
        $db->exec("DELETE FROM application_languages WHERE application_id = $id");
        $db->exec("DELETE FROM application WHERE id = $id");
        $db->commit();
        $message = "Запись #$id успешно удалена.";
        $message_type = 'success';
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Ошибка удаления: " . $e->getMessage();
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $fio = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = $_POST['contract_accepted'] ?? '0';
    $languages = $_POST['languages'] ?? [];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE application SET fio=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_accepted=? WHERE id=?");
        $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $id]);
        
        $db->exec("DELETE FROM application_languages WHERE application_id = $id");
        
        if (!empty($languages)) {
            $placeholders = implode(',', array_fill(0, count($languages), '?'));
            $stmt_lang = $db->prepare("SELECT id, name FROM programming_languages WHERE name IN ($placeholders)");
            $stmt_lang->execute($languages);
            $lang_map = [];
            while ($row = $stmt_lang->fetch(PDO::FETCH_ASSOC)) {
                $lang_map[$row['name']] = $row['id'];
            }
            
            $stmt_link = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang) {
                if (isset($lang_map[$lang])) {
                    $stmt_link->execute([$id, $lang_map[$lang]]);
                }
            }
        }
        
        $db->commit();
        $message = "Запись #$id успешно обновлена.";
        $message_type = 'success';
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Ошибка обновления: " . $e->getMessage();
        $message_type = 'error';
    }
}

$stmt = $db->query("
    SELECT a.*, GROUP_CONCAT(pl.name SEPARATOR ', ') as languages_list
    FROM application a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages pl ON al.language_id = pl.id
    GROUP BY a.id
    ORDER BY a.id DESC
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_stats = $db->query("
    SELECT pl.name, COUNT(al.application_id) as count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id, pl.name
    ORDER BY count DESC
");
$stats = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

$total_users = $db->query("SELECT COUNT(*) FROM application")->fetchColumn();

$edit_mode = false;
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM application WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_data) {
        $stmt_lang = $db->prepare("SELECT pl.name FROM application_languages al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.application_id = ?");
        $stmt_lang->execute([$edit_id]);
        $edit_data['languages'] = $stmt_lang->fetchAll(PDO::FETCH_COLUMN);
        $edit_mode = true;
    }
}

$all_languages = $db->query("SELECT name FROM programming_languages ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 24px; }
        .header .nav-links { display: flex; gap: 15px; }
        .header .nav-links a { color: white; text-decoration: none; }
        .header .nav-links a:hover { text-decoration: underline; }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda; color: #155724; border-left: 4px solid #28a745;
        }
        .message.error {
            background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545;
        }
        
        .stats-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
        }
        .stat-card .label {
            color: #666;
            margin-top: 5px;
        }
        
        .panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .panel h2 {
            margin-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:hover { background: #f8f9fa; }
        
        .actions a {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            margin: 2px;
            font-size: 12px;
        }
        .btn-edit { background: #3498db; }
        .btn-delete { background: #e74c3c; }
        .btn-save { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-cancel { background: #95a5a6; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; display: inline-block; }
        
        .edit-form input, .edit-form select, .edit-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .edit-form label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Админ-панель</h1>
            <div class="nav-links">
                <a href="index.php">На сайт</a>
                <a href="admin.php?logout=1">Выйти</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="panel">
            <h2>Статистика</h2>
            <div class="stats-panel">
                <div class="stat-card">
                    <div class="number"><?= $total_users ?></div>
                    <div class="label">Всего пользователей</div>
                </div>
                <?php foreach ($stats as $stat): ?>
                    <div class="stat-card">
                        <div class="number"><?= $stat['count'] ?></div>
                        <div class="label"><?= htmlspecialchars($stat['name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($edit_mode && $edit_data): ?>
        <div class="panel">
            <h2>Редактирование записи #<?= $edit_data['id'] ?></h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                
                <label>ФИО:</label>
                <input type="text" name="fio" value="<?= htmlspecialchars($edit_data['fio']) ?>">
                
                <label>Телефон:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($edit_data['phone']) ?>">
                
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email']) ?>">
                
                <label>Дата рождения:</label>
                <input type="date" name="birth_date" value="<?= htmlspecialchars($edit_data['birth_date']) ?>">
                
                <label>Пол:</label>
                <select name="gender">
                    <option value="male" <?= $edit_data['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $edit_data['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
                
                <label>Биография:</label>
                <textarea name="biography" rows="3"><?= htmlspecialchars($edit_data['biography']) ?></textarea>
                
                <label>Контракт:</label>
                <select name="contract_accepted">
                    <option value="1" <?= $edit_data['contract_accepted'] == 1 ? 'selected' : '' ?>>Принят</option>
                    <option value="0" <?= $edit_data['contract_accepted'] == 0 ? 'selected' : '' ?>>Не принят</option>
                </select>
                
                <label>Языки:</label>
                <select name="languages[]" multiple style="height: 120px;">
                    <?php foreach ($all_languages as $lang): ?>
                        <option value="<?= $lang ?>" <?= in_array($lang, $edit_data['languages']) ? 'selected' : '' ?>><?= $lang ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div style="margin-top: 15px;">
                    <button type="submit" class="btn-save">Сохранить</button>
                    <a href="admin.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="panel">
            <h2>Все записи (<?= count($applications) ?>)</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Дата рождения</th>
                            <th>Пол</th>
                            <th>Языки</th>
                            <th>Контракт</th>
                            <th>Логин</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 30px;">
                                    Нет данных. <a href="index.php">Заполните форму</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?= $app['id'] ?></td>
                                    <td><?= htmlspecialchars(substr($app['fio'], 0, 30)) ?><?= strlen($app['fio']) > 30 ? '...' : '' ?></td>
                                    <td><?= htmlspecialchars($app['phone']) ?></td>
                                    <td><?= htmlspecialchars(substr($app['email'], 0, 25)) ?><?= strlen($app['email']) > 25 ? '...' : '' ?></td>
                                    <td><?= htmlspecialchars($app['birth_date']) ?></td>
                                    <td><?= $app['gender'] === 'male' ? 'М' : 'Ж' ?></td>
                                    <td><?= htmlspecialchars($app['languages_list'] ?? '-') ?></td>
                                    <td><?= $app['contract_accepted'] ? 'Да' : 'Нет' ?></td>
                                    <td><?= htmlspecialchars($app['login'] ?? '-') ?></td>
                                    <td class="actions">
                                        <a href="admin.php?action=edit&id=<?= $app['id'] ?>" class="btn-edit">Изменить</a>
                                        <a href="admin.php?action=delete&id=<?= $app['id'] ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Точно удалить запись #<?= $app['id'] ?>?')">Удалить</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
