<?php
// Setăm fusul orar pentru România cu scopul de a evita decalajele de oră
date_default_timezone_set('Europe/Bucharest');
$urlSeason = $_GET['season'] ?? null;
$currentMonth = (int)date('n');
if ($currentMonth <= 2 || $currentMonth === 12) { 
   $season = "iarnă"; 
} elseif ($currentMonth >= 3 && $currentMonth <= 5) {
   $season = "primăvară"; 
} elseif ($currentMonth >= 6 && $currentMonth <= 8) {
   $season = "vară";
}  else {
   $season = "toamnă";
}
?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="description" content="Plante compatibile">
      <link rel="stylesheet" href="card_season.css">
      <title>Card</title>
   </head>
   <body>
      <nav>
         <p>💧Smart Plant Irrigation🪴</p>
         <div class="btn-group">
            <a href="choose_season.php" class="nav-btn">🌱 Sezon</a>
         </div>   
      </nav>
      <div class="container">
         <header> 
            <h1><?php echo ucfirst($urlSeason);?></h1>
         </header> 
         <div class="plant" id="plantContainer"> </div>   
      </div>
      <footer class="footer">
         <div>
            <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
         <div>
      </footer>
   <script>
      const currentSeason = "<?php echo $season ?>";
      async function DisplayData(){
         const season = "<?php echo $urlSeason ?>";
         try {
            // Așteptăm să obținem datele din fișierul json, iar execuția funcției se oprește până la primirea răspunsului
            const json_response=await fetch('data.json'); 
            // Așteptăm ca răspunsul să fie convertit într-un obiect JavaScript
            const DataPlant= await json_response.json(); 
            const container=document.getElementById("plantContainer");
            // Verificăm dacă există datele pentru anotimpul selectat de utilizator
            if (DataPlant[season]) {
               console.log("Plantele în anotimpul", DataPlant[season]);
               // Obținem o listă de perechi cheie, valoare
               Object.entries(DataPlant[season])
               .forEach(([name,plant])=>
               {
                  // Construim cartonașele cu date despre plante
                  const imgPath = plant.image ? `${plant.image}` : "./img/default.jpg";
                  // Creăm un element HTML <div> și îl stocăm în variabila card
                  const card = document.createElement("div");
                  card.classList.add("card");
                  card.innerHTML=`<img src="${imgPath}" alt="${name}">
                  <h3>${name}</h3>
                  <p>🌱Umiditatea solului %</p>
                  <p>Valoarea optimă este ${plant.optimalMoisture}%</p>`;
                  // Creăm un element button
                  const bttn = document.createElement("button");
                  // Butonul va fi vizibil doar dacă anotimpul selectat este același cu anotimpul curent
                  bttn.classList.add("button_select");
                  if (currentSeason !== season) {
                     bttn.classList.add("hidden");
                  }
                  bttn.textContent="Selectează";
                  bttn.addEventListener("click", () => {
                     sendActivePlantToServer(name);
                     window.location.href=`data_plant.php`;
                  });
                  // Adaugăm butonul în interiorul cartonașului
                  card.appendChild(bttn);
                  // Adăugăm cardul în container
                  container.appendChild(card);
               });
            } else {
               container.innerHTML="<p>Nicio plantă disponibilă pentru acest sezon. </p>";
            }
         }catch(error) {
            console.error("Eroare: ",error);
         }
      }
      // Trimitem datele folosind fetch către fișierul PHP prin POST 
      function sendActivePlantToServer(plant) {
         console.log(`Planta activa: ${plant}`);
         fetch("save_plant.php", {
            method: "POST",
            headers: { "Content-Type": "application/json"},
               body: JSON.stringify ( {
                     plant:plant
                })
            })
            .then(response => response.text())
            .then(data => {
               console.log("Planta activă a fost trimisă: ", data);
               window.location.href=`data_plant.php`;
            })
            .catch(error => console.error("Eroare la trimiterea plantei: ", error));
        }
      // Funcția va fi executată când structura paginii e complet încărcată
      document.addEventListener("DOMContentLoaded", DisplayData);
     </script>    
   </body>
</html>