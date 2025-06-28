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
  die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
// Verificăm dacă a fost trimis prin POST formularul
if (isset($_POST['submit'])) {
  // Preluăm valorile din formular dacă există, altfel valoarea implicită va fi un string gol
  $username = $_POST['username'] ?? '';
  $email = $_POST['email']  ?? '';
  $password = $_POST['password'] ?? '';
  // Criptăm parola
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
  $confirm_password = $_POST['confirm_password'] ?? '';
  // Verificăm dacă există deja un cont asociat adresei resepective de email
  $sqlCheck = "SELECT * FROM user WHERE email = ?";
  $stmtCheck = $conn->prepare($sqlCheck);
  $stmtCheck->bind_param("s", $email);
  $stmtCheck->execute();
  $resultCheck = $stmtCheck->get_result();
  if ($resultCheck->num_rows > 0) {
       $message[] = "Această adresă de email este deja utilizată.";
  } else {
    // Verificăm dacă sunt identice câmpul de parolă cu cel de confirmare a parolei
    if ($password != $confirm_password) {
      $message[]='Confirmarea parolei nu se potrivește.';
    } else {
      // Generăm un token unic pentru confirmare( fiecare octect este convertit în 2 caractere hexazecimale => 32 caractere)
      $token = bin2hex(random_bytes(16));
      // Înregistrăm noul utilizator în baza de date
      $sql_insert = "INSERT INTO `user` (username, email, password, date_login, verification_token, token_was_verified) VALUES(?, ?, ?, NOW(), ?, 0)";
      $stmtInsert = $conn->prepare($sql_insert);
      $stmtInsert->bind_param("ssss", $username, $email, $hashedPassword, $token);
      // Dacă inserarea a avut loc cu succes, se va trimite email de confirmare folosind PHPMailer
      if ($stmtInsert->execute()) {
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
          $mail->Subject = 'Confirmarea adresei de email';
          // Trimitem linkul pentru confirmarea adresei de email   
          $confirmationLink ="http://localhost/sistem_irigare/confirm.php?token=" . $token;
          $mail->Body ="<h1>Salutare,</h1><p>Confirmă adresa de e-mail făcând clic pe linkul de mai jos:</p>
            <p><a href='$confirmationLink'>Apasă aici</a></p>";
          $mail->AltBody = "Salutare, \n\nConfirmă adresa de e-mail făcând clic pe linkul de mai jos: $confirmationLink\n\nThank you!";
          $mail->send();
        ?>
        <!DOCTYPE html>
        <html lang="ro">
        <head>
        <meta charset="UTF-8">
        </head>
          <body>
            <script>
              // La încărcarea paginii vom crea un formular POST ascuns 
              window.onload = function() {
                var form = document.createElement("form");
                form.method = "POST";
                form.action = "notifications.php"; 
                form.style.display = "none"; 
                var email = "<?php echo $email; ?>";
                // Adăugăm câmpurile în formular și apoi îl inserăm în pagină
                var emailField = document.createElement("input");
                emailField.type = "hidden";
                emailField.name = "email";
                emailField.value = email;
                form.appendChild(emailField);
                var statusField = document.createElement("input");
                statusField.type = "hidden";
                statusField.name = "status";
                statusField.value = "confirm_account_link_sent";
                form.appendChild(statusField);
                document.body.appendChild(form);
                form.submit(); };
            </script>
          </body>
        </html>
        <?php
        }catch(Exception $e) {      
          echo "A apărut o eroare la trimiterea emailului de confirmare. Eroare: {$mail->ErrorInfo}";
        }
        } else {
          echo "Eroare la înregistrare: " .$stmtInsert->error;
        }
        $stmtInsert->close();
       }  
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Creare cont">
  <title>Register Form</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="stylesheet" href="register_style.css">
</head>
<body>  
  <div id="myModal" class="modal">
    <?php
    if (isset($message)) {
        foreach ($message as $message) {
            echo '
            <div class="modal-content">
              <span>'.$message.'</span>
              <i class="bx bx-x" onclick="this.parentElement.remove();"></i>
            </div>
            ';
          }
      } ?>
    </div>
    <nav>
      <p>💧Smart Plant Irrigation🪴</p>
    </nav>
  <div class="container">
    <form action=""  method="post">
      <h1>Înregistrare</h1>
        <div class="input-box">
          <input type="text" name="username" placeholder="Nume utilizator" autocomplete ="username" required>
          <i class='bx bxs-user' ></i>
        </div>
        <div class="input-box">
          <input type="text" name="email" placeholder="Email" autocomplete ="username" required>
          <i class='bx bx-envelope'></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Parolă" autocomplete="new-password"   id="MyInput1" required>
          <span class="eye1" onclick="showPassword()">
            <i id="open_eye1" class="fas fa-eye"></i> 
            <i id="close_eye1" class="fas fa-eye-slash"></i>
          </span>
        </div>
        <div class="input-box">
          <input type="password" name="confirm_password" placeholder="Confirmare parolă" autocomplete="new-password"  id="MyInput2" required>
            <span class="eye2" onclick="showPassword2()">
              <i id="open_eye2" class="fas fa-eye"></i> 
              <i id="close_eye2" class="fas fa-eye-slash"></i>
            </span>
        </div>
          <button type="submit" class="btn" name="submit">Înregistrare</button>
          <div class="register">
            <p>Ai deja un cont?<a href="login.php">Autentificare</a></p>
            </div>
         </form>
    </div>
    <footer class="footer">
            <div>
                <span> 🌎 ©️ 2025 Smart Plant Irrigation</span>
            </div>
    </footer>
    <script>
      // Definim o funcție care îi permite utilizatorului să schimbe vizibilitatea parolei
      function showPassword(){
        var x=document.getElementById("MyInput1");
        var y=document.getElementById("open_eye1");
        var z=document.getElementById("close_eye1");
        if (x.type === 'password') { 
          x.type="text";
          y.style.display = "block";
          z.style.display = "none";
        } else {
          x.type="password";
          y.style.display="none";
          z.style.display="block";
        }
      }
      // Definim o funcție care îi permite utilizatorului să schimbe vizibilitatea confirmării parolei
      function showPassword2(){
        var x=document.getElementById("MyInput2");
        var y=document.getElementById("open_eye2");
        var z=document.getElementById("close_eye2");
        if (x.type === 'password') { 
          x.type="text";
          y.style.display="block";
          z.style.display="none";
        } else {
          x.type="password";
          y.style.display="none";
          z.style.display="block";
        }
      }
    </script>
  </body>
</html>