<?php
session_start();
require 'db_connection.php';

// Проверка, авторизован ли врач
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: auth.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Получение данных врача
$stmt = $pdo->prepare("SELECT * FROM doctor WHERE id = :id");
$stmt->execute([':id' => $doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo "Ошибка: врач не найден.";
    exit();
}

// Обновление данных врача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $speciality = $_POST['speciality'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("
        UPDATE doctor 
        SET name = :name, surname = :surname, speciality = :speciality, phone = :phone, email = :email 
        WHERE id = :id
    ");
    $stmt->execute([
        ':name' => $name,
        ':surname' => $surname,
        ':speciality' => $speciality,
        ':phone' => $phone,
        ':email' => $email,
        ':id' => $doctor_id
    ]);

    echo "Данные обновлены!";
    header("Refresh:0");
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
    $stmt = $pdo->prepare("SELECT password FROM doctor WHERE id = :id");
    $stmt->execute([':id' => $doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        echo "Ошибка: врач не найден.";
        exit();
    }

    // Проверка текущего пароля
    if (!password_verify($current_password, $doctor['password'])) {
        echo "Текущий пароль неверный.";
        exit();
    }

    // Хэшируем новый пароль
    $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);

    // Обновляем пароль в базе данных
    $stmt = $pdo->prepare("UPDATE doctor SET password = :password WHERE id = :id");
    $stmt->execute([
        ':password' => $new_password_hashed,
        ':id' => $doctor_id
    ]);

    // Устанавливаем сообщение об успешном изменении пароля в сессию
    $_SESSION['password_change_success'] = "Пароль успешно изменен!";
    header('Location: ' . $_SERVER['PHP_SELF']); // Перезагружаем страницу
    exit();
}




