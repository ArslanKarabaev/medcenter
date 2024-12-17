<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка, что пользователь студент
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: auth.php');
    exit();
}

$student_id = $_SESSION['user_id'];



// Получение данных студента
$stmt = $pdo->prepare("SELECT * FROM student WHERE id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Обновление личных данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student_data'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $student_number = $_POST['student-number']; // студентский номер
    //$id = $_POST['student-id']; // айди студента, используемое для поиска
    $stmt = $pdo->prepare("UPDATE student SET name = :name, surname = :surname, phone = :phone, email = :email, department = :department WHERE id = :student_id");
    $stmt->execute([
        ':name' => $name,
        ':surname' => $surname,
        ':phone' => $phone,
        ':email' => $email,
        ':department' => $department,
        ':student_id' => $student_id
    ]);
    header('Location: student_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Получаем текущий, новый и подтвержденный пароли из формы
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Проверка, что новый пароль и подтвержденный совпадают
    if ($new_password !== $confirm_new_password) {
        echo "Новый пароль и его подтверждение не совпадают.";
        exit();
    }

    // Получаем текущий пароль из базы данных
    $stmt = $pdo->prepare("SELECT password FROM student WHERE id = :id");
    $stmt->execute([':id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo "Ошибка: Студент не найден.";
        exit();
    }

    // Проверка текущего пароля
    if (!password_verify($current_password, $student['password'])) {
        echo "Текущий пароль неверный.";
        exit();
    }

    // Хэшируем новый пароль
    $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);

    // Обновляем пароль в базе данных
    $stmt = $pdo->prepare("UPDATE student SET password = :password WHERE id = :id");
    $stmt->execute([
        ':password' => $new_password_hashed,
        ':id' => $student_id
    ]);

    // Устанавливаем сообщение об успешном изменении пароля в сессию
    $_SESSION['password_change_success'] = "Пароль успешно изменен!";
    header('Location: ' . $_SERVER['PHP_SELF']); // Перезагружаем страницу
    exit();
}

// Получение медкарты студента
$stmt = $pdo->prepare("SELECT id FROM medical_cards WHERE student_id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$medical_card = $stmt->fetch(PDO::FETCH_ASSOC);

if ($medical_card) {
    $medical_card_id = $medical_card['id'];
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE medical_card_id = :medical_card_id");
    $stmt->execute([':medical_card_id' => $medical_card_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Создание медкарты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_medical_card'])) {
    $stmt = $pdo->prepare("INSERT INTO medical_cards (student_id) VALUES (:student_id)");
    $stmt->execute([':student_id' => $student_id]);
    header('Location: student_dashboard.php');
    exit();
}

// Добавление новой болезни
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medical_record'])) {
    $disease_name = $_POST['disease_name'];
    $disease_date = $_POST['disease_date'];
    $medications = $_POST['medications'];
    $stmt = $pdo->prepare("INSERT INTO medical_records (medical_card_id, disease_name, disease_date, medications, added_by) 
                           VALUES (:medical_card_id, :disease_name, :disease_date, :medications, 'student')");
    $stmt->execute([
        ':medical_card_id' => $medical_card_id,
        ':disease_name' => $disease_name,
        ':disease_date' => $disease_date,
        ':medications' => $medications
    ]);

    // Получаем ID только что вставленной записи
    $medical_record_id = $pdo->lastInsertId();

    // Загрузка файла (если выбран файл)
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $file_name = basename($_FILES['pdf_file']['name']);
        $file_tmp = $_FILES['pdf_file']['tmp_name'];
        $upload_dir = 'uploads/';

        // Создаем папку, если она отсутствует
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Генерируем уникальное имя файла
        $file_path = $upload_dir . uniqid() . '-' . $file_name;

        // Перемещаем загруженный файл в директорию
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Вставляем информацию о файле в таблицу medical_files
            $stmt = $pdo->prepare("INSERT INTO medical_files (medical_record_id, file_name, file_path) 
                                   VALUES (:medical_record_id, :file_name, :file_path)");
            $stmt->execute([
                ':medical_record_id' => $medical_record_id,
                ':file_name' => $file_name,
                ':file_path' => $file_path
            ]);
            header('Location: student_dashboard.php');
            
            exit();
        } else {
            echo "Ошибка при загрузке файла.";
        }
    }
}
//var_dump($doctor_id, $appointment_time, $student_id); 


$stmt = $pdo->prepare("SELECT * FROM doctor WHERE role='doctor' AND status = '1'");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Инициализация дней недели
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Генерация свободных временных слотов
function getAvailableSlots($pdo, $doctor_id, $date) {
    // Получаем день недели для даты
    $day_of_week = date('l', strtotime($date));

    // Проверяем расписание врача на этот день
    $stmt = $pdo->prepare("SELECT * 
                           FROM schedules 
                           WHERE doctor_id = :doctor_id 
                             AND day = :day_of_week 
                             AND is_day_off = 0");
    $stmt->execute([':doctor_id' => $doctor_id, ':day_of_week' => $day_of_week]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        return []; // Врач не работает в этот день
    }

    // Генерация временных слотов с интервалом 30 минут
    $slots = [];
    $current_time = new DateTime($schedule['time_from']);
    $end_time = new DateTime($schedule['time_to']);
    $interval = new DateInterval('PT30M'); // Интервал 30 минут

    while ($current_time < $end_time) {
        $slots[] = $current_time->format('H:i:s');
        $current_time->add($interval);
    }

    // Получаем занятые слоты
    $stmt = $pdo->prepare("SELECT TIME(appointment_time) as time_slot 
                           FROM appointments 
                           WHERE doctor_id = :doctor_id 
                             AND DATE(appointment_time) = :date");
    $stmt->execute([':doctor_id' => $doctor_id, ':date' => $date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Фильтруем занятые слоты
    $available_slots = array_filter($slots, function($slot) use ($booked_slots) {
        return !in_array($slot, $booked_slots);
    });

    return $available_slots;
}

// Обработка записи на приём
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $appointment_time = $_POST['appointment_time'];
    $student_id = $_SESSION['user_id']; // Предполагается, что ID студента хранится в сессии

    $appointment_date = date('Y-m-d', strtotime($appointment_time));
    $appointment_time_only = date('H:i:s', strtotime($appointment_time));

    // Проверка на доступность времени
    $available_slots = getAvailableSlots($pdo, $doctor_id, $appointment_date);

    if (!in_array($appointment_time_only, $available_slots)) {
        $appointment_error = "Выбранное время недоступно. Пожалуйста, выберите другое.";
    } else {
        // Добавляем запись
        $stmt = $pdo->prepare("INSERT INTO appointments (student_id, doctor_id, appointment_time) 
                               VALUES (:student_id, :doctor_id, :appointment_time)");
        $stmt->execute([
            ':student_id' => $student_id,
            ':doctor_id' => $doctor_id,
            ':appointment_time' => "$appointment_date $appointment_time_only"
        ]);

        header('Location: student_dashboard.php');
        exit();
    }
}

// Получение записей на прием
$stmt = $pdo->prepare("SELECT a.id, a.appointment_time, d.name AS doctor_name, d.surname AS doctor_surname, d.speciality 
                       FROM appointments a
                       JOIN doctor d ON a.doctor_id = d.id
                       WHERE a.student_id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Удаление записи
if (isset($_GET['delete_record_id'])) {
    $record_id = $_GET['delete_record_id'];

    // Получаем все файлы, прикрепленные к записи, чтобы удалить их позже
    $stmt = $pdo->prepare("SELECT * FROM medical_files WHERE medical_record_id = :record_id");
    $stmt->execute([':record_id' => $record_id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Удаляем файлы из файловой системы
    foreach ($files as $file) {
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']); // Удаляем файл
        }
    }

    // Удаляем все файлы из базы данных
    $stmt = $pdo->prepare("DELETE FROM medical_files WHERE medical_record_id = :record_id");
    $stmt->execute([':record_id' => $record_id]);

    // Удаляем саму запись
    $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = :record_id  AND added_by = 'student'");
    $stmt->execute([':record_id' => $record_id]);

    // Перенаправление после удаления
    header("Location: student_dashboard.php"); // Убедитесь, что редирект правильный
    exit();
}

// Удаление файла (оставляем логику удаления файла, как было)
if (isset($_GET['delete_file_id'])) {
    $file_id = $_GET['delete_file_id'];

    // Получаем путь файла
    $stmt = $pdo->prepare("SELECT * FROM medical_files WHERE id = :file_id");
    $stmt->execute([':file_id' => $file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file && file_exists($file['file_path'])) {
        unlink($file['file_path']); // Удаляем файл с диска

        // Удаляем файл из базы данных
        $stmt = $pdo->prepare("DELETE FROM medical_files WHERE id = :file_id");
        $stmt->execute([':file_id' => $file_id]);

        // Перенаправление после удаления
        header("Location: student_dashboard.php"); // Убедитесь, что редирект правильный
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет студента</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            background-color: #f4f4f4;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #042444;
            color: white;
            padding: 20px;
            height: 100%;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            margin: 0;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .sidebar ul li {
            padding: 10px 0;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s;
        }

        .sidebar ul li.active {
            background-color: #0d3159;
            font-weight: bold;
        }

        .sidebar ul li:hover {
            background-color: #0d3159;
        }

        .content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            height: 100vh;  /* Задает высоту контента на всю высоту экрана */
    
        }

        .section {
            display: none;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .section.active {
            display: block;
        }

        .section h2 {
            margin-top: 0;
            color: #043464;
        }

        .form-group {
            margin-bottom: 15px;
            padding-left: 20px;
            padding-right: 25px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            text-align: left;
            color: #071F6F;
        }

        .form-group input{
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 13px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .form-group .radio-group {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
        }

        .form-group .radio-group label {
            display: inline-flex;
            align-items: center;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .form-row .half-width {
            width: 48%;
            border: none;
        }

        /* Изменения для расположения радиокнопок и текста в одну строку */
        .radio-group {
            display: inline-flex;
            gap: 15px;
            align-items: center;
            justify-content: flex-start;
        }

        .radio-group input {
            margin-right: 5px;
        }



        .section input, .section select, .section textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn-personal {
            background-color: #071F6F;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 35px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-bottom: 10px;
            margin-left: 10px;
        }

        .btn-personal:hover {
            background-color: #022751;
        }

        

        .medical-records {
        margin-top: 20px;
    }

    .medical-records ul {
        list-style: none;
        padding: 0;
    }

    .medical-records li {
        display: flex;
        justify-content: space-between;
        background-color: #f1f9ff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .record:hover {
        background-color: #e6f7ff;
    }

    .record-info {
        flex: 1;
        font-size: 1rem;
    }

    .record-title {
        font-size: 1rem;
        font-weight: 600;
        color: #043464;
    }

    .record-status {
        font-size: 1rem;
        color: #333;
        margin-top: 5px;
    }

    .record-actions {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 10px;
    }

    .upload-btn, .update-btn {
        padding: 10px 15px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background-color 0.3s ease;
    }

    .upload-btn:hover, .update-btn:hover {
        background-color: #0056b3;
    }

    .add-new-record form {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.add-new-record .input-group {
    display: flex;
    justify-content: space-between;
    width: 1000px;
    gap: 10px;
    margin-bottom: 20px;
}

.add-new-record input,
.add-new-record  {
    width: 90%; /* Занимает 1/4 ширины */
    padding: 10px;
    border-radius: 6px;
    
    font-size: 1rem;
}

.upload-file-label {
    background-color: #071F6F;
    color: white;
    cursor: pointer;
    text-align: center;
    transition: background-color 0.3s ease;
    padding: 10px;
    width: 300px;
    border-radius:6px;
}

.upload-file-label:hover {
    background-color: #022751;
}

.add-new-record button:hover {
    background-color: #022751;
}
.add-btn{
    padding: 10px;
    background-color: #071F6F;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    width: 320px;
    font-size: 1rem;
    margin-top: 20px; 
    margin-left: 65px;
    font-family: 'Montserrat',sans-serif;
    font-weight: bold;
}
.upload-section {
        margin-top: 30px;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .upload-file-input {
        display: none;
    }


    .upload-file-btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #071F6F;
        color: white;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }

    .upload-file-btn:hover {
        background-color: #022751;
    }

    .doctor-visits {
        margin-top: 40px;
    }

    .doctor-visits ul {
        list-style: none;
        padding: 0;
    }

    .doctor-visits li {
        padding: 10px;
        background-color: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 6px;
    }

    .doctor-appointment {
            background-color: white;
            padding: 20px;
            width: 70%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .flex-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .appointment-container, .doctor-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 45%;
        }

        h3 {
            margin-bottom: 10px;
            text-align: center;
            color: #071F6F;
        }

        .flatpickr {
            width: 95%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .time-slot {
            padding: 10px;
            text-align: center;
            background-color: #f1f1f1;
            border-radius: 15px;
            cursor: pointer;
            border: 1px solid transparent;
            color: #031A65;
        }

        .time-slot.selected {
            background-color: #071F6F;
            color: white;
            
        }

        .doctor-list {
            display: grid;
            grid-template-columns: 1fr 1fr; /* 2 столбца */
            gap: 20px; /* Расстояние между врачами */
            margin-top: 20px;
        }

        .time-slots div,
.doctor {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    margin: 5px 0;
    cursor: pointer;
    text-align: center;
}

        .time-slots div.selected,
        .doctor.selected {
    background: #007BFF;
    color: white;
    border-color: #0056b3;
}
        .doctor.selected .doctor-specialty {
            color: white;
        }

        .doctor .doctor-name {
            font-weight: 600; /* Жирное ФИО */
            font-size: 18px;
        }

        .doctor .doctor-specialty {
            font-weight: 400;
            font-size: 14px;
            color: #555;
        }
        label {
        display: block;
        font-size: 16px;
        margin-bottom: 10px;
        font-weight: bold;
    }

    /* Стили для select и input с прозрачностью */
    select, input[type="datetime-local"] {
        width: 100%;
        padding: 12px;
        margin: 5px 0 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 16px;
        background-color: #f1f9ff; /* Светлый цвет с прозрачностью */
        color: #333;
    }


        button.submit-btn {
            padding: 15px;
            background-color: #071F6F;
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 14px;
            cursor: pointer;
            text-align: center;
            width: 20%;
}


button.submit-btn:hover {
    background: #0056b3;
}

        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            display: none; /* Скрыто по умолчанию */
        }
        .btn-del{
            background-color: #071F6F;
            color: white;
            padding: 10px 45px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            display: block;
            text-decoration: none; /* Убирает подчеркивание */
            margin-top: 20px;
        }
        .btn-main {
    text-decoration: none; /* Убираем подчеркивание */
    font-weight: bold;     /* Делаем шрифт жирным */
    color:white;
    font-size: 15px;
}

    .btn-reg{
            background-color: #071F6F;
            color: white;
            padding: 10px 45px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            display: block;
            text-decoration: none; /* Убирает подчеркивание */
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #071F6F;
            color: white;
        }
#appointmentForm label{
    color: #071F6F;
    width: 98%;
}
#appointmentForm input[type="date"] {
    width: 30%;
    padding: 10px;
}

#appointmentForm button{
    padding:10px;
    background-color:#071F6F;
    color: white;
}
    
    </style>
</head>
<body>
<?php if (isset($_SESSION['password_change_success'])): ?>
        <div class="success-message" id="successMessage">
            <?= $_SESSION['password_change_success'] ?>
        </div>
        <?php unset($_SESSION['password_change_success']); ?>
    <?php endif; ?>

    <div class="sidebar">
        <h2>Кош келдиңиз, <?= htmlspecialchars($student_data['name'] . ' ' . $student_data['surname']) ?></h2>
        <ul>
            <li class="active" onclick="showSection('personal-data-form', this)">Жеке маалымат</li>
            <li onclick="showSection('doctor-appointment', this)">Дарыгерге жазылуу</li>
            <li onclick="showSection('medical-card', this)">Медкарта</li>
            <li><a href="page.php" class="btn-main">Башкы бет</a></li>
            <li><a href="logout.php" class="btn-reg">Чыгуу</a></li>
        </ul>
    </div>
    <div class="content">
        <!-- Личные данные -->
        <div id="personal-data-form" class="section active">
            <h2>Жеке маалымат</h2>
            <form method="POST" id="personal-data-form">
            <?php if (isset($student_data) && is_array($student_data)): ?>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="name">Аты</label>
                        <input type="text" id="name" name="name" value="<?= isset($student_data['name']) ? htmlspecialchars($student_data['name']) : '' ?>" disabled id="name">
                    </div>
                    <div class="form-group half-width">
                        <label for="surname">Фамилиясы</label>
                        <input type="text" id="surname" name="surname" value="<?=isset($student_data['surname']) ? htmlspecialchars($student_data['surname']) : '' ?>" disabled id="surname">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="student-number">Студент Номери</label>
                        <input type="text" id="student-number" name="student-number" value="<?=isset($student_data['student_number']) ? htmlspecialchars($student_data['student_number']) :''?>"  disabled id="student_number">
                    </div>
                    <div class="form-group half-width">
                        <label for="department">Бөлүм</label>
                        <input type="text" id="department" name="department" value="<?=isset($student_data['department']) ? htmlspecialchars($student_data['department']) :''?>" disabled id="department">
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Телефон Номери</label>
                    <input type="tel" id="phone" name="phone" value="<?= isset($student_data['phone']) ?htmlspecialchars($student_data['phone']) :''?>" disabled id="phone">
                </div>
                <div class="form">
                    <div class="form-group">
                        <label for="birthdate">Туулган Күнү</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?= isset($student_data['birthdate']) ?htmlspecialchars($student_data['birthdate']) :''?>" disabled id="birthdate">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Почта</label>
                    <input type="email" id="email" name="email" value="<?= isset($student_data['email']) ? htmlspecialchars($student_data['email']) : '' ?>" disabled id="email">
                </div>
                 <!-- Добавляем кнопку "Изменить" -->
                  <button type="button" id="edit-button" class="btn-personal">Өзгөртүү</button>

                 <!-- Кнопка "Сохранить изменения", будет скрыта по умолчанию -->
                  <button type="submit" name="update_student_data" id="save-button" class="btn-personal" style="display: none;">Өзгөртүүлөрдү сактоо</button>
                  <?php else: ?>
            <p>Данные о враче не найдены. Пожалуйста, убедитесь, что врач существует в базе данных.</p>
        <?php endif; ?>
            </form>
<br>
            <h2>Сырсөздү өзгөртүү</h2>
            <form method="POST">
                <div>
                    <label for="current_password">Азыркы сырсөз:</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div>
                    <label for="new_password">Жаңы сырсөз:</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <div>
                    <label for="confirm_new_password">Жаңы сырсөздү ырастоо:</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" required>
                </div>
                <button type="submit" class="btn-personal" name="change_password">Сырсөздү өзгөртүү</button>
            </form>
        </div>

        <!-- Запись к врачу -->
        <div id="doctor-appointment" class="section">
        <h2>Дарыгерге жазылуу</h2>
        <form method="POST" id="appointmentForm">
    <label for="doctor_id">Дарыгерди тандаңыз:</label>
    <select name="doctor_id" id="doctor_id" required>
        <?php foreach ($doctors as $doctor): ?>
            <option value="<?= htmlspecialchars($doctor['id']) ?>">
                <?= htmlspecialchars($doctor['name']. ' ' . $doctor['surname'] . ' (' . $doctor['speciality'] . ')') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="date">Күндү тандаңыз:</label>
    <input type="date" name="date" id="date" required>

    <button type="submit" name="show_slots">Показать свободное время</button>
    </form>

<?php if (isset($_POST['show_slots'])): ?>
    <?php
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['date'];
    $available_slots = getAvailableSlots($pdo, $doctor_id, $date);
    ?>
    <form method="POST" id="appointmentForm">
        <br>
        <label for="appointment_time">Выберите время:</label>
        <select name="appointment_time" id="appointment_time" required>
            <?php foreach ($available_slots as $slot): ?>
                <option value="<?= "$date $slot" ?>">
                    <?= htmlspecialchars($slot) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($doctor_id) ?>">
        <button type="submit" name="book_appointment">Записаться</button>
    </form>
<?php endif; ?>


<?php if (isset($appointment_error)): ?>
    <p style="color:red;"><?= htmlspecialchars($appointment_error) ?></p>
<?php endif; ?>
    <br>
    

    <section id="schedule-section" class="">
    <h2>Дарыгерлердин графиги</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ФИО, Кесип</th>
                <th>Понедельник</th>
                <th>Вторник</th>
                <th>Среда</th>
                <th>Четверг</th>
                <th>Пятница</th>
                <th>Суббота</th>
                <th>Воскресенье</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($doctors as $doctor): ?>
                <tr>
                    <td><?php echo $doctor['name'] . ' ' . $doctor['surname'] . ' <br>' . $doctor['speciality']; ?></td>
                    <?php foreach ($days_of_week as $day): ?>
                        <td>
                            <?php
                                // Search for the doctor's schedule on each day
                                $stmt = $pdo->prepare("SELECT time_from, time_to FROM schedules WHERE doctor_id = :doctor_id AND day = :day");
                                $stmt->execute([':doctor_id' => $doctor['id'], ':day' => $day]);
                                $schedule_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($schedule_data) {
                                    echo $schedule_data['time_from'] . ' - ' . $schedule_data['time_to'];
                                } else {
                                    echo 'Эс алуу күнү';
                                }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </section>

            </div>
        

        <div id="medical-card" class="section">
            <h2>Медкарта</h2>
            <h3>Эскертуу! Дарыгер кошкон жазууну очурсо болбойт.</h3>
    
    <!-- Проверяем, существует ли медицинская карта -->
    <?php if ($medical_card): ?>
        
        <!-- Секция для отображения списка медицинских записей -->
        <div class="medical-records">
            <ul id="medical-records-list">
                <!-- Перебираем все записи в медицинской карте -->
                <?php foreach ($medical_records as $record): ?>
                    <li class="record">
                        <div class="record-info">
                            <!-- Название болезни/процедуры -->
                            <p class="record-title"><?= htmlspecialchars($record['disease_name']) ?></p>
                            <!-- Дата болезни/процедуры, если она есть, иначе отображается "Жок" -->
                            <p class="record-medicines">
                               Дарылар: <?= !empty($record['medications']) ? htmlspecialchars($record['medications']) : 'Жок' ?>
                            </p>
                            <p class="record-status"><?= htmlspecialchars($record['disease_date'] ?: 'Жок') ?></p>
                            <p class="file-list">
                        <?php 
                        // Получаем все файлы, связанные с текущей записью
                        $stmt = $pdo->prepare("SELECT * FROM medical_files WHERE medical_record_id = :record_id");
                        $stmt->execute([':record_id' => $record['id']]);
                        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($files): 
                            foreach ($files as $file): ?>
                                <div class="file-item">
                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a>
                                    <!-- Ссылка для удаления файла 
                                   <a href="?delete_file_id=<?= $file['id'] ?>" class="btn-del" onclick="return confirm('Вы уверены, что хотите удалить этот файл?')">Удалить</a>-->
                                </div>
                            <?php endforeach; 
                        else: ?>
                            <p>Файлдар жок.</p>
                        <?php endif; ?>
                        </p>
                        </div>

                        <div class="record-actions">
                    <!--Форма для загрузки файла, прикрепленная к этой записи -->
                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                        <input type="hidden" name="medical_record_id" value="<?= htmlspecialchars($record['id']) ?>">
                        <input type="file" name="pdf_file" id="upload-file-<?= $record['id'] ?>" class="upload-file-input">
                        <!--<button type="submit" name="upload_file" class="upload-btn">Жүктөө</button>-->
                    </form>
                    <!-- Кнопка для обновления записи 
                    <button class="update-btn" id="edit-button" onclick="openUpdateForm('<?= htmlspecialchars($record['disease_name']) ?>', '<?= htmlspecialchars($record['disease_date']) ?>')">Жаңыртуу</button>
                 <!- Кнопка для удаления записи -->
                 <a href="?delete_record_id=<?= $record['id'] ?>" class="btn-del" onclick="return confirm('Вы уверены, что хотите удалить эту запись? Все файлы, связанные с записью, также будут удалены.')">Жазууну жок кылуу</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

      <!-- Форма для добавления новой медицинской записи -->
<div class="add-new-record">
    <h3>Жаңы медициналык жазууну кошуу</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="input-group">
        
            <!-- Поле для ввода названия новой болезни или процедуры -->
            <input type="text" name="disease_name" placeholder="Жаңы жазуунун атын киргизиңиз" required />
            <!-- Поле для ввода лекарств -->
            <input type="text" name="medications" placeholder="Дарылар">
            <!-- Поле для ввода даты болезни или процедуры -->
            <input type="date" name="disease_date" placeholder="толтуруңуз" required />
            <!-- Поле для загрузки файла -->
            
            <input type="file" name="pdf_file" id="upload-file" class="upload-file-input" />
            <label for="upload-file" class="upload-file-label">Файлды тандоо</label>
         </div>
        <!-- Кнопка для отправки формы -->
        <button type="submit" name="add_medical_record" class="add-btn">Кошуу</button>
    </form>
</div>
                    




    <!-- Если медицинская карта отсутствует -->
    <?php else: ?>
        <p>Медициналык карта жок.</p>
        <form method="POST">
            <button type="submit" name="create_medical_card">Жаңы медкартаны түзүү</button>
        </form>
    <?php endif; ?>

            <!-- Список посещений к врачам -->
        <div class="doctor-visits">
            <h3>Докторго кайрылуулары</h3>
              <ul id="doctor-visits-list">
             <?php if ($appointments): ?>
            <?php foreach ($appointments as $appointment): ?>
                <li>
                    <?= htmlspecialchars($appointment['appointment_time']) ?> - 
                    <?= htmlspecialchars($appointment['doctor_name'] . ' ' . $appointment['doctor_surname']) ?> 
                    (<?= htmlspecialchars($appointment['speciality']) ?>)
                </li>
            <?php endforeach; ?>
            <?php else: ?>
              <li>Жазуулар жок.</li>
            <?php endif; ?>
              </ul>
            </div>

        </div>
    </div>
            </div>
        <script>
           function showSection(sectionId, element) {
              const sections = document.querySelectorAll('.section');
              const menuItems = document.querySelectorAll('.sidebar ul li');

               // Убираем активный класс у всех элементов меню
              menuItems.forEach(item => item.classList.remove('active'));
    
              // Добавляем активный класс для выбранного элемента меню
            element.classList.add('active');
 
             // Прячем все секции
              sections.forEach(section => section.classList.remove('active'));

            // Показываем выбранную секцию
            const sectionToShow = document.getElementById(sectionId);
           if (sectionToShow) {
        sectionToShow.classList.add('active');
           }
          }

        // JavaScript для управления состоянием полей формы
    const editButton = document.getElementById('edit-button');
    const saveButton = document.getElementById('save-button');
    const form = document.getElementById('personal-data-form');
    const inputs = form.querySelectorAll('input');

    editButton.addEventListener('click', () => {
        inputs.forEach(input => input.disabled = false); // Разблокировать поля
        editButton.style.display = 'none'; // Скрыть кнопку "Обновить"
        saveButton.style.display = 'inline-block'; // Показать кнопку "Сохранить изменения"
    });

    document.addEventListener('DOMContentLoaded', () => {
    const records = document.querySelectorAll('.record'); 
    console.log(records); 
});



// Проверяем, если сообщение об успешном изменении пароля существует
window.onload = function() {
            const successMessage = document.getElementById("successMessage");
            if (successMessage) {
                successMessage.style.display = "block"; // Показываем сообщение
                setTimeout(function() {
                    successMessage.style.display = "none"; // Скрываем через 3 секунды
                }, 3000);
            }
        };
    </script>
</body>
</html>