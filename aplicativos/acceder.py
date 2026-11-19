import mysql.connector
from mysql.connector import Error
import bcrypt
import getpass
import serial
import time

# --- CONFIGURACIÓN DEL ARDUINO ---
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
    """
    print("\n" + "="*40)
    print("   SISTEMA DE ACCESO - LOGIN ADMIN")
    print("="*40)
    
    email = input("Email: ")
    password_ingresada = getpass.getpass("Contraseña: ")

    cursor = conexion.cursor(dictionary=True)
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
    print(f"\n" + "-"*40)
    print(f"Procesando Huella ID: {id_huella}...")
    
    cursor = conexion.cursor(dictionary=True)
    
    query_miembro = "SELECT * FROM members WHERE huella = %s AND gym_id = %s"
    cursor.execute(query_miembro, (id_huella, gym_id))
    miembro = cursor.fetchone()
    cursor.close()

    if miembro:
        member_id = miembro['id']
        nombre = miembro['nombre_completo']
        status = miembro['status_licencia']
        
        print(f"👤 Miembro: {nombre}")
        print(f"📋 Estatus: {status}")

        if status == 'Activa':
            print("🟢 ACCESO PERMITIDO")
            registrar_log(conexion, member_id, gym_id, 1)
        elif status == 'Vencida':
            print("🔴 ACCESO DENEGADO: Membresía Vencida.")
            registrar_log(conexion, member_id, gym_id, 0)
        elif status == 'Cancelada':
            print("🔴 ACCESO DENEGADO: Membresía Cancelada.")
            registrar_log(conexion, member_id, gym_id, 0)
    else:
        print(f"🔴 ACCESO DENEGADO: Huella #{id_huella} no pertenece a este Gimnasio.")
        registrar_log(conexion, None, gym_id, 0)
    
    print("-" * 40)

def main():
    conexion = conectar_bd()
    if not conexion:
        return

    try:
        gym_id = iniciar_sesion(conexion)
        if not gym_id:
            return 

        print(f"Operando para el Gimnasio ID: {gym_id}")
        print(f"Iniciando conexión con Arduino en {PUERTO_ARDUINO}...")
        
        try:
            arduino = serial.Serial(PUERTO_ARDUINO, BAUDIOS, timeout=1)
            time.sleep(2) 
            print(f"✅ Arduino conectado.")
        except serial.SerialException:
            print(f"❌ ERROR: No se pudo conectar al puerto {PUERTO_ARDUINO}.")
            print("Asegúrate de que el Monitor Serie de Arduino esté cerrado.")
            return

        print("\n" + "="*40)
        print("   SISTEMA ACTIVO - ESPERANDO HUELLAS")
        print("="*40)

        # Variables para evitar spam de la misma huella si dejas el dedo puesto
        ultimo_id = -1
        tiempo_ultima_lectura = 0

        while True:
            if arduino.in_waiting > 0:
                try:
                    # Leemos la línea y limpiamos errores de decodificación
                    linea = arduino.readline().decode('utf-8', errors='ignore').strip()
                    if not linea: continue
                    
                    # Detecta "Found ID #5 with confidence of 100"
                    if "Found ID #" in linea:
                        # Extraemos solo el número usando split
                        parte_id = linea.split("Found ID #")[1] # Queda "5 with confidence of 100"
                        id_huella = int(parte_id.split(" ")[0]) # Queda 5
                        
                        tiempo_actual = time.time()
                        # Solo procesar si pasaron 3 segundos de la ultima vez o es una huella diferente
                        if id_huella != ultimo_id or (tiempo_actual - tiempo_ultima_lectura) > 3.0:
                            procesar_huella(conexion, arduino, id_huella, gym_id)
                            ultimo_id = id_huella
                            tiempo_ultima_lectura = tiempo_actual
                            
                    # Detecta si el sensor buscó pero no encontró el dedo en su memoria
                    elif "Did not find a match" in linea:
                        tiempo_actual = time.time()
                        if (tiempo_actual - tiempo_ultima_lectura) > 3.0:
                            print("\n⚠️ ALERTA: Dedo detectado, pero no está registrado en el sensor.")
                            tiempo_ultima_lectura = tiempo_actual
                            
                    # Mensajes de estado del propio código original
                    elif "Found fingerprint sensor!" in linea:
                        print("▶ El sensor biométrico se inicializó correctamente.")
                    elif "Did not find fingerprint sensor :(" in linea:
                        print("❌ ERROR CRÍTICO: El Arduino no puede comunicarse con el sensor físico.")
                        
                except Exception as e:
                    pass # Ignora basura serial o errores de split 

    except KeyboardInterrupt:
        print("\nSistema detenido por el usuario.")
    finally:
        if conexion.is_connected():
            conexion.close()
        if 'arduino' in locals() and arduino and arduino.is_open:
            arduino.close()

if __name__ == "__main__":
    main()