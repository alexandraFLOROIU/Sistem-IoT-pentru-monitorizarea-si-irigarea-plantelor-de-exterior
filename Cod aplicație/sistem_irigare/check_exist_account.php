<?php
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error)
{
    echo json_encode(["Conexiunea la baza de date a eșuat: " . $conn->connect_error]);
    exit;
}
// Indică faptul că datele returnate sunt în format json
header('Content-Type: application/json');
// Preluăm valoarea parametrului 'email' din URL dacă există
$email= $_GET['email'] ?? '';
// Verficăm dacă contul asociat emailului respectiv a fost confirmat
$sql = "SELECT token_was_verified FROM user WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
// Dacă nu există nicio înregistrare înseamnă că nu există cont asociat cu emailul respectiv
if ($result->num_rows <= 0) {
   echo json_encode(["exists" =>false]);
} else {
    $user_data = $result->fetch_assoc();
    if ($user_data['token_was_verified'] != 1) {
        // Nu există un cont asociat cu emailul respectiv
        echo json_encode(["exists" =>false]);
    } else {
        // Există un cont asociat cu emailul respectiv
        echo json_encode(["exists" =>true]);
    }
}
$stmt->close();
$conn->close();
?>