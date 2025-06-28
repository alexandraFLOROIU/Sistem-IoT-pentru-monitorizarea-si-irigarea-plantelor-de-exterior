<?php
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
if (!$user_id) {
   echo"Eroare";
}
// Preluăm datele din formular
$type_watering = $_POST['type_watering'] ?? null;
$time_morning = $_POST['time_morning'] ?? null;
$time_evening = $_POST['time_evening'] ?? null;
$time_start = $_POST['time_start'] ?? null;
$time_stop = $_POST['time_stop'] ?? null;
$time_days = $_POST['time_days'] ?? null;
$time_duration = $_POST['time_duration'] ?? null;
$time_hour = $_POST['time_hour'] ?? null;
$plant = $_POST['plant'] ?? null;
// Inserăm datele generale în tabela watering_control
$sql_watering="INSERT INTO watering_control(watering_type, status_pump, duration, created_at, plant_name, user_id) VALUES ('$type_watering', '0', '$time_duration', NOW(), '$plant', '$user_id')";
if ($conn->query($sql_watering) === TRUE ) {
   $watering_id=$conn->insert_id;
}
// Inserăm datele specifice udării automate în tabela automatic_settings
if ($type_watering === 'automatic') {
    $sql_data="INSERT INTO automatic_settings(watering_id, morning_time,evening_time) VALUES ('$watering_id', '$time_morning', '$time_evening')";
    if ($conn->query($sql_data) !== TRUE ) {
      echo'Eroare la inserarea datelor';
   }
} else if ($type_watering === 'periodic') {
   // Inserăm datele specifice udării automate în tabela periodic_settings
    $sql_data="INSERT INTO periodic_settings(watering_id, start_time,stop_time,time_hour,days_time) VALUES ($watering_id, '$time_start', '$time_stop','$time_hour', '$time_days')";
    if ($conn->query($sql_data) !== TRUE ) {
       echo'Eroare la inserarea datelor';
   }
}
$conn->close();
?>