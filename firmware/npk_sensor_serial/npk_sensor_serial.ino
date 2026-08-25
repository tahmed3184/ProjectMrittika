#include <ModbusMaster.h>

// Pin configuration for RS485
const int RE_PIN = 4;  // RE control pin (for RS485)
const int DE_PIN = 5;  // DE control pin (for RS485)

ModbusMaster node;

// Put the RS485 transceiver into transmit mode
void preTransmission() {
  digitalWrite(RE_PIN, HIGH);
  digitalWrite(DE_PIN, HIGH);
}

// Put the RS485 transceiver into receive mode
void postTransmission() {
  digitalWrite(RE_PIN, LOW);
  digitalWrite(DE_PIN, LOW);
}

void setup() {
  // Start serial communication for debugging
  Serial.begin(9600);

  // Initialize the RS485 control pins and set to receive mode
  pinMode(RE_PIN, OUTPUT);
  pinMode(DE_PIN, OUTPUT);
  digitalWrite(DE_PIN, LOW);
  digitalWrite(RE_PIN, LOW);

  // Use HardwareSerial (Serial2) for RS485 communication
  Serial2.begin(4800, SERIAL_8N1, 16, 17); // RX=16, TX=17

  // Initialize Modbus master with slave ID
  node.begin(1, Serial2);  // Use Serial2 for Modbus communication

  // Set Modbus callbacks for controlling RS485 transmission direction
  node.preTransmission(preTransmission);
  node.postTransmission(postTransmission);

  Serial.println("Modbus communication setup complete.");
}

// Assuming bulk density of the soil in kg/m³
const float bulkDensity = 1.2;  // Change this value based on your actual soil conditions

void loop() {
  uint8_t result;

  // Request data from 7 registers starting at address 0x0000
  result = node.readHoldingRegisters(0x0000, 7);

  if (result == node.ku8MBSuccess) {
    // Correctly assign the sensor values from the response buffer
    float humidity = node.getResponseBuffer(0) / 10.0;        // Humidity (% RH)
    float temperature = node.getResponseBuffer(1) / 10.0;     // Temperature (°C)
    float ec = node.getResponseBuffer(2);                     // Electrical Conductivity (uS/cm)
    float ph = node.getResponseBuffer(3) / 10.0;              // pH value
    float nitrogen_mg_kg = node.getResponseBuffer(4);         // Nitrogen in mg/kg
    float phosphorus_mg_kg = node.getResponseBuffer(5);       // Phosphorus in mg/kg
    float potassium_mg_kg = node.getResponseBuffer(6);        // Potassium in mg/kg

    // Convert mg/kg to kg/ha using the formula: kg/ha = mg/kg * bulkDensity * 10,000
    float nitrogen_kg_ha = (nitrogen_mg_kg * bulkDensity * .3*10);
    float phosphorus_kg_ha = (phosphorus_mg_kg * bulkDensity *.3* 10);
    float potassium_kg_ha = (potassium_mg_kg * bulkDensity *.3*10);

    // Display the sensor values on the Serial Monitor
    Serial.println("\nNPK Sensor Data:");
    delay(1000);
    Serial.print("Soil Temperature: "); Serial.print(temperature); Serial.println(" °C");
    Serial.print("Soil Humidity: "); Serial.print(humidity); Serial.println(" %");
    Serial.print("Electrical Conductivity: "); Serial.print(ec); Serial.println(" uS/cm");
    Serial.print("pH: "); Serial.println(ph);
    Serial.print("Nitrogen: "); Serial.print(nitrogen_kg_ha); Serial.println(" kg/ha");
    Serial.print("Phosphorus: "); Serial.print(phosphorus_kg_ha); Serial.println(" kg/ha");
    Serial.print("Potassium: "); Serial.print(potassium_kg_ha); Serial.println(" kg/ha");

  } else {
    // Handle Modbus errors
    printModbusError(result);
  }

  delay(10000);  // Delay between data reads
}


void printModbusError(uint8_t errNum) {
  switch (errNum) {
    case node.ku8MBSuccess:
      Serial.println(F("Success"));
      break;
    case node.ku8MBIllegalFunction:
      Serial.println(F("Illegal Function Exception"));
      break;
    case node.ku8MBIllegalDataAddress:
      Serial.println(F("Illegal Data Address Exception"));
      break;
    case node.ku8MBIllegalDataValue:
      Serial.println(F("Illegal Data Value Exception"));
      break;
    case node.ku8MBSlaveDeviceFailure:
      Serial.println(F("Slave Device Failure"));
      break;
    case node.ku8MBInvalidSlaveID:
      Serial.println(F("Invalid Slave ID"));
      break;
    case node.ku8MBInvalidFunction:
      Serial.println(F("Invalid Function"));
      break;
    case node.ku8MBResponseTimedOut:
      Serial.println(F("Response Timed Out - Check wiring, sensor ID, or baud rate."));
      break;
    case node.ku8MBInvalidCRC:
      Serial.println(F("Invalid CRC"));
      break;
    default:
      Serial.println(F("Unknown Error"));
      break;
  }
}
