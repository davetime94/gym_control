#include <Adafruit_Fingerprint.h>

#if (defined(__AVR__) || defined(ESP8266)) && !defined(__AVR_ATmega2560__)
// Para UNO y otros sin hardware serial...
// pin #2 es IN del sensor (cable VERDE)
// pin #3 es OUT de arduino  (cable BLANCO)
SoftwareSerial mySerial(2, 3);
#else
// Para Leonardo/Mega, etc.
#define mySerial Serial1
#endif

Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

// Inicializamos el ID en 1 de forma global
uint8_t id = 1; 

void setup() {
  Serial.begin(9600);
  while (!Serial)
    ; 
  delay(100);
  Serial.println("\n\nIniciando sensor de huellas Adafruit");

  finger.begin(57600);

  if (finger.verifyPassword()) {
    Serial.println("¡Sensor de huellas encontrado!");
  } else {
    Serial.println("No se encontro el sensor :(");
    while (1) {
      delay(1);
    }
  }
}

void loop() {
  Serial.println("\n=========================================");
  Serial.println("¡Listo para registrar una nueva huella!");
  Serial.print("Por favor, coloca el dedo. Se guardara en el ID #");
  Serial.println(id);
  Serial.println("=========================================");

  // Se queda en este bucle hasta que la huella se registre con éxito
  while (!getFingerprintEnroll())
    ;
    
  // Si salió del bucle, el registro fue un éxito, así que preparamos el siguiente ID
  id++; 
  
  // Límite de seguridad, los sensores básicos soportan hasta 127 o 162 huellas
  if(id > 127) {
    Serial.println("\n¡ATENCION! Se ha alcanzado la capacidad maxima del sensor.");
    while(1); // Detiene el programa
  }
  
  // Pausa de 3 segundos para que retires la mano antes del siguiente registro
  delay(3000); 
}

// Cambiamos el retorno a 'bool' para que 'false' signifique error y 'true' exito
bool getFingerprintEnroll() {
  int p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    switch (p) {
    case FINGERPRINT_OK:
      Serial.println("Imagen tomada");
      break;
    case FINGERPRINT_NOFINGER:
      // Esperando...
      break;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Error de comunicacion");
      break;
    case FINGERPRINT_IMAGEFAIL:
      Serial.println("Error de imagen");
      break;
    default:
      Serial.println("Error desconocido");
      break;
    }
  }

  p = finger.image2Tz(1);
  switch (p) {
  case FINGERPRINT_OK:
    Serial.println("Imagen convertida");
    break;
  case FINGERPRINT_IMAGEMESS:
    Serial.println("Imagen demasiado borrosa");
    return false;
  case FINGERPRINT_PACKETRECIEVEERR:
    Serial.println("Error de comunicacion");
    return false;
  case FINGERPRINT_FEATUREFAIL:
  case FINGERPRINT_INVALIDIMAGE:
    Serial.println("No se pudieron encontrar caracteristicas de la huella");
    return false;
  default:
    Serial.println("Error desconocido");
    return false;
  }

  Serial.println("Por favor, quita el dedo");
  delay(2000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
  }
  
  Serial.println("Vuelve a colocar el mismo dedo");
  p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    switch (p) {
    case FINGERPRINT_OK:
      Serial.println("Imagen tomada");
      break;
    case FINGERPRINT_NOFINGER:
      // Esperando...
      break;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Error de comunicacion");
      break;
    case FINGERPRINT_IMAGEFAIL:
      Serial.println("Error de imagen");
      break;
    default:
      Serial.println("Error desconocido");
      break;
    }
  }

  p = finger.image2Tz(2);
  switch (p) {
  case FINGERPRINT_OK:
    Serial.println("Imagen convertida");
    break;
  case FINGERPRINT_IMAGEMESS:
    Serial.println("Imagen demasiado borrosa");
    return false;
  case FINGERPRINT_PACKETRECIEVEERR:
    Serial.println("Error de comunicacion");
    return false;
  case FINGERPRINT_FEATUREFAIL:
  case FINGERPRINT_INVALIDIMAGE:
    Serial.println("No se pudieron encontrar caracteristicas de la huella");
    return false;
  default:
    Serial.println("Error desconocido");
    return false;
  }

  Serial.print("Creando modelo... ");
  p = finger.createModel();
  if (p == FINGERPRINT_OK) {
    Serial.println("¡Las huellas coinciden!");
  } else if (p == FINGERPRINT_ENROLLMISMATCH) {
    Serial.println("Las huellas no coinciden");
    return false;
  } else {
    Serial.println("Error de comunicacion o desconocido");
    return false;
  }

  p = finger.storeModel(id);
  
  if (p == FINGERPRINT_OK) {
    // MENSAJE FINAL DESTACADO PARA LA BASE DE DATOS
    Serial.println("\n***************************************************");
    Serial.print("✅ ¡EXITO! Ingresa este codigo en la Base de Datos: ");
    Serial.println(id);
    Serial.println("***************************************************\n");
    return true; // Retorna verdadero para que el loop incremente el ID
  } else {
    Serial.println("Error al guardar en la memoria del sensor");
    return false;
  }
}