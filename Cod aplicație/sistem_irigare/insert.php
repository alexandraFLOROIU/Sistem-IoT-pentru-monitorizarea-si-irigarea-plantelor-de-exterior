<?php
// Setăm fusul orar pentru România cu scopul de a evita decalajele de oră
date_default_timezone_set('Europe/Bucharest');
// Indică faptul că datele returnate sunt în format json
header("Content-Type: application/json");
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
    die(json_encode(["eroare" => "Conexiunea la baza de date a eșuat"]));
}
$user_id =  0;
// Preluăm datele JSON de la ESP32 și le convertim într-un array asociativ PHP
$jsonData = file_get_contents("php://input");
file_put_contents("date_senzori.txt", $jsonData . PHP_EOL, FILE_APPEND);
$dataPlant = json_decode($jsonData, true);
// Preluăm codul unic al plăcii ESP
$idESP = $dataPlant['idESP'] ?? null;
if($idESP !== null ){ 
$sqlCheck = "SELECT user_id FROM device WHERE id_ESP=?";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param("s", $idESP);
$stmt->execute();
$result=$stmt->get_result();
if ( $result->num_rows === 0) {
    // Dacă nu există în baza de date, îl inserăm
    $stmtInsert = $conn->prepare("INSERT INTO device(id_ESP) VALUES(?)");
    $stmtInsert->bind_param("s",$idESP);
    $stmtInsert->execute();
} else if($result->num_rows > 0) {
   $row = $result->fetch_assoc();
   $user_id = $row["user_id"];
}
}
// Verificăm dacă există user asociat cu placa
if (!$user_id) {
    die(json_encode(["eroare" => "ESP nu este asociat cu niciun user"]));
} else {
    // Verificăm dacă userul a selectat o plantă
    $sqlPlant = " SELECT active_plant FROM user WHERE id=?";
    $stmtPlant = $conn->prepare ($sqlPlant);
    $stmtPlant->bind_param("i", $user_id);
    $stmtPlant->execute();
    $result = $stmtPlant->get_result();
    if ( $row = $result->fetch_assoc()) {
        $plantName = $row['active_plant'];
    }
}
if (!$plantName) {
    die(json_encode(["eroare" => "Utilizatorul nu a selectat încă o plantă"]));
}
// Ștergem înregistrările pentru care data de inserare este înainte cu 10 zile față de data actuală
$sqlDelete= "DELETE FROM date_senzori WHERE date < DATE_SUB(NOW(), INTERVAL 10 DAY)";
$resultDelete = $conn->query($sqlDelete);
// Preluăm cea mai recentă înregistrare pentru fiecare senzor
$sql ="SELECT ds.id_sensor, ds.value, ds.date, ds.plant_name
       FROM date_senzori ds
       INNER JOIN (
          SELECT id_sensor, MAX(date) AS maxim
          FROM date_senzori
          WHERE user_id=$user_id
          GROUP BY id_sensor
       ) latest ON ds.id_sensor = latest.id_sensor AND ds.date = latest.maxim";
$result = $conn->query($sql);
// Inițializăm array-uri pentru a stoca informații despre valoarea și data din cea mai recentă înregistrare pentru fiecare senzor
$lastValueSensors = [];
$lastDate = [];
$lastPlant = [];
// Verificăm dacă interogarea s-a realizat cu succes și rezultatul conține cel puțin un rând
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lastValueSensors[$row['id_sensor']] = $row['value'];
        $lastDate[$row['id_sensor']] = strtotime($row['date']);
        $lastPlant[$row['id_sensor']] = $row['plant_name'];
    }
}
// Definim pragurile de fluctuație
$fluctuationThresholds = [
    "humiditySoil" => 7.0,
    "lightintensity" => 20.0,
    "airhumidity" => 0.9,
    "temperature" => 0.5,
    "WaterLevel" => 80.0,
    "rain" => 0.5
];
// Preluăm ultima irigare din baza de date
$sqlPump = "SELECT wc.status_pump, wc.created_at FROM watering_control wc 
            JOIN pump_history ph ON wc.id = ph.watering_id
            WHERE wc.user_id=? AND wc.plant_name=? ORDER BY wc.id LIMIT 1";
$stmtPump =  $conn->prepare($sqlPump);
$stmtPump->bind_param("is", $user_id, $plantName);
$stmtPump->execute();
$result = $stmtPump->get_result();
if ($result->num_rows >0) {
    $dataPump = $result->fetch_assoc();
    $statusPump = (int)$dataPump['status_pump'];
    $startTime = strtotime($dataPump['created_at']);
} else {
    $statusPump = null;
    $startTime = 0;
}

// Inserăm în baza de date
foreach ($dataPlant["sensors"] as $sensor) {
    $id_sensor = $sensor["id_sensor"];
    $type = $sensor["type"];
    $value = $sensor["value"];
    // Dacă senzorul nu există in tabela sensors, îl inserăm
    $sql = "SELECT id_sensor FROM sensors WHERE id_sensor='$id_sensor'";
    $result = $conn->query($sql);
    if ($result->num_rows == 0) {
        $sql_insert = "INSERT INTO sensors(id_sensor, type_sensor) VALUES ('$id_sensor', '$type')";
        if (!$conn->query($sql_insert)) {
            die(json_encode(["eroare" => "Eroare la inserarea senzorului $id_sensor"]));
        }
    }
    if( $sensor['type'] === 'WaterLevel' && $statusPump !== null && ($statusPump ===1 || (time()-$startTime) < 300)) {
       $secundeTrecute = time() - $startTime;
       continue;
    } 
    // Verificăm dacă planta selectată diferă de ultima plantă || dacă nu există înregistrări || a trecut o oră de la ultima valoare inserată
    if ($plantName !== $lastPlant[$id_sensor] || $lastPlant[$id_sensor] === null || ((time()-$lastDate[$id_sensor])>3600)) {
        $sql = "INSERT INTO date_senzori(id_sensor, date, value, plant_name, user_id) VALUES ('$id_sensor', NOW(), '$value', '$plantName', '$user_id')";
        if (!$conn->query($sql)) {
            die(json_encode(["eroare" => "Eroare la inserarea valorilor pentru senzorul $id_sensor"]));
        }
    } else if($plantName === $lastPlant[$id_sensor]) {
        //Dacă planta este aceeași, inserăm doar în cazul în care valoarea senzorului fluctuează semnificativ
        if ((abs($value - $lastValueSensors[$id_sensor]) > $fluctuationThresholds[$type])  || ($lastValueSensors[$id_sensor] > 1400  && $value < 1400 && $sensor['type'] === 'WaterLevel')) {
            $sql =  $sql = "INSERT INTO date_senzori(id_sensor, date, value, plant_name, user_id) VALUES ('$id_sensor', NOW(), '$value', '$plantName', '$user_id')";
            if (!$conn->query($sql)) {
                die(json_encode(["eroare" => "Eroare la inserarea valorilor pentru senzorul $id_sensor"]));
            }
        } 
    }
}
echo json_encode(["succes" => "Valorile de la senzori au fost inserate cu succes"]);
?>
