import mysql.connector
from mysql.connector import Error
import bcrypt
import getpass
import serial
import time

#  pip install mysql-connector-python bcrypt pyserial pyinstaller


# --- CONFIGURACIÓN DEL ARDUINO ---
# Cambia 'COM3' por el puerto donde conectes tu Arduino. 
# En Linux/Mac suele ser '/dev/ttyUSB0' o similar.
PUERTO_ARDUINO = 'COM5' 
BAUDIOS = 9600

def conectar_bd():
    """Establece conexión a la base de datos gym3"""
    try:
        conexion = mysql.connector.connect(
            host='localhost',
            database='gym3',
            user='root',
            password=''
        )
        if conexion.is_connected():
            return conexion
    except Error as e:
        print(f"Error al conectar con la base de datos: {e}")
    return None

def iniciar_sesion(conexion):
    """
    Verifica las credenciales del admin y retorna su gym_id.
    Asumimos que la tabla users_admin tiene una columna 'gym_id'.
    """
    print("\n" + "="*40)
    print("   SISTEMA DE ACCESO - LOGIN ADMIN")
    print("="*40)
    
    email = input("Email: ")
    password_ingresada = getpass.getpass("Contraseña: ")

    cursor = conexion.cursor(dictionary=True)
    # Seleccionamos la contraseña y el gym_id al que pertenece este administrador
    cursor.execute("SELECT password, gym_id FROM users_admin WHERE email = %s", (email,))
    admin = cursor.fetchone()
    cursor.close()

    if admin:
        hash_db = admin['password']
        if hash_db.startswith('$2y$'):
            hash_db = hash_db.replace('$2y$', '$2b$')

        if bcrypt.checkpw(password_ingresada.encode('utf-8'), hash_db.encode('utf-8')):
            print("\n✅ ¡Inicio de sesión exitoso!")
            return admin['gym_id']
        else:
            print("\n❌ Contraseña incorrecta.")
            return None
    else:
        print("\n❌ Administrador no encontrado.")
        return None

def registrar_log(conexion, member_id, gym_id, acceso_concedido):
    """Inserta el registro de entrada en access_logs"""
    try:
        cursor = conexion.cursor()
        # Insertamos usando NOW() para la fecha_hora actual
        query = """
            INSERT INTO access_logs 
            (member_id, gym_id, acceso_concedido, fecha_hora) 
            VALUES (%s, %s, %s, NOW())
        """
        cursor.execute(query, (member_id, gym_id, acceso_concedido))
        conexion.commit()
        cursor.close()
    except Error as e:
        print(f"Error al guardar el log de acceso: {e}")

def procesar_huella(conexion, arduino, id_huella, gym_id):
    """Busca la huella en la BD, valida el status e inserta el log"""
    print(f"\nProcesando Huella ID: {id_huella}...")
    
    cursor = conexion.cursor(dictionary=True)
    
    # Validamos que la huella exista y pertenezca al gym del administrador logueado
    query_miembro = "SELECT * FROM members WHERE huella = %s AND gym_id = %s"
    cursor.execute(query_miembro, (id_huella, gym_id))
    miembro = cursor.fetchone()
    cursor.close()

    if miembro:
        member_id = miembro['id']
        nombre = miembro['nombre_completo']
        status = miembro['status_licencia']
        
        print(f"Miembro encontrado: {nombre}")
        print(f"Status de Licencia: {status}")

        if status == 'Activa':
            print("🟢 ACCESO PERMITIDO")
            registrar_log(conexion, member_id, gym_id, 1) # 1 = Concedido
            
            # --- ESPACIO PARA ARDUINO ---
            # Aquí le enviamos una señal al Arduino para que abra la puerta o encienda un LED verde
            if arduino:
                arduino.write(b'1') # '1' podría significar abrir puerta en tu código Arduino
                
        elif status == 'Vencida':
            print("🔴 ACCESO DENEGADO: Membresía Vencida.")
            registrar_log(conexion, member_id, gym_id, 0) # 0 = Denegado
            
            # --- ESPACIO PARA ARDUINO ---
            if arduino:
                arduino.write(b'2') # '2' podría significar LED rojo / buzzer
                
        elif status == 'Cancelada':
            print("🔴 ACCESO DENEGADO: Membresía Cancelada.")
            registrar_log(conexion, member_id, gym_id, 0)
            
            # --- ESPACIO PARA ARDUINO ---
            if arduino:
                arduino.write(b'2')
                
    else:
        print(f"🔴 ACCESO DENEGADO: Huella #{id_huella} no encontrada en este Gimnasio.")
        # Como no hay member_id válido, podemos mandar None o Null a la BD
        registrar_log(conexion, None, gym_id, 0)
        
        # --- ESPACIO PARA ARDUINO ---
        if arduino:
            arduino.write(b'0') # '0' huella desconocida

def main():
    conexion = conectar_bd()
    if not conexion:
        return

    try:
        gym_id = iniciar_sesion(conexion)
        if not gym_id:
            return # Salimos si falla el login

        print(f"Operando para el Gimnasio ID: {gym_id}")
        print("Iniciando conexión con el lector de huellas (Arduino)...")
        
        try:
            arduino = serial.Serial(PUERTO_ARDUINO, BAUDIOS, timeout=1)
            time.sleep(2) # Esperar a que el Arduino reinicie tras abrir el puerto
            print("✅ Arduino conectado. Esperando huellas...")
        except serial.SerialException:
            print(f"⚠️ ADVERTENCIA: No se pudo conectar al Arduino en el puerto {PUERTO_ARDUINO}.")
            print("Modo de prueba activado (Sin hardware). Escribe el ID de huella manualmente y presiona Enter.")
            arduino = None

        print("\n" + "="*40)
        print("   SISTEMA DE LECTURA ACTIVO")
        print("="*40)

        while True:
            # Si el hardware está conectado, leemos del puerto serial
            if arduino and arduino.in_waiting > 0:
                linea = arduino.readline().decode('utf-8').strip()
                
                # Asumimos que tu Arduino de lectura imprime algo como "ID: 5"
                if "ID:" in linea:
                    try:
                        # Extraemos el número después de "ID:"
                        id_huella = int(linea.split(":")[1].strip())
                        procesar_huella(conexion, arduino, id_huella, gym_id)
                    except ValueError:
                        pass # Ignorar si la lectura se corrompió
                        
            # Si no hay hardware, podemos hacer pruebas manuales en consola
            elif not arduino:
                try:
                    entrada_manual = input("\nSimular lectura - Ingresa ID de huella (o 'salir'): ")
                    if entrada_manual.lower() == 'salir':
                        break
                    id_huella = int(entrada_manual)
                    procesar_huella(conexion, None, id_huella, gym_id)
                except ValueError:
                    print("Por favor, ingresa un número válido.")

    except KeyboardInterrupt:
        print("\nSistema detenido por el usuario.")
    finally:
        if conexion.is_connected():
            conexion.close()
            print("Conexión a BD cerrada.")
        if 'arduino' in locals() and arduino and arduino.is_open:
            arduino.close()
            print("Conexión con Arduino cerrada.")

if __name__ == "__main__":
    main()