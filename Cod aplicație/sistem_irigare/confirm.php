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
// Verificăm dacă tokenul a fost trimis în URL
if (!isset($_GET['token'])) {
    die("Tokenul de confirmare lipsește.");
}
$token = $_GET['token'];
// Căutăm în baza de date utilizatorul cu tokenul respectiv
$sql = "SELECT id, email FROM user WHERE verification_token = ? " ;
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$token);
$stmt->execute();
$result = $stmt->get_result();
// Verificăm dacă interogarea s-a realizat cu succes și există o înregistrare
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_id = $row['id'];
    $email= $row['email'];
    // Stergem tokenul și facem update la campul token_was_verified
    $sqlUpdate = " UPDATE user SET token_was_verified=1, verification_token=null WHERE id=?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("i", $user_id);
    if (!$stmtUpdate->execute()) {
        echo "Eroare la actualizarea utilizatorului: " .$stmtUpdate->error;
    }
    $stmtUpdate->close();
    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
      <meta charset="UTF-8">
      <title>Confirmare</title>
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
            statusField.value = "account_confirmation_successful";
            form.appendChild(statusField);
            document.body.appendChild(form);
            form.submit(); };
        </script>
    </body>
</html>
<?php
} else {
    echo"Token-ul este invalid sau e-mailul a fost deja confirmat";
}
$stmt->close();
$conn->close();
?>