// Получение расписания врача
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE doctor_id = :doctor_id");
$stmt->execute([':doctor_id' => $doctor_id]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Инициализация дней недели
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_schedule'])) {
    $pdo->beginTransaction();
    try {
        foreach ($days_of_week as $day) {
            $time_from = $_POST['schedule'][$day]['time_from'] ?? null;
            $time_to = $_POST['schedule'][$day]['time_to'] ?? null;
            $is_day_off = isset($_POST['schedule'][$day]['is_day_off']) ? 1 : 0;

            // Проверка, существует ли запись для дня
            $stmt = $pdo->prepare("SELECT id FROM schedules WHERE doctor_id = :doctor_id AND day = :day");
            $stmt->execute([':doctor_id' => $doctor_id, ':day' => $day]);
            $existing_schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_schedule) {
                // Обновление существующего графика
                $stmt = $pdo->prepare("
                    UPDATE schedules 
                    SET time_from = :time_from, time_to = :time_to, is_day_off = :is_day_off
                    WHERE doctor_id = :doctor_id AND day = :day
                ");
                $stmt->execute([
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off,
                    ':doctor_id' => $doctor_id,
                    ':day' => $day
                ]);
            } else {
                // Создание нового графика
                $stmt = $pdo->prepare("
                    INSERT INTO schedules (doctor_id, day, time_from, time_to, is_day_off) 
                    VALUES (:doctor_id, :day, :time_from, :time_to, :is_day_off)
                ");
                $stmt->execute([
                    ':doctor_id' => $doctor_id,
                    ':day' => $day,
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off
                ]);
            }
        }
        $pdo->commit();
        // Перенаправление на ту же страницу, чтобы отобразились обновленные данные
        header("Location: " . $_SERVER['PHP_SELF'] . "#doctor-schedule");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Ошибка обновления графика: " . $e->getMessage();
    }
}

// Список пациентов на выбранный день
$date_filter = $_GET['date'] ?? date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.id AS appointment_id, s.name AS student_name, s.surname AS student_surname, a.appointment_time
    FROM appointments a
    JOIN student s ON a.student_id = s.id
    WHERE a.doctor_id = :doctor_id AND DATE(a.appointment_time) = :date
    ORDER BY a.appointment_time
");
$stmt->execute([':doctor_id' => $doctor_id, ':date' => $date_filter]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);






// Получение информации о студенте
$search_term = $_GET['search'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM student WHERE student_number LIKE :search_term");
$stmt->execute([':search_term' => '%' . $search_term . '%']);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверка, выбран ли студент
$student_id = $_GET['student_id'] ?? null;
if ($student_id) {
    // Получение информации о студенте
    $stmt = $pdo->prepare("SELECT * FROM student WHERE id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получение медицинской карты студента
    $stmt = $pdo->prepare("SELECT * FROM medical_cards WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $medical_card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medical_card) {
        // Если карты нет, создаем ее
        $stmt = $pdo->prepare("INSERT INTO medical_cards (student_id) VALUES (:student_id)");
        $stmt->execute([':student_id' => $student_id]);
        $medical_card_id = $pdo->lastInsertId();
    } else {
        $medical_card_id = $medical_card['id'];
    }

    // Получение записей из медицинской карты
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE medical_card_id = :medical_card_id");
    $stmt->execute([':medical_card_id' => $medical_card_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка добавления новой записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    $disease_name = $_POST['disease_name'];
    $disease_date = $_POST['disease_date'];
    $medications = $_POST['medications'];

    $stmt = $pdo->prepare("INSERT INTO medical_records (medical_card_id, disease_name, disease_date, medications, added_by) 
                           VALUES (:medical_card_id, :disease_name, :disease_date, :medications, 'doctor')");
    $stmt->execute([
        ':medical_card_id' => $medical_card_id,
        ':disease_name' => $disease_name,
        ':disease_date' => $disease_date,
        ':medications' => $medications
    ]);
    header("Location: ?student_id=$student_id");
    exit();
}

// Обработка загрузки файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medical_file'])) {
    $medical_record_id = $_POST['medical_record_id'];
    $file_name = $_FILES['medical_file']['name'];
    $file_tmp = $_FILES['medical_file']['tmp_name'];
    $file_path = "uploads/" . basename($file_name);

    if (move_uploaded_file($file_tmp, $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO medical_files (medical_record_id, file_name, file_path) 
                               VALUES (:medical_record_id, :file_name, :file_path)");
        $stmt->execute([
            ':medical_record_id' => $medical_record_id,
            ':file_name' => $file_name,
            ':file_path' => $file_path
        ]);
        header("Location: ?student_id=$student_id");
        exit();
    } else {
        echo "Ошибка загрузки файла.";
    }
}

// Обработка удаления файла
if (isset($_GET['delete_file_id'])) {
    $file_id = $_GET['delete_file_id'];
    $stmt = $pdo->prepare("DELETE FROM medical_files WHERE id = :file_id");
    $stmt->execute([':file_id' => $file_id]);
    header("Location: ?student_id=$student_id");
    exit();
}




?>




<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет врача</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
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
            color: #fff;
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

        .main-content {
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

        .form {
            margin-bottom: 15px;
            padding-left: 20px;
            padding-right: 25px;
        }

        .form label {
            display: block;
            font-weight: bold;
            text-align: left;
            color: #071F6F;
        }

        .form input{
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 13px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .section input, .section select, .section textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn {
            background-color: #071F6F;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background-color: #022751;
        }

#schedule form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.schedule-row {
    display: flex;
    align-items: center; /* Выравнивание по вертикали */
    gap: 10px; /* Отступы между элементами */
}

.schedule-row .day-name {
    font-size: 16px;
    color: #333;
}

input[type="time"] {
    padding: 5px;
    font-size: 16px;
    border-radius: 4px;
    border: 1px solid #ccc;
    width: 150px;
}

input[type="checkbox"] {
    margin-left: 10px;
    vertical-align: middle; /* Для выравнивания с текстом */
}

.checkbox-label {
    display: flex;
    align-items: center; /* Чтобы чекбокс и текст были на одной линии */
    gap: 5px;
    white-space: nowrap; /* Это заставит текст оставаться на одной строке */
}

button[type="submit"] {
    background-color: #071F6F;
    color: white;
    font-size: 16px;
    border: none;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button[type="submit"]:hover {
    background-color: #022751;
}

br {
    display: none;
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

        .view-medical-card {
            display: inline-block;
            padding: 10px 20px;
            color: #fff;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }

        .view-medical-card:hover {
            background-color: #0056b3;
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
        <h2>Кош келдиңиз, <?= htmlspecialchars($doctor['name'] . ' ' . $doctor['surname']) ?></h2>
        <ul>
            <li class="active" onclick="showSection('personal-data', this)">Жеке маалымат</li>
            <li onclick="showSection('patients', this)">Студенттер</li>
            <li onclick="showSection('doctor-schedule', this)">Иштөө графиги</li>
            <li onclick="showSection('schedule', this)">Иштөө графикти өзгөртүү</li>
            <li onclick="showSection('appointments', this)">Жазуулар</li>
        </ul>
    </div>
    <!-- Личные данные врача -->

    <div class="main-content">
        <div id="personal-data" class="section active">
            <h2>Жеке маалымат</h2>
            <form method="POST" id="personal-data-form">
            <?php if (isset($doctor) && is_array($doctor)): ?>
        <div class="form">
            <label for="name">Аты:</label>
            <input type="text" name="name" value="<?= isset($doctor['name']) ? htmlspecialchars($doctor['name']) : '' ?>" disabled id="name">
            
            <label for="surname">Фамилия:</label>
            <input type="text" name="surname" value="<?= isset($doctor['surname']) ? htmlspecialchars($doctor['surname']) : '' ?>" disabled id="surname">
            
            <label for="phone">Телефон:</label>
            <input type="text" name="phone" value="<?= isset($doctor['phone']) ? htmlspecialchars($doctor['phone']) : '' ?>" disabled id="phone">
            
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?= isset($doctor['email']) ? htmlspecialchars($doctor['email']) : '' ?>" disabled id="email">
            
            <label for="speciality">Кесип:</label>
            <input type="text" name="speciality" value="<?= isset($doctor['speciality']) ? htmlspecialchars($doctor['speciality']) : '' ?>" readonly id="speciality">
            
            <button type="button" class="btn" id="edit-button">Жаңыртуу</button>
            <button type="submit" name="update_doctor" class="btn" id="save-button" style="display:none;">Өзгөртүүлөрдү сактоо</button>
        </div>
        <?php else: ?>
            <p>Данные о враче не найдены. Пожалуйста, убедитесь, что врач существует в базе данных.</p>
        <?php endif; ?>
            </form>


<h2>Сырсөздү өзгөртүү</h2>
<form method="POST">
    <div>
        <label for="current_password">Азыркы сырсөз:</label>
        <input type="password" name="current_password" required id="current_password">
    </div>
    <div>
        <label for="new_password">Жаңы сырсөз:</label>
        <input type="password" name="new_password" required id="new_password">
    </div>
    <div>
        <label for="confirm_new_password">Жаңы сырсөздү ырастоо:</label>
        <input type="password" name="confirm_new_password" required id="confirm_new_password">
    </div>
    <button type="submit" name="change_password">Сырсөздү өзгөртүү</button>
</form>

</div>


    <!-- Расписание врача -->
    <!-- Форма для управления графиком -->
    <div id="schedule" class="section">
    <h2>График</h2>
    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>#schedule">
        <?php foreach ($days_of_week as $day): ?>
            <?php
            $day_schedule = array_filter($schedule, fn($s) => $s['day'] === $day);
            $day_schedule = $day_schedule ? reset($day_schedule) : null;
            ?>
            <div class="schedule-row">
                <span class="day-name"><?= htmlspecialchars($day) ?>:</span>
                <select name="schedule[<?= htmlspecialchars($day) ?>][time_from]">
                 <?php 
                 // Генерация списка времени с шагом в 30 минут
                  for ($h = 8; $h < 23; $h++) {
                   for ($m = 0; $m < 60; $m += 30) {
                  $time = sprintf('%02d:%02d', $h, $m);
                   $selected = ($day_schedule && $day_schedule['time_from'] === $time) ? 'selected' : '';
                   echo "<option value=\"$time\" $selected>$time</option>";
                  }
                 }
                 ?>
                </select>
                -
                <select name="schedule[<?= htmlspecialchars($day) ?>][time_to]">
        <?php 
        // Генерация списка времени с шагом в 30 минут
        for ($h = 8; $h < 23; $h++) {
            for ($m = 0; $m < 60; $m += 30) {
                $time = sprintf('%02d:%02d', $h, $m);
                $selected = ($day_schedule && $day_schedule['time_to'] === $time) ? 'selected' : '';
                echo "<option value=\"$time\" $selected>$time</option>";
            }
        }
        ?>
    </select>
                <label class="checkbox-label">
                    <input type="checkbox" name="schedule[<?= htmlspecialchars($day) ?>][is_day_off]" 
                           <?= isset($day_schedule['is_day_off']) && $day_schedule['is_day_off'] ? 'checked' : '' ?>>Эс алуу күнү
                </label>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="manage_schedule">Графикти сактоо</button>
    </form>
    
</div>

<div id="doctor-schedule" class="section">
    <h2>Расписание врача</h2>
    
    <table>
        <thead>
            <tr>
                <th>Күн</th>
                <th>Саат</th>
                <th>Саат</th>
                
            </tr>
        </thead>
        <tbody>
            <?php if ($schedule): ?>
                <?php foreach ($schedule as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['day']) ?></td>
                        <td><?= $row['is_day_off'] ? '<span class="day-off">эс алуу күнү</span>' : htmlspecialchars($row['time_from']) ?></td>
                        <td><?= $row['is_day_off'] ? '<span class="day-off">эс алуу күнү</span>' : htmlspecialchars($row['time_to']) ?></td>
                        
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Нет расписания для этого врача.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
            </div>



    <!-- Список пациентов -->
     <div id="appointments" class="section" >
    <h2>Пациенттер <?= htmlspecialchars($date_filter) ?>күнү</h2>
    <form method="GET" >
        <label for="date">Күндү тандоо:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
        <button type="submit">Көрсөтүү</button>
    </form>

    <?php if ($appointments): ?>
        <ul>
            <?php foreach ($appointments as $appointment): ?>
                <li>
                    <?= htmlspecialchars($appointment['student_name'] . ' ' . $appointment['student_surname']) ?> - <?= htmlspecialchars($appointment['appointment_time']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Пациенттер жок.</p>
    <?php endif; ?>
</div>



<?php
// Поиск студентов по студенческому номеру
$search_term = $_GET['search'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM student WHERE student_number LIKE :search_term");
$stmt->execute([':search_term' => '%' . $search_term . '%']);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="patients" class="section" >
    <h2>Студентти тандоо</h2>
    <form method="GET" >
        <label for="search">Студенттик номер боюнча издөө:</label>
        <input type="text" name="search" id="search" value="<?= htmlspecialchars($search_term) ?>">
        <button type="submit">Издөө</button>
    </form>

        <!-- Список мед карт -->
        <!--<h3>Медицинская карта студента</h3>-->

        <h2>Студенттердин тизмеси</h2>
            <table>
                <thead>
                    <tr>
                        <th>Студ. номер</th>
                        <th>Аты</th>
                        <th>Фамилия</th>
                        <th>Бөлүм</th>
                        <th>Телефон</th>
                        <th>Медкарта</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student_item): ?>
                        <tr>
                            <td><?= htmlspecialchars($student_item['student_number']) ?></td>
                            <td><?= htmlspecialchars($student_item['name']) ?></td>
                            <td><?= htmlspecialchars($student_item['surname']) ?></td>
                            <td><?= htmlspecialchars($student_item['department']) ?></td>
                            <td><?= htmlspecialchars($student_item['phone']) ?></td>
                            <td>
                            <a href="doctor_dashboard.php?student_id=<?= $student_item['id'] ?>" class="view-medical-card" role="button">Медкартаны көр</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        
        <?php if (!empty($medical_records)): ?>
            <h2>Медициналык картадагы жазуулар</h2>
            <ul>
                <?php foreach ($medical_records as $record): ?>
                    <li>
                        <strong>Оору:</strong> <?= htmlspecialchars($record['disease_name']) ?> (<?= htmlspecialchars($record['disease_date']) ?>)
                        <br>
                        <strong>Дарылар:</strong> <?= htmlspecialchars($record['medications']) ?><br>

                        <form action="" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="medical_record_id" value="<?= $record['id'] ?>">
                            <label>Файлды жүктөө:</label>
                            <input type="file" name="medical_file" required>
                            <button type="submit" class="btn" >Жүктөө</button>
                        </form>

                        <h4>Файлдар:</h4>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM medical_files WHERE medical_record_id = :record_id");
                        $stmt->execute([':record_id' => $record['id']]);
                        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if ($files): ?>
                            <ul>
                                <?php foreach ($files as $file): ?>
                                    <li>
                                        <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a>
                                        <a href="?delete_file_id=<?= $file['id'] ?>" onclick="return confirm('Удалить файл?')">Жок кылуу</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>Файлдар жок</p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        

        <h2>Жаңы жазууну кошуу</h2>
        <form action="" method="post">
            <label>Оору:</label>
            <input type="text" name="disease_name" required><br>
            <label>Дата:</label>
            <input type="date" name="disease_date" required><br>
            <label>Дарылар:</label>
            <textarea name="medications"></textarea><br>
            <button type="submit" name="add_record">Кошуу</button>
        </form>
    
        <?php else: ?>
            <p>Учурда студенттин медкартасы жок</p>
    <?php endif; ?>
    
</div>
    </div>


<script>
     function showSection(sectionId, element) {
            const sections = document.querySelectorAll('.section');
            const menuItems = document.querySelectorAll('.sidebar ul li');
            
            sections.forEach(section => section.classList.remove('active'));
            menuItems.forEach(item => item.classList.remove('active'));

            document.getElementById(sectionId).classList.add('active');
            element.classList.add('active');
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

    // Проверяем, если сообщение об успешном изменении пароля существует
    window.onload = function() {
            const successMessage = document.getElementById("successMessage");
            if (successMessage) {
                successMessage.style.display = "block"; // Показываем сообщение
                setTimeout(function() {
                    successMessage.style.display = "none"; // Скрываем через 3 секунды
                }, 1000);
            }
        };

    
</script>

</body>
</html>