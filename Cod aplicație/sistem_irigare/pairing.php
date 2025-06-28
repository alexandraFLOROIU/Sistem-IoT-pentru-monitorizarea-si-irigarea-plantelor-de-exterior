<?php
// Inițializăm sesiunea pentru a putea accesa datele despre utilizator
require_once 'session.php';
$user_id = $_SESSION['user_id'];
$id_ESP = trim($_POST['id_ESP']);
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
  die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
// Verificăm dacă codul există
$stmt = $conn->prepare("SELECT user_id FROM device WHERE id_ESP=?");
$stmt->bind_param("s", $id_ESP);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: index.php?value=0");
    exit;
}
// În cazul în care codul există în baza de date verificăm dacă are un user asociat
$row = $result->fetch_assoc();
if ($row['user_id']!= $user_id && !is_null($row['user_id'])) {
    header("Location: index.php?value=1");
    exit;
}
$sqlUpdateId = "Select id_ESP FROM device WHERE user_id=?";
$stmtUpdateId = $conn->prepare($sqlUpdateId);
$stmtUpdateId->bind_param("i", $user_id);
$stmtUpdateId->execute();
$stmtUpdateId->store_result();
if ( $stmtUpdateId->num_rows === 0) {
  // Asociem ESP-ul cu userul curent
  $stmtUpdate = $conn->prepare("UPDATE device SET user_id = ? WHERE id_ESP=?");
  $stmtUpdate->bind_param("is", $user_id,$id_ESP);
  $stmtUpdate->execute();
  header("Location: index.php?value=2");
} 
