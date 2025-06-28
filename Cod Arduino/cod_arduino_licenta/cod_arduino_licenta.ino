#include <WiFi.h> 
#include <ArduinoJson.h>
#include "DHT.h"
#include <Wire.h> 
#include <RTClib.h>
#include <Adafruit_VEML7700.h>
#include <HTTPClient.h>

const char *ssid="iPhone2";
const char *password="alexandra";
const char *insertPath="http://172.20.10.3/sistem_irigare/insert.php";
const char *wateringPath="http://172.20.10.3/sistem_irigare/get_watering_type.php";
const char *updatePumpPath="http://172.20.10.3/sistem_irigare/update_pump_status.php";

#define soilMoisturePin 34
#define maxValueMoistureSoil 4095 
#define minValueMoistureSoil 0    
#define minValueWaterLevel 0
#define maxValueWaterLevel 1800
#define minWaterLevel 20
#define DHTPIN 23    
#define DHTTYPE DHT22 
#define waterLevelPin 32 
#define MAX_SENSORS 20
#define rainPin 26 
#define SDAPin 21
#define SCLPin 22
#define pumpPin 27

float values[MAX_SENSORS];
String types[MAX_SENSORS];
int id_sensors[MAX_SENSORS];
char chipId[17];
int numberOfSensors = 1; // Pentru ca id-ul primului senzor sa fie 1
unsigned long lastRecconect = 0;
// Fusul orar pentru România (GMT+2 iarna și GMT+3 vara) împreună cu trecerea automată la ora de iarnă/vară
const char* tz = "EET-2EEST,M3.5.0/3,M10.5.0/4";

DHT dht(DHTPIN, DHTTYPE);
RTC_DS3231 rtc; 
Adafruit_VEML7700 veml = Adafruit_VEML7700();

void setup() {
  Serial.begin(115200);
  delay(1000); // Pauză pentru stabilizarea comunicației seriale
  dht.begin(); // Pornește senzorul de temperatură și umiditate
  uint64_t aux = ESP.getEfuseMac();
  sprintf(chipId, "%08x%08x", (uint32_t)(aux >> 32), (uint32_t)aux);
  Serial.println(chipId);
  Wire.begin(SDAPin,SCLPin); // Pornește protocolul I2C
  if (!veml.begin()) { // Inițializare senzor de lumină
    Serial.println("Eroare, VEML nu a fost gasit");
  } else {
    Serial.println("VEML a fost gasit");
  }
  // Reducem sensibilitatea pentru a preveni saturația în cazul luminii puternice
  veml.setGain(VEML7700_GAIN_1_8);
  // Pentru a obține măsurări rapide, setăm timpul de integrare scurt
  veml.setIntegrationTime(VEML7700_IT_100MS);
  // Setăm pinul senzorului de nivel de apă ca intrare analogică
  pinMode(waterLevelPin, INPUT);
  // Setăm pinul senzorului de ploaie ca intrare digitală
  pinMode(rainPin, INPUT);
  // Setăm pinul pentru pompă
  pinMode(pumpPin, OUTPUT);
  digitalWrite(pumpPin, HIGH);
  // Inițializăm modulul RTC
  if (!rtc.begin()) {
    Serial.println("Eroare, RTC nu a fost gasit");
    while(1);
  }
  // Verificăm dacă RTC-ul este la prima inițializare sau bateria de backup este descărcată
  if (rtc.lostPower()) {
    // Setăm temporar RTC-ul cu ora compilării până va fi preluată ora corectă de la NTP
    rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  }
  connectToWifi();
}
  
