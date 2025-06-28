<?php 
// Setăm fusul orar pentru România cu scopul de a evita decalajele de oră
date_default_timezone_set('Europe/Bucharest');
// Inițializăm sesiunea pentru a putea accesa datele despre utilizator
require_once 'session.php';
$user_id = $_SESSION['user_id'];
// Indică faptul că datele returnate sunt în format json
header('Content-Type: application/json');
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
   echo json_encode(["Conexiunea la baza de date a eșuat: " . $conn->connect_error]);
}
// Reținem luna curentă
$currentMonth = (int)date('n');
$currentYear = date('Y');
$nextYear = date('Y', strtotime('+1 year'));
$today = date('Y-m-d');
// Verificăm dacă anul este bisect
function isLeapYear($year) {
    return ($year%400 === 0) || ($year % 4 === 0 && $year % 100 !=0);
}
// Reținem anotimpul curent
if ($currentMonth <= 2 || $currentMonth === 12) { 
   $season = "iarnă"; 
   if ($currentMonth ==12) {
        $endSeason = isLeapYear((int)$nextYear) ? "$nextYear-02-29" : "$nextYear-02-28";
   } else {
        $endSeason = isLeapYear((int)$currentYear)? "$currentYear-02-29" : "$currentYear-02-28";
   }
} elseif ($currentMonth >= 3 && $currentMonth <= 5) {
   $season = "primăvară"; 
   $endSeason = "$currentYear-05-31";
} elseif ($currentMonth >= 6 && $currentMonth <= 8) {
   $season = "vară";
   $endSeason = "$currentYear-08-31";
} else {
   $season = "toamnă";
   $endSeason = "$currentYear-11-30";
}
// Obținem planta curentă a userului din baza de date
$plant = null;
$sql =" SELECT active_plant FROM user WHERE id =?";
$stmt = $conn->prepare ($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result  && $row = $result->fetch_assoc()) {
      $plant = $row['active_plant'];
}
$stmt->close();
// Citim conținutul fișierului data.json și îl modificăm într-un array asociativ PHP
$plantInfo = json_decode(file_get_contents("data.json"),true);
// Dacă planta selectată nu este compatibilă cu anotimpul curent, irigarea va fi oprită până la selectarea unei plante compatibile
if (isset($plantInfo[$season][$plant])) {
    $mustChange = false;
} else  {
    $mustChange = true;
}
echo json_encode([
    'mustChange' => $mustChange,
    'currentSeason' => $season,
    'endSeason' => $endSeason,
    'today' => $today
],JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>