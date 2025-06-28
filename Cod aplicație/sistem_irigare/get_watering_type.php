<?php
// Indică faptul că datele returnate sunt în format json
header("Content-Type: application/json");
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password = "";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
   die(json_encode(["eroare" =>"Conexiunea la baza de date a eșuat: " . $conn->connect_error]));
}
// Reținem luna curentă
$currentMonth = date("n");
// Reținem anotimpul curent
if( $currentMonth <= 2 || $currentMonth === 12) { 
   $season="iarnă"; 
} elseif($currentMonth >= 3 && $currentMonth <= 5) {
   $season="primăvară"; 
} elseif($currentMonth >= 6 && $currentMonth <= 8) {
   $season="vară";
} else {
   $season="toamnă";
}
// Preluăm codul unic al ESP din URL
$id_ESP = $_GET['id_ESP'] ?? null;
// Inițializăm id user cu null
$user_id = null;
$plant_name = null;
// Selectăm userul asociat cu ESP-ul respectiv
$stmt = $conn->prepare("SELECT user_id FROM device WHERE id_ESP=?");
$stmt->bind_param("s", $id_ESP);
$stmt->execute();
$result=$stmt->get_result();
if($result->num_rows > 0) {
   $row = $result->fetch_assoc();
   $user_id = $row["user_id"];
   // Verificăm dacă userul a selectat o plantă
   $stmtPlant = $conn->prepare (" SELECT active_plant FROM user WHERE id=?");
   $stmtPlant->bind_param("i", $user_id);
   $stmtPlant->execute();
   $result = $stmtPlant->get_result();
   if ( $row = $result->fetch_assoc()) {
      if( isset($row['active_plant'])) {
         $plant_name = $row['active_plant'];
      }
   }
}
// Citim conținutul fișierului data.json și îl modificăm într-un array asociativ PHP
$plantInfo = json_decode(file_get_contents("data.json"),true);
// Dacă planta selectată nu este compatibilă cu anotimpul curent, irigarea va fi oprită până la selectarea unei plante compatibile
if (isset($plantInfo[$season][$plant_name])) {
   $stop_irrigation = 0;
   $optimal_value = $plantInfo[$season][$plant_name]['optimalMoisture'];
} else {
   $stop_irrigation = 1;
   $optimal_value = 0;
}
// Obținem ultima înregistrare din tabela watering_control de la userul respectiv
$sql="SELECT * FROM watering_control WHERE user_id = ?  ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result=$stmt->get_result();
$watering_control=$result->fetch_assoc();
// Reținem valorile pentru a forma răspunsul către ESP
$id=$watering_control['id'] ?? null;
$watering_type=$watering_control['watering_type'] ?? null;
$status_pump=$watering_control['status_pump'] ??null;
$duration=$watering_control['duration'] ?? null;
$response=[
   "id" => $id,
   "watering_type" => $watering_type,
   "status_pump" => $status_pump,
   "duration" => $duration,
   "stop_irrigation" =>$stop_irrigation,
   "optimal_value" =>$optimal_value
   ];
// În cazul în care programul de udare este automat, implicit sau periodic adăugăm datele suplimentare
if ($watering_type === 'automatic' || $watering_type ==='default') {
   $sql_data="SELECT morning_time, evening_time FROM automatic_settings WHERE watering_id=$id";
   $result_data=$conn->query($sql_data);
   if ($result_data->num_rows>0) {
      $automatic=$result_data->fetch_assoc();
      // Adăugăm datele suplimentare pentru irigarea automată sau implicită
      $response["morning_time"]=$automatic["morning_time"];
      $response["evening_time"]=$automatic["evening_time"];
   }
} else if($watering_type === 'periodic') {
   $currentDate = time();
   $sql_data="SELECT start_time, stop_time, time_hour, days_time FROM periodic_settings WHERE watering_id=$id";
   $result_data=$conn->query($sql_data);
   if ($result_data->num_rows>0) {
      $periodic=$result_data->fetch_assoc();
      // În cazul în care perioada stabilită pentru irigarea periodică a expirat, inserăm modul implicit de irigare
      if ($currentDate >= strtotime($periodic["stop_time"] . '+1 day')) {
         $defaultValue = 'default';
         $status = 0;
         $duration_pump = 3;
         $morning_time ='07:00';
         $evening_time ='19:00';
         $sqlinsert = "INSERT INTO watering_control(watering_type, status_pump, duration, created_at, plant_name, user_id) VALUES(?,?,?,NOW(),?,?)";
         $stmt = $conn->prepare($sqlinsert);
         if ($stmt) {       
            $stmt->bind_param("siisi",$defaultValue,$status, $duration_pump, $plant_name, $user_id);
            $stmt->execute();
            $insert_id=$conn->insert_id;
            $stmt->close();
            $sql_data="INSERT INTO automatic_settings(watering_id, morning_time,evening_time) VALUES ('$insert_id', '$morning_time', '$evening_time')";
            if($conn->query($sql_data) !== TRUE )
            {
                  echo'Eroare la inserarea datelor';
            
            } 
            // Actualizăm răspunsul
            $response=[
               "id" => $id,
               "watering_type" => $defaultValue,
               "status_pump" => $status,
               "duration" => $duration_pump,
               "stop_irrigation" => $stop_irrigation,
               "morning_time" => $morning_time,
               "evening_time" => $evening_time
               ];
         }
      } else {
         // Adăugăm datele suplimentare pentru irigarea periodică
         $response["start_time"]=$periodic["start_time"];
         $response["stop_time"]=$periodic["stop_time"];
         $response["time_hour"]=$periodic["time_hour"];
         $response["days_time"]=$periodic["days_time"];
      }
   }
}
// Trimitem răspunsul JSON la ESP
echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>