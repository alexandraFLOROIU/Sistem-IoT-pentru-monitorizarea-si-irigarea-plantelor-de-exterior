# SISTEM IOT PENTRU MONITORIZAREA ȘI IRIGAREA PLANTELOR DE EXTERIOR - 💧Smart Plant Irrigation🪴
## Scopul sistemului
  Sistemul realizat are ca scop automatizarea procesului de irigare a plantelor de exterior aflate în ghiveci, în funcție de anotimpul curent, planta selectată și datele colectate de la senzori
## Funcționalități cheie:
- Mecanism de asociere unică a contului de utilizator cu placa de dezvoltare
- Monitorizarea în timp real și controlul de la distanță
- 3 moduri principale de irigare: manuală, automată și periodică, plus un mod implicit
- Irigare adaptată sezonului curent
- Notificări prin e-mail și alerte în interfața web
- Vizualizare grafice și istoric activare irigare
- Exportare date în format csv
## Configurare
### Tool-uri necesare
1. XAMPP https://www.apachefriends.org/
2. Visual Studio Code https://code.visualstudio.com/
3. Arduino IDE https://www.arduino.cc/en/software/

### Setări Arduino IDE
1. Pregătirea mediului pentru a putea programa placa de dezvoltare ESP32 DEVKIT V1 

     - Adăugarea linkului  
       - File -> Preferences -> Settings -> Additional Boards Manager URLs: https://dl.espressif.com/dl/package_esp32
       
     - Instalarea pachetul aferent
       - Tools -> Board -> Boards Manager -> Pachetul ESP32 by Espressif Systems
       
2. Selectarea plăcii de dezvoltare 
   - Tools -> Board -> ESP32 Dev Module
3. Setarea portului corespunzător 
   - Tools -> Port
4. Instalarea manuală a bibliotecilor pentru funcționarea corectă a componentelor hardware utilizate în 
realizarea sistemului Tools -> Manage Libraries  
   - ArduinoJson.h 
      -> Arduinojson by Benoit Blanchon(manipularea datelor în format JSON)
   
   - DHT.h 
      -> DHT sensor library by Adafruit(citirea temperaturii și a umidității relative)
   
   - RTClib.h 
      -> RTClib by Adafruit(gestionarea modulului RTC DS3231)
   
   - Adafruit_VEML7700.h 
      -> Adafruit VEML7700 Library by Adafruit(pentru senzorul de lumină)
   
   - WiFi.h, HTTPClient.h și Wire.h sunt incluse implicit în Arduino IDE și nu necesită instalare
   
6. Setarea parametrilor rețelei Wi-Fi la care va fi conectat dispozitivul ESP
   - linia 9 - numele rețelei Wi-Fi
   - linia 10 - parola corespunzătoare
7. Identificarea adresei IPV4
   - executarea comenzii ipconfig în cmd 
8. Configurarea adresei IP a serverului
   - liniile 11, 12, 13 înlocuire 172.20.10.3 cu adresa IPV4 corespunzătoare
9. Compilarea și încărcarea codului sursă pe placă
   - Sketch -> Upload

### Setări XAMPP
1. Descărcarea arhivei și dezarhivarea acesteia în folderul htdocs din directorul unde a fost instalat XAMPP
2. Pornirea manuală a serviciilor Apache și MySQL din panoul de control XAMPP prin apăsarea butonului Start 
din dreptul fiecăruia
3. Accesarea Admin de la MySQL și crearea unei baze de date numită licenta_db
4. Importarea bazei de date
