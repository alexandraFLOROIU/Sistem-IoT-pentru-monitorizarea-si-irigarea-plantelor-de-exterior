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
// Preluăm planta, intervalul și tipul senzorului din URL
$interval= isset($_GET['day']) ? intval($_GET['day']) : 1;
$plant= $_GET['plant'] ?? '';
$sensorType= $_GET['sensorType'] ?? '';
// Adăugăm headerele pentru a indica browserului că va fi descărcat un fișier în format csv
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=dateSenzor.csv');
// Deschidem output pentru a trimite direct datele către browser
$output = fopen('php://output', 'w');
// Definim antetul fișierului .csv
fputcsv($output, array("tip_senzor","data","valoare","nume_planta"));
$sql_csv = "SELECT s.type_sensor, ds.id_sensor, ds.date, ds.value, ds.plant_name
            FROM date_senzori ds
            JOIN sensors s ON ds.id_sensor = s.id_sensor
            WHERE ds.plant_name='$plant' AND ds.user_id='$user_id' AND s.type_sensor='$sensorType' AND date>=NOW()-INTERVAL $interval DAY
            ORDER BY s.type_sensor, ds.id_sensor, ds.date ASC";
$result_csv = $conn->query($sql_csv);
// Formăm conținutul fișierului .csv 
if ($result_csv && $result_csv->num_rows > 0) {
    while ($row = $result_csv->fetch_assoc()) {
        $rowCSV = array (
            $row['type_sensor']=='humiditySoil' ? "Umiditate_sol": ($row['type_sensor']=='lightintensity' ? "Intensitatea_luminii": ($row['type_sensor']=='airhumidity' ? "Umiditatea_aerului":($row['type_sensor']=='temperature' ? "Temperatura":($row['type_sensor']=='WaterLevel' ? "Nivel_apa":"Status_ploaie"))) ),
            $row['date'],
            $row['value'],
            $row['plant_name'],
        );
        fputcsv($output, $rowCSV);
    }
}
fclose($output);
$conn->close();
exit;
?>