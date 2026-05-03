<?php

header('Content-Type: text/html; charset=UTF-8');

$db_user = 'u82591';
$db_pass = '2762718';
$db_name = 'u82591';

$session_started = false;
if (!empty($_COOKIE[session_name()])) {
    session_start();
    $session_started = true;
}

if (isset($_GET['logout']) && $session_started) {
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header('Location: index.php');
    exit();
}

if ($session_started && !empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .admin-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .admin-link a {
            color: #e74c3c;
            text-decoration: none;
        }
        
        .admin-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Вход в систему</h1>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 'wrong'): ?>
            <div class="error-message">Неверный логин или пароль.</div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required placeholder="Введите логин">
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль</label>
                <input type="password" id="pass" name="pass" required placeholder="Введите пароль">
            </div>
            
            <button type="submit">Войти</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">Вернуться к форме</a>
        </div>
        
        <div class="admin-link">
            <a href="admin.php">Войти как администратор</a>
        </div>
    </div>
</body>
</html>
<?php
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    
    if (empty($login) || empty($pass)) {
        header('Location: login.php?error=wrong');
        exit();
    }
    
    try {
        $db = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("SELECT id, login, password_hash FROM application WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['password_hash'] === md5($pass)) {
            if (!$session_started) {
                session_start();
            }
            
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            
            $cookie_fields = ['fio_value', 'phone_value', 'email_value', 'birth_date_value', 
                            'gender_value', 'biography_value', 'contract_accepted_value', 'languages_value'];
            foreach ($cookie_fields as $cookie) {
                setcookie($cookie, '', time() - 3600);
            }
            
            header('Location: index.php');
            exit();
        } else {
            header('Location: login.php?error=wrong');
            exit();
        }
        
    } catch (PDOException $e) {
        die('Ошибка БД: ' . $e->getMessage());
    }
}
