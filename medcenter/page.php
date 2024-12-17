<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lucida+Calligraphy&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        /* Основной стиль */
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(100deg, #0A2B9B 32%, #030F35 100%);
            color: white;
            min-height: 100vh;
        }
        
        * {
         box-sizing: border-box;  /* Это гарантирует, что padding и border не выходят за пределы элемента */
        }

        html {
          scroll-behavior: smooth;
        }

        /* Хедер */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 60px;
            background-color: rgba(255, 255, 255, 0.05);; /* Прозрачный фон */
            position: sticky; /* Закрепляем хедер */
            top: 0; /* Он будет закреплен к верхней части экрана */
            width: 100%;
            z-index: 1000; /* Чтобы хедер был поверх других элементов */
        }

        .header-links {
            display: flex;
            gap: 20px;
        }

        .header-links a {
            text-decoration: none;
            color: #ffff;
            font-weight: bold;
            font-size: 20px;
            font-family: 'Montserrat',sans-serif;
        }

        .header-links a.active {
            text-decoration: underline; /* Подчеркивание активной ссылки */
            text-underline-offset: 7px; /* Смещение подчеркивания вниз */
            color: inherit; /* Сохраняет цвет, не меняет на красный */
        }

        /* Контейнер для информации */
        .info-container {
            display: flex;
            justify-content: space-between;
            padding: 40px;
            margin: 40px;
        }

        .info-container .left {
            width: 60%;
        }

        .info-container .right {
            width: 470px;
            height: 570px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: -50px;
        }

        .right video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            animation: loop 20s infinite; /* Видео будет циклично повторяться */
        }

        /* Шрифты и стили для текста */
        .heading {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 60px;
            color: #fff;
            margin-top: 40px;
            margin-left: 20px;
        }

        .subheading {
            font-family: 'Lucida Calligraphy',sans-serif;
            font-style: italic;
            font-size: 32px;
            margin-top: 60px;
            margin-bottom: 60px;
            margin-left: 40px;
            color: #7E9BFC;
        }

         /* Кнопки */
         .button-register, .button-login {
            padding: 10px 30px;
            border-radius: 25px;  /* Закругленные углы */
            font-size: 18px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 0px;
            transition: background-color 0.3s ease; /* Плавное изменение фона */
            
        }

        .buttonreglog {
            display: flex;
            gap: 20px; /* Расстояние между кнопками*/
        }

        .button-register {
            background-color: white;
            color: #031A65;
        }

        .button-login {
            background-color: #e52424;
            color: white;
        }

        .button-register:hover {
            background-color: #f0f0f0;
        }

        .button-login:hover {
            background-color: #ff4d4d;
        }

        .button-signup {
            background-color: white;
            color: #031A65;
            display: inline-block;
            font-weight: bold;
            font-size: 20px;
            padding: 10px 30px;
            border-radius: 30px;
            text-decoration: none;
            margin-top: 40px;
            margin-left: 250px;
        }

        .button-signup:hover {
            background-color: #f0f0f0;
            font-size: 21px;
        }

        .button-signup::after {
            content: '→';
            margin-left: 10px;
        }

        /* Анимация для видео */
        @keyframes loop {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

         /* Контейнер для "Биз жөнүндө" */
    .about-container {
        padding: 40px 60px;
        color: #fff;
    }

    .about-heading {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: center;
    }

    .about-text p {
        font-size: 18px;
        text-align: justify;
        line-height: 1.6;
        font-family: 'Montserrat', sans-serif;
        margin-bottom: 40px;
    }

    /* Галерея */
    .gallery-container {
        position: relative;
        max-width: 100%;
        overflow: hidden;
        margin-top: 40px;
    }

    .gallery {
        display: flex;
        transition: transform 0.5s ease-in-out;
    }

    .gallery-item {
        width: 80%;
        margin: 0 10px;
        opacity: 0.7;
        transition: opacity 0.5s ease;
    }

    .gallery-item.active {
        opacity: 1;
    }

    .gallery-item img {
        width: 100%;
        height: auto;
        border-radius: 10px;
        object-fit: cover;
    }

    .gallery-controls {
        position: absolute;
        top: 50%;
        width: 100%;
        display: flex;
        justify-content: space-between;
        transform: translateY(-50%);
    }

    .gallery-controls button {
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        font-size: 24px;
        border: none;
        padding: 10px;
        border-radius: 50%;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .gallery-controls button:hover {
        background-color: rgba(0, 0, 0, 0.8);
    }

    .services-container {
        padding: 60px;
        
    }

    .services-heading h2 {
        font-size: 36px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 40px;
        color: #fff;
    }

    .services-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .service-item {
        background-color: rgba(255,255,255,0.9);
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }

    .service-item:hover {
        transform: translateY(-10px);
    }

    .service-item .icon img {
        width: 50px;
        height: 50px;
        margin-bottom: 20px;
    }

    .service-item h3 {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #031A65;
    }

    .service-item p, .service-item ul {
        font-size: 16px;
        color: #555;
        line-height: 1.6;
    }

    .diagnostic-section {
    /*background-color: rgba(255,255,255,0.9); /* Светлый фон */
    padding: 40px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.diagnostic-section h3 {
    font-size: 1.6rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 30px;
    color: #fff;
    text-transform: uppercase;
    font-family: 'Helvetica Neue', sans-serif;
    letter-spacing: 1px;
}

.diagnostic-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 колонки */
    gap: 20px;
}

.diagnostic-item {
    font-size: 18px;
    color: #34495e;
    font-family: 'Arial', sans-serif;
    font-weight: 500;
    background: #fff;
    padding: 12px 20px;
    border-left: 4px solid #3498db; /* Акцентная линия слева */
    transition: transform 0.3s ease, color 0.3s ease;
    cursor: pointer;
}

.diagnostic-item:hover {
    transform: translateX(10px); /* Легкое движение вправо */
    color: #2980b9; /* Изменение цвета текста при наведении */
}

.diagnostic-item:first-child {
    border-top: 2px solid #3498db; /* Линия сверху для первого элемента */
}

.diagnostic-item:last-child {
    border-bottom: 2px solid #3498db; /* Линия снизу для последнего элемента */
}

/* Добавление анимации */
@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.diagnostic-item {
    animation: fadeIn 0.4s ease-out;
}

.diagnostic-list .diagnostic-item:nth-child(even) {
    animation-delay: 0.2s; /* Задержка анимации для четных элементов */
}

.diagnostic-list .diagnostic-item:nth-child(odd) {
    animation-delay: 0.4s; /* Задержка анимации для нечетных элементов */
}

 /* Контейнер врачей */
 .doctors-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            padding: 40px;
        }

        /* Контейнер для каждого врача */
        .doctors {
            display: flex;
            justify-content: space-between; /* Равномерное распределение элементов */
            flex-wrap: wrap; /* Позволяет элементам переноситься на новую строку при необходимости */
            gap: 20px; /* Расстояние между карточками */
        }
        .doctor-card {
            width: calc(25% - 15px);
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .doctor-card:hover {
            transform: scale(1.05);
        }

        .doctor-photo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #ddd;
        }

        .doctor-info {
            padding: 15px;
        }

        .doctor-info h3 {
            font-size: 1.2rem;
            margin: 0;
            color: #030F35;
        }

        .doctor-info p {
            font-size: 1rem;
            color: #777;
        }

        /* Модальное окно с детальной информацией */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            padding: 20px;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 900px;
            width: 100%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .modal-content h2 {
            margin-top: 0;
            color: #030F35;
        }

        .modal-content p {
            font-size: 1rem;
            color: #555;
        }

        .close {
            background-color: #030F35;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .close:hover {
            background-color: #777;
        }

.address-section {
    color: white;
    padding: 40px 0;
    border-radius: 15px;
    font-family: 'Arial', sans-serif;
    text-align: center;
}

.address-section h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 30px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.address-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.address-item {
    background: #1b367c;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: transform 0.3s ease;
}

.address-item i {
    font-size: 20px;
    margin-right: 10px;
    color: white;
}

.address-item p {
    font-size: 16px;
    margin: 0;
}

.address-item:hover {
    transform: translateY(-5px);
}

.address-item a {
    color: white;
    text-decoration: none;
}

.map-container iframe {
    border-radius: 10px;
    border: none;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.footer {
    background-color: #032344;
    color: white;
    padding: 20px 0;
    text-align: center;
    font-family: 'Arial', sans-serif;
    font-size: 14px;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
}

.footer p {
    margin: 5px 0;
}

.footer .social-icons {
    margin-top: 15px;
}

.footer .social-icons a {
    color: white;
    font-size: 20px;
    margin: 0 15px;
    transition: color 0.3s ease;
}

.footer .social-icons a:hover {
    color: #3498db;
}

.footer .social-icons i {
    vertical-align: middle;
}


    </style>
</head>
<body>

    <!-- Хедер -->
    <header>
        <div class="logo">
            <img src="logo.png" alt="Эмблема" style="width: 200px; height: auto;"/>
            
        </div>
        <div class="header-links">
            <a href="#about" class="">Биз жөнүндө</a>
            <a href="#">Адистер</a>
            <!--<a href="#">Иштөө графиги</a>-->
            <a href="#address">Адрес</a>
        </div>  
        <div class="buttonreglog">
            <a href="student_reg.php" class="button-register">Каттоо</a>
            <a href="auth.php" class="button-login">Кирүү</a>
        </div> 
    </header>

    <!-- Контейнер с информацией -->
    <div class="info-container">
        <div class="left">
            <div class="heading">Шейит Эрен Бүлбүл<br> Ден-соолук борбору</div>
            <div class="subheading">
                "Келечегиңизге кам көрүшүңүз үчүн биз<br> 
                сиздин ден соолугуңузга кам көрөбүз."
            </div>
            <a href="auth.php" class="button-signup">Жазылуу</a>
        </div>
        <div class="right">
            <video src="medcenter.mp4" autoplay loop muted></video>
        </div>
    </div>

    <!-- Контейнер для "Биз жөнүндө" -->
    <div class="about-container" id="about">
    <div class="about-heading">
        Биз жөнүндө
    </div>
    <div class="about-text">
        <p>Ден соолук, маданият жана спорт башкармалыгынын курамындагы Ден-соолук борбору студенттерибизге, кызматкерлерибизге жана кызматкерлерибиздин үй-бүлө мүчөлөрүнө 1999-жылдан бери медициналык жардам көрсөтүп келет. 2020-жылы университетибиздин Ч.Айтматов атындагы кампусунда имараттардын бири жаңыланып, Шейит Эрен Бүлбүл атындагы Ден-соолук борбору жана COVID-19 диагностикалык борбору катары пайдаланууга берилди. Кыргызстандын мыйзамдарына ылайык жабдылган Ден-соолук борбору Кыргыз Республикасынын Саламаттык сактоо Министрлиги тарабынан аккредитацияланган.</p>
    </div>

    <!-- Галерея -->
    <div class="gallery-container">
        <div class="gallery">
            <div class="gallery-item active">
                <img src="1.jpg" alt="Фотография 1">
            </div>
            <div class="gallery-item">
                <img src="main.jpg" alt="Фотография 2">
            </div>
            <div class="gallery-item">
                <img src="2.jpg" alt="Фотография 3">
            </div>
            <div class="gallery-item">
                <img src="5.jpg" alt="Фотография 4">
            </div>
        </div>
        <div class="gallery-controls">
            <button class="prev">&#10094;</button>
            <button class="next">&#10095;</button>
        </div>
    </div>
</div>

<!-- Контейнер для Кызматтар -->
<div class="services-container">
    <div class="services-heading">
        <h2>Кызматтар</h2>
    </div>
    <div class="services-list">
        <div class="service-item">
            <!--<div class="icon">
                <img src="first-aid-icon.png" alt="Биринчи медициналык жардам" />
            </div>-->
            <h3>Биринчи медициналык жардам</h3>
            <p>Эгерде күжүрмөн жардамга муктаж болсоңуз, биздин адистер дароо жардам көрсөтүүгө даяр.</p>
        </div>
        
        <div class="service-item">
            <!--<div class="icon">
                <img src="ambulance-icon.png" alt="Амбулатордук жардам" />
            </div-->
            <h3>Амбулатордук жардам</h3>
            <ul>
                <li>Жалпы текшерүү</li>
                <li>Бейтаптарды дарылоо</li>
                <li>Диагностикалоо</li>
            </ul>
        </div>

        <div class="service-item">
            <!--<div class="icon">
                <img src="sport-icon.png" alt="Спорттук иш-чаралары үчүн медициналык кызмат" />
            </div>-->
            <h3>Спорттук иш-чаралары үчүн медициналык кызмат көрсөтүү</h3>
            <p>Спорттук иш-чаралардын коопсуздугу үчүн адистерибиз медициналык көзөмөлдү жүргүзөт.</p>
        </div>

        <div class="service-item">
            <!--<div class="icon">
                <img src="chronic-care-icon.png" alt="Өнөкөт оорулуу бейтаптарга көзөмөл жүргүзүү" />
            </div>-->
            <h3>Өнөкөт оорулуу бейтаптарга көзөмөл жүргүзүү</h3>
            <p>Өнөкөт ооруларды көзөмөлдөп, бейтаптарга узак мөөнөттүү жардам көрсөтүү.</p>
        </div>

        <div class="service-item">
            <!--<div class="icon">
                <img src="prevention-icon.png" alt="Профилактикалык иш чаралар" />
            </div>-->
            <h3>Профилактикалык иш чаралар</h3>
            <p>Ден соолук боюнча алдын ала иш чаралар жана текшерүүлөр.</p>
        </div>
    </div>

    <div class="diagnostic-section">
        <h3>Диагностиканын түрлөрү</h3>
        <ul class="diagnostic-list">
            <li class="diagnostic-item">Рентген</li>
            <li class="diagnostic-item">УЗИ</li>
            <li class="diagnostic-item">ЭКГ</li>
            <li class="diagnostic-item">Холтер ЭКГ</li>
            <li class="diagnostic-item">ПЦР изилдөө (COVID-19)</li>
            <li class="diagnostic-item">Гематологиялык изилдөө</li>
            <li class="diagnostic-item">Биохимиялык изилдөө</li>
            <li class="diagnostic-item">Заараны изилдөө</li>
        </ul>
    </div>
</div>

<section class="doctors-container">
    <!-- Карточка 1 -->
    <div class="doctor-card" onclick="openModal('doctor1')">
        <img src="терапевт.jpg" alt="Доктор 1" class="doctor-photo">
        <div class="doctor-info">
            <h3> Губайдуллина Гульфия Шарифзяновна</h3>
            <p>Жогорку категориядагы терапевт</p>
        </div>
    </div>

    <!-- Карточка 2 -->
    <div class="doctor-card" onclick="openModal('doctor2')">
        <img src="doctor2.jpg" alt="Доктор 2" class="doctor-photo">
        <div class="doctor-info">
            <h3>Мария Петрова</h3>
            <p>Терапевт</p>
        </div>
    </div>

    <!-- Карточка 3 -->
    <div class="doctor-card" onclick="openModal('doctor3')">
        <img src="doctor3.jpg" alt="Доктор 3" class="doctor-photo">
        <div class="doctor-info">
            <h3>Алексей Смирнов</h3>
            <p>Хирург</p>
        </div>
    </div>

    <div class="doctor-card" onclick="openModal('doctor4')">
        <img src="doctor4.jpg" alt="Доктор 4" class="doctor-photo">
        <div class="doctor-info">
            <h3>Алексей Смирнов</h3>
            <p>Хирург</p>
        </div>
    </div>
</section>

<!-- Модальные окна -->
<div id="doctor1" class="modal">
    <div class="modal-content">
        <h2>Губайдуллина Гульфия Шарифзяновна</h2>
        <p><strong>Иш тажрыйбасы:</strong>

            1997-1998-ж.ж. МТЖБСнын (Медициналык тез жардам борбордук станциясы) врач интерни.<br>
            
            1998-2001ж.ж.  МТЖБСнын тез жана кечиктирилгис медициналык жардам көрсөтүү боюнча көчмө кардиологиялык бригадасынын врачы.<br>
            
            2001-2005-ж.ж. «Медикал сервис 051» ЖЧК –Амбулатордук-поликлиникалык кабыл алуу (АПП) көчмө бригадасынын врачы.  <br>
            
            2006-2009-ж.ж. №5 Үй-бүлөлүк медицина борбору, врач-терапевт.<br>
            
            2011-2016-ж.ж. Кыргыз-Түрк «Манас» университетинин медициналык борборунун врач- терапевти.<br>
            
            2016-ж. Азыркы учурда Кыргыз-Түрк «Манас» университетинин медициналык борборунун врач-терапевти. Медборбордун мүдүрүнүн милдетин аткаруучу.</p>
        <p><strong>Квалификацияны жогорулатуу:</strong>

            2001-ж.“Кардиологиянын актуалдуу маселелери” цикли боюнча курстан өткөн.<br>
            
            2003-ж. Медициналык тез жардам врачы биринчи категориядагы квалификациясы ыйгарылган.<br>
            
            2013-ж. “ЭКГ негиздери менен жалпы терапия ” программасы боюнча квалификацияны жогорулатуу курсунан өткөн.<br>
            
            2017-ж. “Ички органдардын ооруларын УЗИ диагностикасы” адистиги боюнча баштапкы  адистик окутуудан өткөн.<br>  
            
            2020-ж. “ЖИО: Атеросклероз, гиперлипидемия. ФКтын туруктуу стенокардиясы жана туруксуз стенокардия. Заманбап протоколдук классификацияны жана дарылоону колдонуу аркылуу ST сегменттин элевациясы менен ККС жана ST сегменттин элевациясыз”.<br>
            
            2020-ж. “Туташтырма ткандардын жана артриттердин системалуу оорулары. Диагностиканын заманбап методдору. Дарылоо” программасы боюнча квалификацияны жогорулатуу курсунан өткөн.<br>
            
            2020-ж. “Дифференциалдык диагностика менен гастроэнтерологиянын актуалдуу маселелери” программасы боюнча квалификацияны жогорулатуу курсунан өткөн.<br>
            
            2020-ж. “Кардиологдун жана үй-бүлөлүк дарыгердин мектеби” илимий-практикалык конференциясына катышкан.<br>
            
            2021-ж.“Терапевт” адистиги боюнча жогорку квалификациялык категория ыйгарылган.</p>
        <button class="close" onclick="closeModal('doctor1')">Жабуу</button>
    </div>
</div>

<div id="doctor2" class="modal">
    <div class="modal-content">
        <h2>Мария Петрова</h2>
        <p>Терапевт с 8-летним стажем. Опыт работы в диагностике и лечении широкого спектра заболеваний. Работала в лучших частных клиниках.</p>
        <p>Работала с 2012 по 2021 год в клинике "Медикал".</p>
        <button class="close" onclick="closeModal('doctor2')">Закрыть</button>
    </div>
</div>

<div id="doctor3" class="modal">
    <div class="modal-content">
        <h2>Алексей Смирнов</h2>
        <p>Хирург с 12-летним опытом. Провел множество операций, включая сложные вмешательства. Специализируется на органах брюшной полости.</p>
        <p>Работал с 2010 по 2022 год в клинике "Операция".</p>
        <button class="close" onclick="closeModal('doctor3')">Закрыть</button>
    </div>
</div>

<div class="address-section" id="address">
    <h3>Дарегибиз</h3>
    <div class="address-details">
        <div class="address-item">
            <i class="fas fa-envelope"></i>
            <p>medcenter@manas.edu.kg</p>
        </div>
        <div class="address-item">
            <i class="fas fa-phone-alt"></i>
            <p>+996 (312) 49 27 65 (942 директор, 940 регистратура)</p>
        </div>
        <div class="address-item">
            <i class="fas fa-map-marker-alt"></i>
            <p>Чыңгыз Айтматов атындагы Жал студенттик шаарчасы, 720038, Жал мкр., Бишкек, КЫРГЫЗСТАН</p>
        </div>
        <div class="address-item">
            <a href="https://go.2gis.com/hlq085" target="_blank">
                <i class="fas fa-map-marked-alt"></i>
                <p>Посмотреть на 2ГИС</p>
            </a>
        </div>
    </div>
    <div class="map-container">
        <iframe src="https://maps.google.com/maps?q=Чыңгыз%20Айтматов%20атындагы%20Жал%20студенттик%20шаарчасы&t=&z=13&ie=UTF8&iwloc=&output=embed" width="100%" height="400" frameborder="0" style="border:0;" allowfullscreen=""></iframe>
    </div>
</div>

<!-- Футер -->
<footer class="footer">
    <div class="footer-content">
        <p>&copy; 2024 Сиздин медициналык борбор. Бардык укуктар корголгон.</p>
        <p>Разработано с любовью</p>
        <div class="social-icons">
            <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</footer>




<script>
    // Галерея слайдер
    let currentIndex = 0;
    const galleryItems = document.querySelectorAll('.gallery-item');
    const totalItems = galleryItems.length;

    document.querySelector('.next').addEventListener('click', () => {
        galleryItems[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % totalItems;
        galleryItems[currentIndex].classList.add('active');
    });

    document.querySelector('.prev').addEventListener('click', () => {
        galleryItems[currentIndex].classList.remove('active');
        currentIndex = (currentIndex - 1 + totalItems) % totalItems;
        galleryItems[currentIndex].classList.add('active');
    });

    // Получаем все ссылки и добавляем обработчик событий
    const links = document.querySelectorAll('.header-links a');

    // Функция для обновления активной ссылки
    function setActiveLink(event) {
        links.forEach(link => link.classList.remove('active')); // Убираем активность с всех ссылок
        event.target.classList.add('active'); // Добавляем активность к нажатой ссылке
    }

    // Назначаем обработчик событий на каждую ссылку
    links.forEach(link => {
        link.addEventListener('click', setActiveLink);
    });

    // Устанавливаем активную ссылку по умолчанию на первую
    document.getElementById('about-link').classList.add('active');

    // Открытие модального окна
    function openModal(doctorId) {
        document.getElementById(doctorId).style.display = 'flex';
    }

    // Закрытие модального окна
    function closeModal(doctorId) {
        document.getElementById(doctorId).style.display = 'none';
    }

</script>

</body>
</html>
