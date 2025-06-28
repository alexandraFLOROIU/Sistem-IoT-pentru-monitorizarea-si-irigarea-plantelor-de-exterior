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
// Facem o interogare pentru a obține toate plantele pentru care senzorii au înregistrat date
$sql = "SELECT plant_name FROM date_senzori WHERE user_id='$user_id' GROUP BY plant_name ORDER BY MAX(date) DESC";
$result = $conn->query($sql);
// Verificăm dacă s-a realizat cu succes interogarea
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Adăugăm valorile în array
        $optionsPlant[] = htmlspecialchars($row['plant_name']);
    }
} else {
    $optionsPlant='';
}
// Obținem planta curentă a userului din baza de date
$active_plant = null;
$sql =" SELECT active_plant FROM user WHERE id =?";
$stmt = $conn->prepare ($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result  && $row = $result->fetch_assoc()) {
      $active_plant = $row['active_plant'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Grafice date senzori">
        <link rel="stylesheet" href="sensor_graphs.css">
        <title>Grafice senzori</title>
    </head>
    <body>
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
            <a href="data_plant.php" class="nav-btn" id="plantBack">🌼Date plantă</a>
        </nav>
        <div class="container">
            <header>
                <h1>Grafice pentru monitorizarea plantei</h1>  
            </header>
            <!--Butoane pentru intervalul de zile -->
            <div class="buttons">
                <?php
                if (isset($optionsPlant) && is_array($optionsPlant)) {
                    $defaultPlant = reset($optionsPlant) ?? null;
                    array_shift($optionsPlant);
                }
                ?>
                <label for="filterPlant"></label>
                    <select id="filterPlant" name="filterPlant">
                     <?php if($defaultPlant !== null): ?>   
                    <option value="<?php echo $defaultPlant; ?>"><?php echo $defaultPlant; ?></option>
                     <?php endif;?>
                        <?php foreach ($optionsPlant as $plant_name): ?>
                            <option value="<?php echo $plant_name; ?>"><?php echo $plant_name; ?></option>
                        <?php endforeach;?>
                    </select>   
                <button class="time-button active" data-days="1">Ultima zi</button>
                <button class="time-button " data-days="2">Ultimele două zile</button>
                <button class="time-button " data-days="7">Ultimele șapte zile</button>
            </div>
            <!-- Grafice -->
            <div class="charts">
                <div>
                    <div class="chart-title">Umiditatea solului🌱</div>
                    <div class="chart-container">
                        <canvas id="chartSoilMoisture"></canvas>
                    </div>
                    <a href=# class="button-export" sensor-type="humiditySoil">Exportă datele 📤</a>
                </div> 
                <div>
                    <div class="chart-title">Intensitatea luminii☀️</div>
                    <div class="chart-container">
                        <canvas id="chartLightIntensity"></canvas>
                    </div>
                    <a href=# class="button-export" sensor-type="lightintensity">Exportă datele 📤</a>
                </div> 
                    <div>
                    <div class="chart-title">Umiditatea aerului💧</div>
                        <div class="chart-container">
                    <canvas id="chartAirHumidity"></canvas>
                    </div>
                    <a href=# class="button-export" sensor-type="airhumidity">Exportă datele 📤</a>
                </div> 
                <div>
                    <div class="chart-title">Temperatura🌡️</div>
                    <div class="chart-container">
                        <canvas id="chartTemperature"></canvas>
                    </div>
                    <a href=# class="button-export" sensor-type="temperature">Exportă datele 📤</a>
                </div> 
                <div>
                    <div class="chart-title">Nivelul apă💦</div>
                        <div class="chart-container">
                        <canvas id="chartWaterLevel"></canvas>
                    </div>
                    <a href=# class="button-export" sensor-type="WaterLevel">Exportă datele 📤</a>
                    </div> 
                <div>
                <div class="chart-title">Status ploaie🌧️</div>
                    <div class="chart-container">
                        <canvas id="chartRain"></canvas>
                    </div>
                    <a href=# class="button-export" sensor-type="rain">Exportă datele 📤</a>
                    </div> 
                </div>
            </div>    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Funcția va fi executată când structura paginii e complet încărcată
        document.addEventListener("DOMContentLoaded",function(){
            document.getElementById("plantBack").href=`data_plant.php`;
            fetchAndUpdate(selectedDays,plantSelected);
            // Funcția se reapelează la fiecare 10 secunde
            setInterval( () => { fetchAndUpdate(selectedDays,plantSelected); },10000);
        });
        const sensorTypes = {
            humiditySoil:'chartSoilMoisture',
            lightintensity:'chartLightIntensity',
            temperature:'chartTemperature',
            airhumidity:'chartAirHumidity',
            WaterLevel:'chartWaterLevel',
            rain:'chartRain',
        };
        const yAxis = {
            humiditySoil:"Valoare (%)",
            lightintensity:"Valoare (lux)",
            temperature:"Valoare (°C)",
            airhumidity: "Valoare (%)",
            WaterLevel: "Valoare",
            rain:"Valoare",
        };
        // Creăm un grafic pentru fiecare senzor
        const charts ={};
        Object.keys(sensorTypes).forEach(type_sensor => {
            const ctx = document.getElementById(sensorTypes[type_sensor]).getContext('2d');
            charts[type_sensor]= new Chart(ctx, {
                type: 'line',
                data: { labels: [], datasets:[]},
                options:{
                    reponsive: true,
                    maintainAspectRatio:false,
                    scales: {
                        x: { title: { display: true, text: 'Timp'}},
                        y: { title: { display: true, text: yAxis[type_sensor]}},
                    },
                },
            });
        });
        let selectedDays = 1;
        let plantSelected = "<?php echo $active_plant ?>";
        async function fetchAndUpdate(days,plant) {
            // Așteptăm să obținem datele, iar execuția funcției se oprește până la primirea răspunsului
            const response = await fetch(`get_sensor_data.php?interval=${days}&plant=${plant}`);
            // Așteptăm ca răspunsul să fie convertit într-un obiect JavaScript
            const data = await response.json();
            console.log(data);
            Object.keys(charts).forEach(sensorType => {
                const chart = charts[sensorType];
                // Resetăm complet graficul
                chart.data.labels = [];
                chart.data.datasets.length = 0; 
                // Verificăm dacă obiectul nu este gol
                if (data[sensorType] && Object.keys(data[sensorType]).length > 0) {
                    let labelsSet = false;
                    Object.keys(data[sensorType]).forEach(sensorId => {
                        const sensorData = data[sensorType][sensorId];
                        if (!labelsSet) {
                            chart.data.labels = sensorData.labels; // Evităm duplicatele
                            labelsSet = true;
                        }
                        const randomColor = `rgb(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255})`;
                        // Adăugăm un nou set de date în grafic
                        chart.data.datasets.push({
                            label: `Senzor ${sensorId}`,
                            data: sensorData.data,
                            borderColor: randomColor,
                            backgroundColor: randomColor,
                            pointStyle:'circle',
                            pointRadius:2.5,
                            borderWidth: 1.5,
                            tension:0.1
                        });
                    });
                } else {
                chart.data.labels = [];
                chart.data.datasets = [];
            }
            chart.update(); 
            });
        }
        // Definim o funcție pentru a exporta datele în format CSV
        function exportData(event){
            event.preventDefault();
            const sensorName=event.currentTarget.getAttribute('sensor-type');
            const url=`exportDataToCsv.php?day=${selectedDays}&plant=${plantSelected}&sensorType=${sensorName}`;
            let iframe = document.getElementById('download');
            // Dacă nu există deja creăm un iframe invizibil
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'download';
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
            }
            // Declanșăm  descărcarea
            iframe.src= url;
        }
        // Când intervalul de zile va fi schimbat
        document.querySelectorAll('.time-button').forEach(button => {
            button.addEventListener('click', () => {
              document.querySelectorAll('.time-button').forEach(btn => btn.classList.remove('active'));
              button.classList.add('active');
              selectedDays=button.dataset.days;
              fetchAndUpdate(selectedDays,plantSelected);
            });
        });
        // Când utilizatoru va modifica planta pentru care sunt afișate datele
        document.querySelector('#filterPlant').addEventListener('change', (event) => {
            plantSelected = event.target.value;
            fetchAndUpdate(selectedDays, plantSelected);
        })
        // Când va fi apasat butonul de export se vor exporta datele specifice fiecărui senzor
        document.querySelectorAll('.button-export').forEach(button => {
            button.addEventListener('click', exportData);
        });
    </script>
    <footer class="footer">
         <div>
            <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
         <div>
   </footer>
</body>
</html>   

