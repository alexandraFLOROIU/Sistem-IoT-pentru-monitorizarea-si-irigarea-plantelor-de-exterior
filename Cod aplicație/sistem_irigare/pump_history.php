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
// Facem o interogare pentru a șterge datele mai vechi de 30 de zile
$sqlDelete= "DELETE FROM pump_history WHERE start_time < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$resultDelete = $conn->query($sqlDelete);
// Facem o interogare pentru a obține toate plantele pe care a folosit userul sistemul
$sql = "SELECT DISTINCT plant_name FROM watering_control WHERE user_id='$user_id' ORDER BY plant_name";
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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Istoric irigare">
        <link rel="stylesheet" href="pump_history.css">
        <title>Istoric irigare</title>
    </head>
    <body>
        <nav>
            <p>💧Smart Plant Irrigation🪴</p>
            <a href="data_plant.php" class="nav-btn" id="plantBack">🌼Date plantă</a>
        </nav>
        <div class="container">
            <header>
                <h1>Istoricul pornirii pompei</h1>
            </header>    
            <a href=# class="button-export">Exportă datele 📤</a> 
            <!-- Dropdown pentru filtrare -->
            <form id="filterForm">
                <div>
                    <label for="filterWatering"></label>
                    <select id="filterWatering">
                        <option value="">Toate tipurile</option>
                        <option value="default">Default</option>
                        <option value="manual">Manual</option>
                        <option value="automatic">Automat</option>
                        <option value="periodic">Periodic</option>
                    </select>   
                </div>
                <div>
                    <label for="filterPlant"></label>
                    <select id="filterPlant">
                        <option value="">Toate plantele</option>
                        <?php foreach ($optionsPlant as $plant_name): ?>
                            <option value="<?php echo $plant_name; ?>"><?php echo $plant_name; ?></option>
                        <?php endforeach;?>
                    </select>   
                </div>
                <button type="submit">Filtrează</button>
            </form>  
            <!-- Tabelul pentru date -->
            <table id="historyData">
                <!-- Definim antetul tabelului -->
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipul de udare</th>
                        <th>Timpul de pornire</th>
                        <th>Durata</th>
                        <th>Planta</th>
                    </tr>
                <thead>
                <tbody></tbody>
            </table>         
            <!--Paginare-->
            <div class="pagination" id="pagination"></div>     
        </div>
        <footer class="footer">
            <div>
                <span>🌎 ©️ 2025 Smart Plant Irrigation</span>
            <div>
        </footer>
    <script>
        let pageCurrent = 1;
        let filterCurrent = "";
        let filterPlant = "";    
        // Funcția va fi executată când structura paginii e complet încărcată
        document.addEventListener("DOMContentLoaded",function() {
            document.getElementById("plantBack").href=`data_plant.php`;
        });
        // Funcția se execută când formularul este trimis
        document.querySelector("#filterForm").addEventListener("submit", function(event) {
            event.preventDefault();
            fetchData(document.querySelector("#filterWatering").value, document.querySelector("#filterPlant").value );
        });
        // Cand va fi apasat butonul de export se vor exporta datele
        document.querySelector('.button-export').addEventListener('click', exportData);
    
        async function fetchData (wateringType="",plantType="",page=1) {
            filterCurrent = wateringType;
            filterPlant = plantType;
            pageCurrent = page;
            // Așteptăm să obținem datele, iar execuția funcției se oprește până la primirea răspunsului
            const response= await fetch(`get_pump_history.php?watering_type=${wateringType}&filter_plant=${filterPlant}&page=${page}`);
            // Așteptăm ca răspunsul să fie convertit într-un obiect JavaScript
            const result = await response.json();
            const dataBody = document.querySelector("#historyData tbody");
            dataBody.innerHTML="";
            if (result.data.length == 0) {
                dataBody.innerHTML="<tr><td colspan='5' class='no-data'>Nu există date disponibile.</td></tr>";
                pagination.style.display="none";
                return;
            }
            // Adăugăm datele în tabel
            result.data.forEach((row,index) =>
            {
                const tr=document.createElement("tr");
                tr.innerHTML=`<td>${index+1+(pageCurrent-1)*20}</td>
                              <td>
                              ${row.watering_type === "automatic" ? "automat" : row.watering_type === "default" ? "implicit" : row.watering_type }
                              </td>
                              <td>${row.start_time}</td>
                              <td>${row.duration} ${row.duration === 1 ? "secundă":"secunde"} </td>
                              <td>${row.plant_name}</td>
                              `;
                              dataBody.appendChild(tr);
            });
            updatePagination(result.page_total);
        }
        // Definim o funcție pentru a actualiza paginarea
        function updatePagination(page_total) {
            const pagination = document.getElementById("pagination");
            pagination.innerHTML= "";
            if (page_total >= 1) {
                pagination.style.display="flex";
            } else {
                pagination.style.display="none";
            }
            for (let i = 1;i <= page_total;i++) {
                let bttn=document.createElement("button");
                bttn.innerText=i;
                if ( i === pageCurrent) {
                    bttn.classList.add("active");
                }
                // Funcția se va apela la apăsarea butoanelor de la paginare
                bttn.addEventListener("click", ()=> fetchData(filterCurrent,filterPlant, i));
                pagination.appendChild(bttn);
            }
        }
        // Definim o funcție pentru a exporta datele în format CSV
        function exportData(event) {
            event.preventDefault();
            const url=`exportPumpHistoryToCsv.php?type=${filterCurrent}&plant=${filterPlant}`;
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
        // Inițial apar valorile pentru toate tipurile de udări și plante
        fetchData();
        // Funcția se reapelează la fiecare 6 secunde
        setInterval(() =>fetchData(filterCurrent,filterPlant,pageCurrent),6000);
    </script>
</body>
</html>   