<?php
// Setăm fusul orar pentru România cu scopul de a evita decalajele de oră
date_default_timezone_set('Europe/Bucharest');
// Importăm clasele din biblioteca PHPMailer necesare pentru a putea trimite emailuri
require_once 'libs/phpmailer/Exception.php';
require_once 'libs/phpmailer/PHPMailer.php';
require_once 'libs/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Indică faptul că datele returnate sunt în format json
header("Content-Type: application/json");
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password = "";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
    die(json_encode(["eroare" =>"Conexiunea la baza de date a eșuat:: " . $conn->connect_error]));
}
// Preluăm datele de la ESP
$id = isset($_POST['id'])? intval($_POST['id']) :null;
$status_pump = isset($_POST['status_pump'])? intval($_POST['status_pump']) :null;
$type = $_POST['type'] ?? null;
$id_ESP = $_POST['id_ESP'] ?? null;
if ($status_pump === null) {
    die(json_encode(["error"=>"Lipseste statusul pompei"]));
}
$user_id = null;
$stmt = $conn->prepare("SELECT user_id FROM device WHERE id_ESP=?");
$stmt->bind_param("s", $id_ESP);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
   $row = $result->fetch_assoc();
   $user_id = $row["user_id"];
   $stmtUser = $conn->prepare("SELECT username, email FROM user WHERE id=?");
   $stmtUser->bind_param("i", $user_id);
   $stmtUser->execute();
   $result = $stmtUser->get_result();
   if ($result->num_rows > 0) {
     $row = $result->fetch_assoc();
     $email = $row['email'];
     $username = $row['username'];
   }
} else {
    die(json_encode(["error" =>"Error"]));
}
// Actualizăm starea pompei
$sql="UPDATE watering_control SET status_pump=$status_pump ORDER BY id DESC LIMIT 1";
$response=$conn->query($sql);
if ($response === TRUE) {
    echo json_encode(["success" => "Statusul pompei a fost actualizat cu succes in $status_pump"]);
} else {
    echo json_encode(["error" => "Eroare la actualizarea statusului pompei"]);
}
// În cazul în care statusul este 1 inserăm și în tabela care ține evidența istoricului activării
if ($status_pump === 1) {
    $sql_insert= "INSERT INTO pump_history(watering_id,start_time) VALUES('$id',NOW())";
    $conn->query($sql_insert);
    // Facem o interogare în baza de date pentru a putea obține intervalul de funcționare 
    $sql_select= "SELECT duration FROM watering_control WHERE id = ?";
    $stmt = $conn->prepare($sql_select);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
       $row = $result->fetch_assoc();
       $duration = $row['duration'];
    } else {
       die(json_encode(["error" =>"Error"]));
    }
    $stmt->close();
    $pumpDate = date("Y-m-d");
    $pumpHour = date("H:i");
    // Inițializăm un obiect PHPMailer
    $mail = new PHPMailer(true);
    try{
        // Setăm trimiterea mailurilor prin SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        // Setăm adresa folosită pentru autentificarea pe serverul SMTP
        $mail->SMTPAuth = true;
        $mail->Username ='plantirrigationsmart@gmail.com'; 
        $mail->Password = 'oihr lxsf etqp rpjh';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        // Setăm adresa expeditorului și a destinatarului
        $mail->setFrom('plantirrigationsmart@gmail.com', 'Smart Plant Irrigation');
        $mail->addAddress($email, $email);
        // Specificăm conținutul emailului
        $mail->isHTML(true);
        $mail->CharSet ='UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = 'Irigarea a fost activată';
        $mail->Body ="<h1>Salutare, </h1>
                        <p>Irigarea <strong>{$type}ă</strong> a fost activată în data de <strong>$pumpDate</strong>, ora <strong>$pumpHour</strong> pentru o perioadă de <strong>$duration " . ($duration == 1 ? 'secundă' : 'secunde') . "</strong>.</p>
                        <p>Vă mulțumim că ați ales Smart Plant Irrigation!</p>
                        ";  
        $mail->AltBody = "Salutare, \n\n" . "Irigarea a fost activată la $pumpDate pentru o perioadă de $duration " . ($duration == 1 ? "secundă" : "secunde") . ".\n\n" . "Vă mulțumim că ați ales Smart Plant Irrigation !";
        $mail->send();
    } catch(Exception $e) {      
    echo json_encode(["eroare2" => "Eroare la trimiterea emailului"]);
    }
}
if ($status_pump === 0) {
    // După o irigare manuală, sistemul revine la modul implicit
    if ($type === 'manual') {
    $sql_prev="SELECT * FROM watering_control ORDER BY id DESC LIMIT 1";
    $result_prev = $conn->query($sql_prev);
    if ($result_prev->num_rows>0) {
        $data=$result_prev->fetch_assoc();
        $plant_name=htmlspecialchars($data['plant_name']);
    }
    // Inserăm modul implicit de irigare
    $sql_insert="INSERT INTO watering_control(watering_type, status_pump,duration,plant_name, user_id) VALUES('default',0,3,'$plant_name', '$user_id')";
    if ($conn->query($sql_insert) === TRUE ) {
        $watering_id=$conn->insert_id;       
    } 
    $sql_data="INSERT INTO automatic_settings(watering_id, morning_time,evening_time) VALUES ('$watering_id', '07:00', '19:00')";
    if ($conn->query($sql_data) !== TRUE ) {
        echo'Eroare la inserarea datelor';
    }
    }
}
$conn->close();
?>
