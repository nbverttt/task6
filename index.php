<?php

// Отправляем браузеру правильную кодировку
header('Content-Type: text/html; charset=UTF-8');

// Настройки БД
$db_user = 'u82591';
$db_pass = '2762718';
$db_name = 'u82591';

// Список разрешенных языков
$allowed_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 
                      'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

// Функция для валидации ФИО
function validate_fio($fio, &$errors, &$error_messages) {
    if (empty($fio)) {
        $errors['fio'] = true;
        $error_messages['fio'] = 'Поле "ФИО" обязательно для заполнения.';
        return false;
    }
    if (strlen($fio) > 150) {
        $errors['fio'] = true;
        $error_messages['fio'] = 'ФИО не должно превышать 150 символов.';
        return false;
    }
    if (!preg_match('/^[\p{L}\s\-\.\']+$/u', $fio)) {
        $errors['fio'] = true;
        $error_messages['fio'] = 'ФИО может содержать только буквы, пробелы, дефисы, точки и апострофы.';
        return false;
    }
    return true;
}

// Функция для валидации телефона
function validate_phone($phone, &$errors, &$error_messages) {
    if (empty($phone)) {
        $errors['phone'] = true;
        $error_messages['phone'] = 'Поле "Телефон" обязательно для заполнения.';
        return false;
    }
    if (!preg_match('/^(\+7|7|8)?[\s\-]?\(?[0-9]{3}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/', $phone)) {
        $errors['phone'] = true;
        $error_messages['phone'] = 'Введите корректный номер телефона.';
        return false;
    }
    return true;
}

// Функция для валидации email
function validate_email($email, &$errors, &$error_messages) {
    if (empty($email)) {
        $errors['email'] = true;
        $error_messages['email'] = 'Поле "E-mail" обязательно для заполнения.';
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors['email'] = true;
        $error_messages['email'] = 'Введите корректный email адрес.';
        return false;
    }
    return true;
}

// Функция для валидации даты рождения
function validate_birth_date($date, &$errors, &$error_messages) {
    if (empty($date)) {
        $errors['birth_date'] = true;
        $error_messages['birth_date'] = 'Поле "Дата рождения" обязательно для заполнения.';
        return false;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors['birth_date'] = true;
        $error_messages['birth_date'] = 'Дата должна быть в формате ГГГГ-ММ-ДД.';
        return false;
    }
    $parts = explode('-', $date);
    if (!checkdate($parts[1], $parts[2], $parts[0])) {
        $errors['birth_date'] = true;
        $error_messages['birth_date'] = 'Введите корректную дату.';
        return false;
    }
    $birth_timestamp = strtotime($date);
    $age = date('Y') - date('Y', $birth_timestamp);
    if (date('md') < date('md', $birth_timestamp)) {
        $age--;
    }
    if ($age < 18) {
        $errors['birth_date'] = true;
        $error_messages['birth_date'] = 'Вы должны быть старше 18 лет.';
        return false;
    }
    return true;
}

// Функция для валидации пола
function validate_gender($gender, &$errors, &$error_messages) {
    if (empty($gender)) {
        $errors['gender'] = true;
        $error_messages['gender'] = 'Поле "Пол" обязательно для заполнения.';
        return false;
    }
    if (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = true;
        $error_messages['gender'] = 'Выберите допустимое значение пола.';
        return false;
    }
    return true;
}

// Функция для валидации языков
function validate_languages($languages, &$errors, &$error_messages, $allowed) {
    if (empty($languages)) {
        $errors['languages'] = true;
        $error_messages['languages'] = 'Выберите хотя бы один язык программирования.';
        return false;
    }
    foreach ($languages as $lang) {
        if (!in_array($lang, $allowed)) {
            $errors['languages'] = true;
            $error_messages['languages'] = 'Выбран некорректный язык программирования.';
            return false;
        }
    }
    return true;
}

// Функция для валидации биографии
function validate_biography($biography, &$errors, &$error_messages) {
    if (strlen($biography) > 5000) {
        $errors['biography'] = true;
        $error_messages['biography'] = 'Биография не должна превышать 5000 символов.';
        return false;
    }
    return true;
}

