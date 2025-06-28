<?php 
// Inițializăm sesiunea pentru a putea accesa datele despre utilizator
require_once 'session.php';
$user_id = $_SESSION['user_id'];
// Inițializăm conexiunea cu baza de date
$servername = "localhost";
$username = "root";
$password ="";
$db="licenta_db";
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) {
   echo json_encode(["Conexiunea la baza de date a eșuat: " . $conn->connect_error]);
}
$plant = null;
// Reținem planta activă
$sql =" SELECT active_plant FROM user WHERE id =?";
$stmt = $conn->prepare ($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result  && $row = $result->fetch_assoc()) {
      $plant = $row['active_plant'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Date de la senzori">
        <link rel="stylesheet" href="data_plant.css">
        <title>Date despre plantă</title>
    </head>
    <body>
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
            <div class="btn-group">
                <a href="index.php" class="nav-btn">🏠Acasă</a>
                <a href="sensor_graphs.php" class="nav-btn">📈Grafice senzori</a>
                <a href="dashboard_plant.html" class="nav-btn">📊Tabou de bord</a>
                <a href="pump_history.php" class="nav-btn">📜Istoric irigare</a>
            </div>   
        </nav>
        <div class="container">
            <header>
                <h1>Monitorizarea mediului de creștere al plantelor</h1>
                <div class="season-and-plant">
                    Anotimp: <span class="season-name">...</span> | Plantă: <span class="plant-name"><?php echo ucfirst($plant);?></span>
                </div>    
            </header>
            <div class="sensor-data">
                <h2>Date colectate de la senzori</h2>
                <div id="sensor">
                </div>
                <div class="pump_watering">
                    <div id="wateringType">--</div>
                    <div id="separator">| </div>
                    <div id="status_pump">--</div>
                </div>
            </div>
            <form id="watering">
                <h2>Selectează tipul de udare</h2>
                <div class="form-group">
                    <label for="TypeWatering">Tipul de udare:</label>
                    <select id="TypeWatering" name="type_watering" required>
                        <option value="manual">Manual</option>
                        <option value="automatic">Automat</option>
                        <option value="periodic">Periodic</option>
                    </select>
                </div>    
                <div id="automatic_settings" class="hidden">
                    <div class="form-group">
                        <label for="morning_time">Ora dimineața:</label>
                        <input type="time" id="morning_time" name="time_morning" value="07:00" step="60">
                    </div>
                    <div class="form-group">
                        <label for="evening_time">Ora seara:</label>
                        <input type="time" id="evening_time" name="time_evening" value="19:00" step="60">
                    </div>
                </div>    
                <div id="periodic_settings" class="hidden">
                    <div class="form-group">
                        <label for="start_time">Data de începere:</label>
                        <input type="date" id="start_time" name="time_start">
                    </div>
                    <div class="form-group">
                        <label for="stop_time">Data de încheiere:</label>
                        <input type="date" id="stop_time" name="time_stop">
                    </div>
                    <div class="form-group">
                        <label for="hour_time">Ora:</label>
                        <input type="time" id="hour_time" name="time_hour">
                    </div>
                    <div class="form-group">
                        <label for="days_time">Intervalul de zile:</label>
                        <select id="days_time" name="time_days" required>
                            <option value="1"> 1 zi</option>
                            <option value="2"> 2 zile</option>
                            <option value="3"> 3 zile</option>
                            <option value="4"> 4 zile</option>
                            <option value="5"> 5 zile</option>
                            <option value="6"> 6 zile</option>
                            <option value="7"> 7 zile</option>
                        </select>   
                    </div>
                </div> 
                <div class="form-group">
                    <span class="info_duration">Debitul aproximativ: <br>  1s: 0.18l/s <br> 2s: 0.036l/s <br> 3s: 0.055l/s</span>
                    <label for="duration">Durata:</label>
                    <select id="duration" name="time_duration" required>
                        <option value="1">1 secundă</option>
                        <option value="2">2 secunde</option>
                        <option value="3">3 secunde</option>
                    </select> 
                </div>
                <div class="button-section">
                    <button id="save" type="submit">Save settings</button>
                </div>  
            <form>
        </div>
        <!--Dacă am trecut în alt anotimp și planta selectată nu este relevantă pentru sezonul curent, utilizatorul este rugat să selecteze o altă plantă-->
        <div id="seasonMisMatch" class="aux" style="display:none;">
            <div class="aux-content">
                <h2>Alertă!</h2>
                <p> Planta (<span id="plant-current"><?php echo ucfirst($plant);?></span>) nu este potrivită pentru acest sezon (<span id="season-current"></span>). Te rugăm să selectezi altă plantă. </p>
                <div class="alert-button">
                    <button id="closeAux">Selectează altă plantă</button>
                </div>    
            </div>
        </div>   
        <footer class="footer">
            <div>
                <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
            <div>
        </footer>
        <script>
            const TypeWatering=document.getElementById('TypeWatering');
            const automatic_settings=document.getElementById('automatic_settings');
            const periodic_settings=document.getElementById('periodic_settings');
            // Când utilizatorul va schimba selecția din dropdown-ul cu id-ul 'TypeWatering' se va executa funcția
            TypeWatering.addEventListener('change',function() {
                const typeWatering=TypeWatering.value;
                typeWatering == 'automatic'? automatic_settings.classList.remove('hidden'):automatic_settings.classList.add('hidden');
                typeWatering == 'periodic'? periodic_settings.classList.remove('hidden'):periodic_settings.classList.add('hidden');            
            });
            // Funcția se execută când formularul este trimis
            document.getElementById('watering').addEventListener('submit', function(event) {
                // Prevenim trimiterea automată a formularului
                event.preventDefault(); 
                // Creăm un obiect cu datele din formular
                const data = new FormData(event.target);
                const plant = <?php echo json_encode($plant); ?>;
                // Adăugăm o nouă pereche cheie valoare în formular
                data.append('plant', plant);
                // Afișăm în consolă toate perechile cheie-valoare
                for (let [key, value] of data.entries()) {
                console.log(`${key}: ${value}`);
                }
                // Trimitem datele folosind fetch către fișierul PHP prin POST 
                fetch('watering_control.php',{
                    method:'POST',
                    body:data
                })
                .then(response => response.text())
                .then(result => { console.log(result); })
                .catch(error => { console.error('Eroare la cererea POST:', error); });
            });
            // Trimitem o cerere GET pentru a obține datele colectate de senzori
            function fetchData() {
                fetch('getLatestData.php')
                .then(response => response.json())
                .then(data => {
                    console.log("Datele primite:", data); 
                    // Stocăm elementul cu id-ul respectiv într-o variabilă
                    const container = document.getElementById('sensor');
                    container.innerHTML="";      
                    // Parcurgem toate obiectele din array
                    data.forEach(sensor => {
                        const div = document.createElement('div');
                        let info="";
                        // În funcție de tip setăm conținutul HTML
                        switch(sensor.type) {
                            case "humiditySoil":
                                info = `🌱Umiditatea solului: <strong>${sensor.value} %</strong>`;
                                break;
                            case "temperature":
                                info =`🌡️Temperatura: <strong>${sensor.value} °C</strong>`;
                                break;
                            case "lightintensity":
                                info=`☀️Intensitatea luminii: <strong>${sensor.value} luxi</strong>`;
                                break;
                            case "airhumidity":
                                info=`💧Umiditatea aerului: <strong>${sensor.value} %</strong>`;    
                                break;
                            case "WaterLevel":
                                info= sensor.value >1400? `✅Nivelul apei: <strong>${sensor.value}</strong>`: `⚠️<span style="color:red";><strong> Nivel scăzut al apei!</strong>`;
                                break;
                            case "rain":
                                info= sensor.value ==0? `⚠️<span style="color:red";><strong>Ploaie detectată!</strong>` : `🌤️Nu a fost detectată ploaie.`;
                                break;
                        }
                        div.innerHTML = info;
                        // Atașăm elementul div ca nod copil containerului respectiv
                        container.appendChild(div);
                    });
                })    
            }
            function fetchWateringData() {
                // Trimitem o cerere GET pentru a obține date despre tipul de irigare selectat și starea pompei
                fetch('getLatestWatering.php')
                .then(response => response.json())
                .then(data => {
                    console.log("Date despre tipul de udare:", data); 
                    switch (data.watering_type) {
                        case "automatic":  
                            document.getElementById("wateringType").innerHTML=`🤖Tipul de udare:<strong> automat</strong>`;
                            break;
                        case "manual":
                            document.getElementById("wateringType").innerHTML=`✋Tipul de udare:<strong> manual</strong>`;
                            break;
                        case "periodic":     
                            document.getElementById("wateringType").innerHTML=`🗓️Tipul de udare:<strong>  periodic</strong>`;
                            break;
                        case "default":    
                            document.getElementById("wateringType").innerHTML=`🔄Tipul de udare:<strong>  implicit</strong>`;
                            break;
                    }   
                    document.getElementById("status_pump").innerHTML= data.status_pump ==0 ?`🔴Status pompă:  <strong>OFF</strong>`:`🟢Status pompă: <strong>ON</strong>`;   
                    const button=document.getElementById("save");
                    // Utilizatorul nu poate modifica setările în timp ce pompa e pornită
                    if (data.status_pump == 1) {
                        button.disabled=true;
                        button.innerText= "⚠️Modificarea setărilor indisponibilă în timpul funționării pompei";
                    } else if (data.status_pump == 0) { 
                        button.disabled=false;
                        button.innerText="Salvează";
                    }
                })
            }
            function initSeason(startData,endData) {
                fetch("check_plant.php")
                .then(result => result.json())
                .then(data => {
                    document.querySelector(".season-name").textContent = data.currentSeason.charAt(0).toUpperCase()+ data.currentSeason.slice(1);
                    document.getElementById("season-current").textContent = data.currentSeason.charAt(0).toUpperCase() + data.currentSeason.slice(1);
                    if (data.mustChange) {
                        document.getElementById('seasonMisMatch').style.display = 'block';
                        return;
                    }
                    startData.min = data.today;
                    startData.max = data.endSeason;
                    endData.min = data.today;
                    endData.max = data.endSeason;
                    startData.value = "";
                    endData.value = "";
                })
            }
            const startData = document.getElementById("start_time");
            const endData =  document.getElementById("stop_time");
            startData.addEventListener("change", () => {
                if (!startData.value) return;
                const data = new Date(startData.value);
                data.setDate(data.getDate()+1);
                endData.min = data.toISOString().split("T")[0];
            });
            // Funcția se va executa când conținutul paginii a fost încărcat complet
            document.addEventListener("DOMContentLoaded", function () {
                const startData = document.getElementById("start_time");
                const endData =  document.getElementById("stop_time");
                sendDefaultWateringType();
                initSeason(startData, endData);
                fetchWateringData();
                fetchData();
                const closeButton = document.getElementById('closeAux');
                // La apăsarea butonului utilizatorul va fi redirecționat pentru a selecta o plantă compatibilă cu noul anotimp
                closeButton.addEventListener('click', function(){
                    document.getElementById('seasonMisMatch').style.display="none";
                    window.location.href="choose_season.php"; 
                })
                // Apelează funcția la fiecare 1000ms
                setInterval(fetchData, 1000);
                // Apelează funcția la fiecare 500ms
                setInterval(fetchWateringData, 500);
                setInterval(() => initSeason(startData, endData),3600000);
            });
            function sendDefaultWateringType() {
                // Creăm un formular cu datele pentru irigarea implicită
                const dataForm = new FormData();
                dataForm.append('plant_name', <?=json_encode($plant)?>);
                dataForm.append('watering_type','default');
                dataForm.append('status', '0');
                dataForm.append('duration','3');
                dataForm.append('morning_time', '07:00');
                dataForm.append('evening_time','19:00');
                // Trimitem datele folosind fetch către fișierul PHP prin POST 
                fetch("default_watering.php", {
                    method:'POST',
                    body: dataForm
                })
                .then(response => response.json())
                .then(data => {console.log("Server response:", data);
                })
                .catch(error => { console.error("Error send data", error);
                })  
            }
   </script>
</body>
</html>