<?php
// Indică faptul că datele returnate sunt în format json
header("Content-Type: application/json");
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
    die(json_encode(["eroare" =>"Conexiunea la baza de date a eșuat: " . $conn->connect_error]));
}
// Preluăm datele din formular
$plant_name = $_POST['plant_name'] ?? '';
$watering_type = $_POST['watering_type'] ?? '';
$status = $_POST['status'] ??'';
$duration = $_POST['duration'] ??'';
$morning_time = $_POST['morning_time'] ?? '';
$evening_time = $_POST['evening_time'] ?? '';
// Facem o interogare în baza de date pentru a obține planta din ultima înregistrare din tabela watering_control
$queryPlant = "SELECT plant_name FROM watering_control WHERE user_id= $user_id ORDER BY id DESC LIMIT 1";
$result=$conn->query($queryPlant);
$lastPlant=$result->fetch_assoc()['plant_name'] ??'';
// Verificăm dacă avem vreun mod de udare inserat pentru planta selectată
$query = "SELECT COUNT(*) AS cnt FROM watering_control WHERE plant_name = ? AND user_id = $user_id";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $plant_name);
$stmt->execute();
$result = $stmt->get_result();
$row=$result->fetch_assoc();
$count=$row['cnt'];
$stmt->close();
// Inserăm modul implicit de irigare dacă planta nu a fost selectată niciodată sau a fost anterior, dar s-a revenit acum la ea
if ( $count == 0 || ($lastPlant !== $plant_name)) {
    $sqlinsert = "INSERT INTO watering_control(watering_type, status_pump, duration, created_at, plant_name, user_id) VALUES(?,?,?,NOW(),?,?)";
    $stmt = $conn->prepare($sqlinsert);
    if ($stmt) {
        $stmt->bind_param("siisi", $watering_type, $status, $duration, $plant_name, $user_id);
        $stmt->execute();
        $insert_id=$conn->insert_id;
        $stmt->close();
    }
    $sql_data="INSERT INTO automatic_settings(watering_id, morning_time,evening_time) VALUES ('$insert_id', '$morning_time', '$evening_time')";
    if ($conn->query($sql_data) !== TRUE ) {
        echo'Eroare la inserarea datelor';
    }
    echo json_encode(["success"=>"Modul implicit a fost inserat"]);
} else {
    echo json_encode(["mesaj" => "Există deja un mod de irigare setat"]);
}
$conn->close();
?>