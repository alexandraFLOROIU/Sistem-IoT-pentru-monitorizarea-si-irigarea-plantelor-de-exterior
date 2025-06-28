<?php
// Pornim sesiunea
session_start(); 
// Ștergem toate variabilele din sesiune
session_unset(); 
// Oprim sesiunea complet
session_destroy(); 
// Redirecționăm la pagina de home
header('location:index.php'); 
exit();
?>
