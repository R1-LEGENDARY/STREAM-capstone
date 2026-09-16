#include <EEPROM.h>

#include <Wire.h> 
#include <LiquidCrystal_I2C.h>
#include "OneWireNg_CurrentPlatform.h"
#include "drivers/DSTherm.h"
#include "utils/Placeholder.h"

//======================================
#include <Arduino.h>

#define VREF    5000
#define ADC_RES 1024
//======================================

static Placeholder<OneWireNg_CurrentPlatform> ow;
DSTherm drv(ow);

LiquidCrystal_I2C lcd(0x27,16,2);

// WiFi credentials
const char* WIFI_SSID = "YOUR_WIFI_NAME";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// Server endpoint for uploads
const char* POST_PATH = "/";
const char* SERVER_NAME = "YOUR_SERVER_IP";
const int SERVER_PORT = 5000;

// Polling interval and retry limit
const long POST_INTERVAL = 5000;
const int MAX_FAILURES = 3;

// Sensor pins
const int PIN_PH   = 17;
const int PIN_DO   = 18;
const int PIN_TEMP = 19;

const byte PIN_BTN_UPLOAD  = 6;
const byte PIN_LED_TX_DATA  = 14;
const byte PIN_LED_HB  = 13;
const byte PIN_LED_WIFI  = 15;
const byte PIN_BUZZ    = 16;

// pH calibration points
const float PH_ADC_4_01 = 900;
const float PH_ADC_7_00 = 786;
const float PH_ADC_9_18 = 721;

float ph_f;
float temp_f;
unsigned int temp_g;

unsigned int DO_g;
unsigned int DO_f;
unsigned long DO_ADC_Voltage_mv;
uint16_t ADC_Raw;

byte phx10_g;
char buf[17]="";

unsigned long sm_timer;
byte sm_state;
byte prev_state;

// Runtime state
long lastPostTime = 0;
int failureCount = 0;
unsigned long state_start_time = 0;
float temp, ph;
unsigned int DO;
String espResponse = "";

unsigned long last_unpressed;
bool flag_button;

// State machine for the entire process
enum SystemState 
{
  STATE_INIT,
  STATE_WAIT_FOR_READY,
  STATE_SET_CWMODE,
  STATE_WAIT_FOR_CWMODE_OK,
  STATE_JOIN_WIFI,
  STATE_WAIT_FOR_WIFI_OK,
  STATE_WIFI_CONNECTED,
  STATE_READ_SENSORS,
  STATE_START_TCP,
  STATE_WAIT_FOR_TCP_CONNECT,
  STATE_SEND_PAYLOAD,
  STATE_WAIT_FOR_POST_RESPONSE,
  STATE_CLOSE_TCP,
  STATE_WAIT_FOR_TCP_CLOSED,
  STATE_FAILED,
  STATE_RESET_ESP,
  STATE_DISPLAY_SENSOR_VAL

};

SystemState currentState = STATE_INIT;


// Two-point calibration is enabled.
#define TWO_POINT_CALIBRATION 1

// Dissolved oxygen calibration points
#define CAL1_V (517)
#define CAL1_T (38)
#define CAL2_V (312)
#define CAL2_T (8)

const uint16_t DO_Table[41] = 
{
  14460, 14220, 13820, 13440, 13090, 12740, 12420, 12110, 11810, 11530,
  11260, 11010, 10770, 10530, 10300, 10080, 9860, 9660, 9460, 9270,
  9080, 8900, 8730, 8570, 8410, 8250, 8110, 7960, 7820, 7690,
  7560, 7430, 7300, 7180, 7070, 6950, 6840, 6730, 6630, 6530, 6410
};

