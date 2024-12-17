<?php
session_start();
require 'db_connection.php';

// Проверка авторизации
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: auth.php');
    exit();
}

// Получение данных администратора
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM doctor WHERE id = :id");
$stmt->execute([':id' => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Обновление данных администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("UPDATE doctor SET name = :name, surname = :surname, phone = :phone, email = :email WHERE id = :id");
    $stmt->execute([':name' => $name, ':surname' => $surname, ':phone' => $phone, ':email' => $email, ':id' => $admin_id]);

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
    $stmt->execute([':id' => $admin_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "Ошибка: Админ не найден.";
        exit();
    }

    // Проверка текущего пароля
    if (!password_verify($current_password, $admin['password'])) {
        echo "Текущий пароль неверный.";
        exit();
    }

    // Хэшируем новый пароль
    $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);

    // Обновляем пароль в базе данных
    $stmt = $pdo->prepare("UPDATE doctor SET password = :password WHERE id = :id");
    $stmt->execute([
        ':password' => $new_password_hashed,
        ':id' => $admin_id
    ]);

    // Устанавливаем сообщение об успешном изменении пароля в сессию
    $_SESSION['password_change_success'] = "Пароль успешно изменен!";
    header('Location: ' . $_SERVER['PHP_SELF']); // Перезагружаем страницу
    exit();
}

//я добавила для переключателя до 116 строки
// Обновление статуса пользователя, если получен POST-запрос
if (isset($_POST['id']) && isset($_POST['status'])) {
    $user_id = $_POST['id'];
    $status = $_POST['status'];

    // Выводим полученные данные для отладки
    error_log("Получены данные: ID = $user_id, статус = $status");

    try {
        // Определяем, где находится пользователь: doctor или student
        $updated = false;

        // Попытка обновления статуса в таблице doctor
        $stmt = $pdo->prepare("UPDATE doctor SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $user_id]);

        if ($stmt->rowCount() > 0) {
            error_log("Статус обновлен в таблице doctor.");
            $updated = true;
        }

        // Попытка обновления статуса в таблице student, если в doctor ничего не обновилось
        if (!$updated) {
            $stmt = $pdo->prepare("UPDATE student SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $user_id]);

            if ($stmt->rowCount() > 0) {
                error_log("Статус обновлен в таблице student.");
                $updated = true;
            }
        }

        // Проверяем результат и отправляем ответ
        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Статус успешно обновлен.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не удалось обновить статус.']);
        }
    } catch (Exception $e) {
        // Логируем ошибку
        error_log("Ошибка: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Произошла ошибка при обновлении статуса.']);
    }

    exit();
}

// Управление врачами и студентами
if (isset($_GET['deactivate_user'])) {
    $user_id = $_GET['deactivate_user'];
    $stmt = $pdo->prepare("UPDATE doctor SET status = '0' WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    $stmt = $pdo->prepare("UPDATE student SET status = '0' WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    echo "Пользователь деактивирован.";
    header('Location: admin.php');
    exit();
}

if (isset($_GET['activate_user'])) {
    $user_id = $_GET['activate_user'];
    $stmt = $pdo->prepare("UPDATE doctor SET status = '1' WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    $stmt = $pdo->prepare("UPDATE student SET status = '1' WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    echo "Пользователь активирован.";
    header('Location: admin.php');
    exit();
}

// Получение списка всех врачей
$stmt = $pdo->prepare("SELECT * FROM doctor WHERE role='doctor'");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение расписания врача
$stmt = $pdo->prepare("SELECT * FROM schedules");
$stmt->execute();
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
            $stmt = $pdo->prepare("SELECT id FROM schedules WHERE doctor_id = :user_id AND day = :day");
            $stmt->execute([':user_id' => $user_id, ':day' => $day]);
            $existing_schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_schedule) {
                // Обновление существующего графика
                $stmt = $pdo->prepare("
                    UPDATE schedules 
                    SET time_from = :time_from, time_to = :time_to, is_day_off = :is_day_off
                    WHERE doctor_id = :user_id AND day = :day
                ");
                $stmt->execute([
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off,
                    ':user_id' => $user_id,
                    ':day' => $day
                ]);
            } else {
                // Создание нового графика
                $stmt = $pdo->prepare("
                    INSERT INTO schedules (doctor_id, day, time_from, time_to, is_day_off) 
                    VALUES (:user_id, :day, :time_from, :time_to, :is_day_off)
                ");
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':day' => $day,
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off
                ]);
            }
        }
        $pdo->commit();
        echo "График успешно обновлен!";
        header("Refresh:0");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Ошибка обновления графика: " . $e->getMessage();
    }
}

// Получение списка студентов
$stmt = $pdo->prepare("SELECT * FROM student");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Просмотр медицинской карты студента
$student_id = $_GET['student_id'] ?? null;
if ($student_id) {
    // Получаем данные студента
    $stmt = $pdo->prepare("SELECT * FROM student WHERE id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получаем или создаем медицинскую карту
    $stmt = $pdo->prepare("SELECT * FROM medical_cards WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $medical_card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medical_card) {
        $stmt = $pdo->prepare("INSERT INTO medical_cards (student_id) VALUES (:student_id)");
        $stmt->execute([':student_id' => $student_id]);
        $medical_card_id = $pdo->lastInsertId();
    } else {
        $medical_card_id = $medical_card['id'];
    }

    // Получаем медицинские записи
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE medical_card_id = :medical_card_id");
    $stmt->execute([':medical_card_id' => $medical_card_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} 


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
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

        form div {
            margin-bottom: 15px;
        }

        form label {
            display: block;
            font-weight: bold;
            text-align: left;
            color: #071F6F;
        }

        form input{
            width: 98%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 13px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            background-color: rgba(255, 255, 255, 0.8);
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
        }

        .btn-personal:hover {
            background-color: #022751;
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

        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: #fff;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Стили для переключателя статуса */
        .switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 20px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 20px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:checked + .slider:before {
    transform: translateX(14px);
}

.view-medical-card {
  display: inline-block;
  padding: 5px 20px;
  background-color: #007bff;
  color: white;
  text-align: center;
  border-radius: 5px;
  text-decoration: none;
  cursor: pointer;
}

.view-medical-card:hover {
  background-color: #0056b3;
}

.view-medical-card:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.5);
}

h3{
    color: #071F6F;
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
        <h2>Кош келдиңиз, <?= htmlspecialchars($admin['name'] . ' ' . $admin['surname']) ?></h2>
        <ul>
            <li class="active" onclick="showSection('personal-data', this)">Жеке маалымат</li>
            <li onclick="showSection('doctors', this)">Дарыгерлердин тизмеси</li>
            <li onclick="showSection('students', this)">Студенттердин тизмеси</li>
            <li id="schedule-link" onclick="showSection('schedule-section',this)">Дарыгерлердин графиги</li>
            <li><a href="doctor_reg.php" class="btn-reg">Дарыгерди каттоо</a></li> <!-- Кнопка регистрации врача -->
            <li><a href="logout.php" class="btn-reg">Чыгуу</a></li>
        </ul>
    </div>

    <div class="content">
        <section id="personal-data" class="section active">
            <h2>Жеке маалымат</h2>
            <form method="POST">
            <?php if (isset($admin) && is_array($admin)): ?>
                <div>
                    <label for="name">Аты:</label>
                    <input type="text" name="name" id="name" value="<?=isset($admin['name']) ? htmlspecialchars($admin['name']): ''?>" disabled id="name">
                </div>
                <div>
                    <label for="surname">Фамилия:</label>
                    <input type="text" name="surname" id="surname" value="<?= isset($admin['surname']) ? htmlspecialchars($admin['surname']) : '' ?>" disabled id="surname">
                </div>
                <div>
                    <label for="phone">Телефон:</label>
                    <input type="text" name="phone" id="phone" value="<?= isset($admin['phone']) ? htmlspecialchars($admin['phone']) :''?>" disabled id="phone">
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?= isset($admin['email']) ? htmlspecialchars($admin['email']) :'' ?>" disabled id="email">
                </div>
                <button type="button" class="btn-personal" id="edit-button">Жаңыртуу</button>
                <button type="submit" class = "btn-personal" name="update_admin" id="save-button" style="display:none;">Өзгөртүүлөрдү сактоо</button>
                <?php else: ?>
            <p>Данные об админе не найдены. Пожалуйста, убедитесь, что админ существует в базе данных.</p>
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
        </section>

        <section id="doctors" class="section">
            <h2>Дарыгердердин тизмеси</h2>
            <table>
                <thead>
                    <tr>
                        <th>Аты</th>
                        <th>Фамилия</th>
                        <th>Кесип</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td><?= htmlspecialchars($doctor['name']) ?></td>
                            <td><?= htmlspecialchars($doctor['surname']) ?></td>
                            <td><?= htmlspecialchars($doctor['speciality']) ?></td>
                            <td><?= htmlspecialchars($doctor['phone']) ?></td>
                            <td><!-- Переключатель статуса -->
                                <label class="switch">
                                  <input type="checkbox" class="status-toggle" data-id="<?= $doctor['id'] ?>" <?= $doctor['status'] ? 'checked' : '' ?>>
                                  <span class="slider"></span>
                                </label>
                            </td>
                            <!--<td>
                                <a href="?deactivate_user=<?= $doctor['id'] ?>" class="btn">Деактивировать</a>
                                <a href="?activate_user=<?= $doctor['id'] ?>" class="btn">Активировать</a>
                            </td>-->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section id="students" class="section">
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
                        <th>Статус</th>
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
                            <a href="admin.php?student_id=<?= $student_item['id'] ?>" class="view-medical-card" role="button" >Медкартаны көрүү</a>
                            </td>
                            <td><!-- Переключатель статуса -->
                                <label class="switch">
                                  <input type="checkbox" class="status-toggle" data-id="<?= $student_item['id'] ?>" <?= $student_item['status'] ? 'checked' : '' ?>>
                                  <span class="slider"></span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($student_id && $student): ?>
        <h2>Студенттин медкартасы</h2>
        <p>Имя: <?= htmlspecialchars($student['name']) ?> <?= htmlspecialchars($student['surname']) ?></p>
        <h3>Медкартадагы жазуулар</h3>
        <?php if ($medical_records): ?>
            <ul>
                <?php foreach ($medical_records as $record): ?>
                    <li>
                        <strong>Оору:</strong> <?= htmlspecialchars($record['disease_name']) ?> (<?= htmlspecialchars($record['disease_date']) ?>)<br>
                        <strong>Дарылар:</strong> <?= htmlspecialchars($record['medications']) ?><br>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Жазуулар жок.</p>
        <?php endif; ?>
    <?php endif; ?>
        </section>

        <section id="schedule-section" class="section">
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

    <script>
        function showSection(sectionId, element) {
        // Скрываем все секции
        const sections = document.querySelectorAll('.section');
        sections.forEach(section => section.classList.remove('active'));

        // Убираем активный класс с всех элементов меню
        const menuItems = document.querySelectorAll('.sidebar ul li');
        menuItems.forEach(item => item.classList.remove('active'));

        // Показываем нужную секцию
        const activeSection = document.getElementById(sectionId);
        if (activeSection) {
            activeSection.classList.add('active');
        }

        // Добавляем активный класс на выбранный элемент меню
        element.classList.add('active');
    }

    // JavaScript для управления состоянием полей формы
    const editButton = document.getElementById('edit-button');
    const saveButton = document.getElementById('save-button');
    const form = document.getElementById('personal-data');
    const inputs = form.querySelectorAll('input');

    editButton.addEventListener('click', () => {
        inputs.forEach(input => input.disabled = false); // Разблокировать поля
        editButton.style.display = 'none'; // Скрыть кнопку "Обновить"
        saveButton.style.display = 'inline-block'; // Показать кнопку "Сохранить изменения"
    });




        document.querySelectorAll('.status-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const userId = this.getAttribute('data-id');
            const status = this.checked ? 1 : 0;

            // Отправка запроса на сервер для обновления статуса
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin.php', true); // Используем тот же файл admin.php
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    console.log('Статус успешно обновлен');
                } else {
                    console.log('Ошибка при обновлении статуса');
                    // Возврат состояния переключателя в исходное, если произошла ошибка
                    toggle.checked = !toggle.checked;
                }
            };
            xhr.send('id=' + userId + '&status=' + status);
        });
    });

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