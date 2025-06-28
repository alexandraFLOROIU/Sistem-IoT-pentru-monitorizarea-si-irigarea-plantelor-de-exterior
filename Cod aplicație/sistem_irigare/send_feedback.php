<?php
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
  die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}
// Verificăm dacă a fost trimis prin POST formularul
if (isset($_POST['submit'])) { 
  $rating = $_POST['rating'];     
  $opinion = $_POST['opinion'];   
  $username = $_POST['username'];   
  // Inserăm feedback-ul în baza de date
  $stmt = $conn->prepare("INSERT INTO feedback (rating, message, username) VALUES (?, ?,?)");
  $stmt->bind_param("iss", $rating, $opinion, $username); 
  if ($stmt->execute()) {
    echo "Feedback-ul a fost trimis cu succes";
    header("Location: index.php"); 
    exit;
  } else {
    echo "Eroare la trimiterea feedback-ului:" . $stmt->error;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="description" content="Feeback">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Informații de contact</title>
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="send_feedback.css" />
  </head>
  <body>
  <nav>
    <p>💧Smart Plant Irrigation🪴</p>
      <div class="btn-group">
        <a href="index.php" class="nav-btn">🏠Acasă</a>
      </div>   
  </nav>
    <div class="container">
      <div class="form">
        <div class="contact-info">
          <p>Ne puteți găsi la următoarea adresă:</p>
          <div class="info">
            <i class='bx bx-map' style='color:#6a9c89'  ></i>
            <p>Bulevardul Republicii nr. 1</p>
          </div>
          <div class="info">
            <i class='bx bx-envelope' style='color:#6a9c89'></i>
            <p>plantirrigationsmart@gmail.com</p>
          </div>
          <div class="info">
            <i class='bx bxs-phone-call' style='color:#6a9c89' ></i>
            <p>0725679321</p>
          </div>
          <div class="social-connections">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2646.1367081420494!2d21.22175277612156!3d45.75353537108017!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47455d7fa67256b9%3A0x4736aba2928abbe9!2sBulevardul%20Republicii%201%2C%20Timi%C8%99oara%20300005!5e1!3m2!1sro!2sro!4v1732214254349!5m2!1sro!2sro" width="300" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Hartă"></iframe>
          </div>
        </div>
        <div class="contact-form">
          <span class="circle one"></span>
          <span class="circle two"></span>
          <h3 class="title">Feedback</h3>
          <form action="send_feedback.php" method="POST">
            <div class="rating">
				      <input type="number" name="rating" hidden>
              <i class='bx bx-star stars' style='color:#e6c927' ></i>
				      <i class='bx bx-star stars' style='color:#e6c927'></i>
				      <i class='bx bx-star stars' style='color:#e6c927'></i>
				      <i class='bx bx-star stars' style='color:#e6c927'></i>
				      <i class='bx bx-star stars' style='color:#e6c927'></i>
			      </div>
            <input type="text" name="username"class="username" placeholder="Numele... ">
              <div class="message">
                <textarea name="opinion" cols="25" rows="6" placeholder="Opinia..." required></textarea>
              </div>
            <button type="submit" class="btn_submit" name="submit">Submit</button>
          </form>       
        </div>
      </div>
    </div>
    <footer class="footer">
      <div>
        <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
      <div>
    </footer>
    <script>
      const ratingStars = document.querySelectorAll('.rating .stars')
      const ratingValue = document.querySelector('.rating input')
      // Adăugăm un eveniment de click pentru a actualiza nota
      ratingStars.forEach((element, position)=> {  
        element.addEventListener('click', function () { 
          // Adăugăm 1 pentru ca nota să fie de la 1 la 5
		      ratingValue.value = position + 1  
          // Toate stelele vor fi resetate, adică vor deveni goale  
		      ratingStars.forEach(element=> { element.classList.replace('bxs-star', 'bx-star') })
          // Vor fi colorate doar stelele până la cea selectată, inclusiv aceasta
		      let i=0;
		      while (i < ratingStars.length) {
            if ( i <= position) {
					    ratingStars[i].classList.replace('bx-star', 'bxs-star') 
				    }
				    i++;
		      }
	      })
      })
    </script>
  </body>
</html>