// Functie pentru inserarea valorilor de la senzori în baza de date  
void insertDataToDB()
{
  // Verificăm dacă ESP este conectat la Wi-Fi
  if (WiFi.status() == WL_CONNECTED) {
    // Creăm obiectul client HTTP
    HTTPClient http;
    // Inițializăm conexiunea către server
    http.begin(insertPath);
    // Setăm antetul HTTP pentru a specifica faptul că datele transmise sunt în format JSON
    http.addHeader("Content-Type", "application/json");
    // Creăm documentul JSON și un array numit "sensors" în care vom insera pentru fiecare senzor id-ul, tipul și valoarea
    StaticJsonDocument<1024> jsonFile;
    JsonArray sensorsJson = jsonFile.createNestedArray("sensors");
    for (int i = 0; i < numberOfSensors-1; i++) {
      JsonObject sensor = sensorsJson.createNestedObject();
      sensor["id_sensor"] = id_sensors[i];
      sensor["type"] = types[i];
      sensor["value"] = values[i];
    }
    jsonFile["idESP"] = chipId;
    // Serializăm documentul JSON într-un string pentru a-l putea trimite către server
    String stringJson;
    serializeJson(jsonFile,stringJson);
    int httpResponseCode=http.POST(stringJson);
    //Afisez răspunsul primit de la server
    if (httpResponseCode > 0) {
      Serial.println(stringJson);
    } else {
      Serial.println("Error: " + String(httpResponseCode));
    } 
    // Închidem conexiunea și eliberăm memoria
    http.end();     
  } else {
    Serial.println("Conexiunea la Wi-Fi a fost întreruptă");
    // Reîncercăm conectarea la Wi-Fi
    connectToWifi();
  }
}

void connectToWifi() {
  WiFi.disconnect(true); // Resetăm conexiunile Wi-Fi anterioare
  delay(1000); //Pauză necesară pentru ca sistemul să curețe conexiunile
  WiFi.mode(WIFI_STA); // Setăm ESP ca client Wi-Fi
  WiFi.begin(ssid, password); // Începe conectarea la rețeaua respectivă
  WiFi.setAutoReconnect(true); // Permite reconectare automată
  Serial.print("Conectare la Wi-Fi...");
  // Încercăm conectarea la Wi-Fi cu un număr maxim de 10 încercări și o pauză de o secundă între încercări
  int tries = 0; 
  const int triesMax = 10; 
  while (WiFi.status() != WL_CONNECTED && tries < triesMax) {
    delay(1000); 
    Serial.println(".");
    tries++;
  }
  // Verificăm dacă a reușit conexiunea Wi-Fi
  if (WiFi.status() == WL_CONNECTED) {  
    Serial.print("IP-ul ESP32: ");
    Serial.println(WiFi.localIP());
    Serial.println("Conectarea la Wi-Fi s-a realizat cu succes");
    // Setăm ora locală pentru Romănia, inclusiv trecerea automată la ora de iarnă/vară și o sincronizăm cu ora exactă de pe internet
    configTzTime(tz, "pool.ntp.org");
    // Definim structura standard pentru primirea timpului de la NTP
    struct tm timeinfo;
    // Dacă am obținut timpul de la NTP, creăm un obiect DateTime din datele primite de la NTP, făcând ajustările necesare
    if (getLocalTime(&timeinfo)) {
      DateTime ntpTime(
      // Anul în struct tm este calculat ca anul_curent-1900, de aceea trebuie să adunăm 1900 pentru a obține anul real  
      timeinfo.tm_year + 1900, 
      // Lunile în struct tm încep de la 0, de aceea trebuie să adunăm 1 pentru a obține luna corectă
      timeinfo.tm_mon + 1, 
      timeinfo.tm_mday,
      timeinfo.tm_hour,
      timeinfo.tm_min,
      timeinfo.tm_sec
      );
      DateTime rtcTime = rtc.now();
      // Calculăm diferența exprimată în secunde dintre RTC și timpul NTP, unixtime() convertind data și ora într-un număr de secunde de la 1 ianuarie 1970
      long differences = (long)rtcTime.unixtime() - (long)ntpTime.unixtime();
      Serial.print("Diferența dintre RTC și timpul NTP: ");
      Serial.println(differences);
      // Dacă RTC a pierdut ora și data sau diferența este mai mare de 10s, atunci ajustăm ora din RTC
      if (rtc.lostPower() || abs(differences) > 10) {
        rtc.adjust(ntpTime);
        Serial.println("A fost setată data și ora curentă");
      } else {
        Serial.println("RTC a fost deja setat corect și nu trebuie rescris");
      }
    } else {
      Serial.println("Eroare la sincronizarea NTP");
    }
  } else {
  Serial.println("Va fi folosită ora din RTC pentru că conexiunea Wi-Fi a eșuat");
  }
}
// Funcție pentru compararea a două șiruri de caractere (ore)
bool isTimeEqual(const char* targetTime, const char* currentTime){
  Serial.println("Verificăm dacă ora curentă este ora stabilită");
  return strcmp(targetTime, currentTime) == 0;
}

