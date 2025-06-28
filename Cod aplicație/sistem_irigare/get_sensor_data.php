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
    die("Conexiunea la baza de date a eșuat " . $conn->connect_error);
}
// Preluăm valoarea intervalul și planta din URL dacă există
$interval= isset($_GET['interval']) ? intval($_GET['interval']) : 1;
$plant= $_GET['plant'] ?? '';
$sql= "SELECT s.type_sensor, ds.id_sensor, ds.date, ds.value, ds.plant_name
    FROM date_senzori ds
    JOIN sensors s ON ds.id_sensor = s.id_sensor
    WHERE ds.plant_name='$plant' AND ds.user_id='$user_id' AND date>=NOW()-INTERVAL $interval DAY
    ORDER BY s.type_sensor, ds.id_sensor, ds.date ASC";
$result = $conn->query($sql);
$data=[];
while ($row = $result->fetch_assoc()) {
    $type_sensor=$row['type_sensor'];
    $id_sensor=$row['id_sensor'];
    // Dacă nu există cheia în array o inițializăm cu un array gol
    if (!isset($data[$type_sensor])) {
        $data[$type_sensor] = [];
    }
    // Dacă nu există combinația $type_sensor-$id_sensor o inițializăm cu un array asociativ având 2 chei: labels și data
    if (!isset($data[$type_sensor][$id_sensor])) {
        $data[$type_sensor][$id_sensor]=['labels'=> [], 'data' => []];
    }
    // Adăugăm valorile
    $data[$type_sensor][$id_sensor]['labels'][]=$row['date'];
    $data[$type_sensor][$id_sensor]['data'][]=$row['value'];
}
$conn->close();
// Convertim array-ul în format JSON
echo json_encode($data, JSON_PRETTY_PRINT);
?>