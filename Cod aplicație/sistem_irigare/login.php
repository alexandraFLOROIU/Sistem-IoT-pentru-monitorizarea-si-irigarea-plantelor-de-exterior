<?php
// Inițializăm sesiunea pentru a putea salva datele despre utilizator și a le accesa ulterior
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
// Verificăm dacă a fost trimis prin POST formularul
if (isset($_POST['submit'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // Facem o interogare în baza de date pentru a vedea dacă există un cont asociat cu emailul respectiv
    $sql = "SELECT id, username, password, token_was_verified FROM user WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $message[] = 'Nu există niciun cont cu adresa de email respectivă!';
    }
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        if (!password_verify($password, $user_data['password'])) {
            $message[] = 'Parola a fost introdusă incorect!';
        } else if ($user_data['token_was_verified'] != 1) {
            $message[] ="Contul nu este confirmat";
        } else {
            // Reținem datele utilizatorului în variabile de sesiune
            $_SESSION['user_name'] = $user_data['username'];
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['authenticated_user']=true; 
            // Actualizăm data conectării în baza de date
            $sql_update = "UPDATE user SET date_login = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $_SESSION['user_id']);
            $stmt_update->execute();
            // Reținem într-un cookie care este valabil 30 zile adresa de email pentru completare automată la următoarea autentificare
            if (isset($_POST['remember'])) {
            setcookie('email', $email, time() + (60*60*24 * 30));
            }
            header('location:index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Autentificare">
    <title>Login Form</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="login_style.css">
</head> 
<body>  <div id="myModal" class="modal">
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
            <h1>Autentificare</h1>
            <div class="input-box">
               <input type="text" name="email" id="email" placeholder="Email" autocomplete="email" value="<?php echo $_COOKIE['email'] ?? '' ;?>" required>
               <i class='bx bx-envelope'></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Parolă" id="MyInput" required>
                <span class="eye" onclick="showPassword()">
                   <i id="open_eye" class="fas fa-eye"></i> 
                   <i id="close_eye" class="fas fa-eye-slash"></i>
                </span>
            </div>
            <div class="remember-login">
            <div class="forgot-or-remember">
                <label><input type="checkbox" name="remember">Ține-mă minte</label>
                <a href="#" id="forgotPassword">Ai uitat parola?</a>
            </div>
            <button type="submit" class="btn" name="submit">Autentificare</button>
            </div>
            <div class="register">
                 <p>Nu ai un cont?<a href="register.php">Creare cont</a></p>
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
        function showPassword() {
            var x=document.getElementById("MyInput");
            var y=document.getElementById("open_eye");
            var z=document.getElementById("close_eye");
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
        // Atașăm pe elementul cu id="forgotPassword" un eveniment când utilizatorul dă click
        document.getElementById("forgotPassword").addEventListener("click", function(event) {
            // Prevenim comportamentul implicit al evenimentului înainte să fie procesat
            event.preventDefault();
            // Verificăm faptul că a introdus o adresă de email înainte de a apăsa pe acel element
            var email = document.getElementById("email").value;
            if (!email) {
                alert("Te rog să introduci adresa de email");
                return;
            }
            // Verificăm dacă există un cont asociat cu emailul respectiv
            fetch("check_exist_account.php?email="+encodeURIComponent(email))
            .then(response =>response.json())
            .then(value => {
            if (value.exists) {
                // Creăm un formular POST ascuns 
                var form = document.createElement("form");
                form.method = "POST";
                form.action = "notifications.php"; 
                form.style.display = "none"; 
                // Adăugăm câmpurile în formular și apoi îl inserăm în pagină
                var emailField = document.createElement("input");
                emailField.type = "hidden";
                emailField.name = "email";
                emailField.value = email;
                form.appendChild(emailField);
                var statusField = document.createElement("input");
                statusField.type = "hidden";
                statusField.name = "status";
                statusField.value = "reset_link_sent";
                form.appendChild(statusField);
                document.body.appendChild(form);
                form.submit();
            } else {
                alert("Nu există cont asociat cu această adresă de email");
            }
        })
        .catch(error =>console.error("Error:", error));
    });
</script>
</body>
</html>