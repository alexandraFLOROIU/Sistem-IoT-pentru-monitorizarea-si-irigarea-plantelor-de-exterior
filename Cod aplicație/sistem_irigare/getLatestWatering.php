<?php
// Indică faptul că datele returnate sunt în format json
header("Content-Type:application/json");
// Inițializăm sesiunea pentru a putea accesa datele despre utilizator
require_once 'session.php';
$user_id=$_SESSION['user_id'];
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password = "";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
    die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
$stmtPlant = $conn->prepare ("SELECT active_plant FROM user WHERE id=?");
$stmtPlant->bind_param("i", $user_id);
$stmtPlant->execute();
$result = $stmtPlant->get_result();
if ( $row = $result->fetch_assoc()) {
    $plantName = $row['active_plant'];
}
if (!$plantName) {
    echo json_encode([]);
    exit;
}
// Preluăm cel mai recent program de udare pentru planta actuală și starea pompei
$sql = "SELECT * FROM watering_control WHERE plant_name='$plantName' AND user_id='$user_id'ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
// Verificăm dacă există înregistrări 
if ($result->num_rows > 0) {
    $data=$result->fetch_assoc();
    $response= [
            "watering_type"=>$data["watering_type"],
            "status_pump"=>$data["status_pump"]
        ];
        echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    // Dacă nu există înregistrări trimitem un JSON gol
    echo json_encode([]);
    exit;
}
$conn->close();
?>    