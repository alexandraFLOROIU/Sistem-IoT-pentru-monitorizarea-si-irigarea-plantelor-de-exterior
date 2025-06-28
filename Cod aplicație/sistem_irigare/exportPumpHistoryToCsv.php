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
// Preluăm planta și tipul de udare din URL
$plant= $_GET['plant'] ?? '';
$type= $_GET['type'] ?? '';
// Adăugăm headerele pentru a indica browserului că va fi descărcat un fișier în format csv
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=istoric_pompă.csv');
// Deschidem output pentru a trimite direct datele către browser
$output = fopen('php://output', 'w');
// Definim antetul fișierului .csv
fputcsv($output, array("data_si_ora","tipul_de_irigare","durata","numele_plantei"));
$sql = "SELECT ph.id AS ph_id, ph.start_time, wc.watering_type, wc.duration, wc.plant_name, wc.user_id
        FROM pump_history ph
        JOIN watering_control wc ON ph.watering_id = wc.id";
// Tratăm toate cazurile
if (!empty($type) && !empty($plant)) {
  $sql.=" WHERE wc.watering_type=? AND wc.plant_name=? ";
} else if (!empty($type) && empty($plant)) {
  $sql.=" WHERE wc.watering_type=? ";
} else if (empty($type) && !empty($plant)) {
  $sql.=" WHERE wc.plant_name=? ";
}
$sql .= " AND wc.user_id=? ORDER BY ph.id DESC";
$stmt = $conn->prepare($sql);
if (!empty($type) && !empty($plant)) {
  $stmt->bind_param("ssi", $type, $plant, $user_id);
} else if (!empty($type) && empty($plant)) {
  $stmt->bind_param("si", $type, $user_id);
} else if (empty($type) && !empty($plant)) {
  $stmt->bind_param("si", $plant, $user_id);
} else {
  $stmt->bind_param("i",$user_id);
}
$stmt->execute();
$result_csv=$stmt->get_result();    
// Formăm conținutul fișierului .csv
if ($result_csv && $result_csv->num_rows >0) {
  while ($row = $result_csv->fetch_assoc()) {
    $rowCSV = array (
      $row['start_time'],
      $row['watering_type'],
      $row['duration'],
      $row['plant_name'],
    );
    fputcsv($output, $rowCSV);
  }
}
fclose($output);
$stmt->close();
$conn->close();
exit;
?>