// Funcție pentru a face update la statusul pompei în baza de date
void updatePumpStatus(int status, int id, String wateringType)
{
  Serial.print("Starea pompei va fi modificată în ");
  Serial.println(status);
  if (WiFi.status() == WL_CONNECTED) {
    // Creăm obiectul client HTTP
    HTTPClient http;
    // Inițializăm conexiunea către server
    http.begin(updatePumpPath); 
    // Setăm antetul HTTP pentru a specifica formatul datelor transmise
    http.addHeader("Content-Type","application/x-www-form-urlencoded");
    // Creăm corpul cererii POST și trimitem cererea către server
    String data = "status_pump="+String(status)+"&id="+String(id)+"&type="+wateringType+"&id_ESP="+String(chipId);
    int httpResponseCode=http.POST(data);
    if (httpResponseCode > 0) {
      Serial.println(http.getString());
    } else {
      Serial.print("Request error: ");
      Serial.println(httpResponseCode);
    }
    // Închidem conexiunea și eliberăm memoria
    http.end();
  } else {
    Serial.println("Conexiunea la Wi-Fi a fost întreruptă");
    // Reîncercăm conectarea la Wi-Fi
    connectToWifi();
  }
}

// Funcție pentru transformarea unei datei într-un timestamp UNIX
time_t parseData(const char* Data)
{
  struct tm dataStruct={};
  // Convertim șirul de caractere în struct tm
  sscanf(Data, "%d-%d-%d", &dataStruct.tm_year, &dataStruct.tm_mon, &dataStruct.tm_mday);
  // Ajustăm valorile ținănd cont că în struct tm anii sunt stocați ca număr de ani trecuți de la 1900, iar lunile încep de la 0
  dataStruct.tm_year -= 1900; 
  dataStruct.tm_mon -= 1; 
  // Convertim structura tm într-un timestamp UNIX
  return mktime(&dataStruct);
}

// Funcție care verifică dacă este ziua de udare 
bool isWaterDay(const char* startData, const char* currentData, const char* endData, int interval)
{     
  // Convertim data de început, de sfârșit și data curentă într-un timestamp UNIX
  time_t start = parseData(startData);
  time_t current = parseData(currentData);
  time_t end = parseData(endData);
  // Dacă data curentă este în afara intervalului selectat
  if((start > current) || (end < current))
  {
    return false;
  }
  // Calculăm diferența dintre data actuală și cea de început, exprimate în secunde
  // Apoi împărțim la numărul de secunde dintr-o zi(86.400s) pentru a afla numărul de zile dintre cele două momente de timp
  int diffDays = (difftime(current,start)/(60*60*24)); 
  Serial.println("Diferența de zile este:");
  Serial.println(diffDays);
  Serial.println("Modulo:");
  Serial.println(diffDays % interval);
  // Împărțim numărul de zile la intervalul de udare stabilit, iar dacă restul împărțirii este zero, atunci este ziua de udare
  return (diffDays % interval) == 0;
}