// Функция для валидации чекбокса
function validate_contract($contract, &$errors, &$error_messages) {
    if (empty($contract) || $contract != '1') {
        $errors['contract_accepted'] = true;
        $error_messages['contract_accepted'] = 'Необходимо подтвердить ознакомление с контрактом.';
        return false;
    }
    return true;
}

// Функция генерации уникального логина
function generate_login() {
    $prefixes = ['dev', 'coder', 'programmer', 'hacker', 'geek', 'ninja', 'master', 'pro'];
    $random_num = rand(1000, 9999);
    $random_str = substr(md5(uniqid()), 0, 4);
    return $prefixes[array_rand($prefixes)] . $random_num . $random_str;
}

// Функция генерации пароля
function generate_password() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < 10; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Функция для загрузки данных из БД для авторизованного пользователя
function load_user_data($db, $user_id, &$values, &$error_messages) {
    try {
        $stmt = $db->prepare("SELECT fio, phone, email, birth_date, gender, biography, contract_accepted FROM application WHERE id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $values['fio'] = htmlspecialchars($data['fio']);
            $values['phone'] = htmlspecialchars($data['phone']);
            $values['email'] = htmlspecialchars($data['email']);
            $values['birth_date'] = htmlspecialchars($data['birth_date']);
            $values['gender'] = htmlspecialchars($data['gender']);
            $values['biography'] = htmlspecialchars($data['biography'] ?? '');
            $values['contract_accepted'] = htmlspecialchars($data['contract_accepted']);
            
            $stmt_lang = $db->prepare("SELECT pl.name FROM application_languages al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.application_id = ?");
            $stmt_lang->execute([$user_id]);
            $values['languages'] = $stmt_lang->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        $error_messages['db'] = 'Ошибка загрузки данных: ' . $e->getMessage();
    }
}

// Начинаем сессию, если есть кука сессии
$session_started = false;
if (!empty($_COOKIE[session_name()])) {
    session_start();
    $session_started = true;
}

// Подключение к БД
try {
    $db = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// Обработка GET запроса (показ формы)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $error_messages = array();
    $values = array();
    $is_authorized = false;
    
    if ($session_started && !empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $is_authorized = true;
        load_user_data($db, $_SESSION['uid'], $values, $error_messages);
        $messages[] = '<div class="success-message">
            Вы вошли как <strong>' . htmlspecialchars($_SESSION['login']) . '</strong> (ID: ' . $_SESSION['uid'] . ').
            <br><br>
            <a href="login.php?logout=1" class="logout-btn">Выйти</a>
        </div>';
    } else {
        if (!empty($_COOKIE['save'])) {
            setcookie('save', '', time() - 3600);
            $messages[] = '<div class="success-message">Спасибо, результаты успешно сохранены!</div>';
            
            if (!empty($_COOKIE['show_credentials']) && !empty($_COOKIE['tmp_login']) && !empty($_COOKIE['tmp_pass'])) {
                $messages[] = sprintf(
                    '<div class="success-message">Ваши данные для входа:<br>Логин: <strong>%s</strong><br>Пароль: <strong>%s</strong><br><a href="login.php">Войти для изменения данных</a></div>',
                    htmlspecialchars($_COOKIE['tmp_login']),
                    htmlspecialchars($_COOKIE['tmp_pass'])
                );
                setcookie('show_credentials', '', time() - 3600);
                setcookie('tmp_login', '', time() - 3600);
                setcookie('tmp_pass', '', time() - 3600);
            }
        }
        
        $fields = ['fio', 'phone', 'email', 'birth_date', 'gender', 'languages', 'contract_accepted', 'biography'];
        foreach ($fields as $field) {
            $errors[$field] = !empty($_COOKIE[$field . '_error']);
            if ($errors[$field]) {
                $error_messages[$field] = $_COOKIE[$field . '_error_msg'] ?? 'Ошибка заполнения поля.';
                setcookie($field . '_error', '', time() - 3600);
                setcookie($field . '_error_msg', '', time() - 3600);
            }
        }
        
        $cookie_fields = ['fio', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted'];
        foreach ($cookie_fields as $field) {
            $values[$field] = $_COOKIE[$field . '_value'] ?? '';
        }
        
        $values['languages'] = [];
        if (!empty($_COOKIE['languages_value'])) {
            $values['languages'] = explode('|', $_COOKIE['languages_value']);
        }
    }
    
    include('form.php');
    exit();
}

// Обработка POST запроса (валидация и сохранение)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();
    $error_messages = array();
    $has_errors = false;
    
    $fio = trim($_POST['fio'] ?? '');
    if (!validate_fio($fio, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    $phone = trim($_POST['phone'] ?? '');
    if (!validate_phone($phone, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    $email = trim($_POST['email'] ?? '');
    if (!validate_email($email, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    $birth_date = $_POST['birth_date'] ?? '';
    if (!validate_birth_date($birth_date, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    $gender = $_POST['gender'] ?? '';
    if (!validate_gender($gender, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    $languages = $_POST['languages'] ?? [];
    if (!validate_languages($languages, $errors, $error_messages, $allowed_languages)) {
        $has_errors = true;
    }
    
    $biography = trim($_POST['biography'] ?? '');
    if (!validate_biography($biography, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    $contract_accepted = $_POST['contract_accepted'] ?? '';
    if (!validate_contract($contract_accepted, $errors, $error_messages)) {
        $has_errors = true;
    }
    
    if ($has_errors) {
        foreach ($errors as $field => $has_error) {
            if ($has_error) {
                setcookie($field . '_error', '1', time() + 24 * 60 * 60);
                setcookie($field . '_error_msg', $error_messages[$field], time() + 24 * 60 * 60);
            }
        }
        
        setcookie('fio_value', $fio, time() + 30 * 24 * 60 * 60);
        setcookie('phone_value', $phone, time() + 30 * 24 * 60 * 60);
        setcookie('email_value', $email, time() + 30 * 24 * 60 * 60);
        setcookie('birth_date_value', $birth_date, time() + 30 * 24 * 60 * 60);
        setcookie('gender_value', $gender, time() + 30 * 24 * 60 * 60);
        setcookie('biography_value', $biography, time() + 30 * 24 * 60 * 60);
        setcookie('contract_accepted_value', $contract_accepted, time() + 30 * 24 * 60 * 60);
        setcookie('languages_value', implode('|', $languages), time() + 30 * 24 * 60 * 60);
        
        header('Location: index.php');
        exit();
    }
    
    $fields = ['fio', 'phone', 'email', 'birth_date', 'gender', 'languages', 'contract_accepted', 'biography'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
        setcookie($field . '_error_msg', '', time() - 3600);
    }
    
    $is_update = false;
    $user_id = null;
    
    if ($session_started && !empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $is_update = true;
        $user_id = $_SESSION['uid'];
    }
    
    try {
        $db->beginTransaction();
        
        if ($is_update) {
            $stmt = $db->prepare("UPDATE application SET fio = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, contract_accepted = ? WHERE id = ?");
            $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $user_id]);
            
            $stmt_del = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $stmt_del->execute([$user_id]);
            
            $app_id = $user_id;
        } else {
            $login = generate_login();
            $password = generate_password();
            $password_hash = md5($password);
            
            $stmt = $db->prepare("INSERT INTO application (fio, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $login, $password_hash]);
            $app_id = $db->lastInsertId();
        }
        
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
                    $stmt_link->execute([$app_id, $lang_map[$lang]]);
                }
            }
        }
        
        $db->commit();
        
        if ($is_update) {
            setcookie('fio_value', $fio, time() + 365 * 24 * 60 * 60);
            setcookie('phone_value', $phone, time() + 365 * 24 * 60 * 60);
            setcookie('email_value', $email, time() + 365 * 24 * 60 * 60);
            setcookie('birth_date_value', $birth_date, time() + 365 * 24 * 60 * 60);
            setcookie('gender_value', $gender, time() + 365 * 24 * 60 * 60);
            setcookie('biography_value', $biography, time() + 365 * 24 * 60 * 60);
            setcookie('contract_accepted_value', $contract_accepted, time() + 365 * 24 * 60 * 60);
            setcookie('languages_value', implode('|', $languages), time() + 365 * 24 * 60 * 60);
            
            header('Location: index.php');
        } else {
            setcookie('save', '1', time() + 24 * 60 * 60);
            setcookie('show_credentials', '1', time() + 300);
            setcookie('tmp_login', $login, time() + 300);
            setcookie('tmp_pass', $password, time() + 300);
            
            header('Location: index.php');
        }
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        setcookie('db_error', $e->getMessage(), time() + 24 * 60 * 60);
        header('Location: index.php');
        exit();
    }
}
