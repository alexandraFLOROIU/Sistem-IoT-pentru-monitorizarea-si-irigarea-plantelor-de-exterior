<?php
// Indică faptul că datele returnate sunt în format json
header('Content-Type: application/json');
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
// Preluăm parametrii pentru filtrare și paginare din URL 
$wateringType=$_GET['watering_type'] ?? null;
$page = isset($_GET['page'])? max(1, intval($_GET['page'])) : 1;
$filter_plant = $_GET['filter_plant'] ?? null;
// Stabilim numărul de rânduri vizibile pe o pagină
$limit = 20;
$offset = ($page -1) * $limit;
// Calculăm numărul total de înregistrări din tabela pump_history 
$sqlCount = "SELECT COUNT(*) AS pages FROM pump_history p JOIN watering_control w ON p.watering_id=w.id";
// Tratăm toate cazurile posibile
if (!empty($wateringType) && !empty($filter_plant)) {
  $sqlCount.=" WHERE w.watering_type=? AND w.plant_name=? AND w.user_id=? ";
} else if (!empty($wateringType) && empty($filter_plant)) {
  $sqlCount.=" WHERE w.watering_type=? AND w.user_id=?";
} else if (empty($wateringType) && !empty($filter_plant)) {
  $sqlCount.=" WHERE w.plant_name=? AND w.user_id=?";
} else {
  $sqlCount.=" WHERE w.user_id=?";
}
$stmtCount = $conn->prepare($sqlCount);
if (!empty($wateringType) && !empty($filter_plant)) {
  $stmtCount->bind_param("ssi",$wateringType, $filter_plant, $user_id);
} else if (!empty($wateringType) && empty($filter_plant)) {
  $stmtCount->bind_param("si",$wateringType, $user_id);
} else if (empty($wateringType) && !empty($filter_plant)) {
  $stmtCount->bind_param("si", $filter_plant, $user_id);
} else {
  $stmtCount->bind_param("i", $user_id);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRows = $resultCount->fetch_assoc()['pages'];
// Calculăm numărul total de pagini
$totalPages = ceil($totalRows/$limit);
$stmtCount->close();
// Obținem înregistrările, începând cu cea mai recentă, ținând cont de offset și limita de rânduri pe pagină
$sql = "SELECT p.id, w.watering_type, p.start_time, w.duration, w.plant_name FROM pump_history p JOIN watering_control w ON p.watering_id=w.id";
// Tratăm toate cazurile posibile
if (!empty($wateringType) && !empty($filter_plant)) {
  $sql.=" WHERE w.watering_type=? AND w.plant_name=? ";
} else if (!empty($wateringType) && empty($filter_plant)) {
  $sql.=" WHERE w.watering_type=? ";
} else if (empty($wateringType) && !empty($filter_plant)) {
  $sql.=" WHERE w.plant_name=? ";
}
$sql .= " AND w.user_id=? ORDER BY p.start_time DESC, p.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($wateringType) && !empty($filter_plant)) {
  $stmt->bind_param("ssiii", $wateringType, $filter_plant, $user_id, $limit, $offset);
} else if (!empty($wateringType) && empty($filter_plant)) {
  $stmt->bind_param("siii", $wateringType, $user_id, $limit, $offset);
} else if (empty($wateringType) && !empty($filter_plant)) {
  $stmt->bind_param("siii", $filter_plant, $user_id, $limit, $offset);
} else {
  $stmt->bind_param("iii",$user_id, $limit, $offset);
}
$stmt->execute();
$result=$stmt->get_result();       
// Formăm răspunsul
$data=[];
while ($row=$result->fetch_assoc()) {
  $data[]=$row;
}  
$stmt->close();
$conn->close();
echo json_encode(["page_total" =>$totalPages,"data" => $data],JSON_PRETTY_PRINT);
?>
