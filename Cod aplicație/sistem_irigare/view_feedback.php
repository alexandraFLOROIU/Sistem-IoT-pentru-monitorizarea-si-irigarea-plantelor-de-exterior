<?php
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
  die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
// Verificăm dacă în URL există o pagină setată, iar dacă nu există valoarea va fi 1 implicit
$page = $_GET['page'] ?? 1;
// Stabilim numărul de comentarii vizibile pe o pagină
$reviewsPerPage = 5;
$offset =($page-1)*$reviewsPerPage;
// Obținem recenziile, începând cu cea mai recentă, ținând cont de offset și limita de recenzii pe pagină
$sql="SELECT rating, message, created_at, username FROM feedback ORDER BY created_at DESC LIMIT $reviewsPerPage OFFSET $offset" ;
$result=$conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Recenzii de la utilizatori">
        <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
        <link rel="stylesheet" href="view_feedback.css" />
        <title>Feedback</title>
    </head>
    <body>
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
            <div class="btn-group">
                <a href="index.php" class="nav-btn">🏠Acasă</a>
            </div>   
        </nav>
        <div class="container">
        <div class="container-feedback">
            <h1>Recenzii de la utilizatori</h1>
            <?php
                if ($result->num_rows < 0) {
                    echo"Nicio recenzie";
                } else {           
                    while ($row=$result->fetch_assoc()) {
                        // Afișăm recenziile
                        $rating=$row['rating'];
                        $username=$row['username'];
                        $message=$row['message'];
                        $created_at=$row['created_at'];
                        echo "<div class='username'><strong>Utilizator: </strong>".(empty($username)==true ? "Anonim": htmlspecialchars($username))."</div>";
                        // Calculăm numărul de stele pline și goale
                        echo "<div class='ratings'>".str_repeat("★",$rating).str_repeat("☆",5-$rating)."</div>"; 
                        echo "<div class='message'><strong>Opinie: </strong>".htmlspecialchars($message)."</div>";
                        echo "<div class='create_at'>Trimis la data: ".htmlspecialchars($created_at)."<hr></div>";
                    }
                }
                // Calculăm numărul total de recenzii din baza de date
                $sqlTotal="SELECT COUNT(*) AS total FROM feedback";
                $total=$conn->query($sqlTotal)->fetch_assoc()['total'];
                // Calculăm numărul total de pagini
                $pageTotal = ceil($total/$reviewsPerPage);
                echo "<div style='margin-top:25px;'>";
                // Afișăm butoanele de paginare
                for ($i=1 ;$i<= $pageTotal; $i++) {
                    if ($i == $page) {
                        echo "<strong style='margin:0 5px;'>[$i]</strong>";
                    } else {
                        echo "<a href='?page=$i' style='margin:0 5px;'>[$i]</a>";
                    }
                }
                echo"<div>";
            ?>
            </div>
        </div>
        <footer class="footer">
             <div>
                <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
            <div>
        </footer>
    </body>
</html>