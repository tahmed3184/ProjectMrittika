#include <WiFi.h>
#include <HTTPClient.h>
#include <ModbusMaster.h>
#include "secrets.h"

// WiFi credentials and server URL now live in secrets.h (gitignored).
// Copy secrets.example.h to secrets.h and fill in your own values.
const char* ssid = WIFI_SSID;
const char* password = WIFI_PASSWORD;

// PHP URL for sending data
String URL = SERVER_URL; // URL to send data

// Pin configuration for RS485
const int RE_PIN = 4; // Define RE (Receive Enable) pin for RS485
const int DE_PIN = 5; // Define DE (Driver Enable) pin for RS485

ModbusMaster node; // Create ModbusMaster object

void setup() {
  Serial.begin(9600); // Initialize Serial communication at 9600 baud rate

  // Connect to WiFi
  connectWiFi();

  // Initialize the RS485 control pins and set to receive mode
  pinMode(RE_PIN, OUTPUT); // Set RE_PIN as output
  pinMode(DE_PIN, OUTPUT); // Set DE_PIN as output
  digitalWrite(DE_PIN, LOW); // Set DE_PIN to low (receive mode)
  digitalWrite(RE_PIN, LOW); // Set RE_PIN to low (receive mode)

  // Initialize Modbus master with slave ID
  Serial2.begin(4800, SERIAL_8N1, 16, 17); // Initialize Serial2 for Modbus communication
  node.begin(1, Serial2); // Set Modbus slave ID to 1

  // Set Modbus callbacks for RS485 transmission direction
  node.preTransmission(preTransmission); // Set callback before transmission
  node.postTransmission(postTransmission); // Set callback after transmission

  Serial.println("Modbus communication setup complete."); // Debug message
}

// Assuming bulk density of the soil in kg/m³
const float bulkDensity = 1.2;  // Change this value based on your actual soil conditions

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi(); // Reconnect to WiFi if disconnected
  }

  uint8_t result = node.readHoldingRegisters(0x0000, 7); // Read 7 holding registers starting from address 0x0000

  if (result == node.ku8MBSuccess) {
    // Retrieve and process sensor data
    float humidity = node.getResponseBuffer(0) / 10.0; // Get humidity value
    float temperature = node.getResponseBuffer(1) / 10.0; // Get temperature value
    float conductivity = node.getResponseBuffer(2); // Get conductivity value
    float ph = node.getResponseBuffer(3) / 10.0; // Get pH value
    float nitrogen = node.getResponseBuffer(4); // Get nitrogen value
    float phosphorus = node.getResponseBuffer(5); // Get phosphorus value
    float potassium = node.getResponseBuffer(6); // Get potassium value
    
    // Convert mg/kg to kg/ha using the formula: kg/ha = mg/kg * bulkDensity * 10,000
    float nitrogen_kg_ha = (nitrogen* bulkDensity * 0.3*10);
    float phosphorus_kg_ha = (phosphorus* bulkDensity *0.3* 10)/10;
    float potassium_kg_ha = (potassium* bulkDensity *0.3*10)/10;

    // Post data to the server
    String postData = "temperature=" + String(temperature) +
                      "&humidity=" + String(humidity) +
                      "&conductivity=" + String(conductivity) +
                      "&ph=" + String(ph) +
                      "&nitrogen=" + String(nitrogen_kg_ha) +
                      "&phosphorus=" + String(phosphorus_kg_ha) +
                      "&potassium=" + String(potassium_kg_ha);

    HTTPClient http; // Create HTTPClient object
    http.begin(URL); // Specify the URL
    http.addHeader("Content-Type", "application/x-www-form-urlencoded"); // Set content type

    delay(1000); // Short delay before sending data

    int httpCode = http.POST(postData); // Send POST request
    String payload = http.getString(); // Get server response

    // Debugging output
    Serial.println("Data sent to server:");
    Serial.println(postData); // Print sent data
    Serial.println("HTTP Code: " + String(httpCode)); // Print HTTP response code
    Serial.println("Server Response: " + payload); // Print server response

    http.end(); // Close connection
  } else {
    printModbusError(result); // Print Modbus error if communication fails
  }

  delay(30000); // Wait 40 seconds before next reading
}

// Function to connect to WiFi
void connectWiFi() {
  WiFi.mode(WIFI_STA); // Set WiFi mode to station
  WiFi.begin(ssid, password); // Connect to WiFi with provided credentials

  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500); // Wait half a second
    Serial.print("."); // Print dot while connecting
  }
  Serial.println("\nWiFi connected"); // Print message when connected
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP()); // Print local IP address
}

// RS485 transmission control
void preTransmission() {
  digitalWrite(RE_PIN, HIGH); // Set RE_PIN to high (transmit mode)
  digitalWrite(DE_PIN, HIGH); // Set DE_PIN to high (transmit mode)
}

void postTransmission() {
  digitalWrite(RE_PIN, LOW); // Set RE_PIN to low (receive mode)
  digitalWrite(DE_PIN, LOW); // Set DE_PIN to low (receive mode)
}

// Error handling for Modbus communication
void printModbusError(uint8_t errNum) {
  switch (errNum) {
    case node.ku8MBSuccess:
      Serial.println(F("Success")); // No error
      break;
    case node.ku8MBIllegalFunction:
      Serial.println(F("Illegal Function Exception")); // Illegal function error
      break;
    case node.ku8MBIllegalDataAddress:
      Serial.println(F("Illegal Data Address Exception")); // Illegal data address error
      break;
    case node.ku8MBIllegalDataValue:
      Serial.println(F("Illegal Data Value Exception")); // Illegal data value error
      break;
    case node.ku8MBSlaveDeviceFailure:
      Serial.println(F("Slave Device Failure")); // Slave device failure error
      break;
    case node.ku8MBInvalidSlaveID:
      Serial.println(F("Invalid Slave ID")); // Invalid slave ID error
      break;
    case node.ku8MBInvalidFunction:
      Serial.println(F("Invalid Function")); // Invalid function error
      break;
    case node.ku8MBResponseTimedOut:
      Serial.println(F("Response Timed Out - Check wiring, sensor ID, or baud rate.")); // Response timed out error
      break;
    case node.ku8MBInvalidCRC:
      Serial.println(F("Invalid CRC")); // Invalid CRC error
      break;
    default:
      Serial.println(F("Unknown Error")); // Unknown error
      break;
  }
}
