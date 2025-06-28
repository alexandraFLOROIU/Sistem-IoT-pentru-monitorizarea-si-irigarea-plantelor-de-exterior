<?php
// Inițializăm sesiunea pentru a putea accesa datele despre utilizator
require_once 'session.php';
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
    die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
$viewModal = 0;
$userAuthenticated = isset($_SESSION['authenticated_user']) && $_SESSION['authenticated_user'] === true;
$user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
// Verificăm dacă userul are contul asociat cu o placă de dezvoltare ESP
if ($userAuthenticated) {
    $sqlUser = "SELECT id_ESP FROM device WHERE user_id = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param('i',$user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $viewModal = 1;
    } else {
        $stmt->bind_result($id_ESP);
        $stmt->fetch();
    }
}
echo "<script> const viewModal = $viewModal;</script>";
// Calculăm numărul de note și media notelor
$sql="SELECT AVG(rating) AS average, COUNT(rating) AS reviews FROM feedback";
$result=$conn->query($sql);
if ($result->num_rows > 0) {
    // În cazul în care există înregistrări, reținem numărul de note și media acestora în două variabile
    $row=$result->fetch_assoc();
    $ratingAverage=round($row['average'],1);
    $totalReviews=$row['reviews'];
} else {
    // Dacă nu există nicio înregistrare vor fi inițializate cu 0
    $totalReviews=0;
    $ratingAverage=0;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="description" content="Aplicație pentru irigarea automată a plantelor">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="index.css">
        <title>Smart Plant Irrigation</title>
    </head>
    <body>
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
        </nav>
    <main role="main" class="container">
        <div class="welcome">
            <h1>Bun venit la Smart Plant Irrigation System❗</h1>
            <p>Te confrunți cu probleme privind irigarea plantelor? Prin utilizarea acestui sistem, plantele tale  vor beneficia de o dezvoltare armonioasă🤝</p>
            <!--Butoane vizibile înainte ca userul să fie autentificat-->
            <?php if(!$userAuthenticated):?>
                <a id="active" class="btn btn-lg" href="#">🏠Acasă</a>
                <a class="btn btn-lg" href="hardware.html">🔌Componente</a>
                <a class="btn btn-lg" href="login.php">🔒 Log in</a>
            <?php endif;?>
            <!--Butoane vizibile după ce userul este autentificat-->
            <?php if($userAuthenticated):?>
                 <a id="active" class="btn btn-lg" href="#">🏠Acasă</a>
                 <a class="btn btn-lg" href="hardware.html">🔌Componente</a>
                 <a class="btn btn-lg" href="choose_season.php">🌿Planta mea</a>
                 <a class="btn btn-lg" href="send_feedback.php">💬Feedback</a>
                 <a class="btn btn-lg" href="#" onclick="modalOpen()">🔑ID ESP</a>
                 <a class="btn btn-lg" href="log_out.php">🔓 Log out</a>
           <?php endif;?>
        </div>
        <div class="reason">
            <h2>De ce să alegi Smart Plant Irrigation System?</h2>
            <p>Acest sistem își propune să satisfacă nevoile tuturor utilizatorilor, oferind opțiunea de udare implicită, manuală, automată sau periodică</p>
            <h3>Începe acum:</h3>
            <div class="steps-wrapper">
                <div class="steps-card">
                    <div class="steps-icon">👤</div>
                    <div class="steps-title">Pasul 1</div>
                    <div class="steps-info">Creează și confirmă contul</div>
                </div>
                <div class="steps-card">
                    <div class="steps-icon">🔑</div>
                    <div class="steps-title">Pasul 2</div>
                    <div class="steps-info"> Asociază contul cu placa de dezvoltare ESP</div>
                </div>   
                <div class="steps-card">
                    <div class="steps-icon">📅</div>
                    <div class="steps-title">Pasul 3</div>
                    <div class="steps-info"> Selectează anotimpul curent</div>
                </div>   
                <div class="steps-card">
                    <div class="steps-icon">🌱</div>
                    <div class="steps-title">Pasul 4</div>
                    <div class="steps-info"> Alege o plantă dintre cele disponibile</div>
                </div>   
                <div class="steps-card">
                    <div class="steps-icon">💧</div>
                    <div class="steps-title">Pasul 5</div>
                    <div class="steps-info">Setează modul de irigare dorit</div>
                </div> 
            </div>    
            <h3>📌Funcționalități:</h3>
            <ul>
                <li>Autentificare sigură.</li>
                <li>Notificări prin e-mail și alerte în interfața web.</li>
                <li>Controlul irigării de la distanță.</li>
                <li>Monitorizarea în timp real a parametrilor esențiali.</li>
                <li>Vizualizare grafică a datelor.</li>
                <li>Selectare plantă din sezonul actual.</li>
                <li>Program de irigare personalizat.</li>
                <li>Istoric activare irigare.</li>
                <li>Exportare date în format csv.</li>
            </ul>
            <h3>📝 Contactează-ne:</h3>
            <div class="info">
                <p>📧plantirrigationsmart@gmail.com</p>
                <p>📍Timișoara, Bulevardul Republicii nr. 1</p>
                <p>📞 0725679321</p>
            </div>
            <h3>⭐ Recenzii:</h3>
            <div class="container-rating">
                <div class="stars-rating">
                    <?php
                    $i = 1;
                    while ($i < 6) {
                        // Dacă $i este mai mic sau egal cu media, atunci se va afișa o stea plină
                        if ($i<=floor($ratingAverage)) {
                            echo '<i class="star full">&#9733;</i>';
                        } elseif (1>($i-$ratingAverage)) {
                        // Dacă diferența dintre $i și medie este mai mică de 1, se va afișa o stea jumătate plină
                            echo '<i class="star half">&#9733;</i>';
                        } else {
                        // Altfel se va afișa o stea goală
                            echo '<i class="star empty">&#9733;</i>';
                        }
                        $i++;
                    }
                    ?>
                </div>   
                <span class="all_reviews">(<?php echo $totalReviews;?>)</span>    
            </div>
            <a class="review" href="view_feedback.php">💬 Vezi recenzii</a>
                <div id="modalUpdate" class="modalUpdate">
                    <div class="modal-update">
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                        <h3>Codul actual:</h3>
                        <p class="current-code"><strong id="espDisplay">
                            <?php
                                if(isset($id_ESP)) { 
                                $visibleStart = substr($id_ESP, 0, 4);
                                $visibleEnd = substr($id_ESP, -3);
                                $maskLength = strlen($id_ESP) - 7;
                                $masked = str_repeat('•', $maskLength);
                                echo htmlspecialchars($visibleStart . $masked . $visibleEnd); 
                                }
                            ?></strong>
                        <i id="toggleEye" class="fas fa-eye-slash eye-icon" onclick="toggleEsp(this)"></i></p>
                        <h3>🔑Introdu noul cod unic al plăcii ESP</h3>
                        <form id="updateEsp" action="update_idESP.php" method="POST">
                            <input type="text" name="id_ESP" placeholder="Cod ESP nou" required/>
                            <button type="submit">Actualizează</button>
                        </form>
                        <?php
                            if (isset($_GET['msg'])) {
                                $aux = $_GET['msg'];
                                if($aux == 0) {
                                        $message = "❌Codul nu există";
                                } else if ($aux == 1) {
                                    $message = "❌Codul a fost deja asociat cu un alt utilizator";
                                } else if ($aux == 2) {
                                    $message = "✅Contul a fost actualizat cu succes";
                                }
                                echo "<div>".htmlspecialchars($message)."</div>";
                            }
                        ?>
                    </div>
                </div>   
        </div>
        <div id="modal" class="modal">
            <div class="modal-content">
                <h3>🔑Introdu codul unic al plăcii ESP</h3>
                <form action="pairing.php" method="POST">
                    <input type="text" name="id_ESP" placeholder="Cod ESP" required>
                    <button type="submit">Asociază</button>
                </form>
                <?php
                    if (isset($_GET['value'])) {
                        $aux = $_GET['value'];
                        switch($aux) {
                            case 0: 
                                $message = "❌Codul nu există";
                                break;
                            case 1: 
                                $message = "❌Codul a fost deja asociat cu un alt utilizator";
                                break;
                            case 2:
                                $message = "✅Contul a fost asociat cu succes cu placa ESP";
                                break;
                            default:
                                $message ="Mesaj necunoscut";    
                        }
                        echo "<div>".htmlspecialchars($message)."</div";
                    }
                ?>
            </div>
        </div>            
    </main>
    <footer class="footer">
        <div>
            <span> 🌎 ©️ 2025 Smart Plant Irrigation</span>
        </div>
    </footer>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlNew = new URL(window.location);
            const value = urlNew.searchParams.get("value");
            const msg = urlNew.searchParams.get("msg");
            const display = document.getElementById("espDisplay");
            const raw = <?php echo json_encode($id_ESP ?? ''); ?>;
            if (raw.length > 7) {
                const masked = raw.substring(0, 4) + '•'.repeat(raw.length - 7) + raw.slice(-3);
                display.dataset.full = raw;
                display.dataset.masked = masked;
                display.textContent = masked;
                display.dataset.state = "masked";
            }
            modal = document.getElementById("modal");
            modalUpdate = document.getElementById("modalUpdate");
            // Popupul va fi vizibil și userul va trebui să asocieze contul cu o placă
            if ( viewModal === 1) {
                modal.style.display = "flex";
                document.body.style.overflow = "hidden";
            }
            if ( value === "2") {
                modal.style.display = "flex";
                document.body.style.overflow = "hidden";
                setTimeout( () => {
                    modal.style.display = "none";
                    document.body.style.overflow = "";
                    urlNew.searchParams.delete("value");
                    window.history.replaceState({},'',urlNew)
                },3000);
            }
            if ( msg === "2") {
                 modalUpdate.style.display = "flex";
                 document.body.style.overflow = "hidden";
                setTimeout( () => {
                    modalUpdate.style.display = "none";
                    document.body.style.overflow = "";
                    urlNew.searchParams.delete("msg");
                    window.history.replaceState({},'',urlNew)
                },3000);
            } 
            if( msg ==="1" || msg ==="0") {
                modalUpdate.style.display = "flex";
                document.body.style.overflow = "hidden";
                setTimeout( () => {
                    urlNew.searchParams.delete("msg");
                    window.history.replaceState({},'',urlNew)
                },3000);
            }
        });
        function modalOpen() {
            modalUpdate.style.display = "flex";
            document.body.style.overflow = "overflow";
        }
        function closeModal() {
            modalUpdate.style.display = "none";
            document.body.style.overflow = "";
        }
     function toggleEsp(icon) {
      const display = document.getElementById("espDisplay");
      const isHidden = display.dataset.state === "masked";
      display.textContent = isHidden ? display.dataset.full : display.dataset.masked;
      display.dataset.state = isHidden ? "full" : "masked";
      icon.classList.toggle("fa-eye");
      icon.classList.toggle("fa-eye-slash");
    }
</script>
</body>
</html>