// Funcție pentru a prelua din baza de date datele despre tipul de udare selectat și setările aferente
void getWateringType(float humiditySoil, float rainValue, float WaterLevel){
  static bool pumpTriggered = false;
  // Verificăm dacă ESP este conectat la Wi-Fi
  if (WiFi.status() == WL_CONNECTED) {
    // Dacă este detectată ploaia sau nivelul de apă din rezervor este sub limită, irigarea va fi oprită
    if (rainValue == 0) {
      Serial.println("Plouă, irigarea va fi oprită");
      return;
    }
    if(WaterLevel < 77) {
      Serial.println("Nivelul de apă din rezervor este prea scăzut");
      return;
    }
    // Creăm obiectul client HTTP
    HTTPClient http;
    // Inițializăm conexiunea către server
    String fullPath = String(wateringPath)+ "?id_ESP=" + String(chipId);
    http.begin(fullPath); 
    // Trimitem o cerere GET
    int httpResponseCode = http.GET();
    // Verificăm răspunsul
    if (httpResponseCode > 0) {
      // Salvăm răspunsul de la server într-o variabilă String
      String payload = http.getString(); 
      Serial.println("Răspunsul primit de la server: ");
      Serial.println(payload);
      // Deserializăm payload-ul într-un obiect JSON. În caz de eroare funcția va returna o eroare
      StaticJsonDocument<512> dataFromJson;
      DeserializationError error = deserializeJson(dataFromJson, payload);  
      if (error) {
        Serial.print("Eroare la parsarea JSON: ");
        Serial.println(error.c_str());
        return;
      }
      int stop_irrigation = dataFromJson["stop_irrigation"];
      // Dacă stop_irrigation == 1, înseamnă că există o condiție de oprire, prin urmare irigarea va fi anulată
      if (stop_irrigation == 1) {
        Serial.println("Irigarea va fi oprită");
        return;
      }
      String wateringType = dataFromJson["watering_type"];
      int pumpStatus = dataFromJson["status_pump"];
      int duration = dataFromJson["duration"];
      int id = dataFromJson["id"];
      float optimalHumidity = dataFromJson["optimal_value"];
      DateTime current = rtc.now();
      char current_time[6];
      char current_data[11];
      sprintf(current_time,"%02d:%02d", current.hour(), current.minute());
      sprintf(current_data,"%04d-%02d-%02d", current.year(), current.month(), current.day());
      if (pumpStatus == 0) {
        // Executăm verificările și acțiunile specifice modului de irigare selectat
        if (wateringType == "manual") {
          Serial.println("Irigare manuală");
          // Activăm irigarea pentru o durată selectată de utilizator, exprimată în secunde
          updatePumpStatus(1,id,wateringType);
          digitalWrite(pumpPin, LOW);
          delay(duration*1000);
          digitalWrite(pumpPin, HIGH);
          updatePumpStatus(0,id,wateringType);
        } else if (wateringType == "automatic" || wateringType == "default") {
          Serial.println("Irigare automată sau implicită");
          // Extragem ora de dimineață și de seara selectată de utilizator din obiectul JSON
          const char* morningTime = dataFromJson["morning_time"];
          const char* eveningTime = dataFromJson["evening_time"];
          Serial.println("Declanșare pornire pompă");
          Serial.println(pumpTriggered);
          // Comparăm valorile extrase cu ora curentă
          if (isTimeEqual(current_time, morningTime) || isTimeEqual(current_time, eveningTime)) {
            if (humiditySoil < optimalHumidity && !pumpTriggered) {
              // Activăm irigarea pentru o durată selectată de utilizator, exprimată în secunde
              updatePumpStatus(1,id,wateringType);
              digitalWrite(pumpPin, LOW);
              delay(duration*1000);
              digitalWrite(pumpPin, HIGH);
              updatePumpStatus(0,id,wateringType);
              pumpTriggered = true;
            }
          } else {
            pumpTriggered = false;
            Serial.println("Nu este ora potrivită pentru irigare");
          }
        } else if (wateringType == "periodic") {
          Serial.println("Irigare periodică");
          const char* start_data = dataFromJson["start_time"];
          const char* stop_data = dataFromJson["stop_time"];
          const char* time_hour = dataFromJson["time_hour"];
          int days_time = dataFromJson["days_time"];
          Serial.println("pumpTriggered");
          Serial.println(pumpTriggered);
          // Verificăm dacă data și ora actuală corespund cu cele selectate de utilizator pentru irigarea periodică
          if (isWaterDay(start_data, current_data, stop_data, days_time) !=0 && isTimeEqual(current_time, time_hour)) {
            Serial.println("Este ziua de udare și ora");
            if (humiditySoil < optimalHumidity && !pumpTriggered) {
              // Activăm irigarea pentru o durata selectată de utilizator, exprimată în secunde
              updatePumpStatus(1,id,wateringType);
              digitalWrite(pumpPin, LOW);
              delay(duration*1000);
              digitalWrite(pumpPin, HIGH);
              updatePumpStatus(0,id,wateringType);
              pumpTriggered = true;
            }
          } else if (isWaterDay(start_data, current_data, stop_data, days_time) == 0) {
            pumpTriggered = false;
            Serial.println("Nu este ziua de udare");
          } else if (isTimeEqual(current_time, time_hour)!=1) {
            pumpTriggered = false;
            Serial.println("Nu este ora de udare");
          }
        }
      }
    } else {
      Serial.print("Eroare la solicitare: ");
      Serial.println(httpResponseCode);
    }
    // Închidem conexiunea și eliberăm memoria
    http.end();
  } else {
    Serial.println("Conexiunea la Wi-Fi a fost întreruptă");
    // Reîncercăm conectarea la Wi-Fi
    connectToWifi();
  }
}

