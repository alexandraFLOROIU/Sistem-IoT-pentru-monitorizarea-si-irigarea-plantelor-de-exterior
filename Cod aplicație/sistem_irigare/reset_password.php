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
// Preluăm valoarea parametrului 'token' din URL dacă există
if (!isset($_GET['token'])) {
    die("Lipseste tokenul de confirmare");
}
$token = $_GET['token'];
// Verificăm dacă a fost trimis prin POST formularul
if (isset($_POST['submit'])) {
    $password = $_POST['password'] ?? '';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Verificăm dacă câmpul de nouă parolă cu cel de confirmare nouă parolei sunt identice
    if ($password != $confirm_password) {
        $message[]= 'Parolele nu coincid. Te rugăm să reîncerci.';
    } else {
        // Verificăm dacă există vreun user în baza de date cu tokenul respectiv, iar dacă există îl actualizăm
        $null_param=null;
        $sqlUpdate = "UPDATE user SET password=?, verification_token=? WHERE verification_token =?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sss", $hashedPassword,$null_param,$token);
        $stmtUpdate->execute();
        if ($stmtUpdate->affected_rows == 0) {
            $message[]='Token-ul invalid sau deja utilizat';
            $stmtUpdate->close();
        } else {
            header("Location:login.php");
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
             <h1>Resetare parolă</h1>
             <label for="MyInput1" style="margin-top:30px; display:block">Introdu o nouă parolă:</label>
            <div class="input-box">
                <input  type="password" name="password" placeholder="Parolă" oncopy="return false;" onpaste="return false" oncut="return false" id="MyInput1" required>
                <span class="eye1" onclick="showPassword()">
                   <i id="open_eye1" class="fas fa-eye"></i> 
                   <i id="close_eye1" class="fas fa-eye-slash"></i>
                </span>
            </div>
            <label for="MyInput2">Confirmă noua parolă:</label>
            <div class="input-box">
                <input type="password" name="confirm_password" placeholder="Confirmare parolă" oncopy="return false;" onpaste="return false" oncut="return false" id="MyInput2" required>
                <span class="eye2" onclick="showPassword2()">
                   <i id="open_eye2" class="fas fa-eye"></i> 
                   <i id="close_eye2" class="fas fa-eye-slash"></i>
                </span>
            </div>
            <button type="submit" class="btn" name="submit">Continuă</button>
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
            if(x.type === 'password') { 
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
            if(x.type === 'password') { 
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