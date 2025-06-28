<?php
// Importăm clasele din biblioteca PHPMailer necesare pentru a putea trimite emailuri
require_once 'libs/phpmailer/Exception.php';
require_once 'libs/phpmailer/PHPMailer.php';
require_once 'libs/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
    die("Conexiunea la baza de date a eșuat " . $conn->connect_error);
}
// Preluăm valoarea parametrilor 
$email = $_POST['email'] ?? '';
$status = $_POST['status'] ?? '';
// Formăm mesajul aferent statusului primit
if ($status === 'reset_link_sent') { 
    $message = "Am trimis un email cu linkul de resetare\n a parolei la adresa specificată.\n Dacă nu apare în câteva minute, verifică folderul de spam.✅";
} else if ($status === 'confirm_account_link_sent') {
    $message = "Am trimis un email cu linkul de confirmare a\n contului la adresa specificată.\n Dacă nu apare în câteva minute, verifică folderul de spam.✅";
} else if ($status === 'account_confirmation_successful') {
    $message = "Contul a fost confirmat cu succes.\n Te poți autentifica acum.✅";
} 
// Dacă statusul primit este 'reset_link_sent' vom trimite un email de resetare a parolei
if (!empty($email) && $status=== 'reset_link_sent') {
    // Generăm un token unic pentru confirmare (fiecare octet este convertit în 2 caractere hexazecimale => 32 caractere)
    $token = bin2hex(random_bytes(16));
    // Facem update cu noul token pentru resetarea parolei
    $sqlUpdate = "UPDATE user SET verification_token=? WHERE email=?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ss",  $token, $email);
    // Dacă interogarea s-a realizat cu succes, se va trimite email de confirmare folosind PHPMailer
    if ($stmtUpdate->execute()) {
        // Inițializăm un obiect PHPMailer
        $mail = new PHPMailer(true);
        try{
            // Setăm trimiterea mailurilor prin SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            // Setăm adresa folosită pentru autentificarea pe serverul SMTP
            $mail->Username ='plantirrigationsmart@gmail.com'; 
            $mail->Password = 'oihr lxsf etqp rpjh';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            // Setăm adresa expeditorului și a destinatarului
            $mail->setFrom('plantirrigationsmart@gmail.com', 'Smart Plant Irrigation');
            $mail->addAddress($email, $email);
            // Specificăm conținutul emailului
            $mail->isHTML(true);
            $mail->CharSet ='UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = 'Resetare parolă';
            // Trimitem linkul pentru resetarea parolei  
            $resetLink ="http://localhost/sistem_irigare/reset_password.php?token=" . $token;
            $mail->Body ="<h1>Salutare,</h1><p>Am înregistrat cererea ta de resetare a parolei.<br>Ca să alegi o nouă parolă apasă pe linkul de mai jos:</p>
                   <p><a href='$resetLink'>Apasă aici</a></p>";
            $mail->AltBody = "Salutare,\nAm înregistrat cererea ta de resetare a parolei.\nCa să alegi o nouă parolă apasă pe linkul de mai jos: $resetLink";
            $mail->send();
        } catch (Exception $e) {      
            echo"A apărut o eroare la trimiterea e-mailului de resetare a parolei. Eroare: {$mail->ErrorInfo}";
        }
     } else {
        echo "Eroare la resetare parola: " .$stmtInsert->error;
        $stmtUpdate->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Notificare</title>
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="stylesheet" href="register_style.css">
    </head>
    <body> 
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
        </nav>
        <div class="container">
            <img src="./img/user.png" alt="User" style="width:200px; margin-left:100px">
            <h2 style="font-size:20px; color:grey; text-align:center; margin-bottom:30px"><?php echo $email?></h2>
            <h3 style="text-align:center ;margin-bottom:30px ;color:green" ><?=$message?></h3>
            <button type="button" onclick="window.location.href='login.php'" class="btn">Du-mă la pagina de autentificare</button>
        </div>
        <footer class="footer">
            <div>
                <span> 🌎 ©️ 2025 Smart Plant Irrigation</span>
            </div>
        </footer>
    </body>
</html>