<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="description" content="Vizualizarea anotimpurilor">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
        <link rel="stylesheet" href="choose_season_style.css">
        <title>Choose Season</title>
    </head>
    <body>
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
            <div class="btn-group">
                <a href="index.php" class="nav-btn">🏠Acasă</a>
            </div>   
        </nav>
        <div class="message">
             <h1>Natura este într-o continuă schimbare!<br>🌿Descoperă plantele ideale pentru această perioadă a anului🌿</h1>
        </div>
        <!-- Containerul principal-->
        <section class="swiper SwiperSeason">
            <!-- Wrapper necesar pentru a grupa toate slide-urile-->
            <div class="swiper-wrapper">
                <!-- Slide individual din slider -->
                <div class="swiper-slide" id="t1">
                    <div class="image">
                        <img src="./img/primavara.png" alt="Primăvara">
                    </div>
                    <div class="content">
                        <span class="title" id="title1">PRIMĂVARĂ 🌸 </span>
                        <button class="button_select" id="prim" onclick="redirectToPlantCardsPage('primăvară')">Selectează</button>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="image">
                        <img src="./img/vara.png" alt="Vara">
                    </div>
                    <div class="content">
                        <span class="title" id="title2">VARĂ ☀️</span>
                        <button class="button_select" id="doi" onclick="redirectToPlantCardsPage('vară')">Selectează</button>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="image">
                        <img src="./img/toamna.png" alt="Toamna">
                    </div>
                    <div class="content">
                        <span class="title" id="title3">TOAMNĂ 🍂</span>
                        <button class="button_select" id="trei" onclick="redirectToPlantCardsPage('toamnă')">Selectează</button>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="image">
                        <img src="./img/iarna.png" alt="Iarna">
                    </div>
                    <div class="content">
                        <span class="title" id="title4">IARNĂ ❄️</span>
                        <button class="button_select" id="patru" onclick="redirectToPlantCardsPage('iarnă')">Selectează</button>
                    </div>
                </div>
                </div>
            <div class="swiper-pagination"></div>
        </section>
        <footer class="footer">
            <div>
                <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
            <div>
        </footer>
         <script>
            function redirectToPlantCardsPage(season){
                window.location.href=`card_season.php?season=${season}`;
             }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".SwiperSeason", {
            // Sliderul se reia automat 
            loop: true,
            // Pentru ca slide-urile să se schimbe automat la fiecare 2500ms
            autoplay: {
                delay: 2500,
                disableOnInteraction: false,
            },
            coverflowEffect: {     
                // Punem umbre la slide-urile din spate
                slideShadows: true,
                // Setăm unghiul de rotire al slide-urilor
                rotate: 0,
                // Setăm distanța dintre slide-uri
                depth: 400,
            },
            pagination: {
                // Afișează bulinele de paginare sub slider
                el: ".swiper-pagination",
                // Permite apăsarea pe buline pentru a naviga prin slide-uri
                clickable: true,
            },
            // Setează automat numărul de slide-uri vizibile
            slidesPerView: "auto",
            // Permite ca slide-ul central să fie evidențiat, iar celelalte să fie în lateral
            effect: "coverflow",
            grabCursor: true,
            // Centrează slide-ul activ pe ecran
            centeredSlides: true,
            // Setăm spațiul dintre slide-uri
            spaceBetween: 25,
        });
    </script>
</body>
</html>