int16_t readDO(uint32_t voltage_mv, uint8_t temperature_c)
{
#if TWO_POINT_CALIBRATION == 0
  uint16_t V_saturation = (uint32_t)CAL1_V + (uint32_t)35 * temperature_c - (uint32_t)CAL1_T * 35;
  return (voltage_mv * DO_Table[temperature_c] / V_saturation);
#else
  uint16_t V_saturation = (int16_t)((int8_t)temperature_c - CAL2_T) * ((uint16_t)CAL1_V - CAL2_V) / ((uint8_t)CAL1_T - CAL2_T) + CAL2_V;
  return (voltage_mv * DO_Table[temperature_c] / V_saturation);
#endif
}


void heartbeat()
{
  static unsigned long t;
  if(millis() - t < 500) return;
  t = millis();

   digitalWrite(PIN_LED_HB, !digitalRead(PIN_LED_HB)); 
}



void button_reader()
{
   static unsigned long t,t_unpress;
  if (millis() - t < 1) return;
  t = millis(); 

  if(digitalRead(PIN_BTN_UPLOAD) ==LOW)
  {
    if(millis() - t_unpress >= 1)
    {
      flag_button = true;  
      digitalWrite(PIN_BUZZ,HIGH);
      delay(10);
      digitalWrite(PIN_BUZZ,LOW);
      while(digitalRead(PIN_BTN_UPLOAD) ==LOW){}
    }
  }
  else
  {
    t_unpress = millis();  
  }
}

void doroutine()
{
  static unsigned long t;
  if (millis() - t < 1000) return;
  t = millis();


  


  ADC_Raw = 0;
  for(byte i=0;i<10;i++)
  { 
   ADC_Raw += analogRead(PIN_DO);
  }

  ADC_Raw /= 10;  
  DO_ADC_Voltage_mv = uint32_t(VREF) * ADC_Raw / ADC_RES;
  DO_g = readDO(DO_ADC_Voltage_mv, (uint8_t)temp_f); // unit: ug/L
  DO_f = (DO_g/1000); //unit:mg/L





}
//-------------------------------------------------------------------

float getTemperature()
{  /* convert temperature on all sensors connected... */
  drv.convertTempAll(DSTherm::MAX_CONV_TIME);
  static Placeholder<DSTherm::Scratchpad> scrpd;
  OneWireNg::ErrorCode ec = drv.readScratchpadSingle(scrpd);
  DSTherm::Scratchpad scrpd2=scrpd;
  if (ec == OneWireNg::EC_SUCCESS)
  {
      return scrpd2.getTemp()/1000.0;
  }
  else if (ec == OneWireNg::EC_CRC_ERROR)
  {
    return OneWireNg::EC_CRC_ERROR;
  }
  else return 0;
}
void temp_routine()
{
  temp_f = getTemperature();
 //Serial.print("Temp:"); Serial.println(temp_f)
  temp_g = (unsigned int)(temp_f*100); 
}


//------------------------------------------------------------------



void read_ph()
{
  static unsigned long timer_ph;
  if (millis() - timer_ph < 500) return;
  timer_ph = millis();

  

  
  float adc_val,phx10f;
  adc_val= 0;
  for (byte i = 0; i < 10; i++)
  {
   adc_val += (float)analogRead(PIN_PH);
  }
  adc_val /= 10;

Serial.print("adc:"); Serial.println(adc_val);

    float m,b;
  if(adc_val <= PH_ADC_7_00) //9-7 curve
  {
     m = (9.18 - 7.0) / (PH_ADC_9_18 - PH_ADC_7_00);

    b = 7.0 - (m*PH_ADC_7_00);
    ph_f = m*adc_val + b;
  }
  else //7 - 4 curve
  {
    m = (7.0 - 4.01) / (PH_ADC_7_00 - PH_ADC_4_01);
    b = 7.0 - (m*PH_ADC_7_00);
    ph_f = m*adc_val + b;

  }


  phx10_g = (byte)(ph_f*10);
  // Serial.print("ph:");Serial.println(ph_f);
}



// State machine helpers
void tx_data();
void readSensorData();
void resetState();