// Funcție pentru adăugarea unui senzor
void addSensor(float value, const char* type) {
  if (MAX_SENSORS > numberOfSensors) { //Verifcăm faptul că nu am depășit limita maximă
    values[numberOfSensors-1] = value;  //Salvăm valoarea în vectorul global
    types[numberOfSensors-1] = type;    // Salvăm tipul în vectorul global
    id_sensors[numberOfSensors-1] = numberOfSensors; //Setăm id-ul în vectorul global
    numberOfSensors++; //Incrementăm
  }
}

float readWaterLevel(int pin, int numarCitiri = 10, int delayValue = 50) {
  long total = 0;
  for (int i = 0; i < numarCitiri; i++) {
    total += analogRead(pin);
    delay(delayValue);
  }
  return total / float(numarCitiri);
}

void loop() {
  int moistureSoilValue = analogRead(soilMoisturePin); //valoarea brută a umidității solului
  // Convertim valoarea analogică într-un procent invers proporțional - valoare mică = sol umed(100%), valoare mare = sol uscat(0%)
  float humiditySoil = (float)(maxValueMoistureSoil-moistureSoilValue) / (maxValueMoistureSoil - minValueMoistureSoil ) * 100.0;
  // Adăugăm senzorul de umiditate sol
  addSensor(humiditySoil,"humiditySoil");
  // Adăugăm senzorul de lumină
  float lightintensity = veml.readLux();
  addSensor(lightintensity,"lightintensity");
  // Adăugăm senzorii de temperatură și umiditate aer
  float airhumidity = dht.readHumidity();
  float temperature = dht.readTemperature();
  if (isnan(temperature) || isnan(airhumidity)) {
    Serial.println("Eroare la citirea senzorului!");
    return;
  }
  addSensor(airhumidity,"airhumidity");
  addSensor(temperature,"temperature");
  // Adăugăm senzorul de nivel apă
  float WaterValue = readWaterLevel(waterLevelPin); 
  Serial.print("WaterValue ");
  Serial.println(WaterValue);
  // Convertim valoarea analogică într-un procent direct proporțional - valoare mică = rezervor gol(0%), valoare mare = rezervor plin(100%)
  float WaterLevel = ((float)(WaterValue- minValueWaterLevel)/(maxValueWaterLevel - minValueWaterLevel))*100.0;
  WaterLevel = constrain(WaterLevel, 0, 100);
  addSensor(WaterValue,"WaterLevel");
  // Adăugăm senzorul de ploaie
  float rainValue = digitalRead(rainPin);
  addSensor(rainValue,"rain");
  // Afișăm datele necesare pentru verificare
  for(int i = 0; i < numberOfSensors-1; i++) {
    Serial.printf("Senzor ID: %d, Tip senzor: %s, Valoare: %.2f\n", id_sensors[i], types[i].c_str(), values[i]);
  }
  // Citim data și ora curentă
  DateTime now = rtc.now();
  Serial.printf("Data: %02d/%02d/%04d Ora: %02d:%02d:%02d\n", now.day(), now.month(), now.year(), now.hour(), now.minute(), now.second());
  insertDataToDB();
  getWateringType(humiditySoil, rainValue, WaterLevel );
  // Resetăm pentru următorul ciclu
  numberOfSensors = 1;
  delay(5000); 
}