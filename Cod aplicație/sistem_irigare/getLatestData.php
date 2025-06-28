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
    echo json_encode(["Conexiunea la baza de date a eșuat: " . $conn->connect_error]);
}
// Verificăm dacă userul a selectat o plantă
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
// Preluăm cea mai recentă înregistrare pentru fiecare senzor
$sql= "SELECT ds.id_sensor, s.type_sensor, TRUNCATE(ds.value,2) AS value, ds.date
    FROM date_senzori ds
    JOIN (
       SELECT id_sensor, MAX(date) AS last_date
       FROM date_senzori
       WHERE user_id='$user_id' AND plant_name='$plantName'
       GROUP BY id_sensor
    ) AS latest ON ds.id_sensor = latest.id_sensor AND ds.date = latest.last_date
    JOIN sensors s ON ds.id_sensor = s.id_sensor
    WHERE ds.user_id = '$user_id' and ds.plant_name = '$plantName'
    ORDER BY ds.id_sensor";
$result=$conn->query($sql);
$data=[];
// Verificăm dacă există înregistări 
if ($result->num_rows > 0) {
    while ($row=$result->fetch_assoc()) {
    // Adăugăm în array-ul data array-ul asociativ corespunzător fiecărei înregistrări 
        $data[]=[
            "type" => $row['type_sensor'],
            "value" => $row['value']
            ];    
    }
} else {
    // Dacă nu există înregistrări trimitem un JSON gol
    echo json_encode([]);
    exit;
}
// Convertim array-ul în format JSON
echo json_encode($data,JSON_PRETTY_PRINT);
$conn->close();
?>