// Read ESP-01 replies and echo them for debugging.
void tx_data() 
{  
  while (Serial1.available()) 
  {
    char inChar = Serial1.read();
    Serial.write(inChar);
    espResponse += inChar;
  }
}

// Copy the latest sensor values into the shared state variables.
void readSensorData() 
{ 
  DO   = DO_g;
  ph   = ph_f;
  temp = temp_f;
}

// Reset the serial buffer and timer for the current state.
void resetState() 
{
  espResponse = "";
  state_start_time = millis();
}

// Handle WiFi and TCP state transitions.
void tcp_update() 
{

  if(flag_button) 
  {
    flag_button = false;
    Serial.println("Button pressed! Forcing immediate TCP send...");
    lcd.setCursor(0,0); lcd.print("FORCE SEND      ");
    lcd.setCursor(0,1); lcd.print("TO TCP SERVER...");
    digitalWrite(PIN_LED_TX_DATA, HIGH);
    delay(2000);
    digitalWrite(PIN_LED_TX_DATA, LOW);
    // Reset states to force TCP send
    resetState();
    currentState = STATE_START_TCP;  // jump directly to TCP start
  }


    switch (currentState) 
    {
      case STATE_INIT:
        Serial.println("\nSystem Initialized. Resetting ESP-01...");

        Serial1.println("AT+RST");

        lcd.setCursor(0,0);lcd.print("SYSTEM          ");
        lcd.setCursor(0,1);lcd.print("INITIALIZING... ");
      
        resetState();
        currentState = STATE_WAIT_FOR_READY;
      break;

      case STATE_WAIT_FOR_READY:
        if (millis() - state_start_time > 5000) 
        {
          Serial.println("\nESP-01 not ready. Retrying...");
          currentState = STATE_INIT;
          lcd.setCursor(0,0);lcd.print("RECONNECTING... ");
          lcd.setCursor(0,1);lcd.print("PLEASE WAIT!    ");
          digitalWrite(PIN_LED_WIFI, LOW);
        }
        if (espResponse.indexOf("ready") != -1) 
        {
          Serial.println("\nESP-01 ready.");
          lcd.setCursor(0,0);lcd.print("     READY!!    ");
          lcd.setCursor(0,1);lcd.print("                ");
          resetState();
          currentState = STATE_SET_CWMODE; 
          digitalWrite(PIN_LED_WIFI, LOW);     
        }
      break;

      case STATE_SET_CWMODE:
        Serial.println("Setting CWMODE...");
        Serial1.println("AT+CWMODE=1");
        lcd.setCursor(0,0);lcd.print("SETTING ESP32   ");
        lcd.setCursor(0,1);lcd.print("AS A WIFI CLIENT");
        resetState();
        currentState = STATE_WAIT_FOR_CWMODE_OK;
        digitalWrite(PIN_LED_WIFI, LOW);
      
      break;

      case STATE_WAIT_FOR_CWMODE_OK:
        if (millis() - state_start_time > 2000) 
        {
          Serial.println("Failed to set CWMODE. Retrying.");
          lcd.setCursor(0,0);lcd.print("FAILED!!        ");
          lcd.setCursor(0,1);lcd.print("RECONNECTING... ");
          currentState = STATE_FAILED;
          digitalWrite(PIN_LED_WIFI, LOW);
        }
        if (espResponse.indexOf("OK") != -1) 
        {
          Serial.println("CWMODE set. Joining WiFi network...");
          lcd.setCursor(0,0);lcd.print("CONNECTING TO   ");
          lcd.setCursor(0,1);lcd.print("WIFI...         ");      
          resetState();
          currentState = STATE_JOIN_WIFI;
          digitalWrite(PIN_LED_WIFI, LOW);
        }
      break;

      case STATE_JOIN_WIFI:
        {
          String join_cmd = "AT+CWJAP=\"" + String(WIFI_SSID) + "\",\"" + String(WIFI_PASSWORD) + "\"\r\n";
          Serial1.print(join_cmd);
          resetState();
          currentState = STATE_WAIT_FOR_WIFI_OK;
          digitalWrite(PIN_LED_WIFI, LOW);
        }
      break;

      case STATE_WAIT_FOR_WIFI_OK:
        if (millis() - state_start_time > 15000) 
        {
          Serial.println("Failed to connect to WiFi. Timed out.");
          lcd.setCursor(0,0);lcd.print("FAILED!! T.OUT  ");
          lcd.setCursor(0,1);lcd.print("RECONNECTING... ");
          currentState = STATE_FAILED;
          digitalWrite(PIN_LED_WIFI, LOW);
        }
        if (espResponse.indexOf("OK") != -1) 
        {
          Serial.println("WiFi Connected!");
          lcd.setCursor(0,0);lcd.print("WIFI CONNECTED  ");
          lcd.setCursor(0,1);lcd.print("                ");
          digitalWrite(PIN_LED_WIFI, HIGH);
          resetState();
          currentState = STATE_WIFI_CONNECTED;
        } 
        else if (espResponse.indexOf("FAIL") != -1 || espResponse.indexOf("ERROR") != -1) 
        {
          Serial.println("WiFi connection failed. Re-attempting.");
          lcd.setCursor(0,0);lcd.print("WIFI FAILED!    ");
          lcd.setCursor(0,1);lcd.print("RECONNECTING... ");
          currentState = STATE_FAILED;
          digitalWrite(PIN_LED_WIFI, LOW);
        }
      break;

      case STATE_WIFI_CONNECTED:
        Serial.println("Ready to send data.");
        lcd.setCursor(0,0);lcd.print("READY TO SEND   ");
        lcd.setCursor(0,1);lcd.print("DATA            ");
        resetState();
        currentState = STATE_READ_SENSORS;
      break;

      case STATE_READ_SENSORS:
        if (millis() - lastPostTime >= POST_INTERVAL) 
        {
          readSensorData();
          Serial.println("Starting POST request...");
          lcd.setCursor(0,0);lcd.print("STARTING POST   ");
          lcd.setCursor(0,1);lcd.print("REQUEST         ");
          currentState = STATE_START_TCP;
        }

        sprintf(buf,"DO=%05d ug/L   ",DO_g);
        lcd.setCursor(0,0); lcd.print(buf);

        sprintf(buf,"T=%02d.%02dC PH=%02d.%d",(temp_g/100),(temp_g%100),(phx10_g/10),phx10_g%10);
        lcd.setCursor(0,1);lcd.print(buf);

      break;

      case STATE_START_TCP:
        {
          String cipstart_cmd = "AT+CIPSTART=\"TCP\",\"" + String(SERVER_NAME) + "\"," + String(SERVER_PORT) + "\r\n";
          Serial.print("Attempting to connect to TCP server... ");
          lcd.setCursor(0,0);lcd.print("RECONNECTING TO ");
          lcd.setCursor(0,1);lcd.print("TCP SERVER...   ");
          Serial1.print(cipstart_cmd);
          resetState();
          currentState = STATE_WAIT_FOR_TCP_CONNECT;
        }
      break;

      case STATE_WAIT_FOR_TCP_CONNECT:
        if (millis() - state_start_time > 5000)
        {
          Serial.println("TCP connection failed or timed out.");
          lcd.setCursor(0,0);lcd.print("TCP CONNECTION  ");
          lcd.setCursor(0,1);lcd.print("FAILED!         ");
          currentState = STATE_FAILED;
        }
        if (espResponse.indexOf("CONNECT") != -1) 
        {
          Serial.println("Connected!");
          resetState();
          currentState = STATE_SEND_PAYLOAD;       
        } 
        else if (espResponse.indexOf("ERROR") != -1) 
        {
          Serial.println("TCP connection error.");
          lcd.setCursor(0,0);lcd.print("TCP CONNECTION  ");
          lcd.setCursor(0,1);lcd.print("ERROR!          ");
          currentState = STATE_FAILED;
        }
      break;


      case STATE_SEND_PAYLOAD:
        {
          uint32_t t;
          uint8_t dd, hh, mm, ss;
          char timestampstr[16]="";
          t = millis() / 1000;
          ss = t % 60;
          mm = (t/60) % 60;
          hh = (t/3600) % 24;
          dd = (t/(3600*24)) % 100;
          sprintf(timestampstr,"\"%02d %02d:%02d:%02d\"", dd, hh, mm, ss);

          float do_mgL = DO_g / 100.0;
          String json_payload = "{\"TS\":" + String(timestampstr) + ",\"DO\":" + String(do_mgL, 2) + ",\"pH\":" + String(ph, 2) + ",\"Temperature\":" + String(temp, 2) + "}";
          String post_request = "POST " + String(POST_PATH) + " HTTP/1.1\r\n";
          post_request += "Host: " + String(SERVER_NAME) + "\r\n";
          post_request += "Content-Type: application/json\r\n";
          post_request += "Content-Length: " + String(json_payload.length()) + "\r\n";
          post_request += "\r\n";
          post_request += json_payload;

          Serial.println("Sending data...");
          Serial1.print("AT+CIPSEND=" + String(post_request.length()) + "\r\n");
          resetState();
          // Wait for the ESP-01 prompt before sending the payload.
          while(espResponse.indexOf(">") == -1) 
          {
            tx_data();
              if (millis() - state_start_time > 2000) 
              {
                Serial.println("ESP-01 not ready to send data. Timed out.");
                lcd.setCursor(0,0);lcd.print("ESP32 NOT READY ");
                lcd.setCursor(0,1);lcd.print("TO SEND DATA    ");
                currentState = STATE_FAILED;
                digitalWrite(PIN_LED_WIFI, LOW);
                return;
              }
          }
          Serial1.print(post_request);
          resetState();
          currentState = STATE_WAIT_FOR_POST_RESPONSE;
        }
      break;

      case STATE_WAIT_FOR_POST_RESPONSE:
        if (millis() - state_start_time > 5000) 
        {
          Serial.println("No response from server. Assuming success.");
          lcd.setCursor(0,0);lcd.print("NO RESPONSE FROM");
          lcd.setCursor(0,1);lcd.print("SERVER          ");
          currentState = STATE_CLOSE_TCP;
        }
        if (espResponse.indexOf("OK") != -1 || espResponse.indexOf("HTTP/1.1 200 OK") != -1) 
        {
          Serial.println("\nPOST request sent successfully.");
          lcd.setCursor(0,0);lcd.print("POST REQUEST    ");
          lcd.setCursor(0,1);lcd.print("SENT!           ");

          digitalWrite(PIN_LED_TX_DATA, HIGH); 
          digitalWrite(PIN_BUZZ, HIGH);
          delay(200);
          digitalWrite(PIN_BUZZ, LOW);
          digitalWrite(PIN_LED_TX_DATA, LOW); 
          currentState = STATE_CLOSE_TCP;
        }
      break;

      case STATE_CLOSE_TCP:
        Serial.println("Closing connection...");
        lcd.setCursor(0,0);lcd.print("CLOSING         ");
        lcd.setCursor(0,1);lcd.print("CONNECTION...   ");
        Serial1.println("AT+CIPCLOSE");
        resetState();
        currentState = STATE_WAIT_FOR_TCP_CLOSED;
      break;

      case STATE_WAIT_FOR_TCP_CLOSED:
        if (millis() - state_start_time > 2000) 
        {
          Serial.println("Timed out waiting for connection to close.");
          lcd.setCursor(0,0);lcd.print("TIMEOUT! WAITING");
          lcd.setCursor(0,1);lcd.print("CONNECTION CLOSE"); 
          currentState = STATE_FAILED;
          digitalWrite(PIN_LED_WIFI, LOW);
        }
        if (espResponse.indexOf("CLOSED") != -1) 
        {
          Serial.println("Connection closed. Request successful.");
          lcd.setCursor(0,0);lcd.print("CONNECTION      ");
          lcd.setCursor(0,1);lcd.print("CLOSED          ");
          failureCount = 0; // Reset failure count on success
          lastPostTime = millis();
          resetState();
          currentState = STATE_DISPLAY_SENSOR_VAL;
        }
      break;

      case STATE_DISPLAY_SENSOR_VAL:
        sprintf(buf,"DO=%05d ug/L   ",DO_g);
        lcd.setCursor(0,0); lcd.print(buf);
        sprintf(buf,"T=%02d.%02dC PH=%02d.%d",(temp_g/100),(temp_g%100),(phx10_g/10),phx10_g%10);
        lcd.setCursor(0,1);lcd.print(buf);

        currentState = STATE_READ_SENSORS;
      break;

      case STATE_FAILED:

        failureCount++;
        Serial.print("\nRequest failed. Consecutive failures: ");
        Serial.println(failureCount);
        lcd.setCursor(0,0);lcd.print("REQUEST FAILED  ");
        lcd.setCursor(0,1);lcd.print("CONSECUTIVE FAIL");
        digitalWrite(PIN_LED_WIFI, LOW);

        if (failureCount >= MAX_FAILURES) 
        {
          Serial.println("Maximum failures reached. Initiating ESP-01 reset.");
          lcd.setCursor(0,0);lcd.print("MAX FAILURES    ");
          lcd.setCursor(0,1);lcd.print("REACHED!        ");
          currentState = STATE_RESET_ESP;
          digitalWrite(PIN_LED_WIFI, LOW);
        } 
        else 
        {
          lastPostTime = millis();
          currentState = STATE_READ_SENSORS;
        }
      break;

      case STATE_RESET_ESP:
        Serial.println("Resetting ESP-01 module...");
        lcd.setCursor(0,0);lcd.print("FAILED!!        ");
        lcd.setCursor(0,1);lcd.print("RECONNECTING... ");
        Serial1.println("AT+RST");
        resetState();
        failureCount = 0; // Reset the counter for the new attempt
        currentState = STATE_WAIT_FOR_READY;
        digitalWrite(PIN_LED_WIFI, LOW);
      break;
    }
}




