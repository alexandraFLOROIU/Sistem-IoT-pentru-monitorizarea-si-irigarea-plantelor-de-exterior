<?php
// Inițializăm sesiunea pentru a putea accesa datele despre utilizator
require_once 'session.php';
$user_id=$_SESSION['user_id'];
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
    die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
// Preluăm datele JSON de la JavaScript și le convertim într-un array asociativ PHP
$data = json_decode(file_get_contents("php://input"),true);
// Reținem valoarea câmpului plantă
$plant = $data["plant"];
$stmt = $conn->prepare("UPDATE user SET active_plant =? WHERE id=?");
$stmt->bind_param("si",$plant,$user_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    echo "Planta a fost actualizată";
} else {
    echo "Nu s-au făcut modificări";
}
?>