void setup() 
{
  Serial.begin(115200);
  Serial.println("Serial Monitor Initialized");
  Serial1.begin(115200);
  Serial.println("ESP-01 communication initialized");
  lcd.init();                      
  lcd.backlight();

  pinMode(PIN_BTN_UPLOAD, INPUT_PULLUP);
  pinMode(PIN_LED_TX_DATA, OUTPUT);
  pinMode(PIN_LED_HB, OUTPUT);
  pinMode(PIN_LED_WIFI, OUTPUT);
  pinMode(PIN_BUZZ, OUTPUT);  

  analogReadResolution(10);

  new (&ow) OneWireNg_CurrentPlatform(PIN_TEMP, false);
  
  // Set initial state
  currentState = STATE_INIT;

  digitalWrite(PIN_LED_WIFI, LOW);
  digitalWrite(PIN_LED_TX_DATA, LOW);

  lcd.setCursor(0,0);lcd.print(" STREAM AQUATIC ");
  lcd.setCursor(0,1);lcd.print("   MONITORING   ");


  digitalWrite(PIN_BUZZ, HIGH);
  delay(100);
  digitalWrite(PIN_BUZZ, LOW);
  Serial.println(">");
}

uint32_t t;

void loop() 
{
  button_reader();
  temp_routine();
  doroutine();
  read_ph();
  tx_data();
  tcp_update();
  heartbeat();
}
