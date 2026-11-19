import tkinter as tk
from tkinter import ttk, messagebox
import mysql.connector
from mysql.connector import Error
import bcrypt
import serial
import time
import threading

# --- CONFIGURACIÓN ---
PUERTO_ARDUINO = 'COM5'
BAUDIOS = 9600

class ControlAccesoApp:
    def __init__(self, root):
        self.root = root
        self.root.title("HUD - Sistema de Acceso Gym")
        self.root.geometry("900x650")
        self.root.configure(bg="#1e1e1e") # Fondo oscuro tipo terminal
        
        # Variables globales de la app
        self.conexion_db = None
        self.arduino = None
        self.gym_id = None
        self.hilo_arduino = None
        self.ejecutando = True
        
        self.ultimo_id = -1
        self.tiempo_ultima_lectura = 0

        # Intentar conectar a BD al iniciar
        self.conectar_bd()
        
        # Mostrar pantalla de inicio de sesión
        self.crear_pantalla_login()

        # Manejar el cierre de la ventana para apagar el hilo limpiamente
        self.root.protocol("WM_DELETE_WINDOW", self.cerrar_aplicacion)

    def conectar_bd(self):
        try:
            self.conexion_db = mysql.connector.connect(
                host='localhost',
                database='gym3',
                user='root',
                password=''
            )
        except Error as e:
            messagebox.showerror("Error de Base de Datos", f"No se pudo conectar: {e}")

    def crear_pantalla_login(self):
        self.frame_login = tk.Frame(self.root, bg="#1e1e1e")
        self.frame_login.pack(expand=True)

        lbl_titulo = tk.Label(self.frame_login, text="INICIO DE SESIÓN", font=("Arial", 24, "bold"), fg="#ffffff", bg="#1e1e1e")
        lbl_titulo.pack(pady=20)

        lbl_email = tk.Label(self.frame_login, text="Email del Administrador:", font=("Arial", 12), fg="#aaaaaa", bg="#1e1e1e")
        lbl_email.pack(anchor="w")
        self.entry_email = tk.Entry(self.frame_login, font=("Arial", 14), width=30)
        self.entry_email.pack(pady=5)

        lbl_pass = tk.Label(self.frame_login, text="Contraseña:", font=("Arial", 12), fg="#aaaaaa", bg="#1e1e1e")
        lbl_pass.pack(anchor="w", pady=(10, 0))
        self.entry_pass = tk.Entry(self.frame_login, font=("Arial", 14), width=30, show="*")
        self.entry_pass.pack(pady=5)

        btn_login = tk.Button(self.frame_login, text="ENTRAR AL SISTEMA", font=("Arial", 12, "bold"), bg="#007acc", fg="white", 
                              command=self.verificar_login, width=20, pady=10)
        btn_login.pack(pady=30)

    def verificar_login(self):
        if not self.conexion_db or not self.conexion_db.is_connected():
            messagebox.showerror("Error", "No hay conexión a la base de datos.")
            return

        email = self.entry_email.get()
        password_ingresada = self.entry_pass.get()

        cursor = self.conexion_db.cursor(dictionary=True)
        cursor.execute("SELECT password, gym_id FROM users_admin WHERE email = %s", (email,))
        admin = cursor.fetchone()
        cursor.close()

        if admin:
            hash_db = admin['password']
            if hash_db.startswith('$2y$'):
                hash_db = hash_db.replace('$2y$', '$2b$')

            if bcrypt.checkpw(password_ingresada.encode('utf-8'), hash_db.encode('utf-8')):
                self.gym_id = admin['gym_id']
                self.frame_login.destroy()
                self.crear_hud()
                self.iniciar_arduino()
            else:
                messagebox.showerror("Error", "Contraseña incorrecta.")
        else:
            messagebox.showerror("Error", "Administrador no encontrado.")

    def crear_hud(self):
        # Header
        frame_header = tk.Frame(self.root, bg="#2d2d2d", height=60)
        frame_header.pack(fill="x", side="top")
        
        tk.Label(frame_header, text=f"SISTEMA ACTIVO | GYM ID: {self.gym_id}", font=("Arial", 14, "bold"), fg="#00ff00", bg="#2d2d2d").pack(side="left", padx=20, pady=15)
        self.lbl_estado_arduino = tk.Label(frame_header, text="Conectando Arduino...", font=("Arial", 12), fg="#ffaa00", bg="#2d2d2d")
        self.lbl_estado_arduino.pack(side="right", padx=20, pady=15)

        # Panel de Estado Principal (El cuadro grande)
        self.frame_estado = tk.Frame(self.root, bg="#333333", bd=5, relief="ridge")
        self.frame_estado.pack(fill="both", expand=True, padx=20, pady=20)

        self.lbl_mensaje_principal = tk.Label(self.frame_estado, text="ESPERANDO HUELLA...", font=("Arial", 36, "bold"), fg="#ffffff", bg="#333333")
        self.lbl_mensaje_principal.pack(expand=True)

        self.lbl_datos_usuario = tk.Label(self.frame_estado, text="", font=("Arial", 20), fg="#aaaaaa", bg="#333333")
        self.lbl_datos_usuario.pack(pady=20)

        # Historial de Registros
        frame_historial = tk.Frame(self.root, bg="#1e1e1e", height=200)
        frame_historial.pack(fill="x", side="bottom", padx=20, pady=(0, 20))
        
        tk.Label(frame_historial, text="Historial de Accesos Recientes", font=("Arial", 12, "bold"), fg="#ffffff", bg="#1e1e1e").pack(anchor="w")
        
        # Consola visual
        self.text_log = tk.Text(frame_historial, height=8, bg="#000000", fg="#00ff00", font=("Consolas", 11), state="disabled")
        self.text_log.pack(fill="x", pady=5)

    def agregar_log(self, mensaje):
        """Agrega texto a la consola visual del HUD"""
        self.text_log.config(state="normal")
        hora_actual = time.strftime("%H:%M:%S")
        self.text_log.insert("1.0", f"[{hora_actual}] {mensaje}\n")
        self.text_log.config(state="disabled")

    def actualizar_pantalla_estado(self, mensaje_principal, datos_usuario, color_fondo, color_texto):
        """Cambia el color y texto del cuadro grande"""
        self.frame_estado.config(bg=color_fondo)
        self.lbl_mensaje_principal.config(text=mensaje_principal, bg=color_fondo, fg=color_texto)
        self.lbl_datos_usuario.config(text=datos_usuario, bg=color_fondo, fg=color_texto)
        
        # Volver al estado de espera después de 3.5 segundos
        self.root.after(3500, self.resetear_pantalla_estado)

    def resetear_pantalla_estado(self):
        self.frame_estado.config(bg="#333333")
        self.lbl_mensaje_principal.config(text="ESPERANDO HUELLA...", bg="#333333", fg="#ffffff")
        self.lbl_datos_usuario.config(text="", bg="#333333")

    def iniciar_arduino(self):
        try:
            self.arduino = serial.Serial(PUERTO_ARDUINO, BAUDIOS, timeout=1)
            time.sleep(2)
            self.lbl_estado_arduino.config(text=f"Arduino Conectado ({PUERTO_ARDUINO})", fg="#00ff00")
            self.agregar_log("Sistema iniciado correctamente. Lector activo.")
            
            # Iniciar el hilo que escuchará al Arduino
            self.hilo_arduino = threading.Thread(target=self.escuchar_arduino, daemon=True)
            self.hilo_arduino.start()
            
        except serial.SerialException:
            self.lbl_estado_arduino.config(text=f"Error en {PUERTO_ARDUINO}", fg="#ff0000")
            self.agregar_log(f"ERROR: No se pudo conectar al puerto {PUERTO_ARDUINO}. Cierra el Monitor Serie.")

    def escuchar_arduino(self):
        """Esta función corre en segundo plano y no congela la interfaz"""
        while self.ejecutando and self.arduino and self.arduino.is_open:
            if self.arduino.in_waiting > 0:
                try:
                    linea = self.arduino.readline().decode('utf-8', errors='ignore').strip()
                    if not linea: continue
                    
                    if "Found ID #" in linea:
                        parte_id = linea.split("Found ID #")[1] 
                        id_huella = int(parte_id.split(" ")[0])
                        
                        tiempo_actual = time.time()
                        # Filtro protector de 3 segundos para no registrar 50 veces el mismo dedo
                        if id_huella != self.ultimo_id or (tiempo_actual - self.tiempo_ultima_lectura) > 3.0:
                            # root.after sirve para ejecutar funciones en el hilo principal (GUI) desde un hilo secundario
                            self.root.after(0, self.procesar_huella, id_huella)
                            self.ultimo_id = id_huella
                            self.tiempo_ultima_lectura = tiempo_actual
                            
                    elif "Did not find a match" in linea:
                        tiempo_actual = time.time()
                        if (tiempo_actual - self.tiempo_ultima_lectura) > 3.0:
                            self.root.after(0, self.actualizar_pantalla_estado, "DEDO DESCONOCIDO", "No registrado en el sensor", "#aa0000", "#ffffff")
                            self.root.after(0, self.agregar_log, "ALERTA: Dedo detectado pero no registrado en el sensor físico.")
                            self.tiempo_ultima_lectura = tiempo_actual

                except Exception as e:
                    pass 

    def procesar_huella(self, id_huella):
        if not self.conexion_db.is_connected():
            self.conectar_bd()

        cursor = self.conexion_db.cursor(dictionary=True)
        query_miembro = "SELECT * FROM members WHERE huella = %s AND gym_id = %s"
        cursor.execute(query_miembro, (id_huella, self.gym_id))
        miembro = cursor.fetchone()
        cursor.close()

        if miembro:
            member_id = miembro['id']
            nombre = miembro['nombre_completo']
            status = miembro['status_licencia']
            
            if status == 'Activa':
                self.actualizar_pantalla_estado("ACCESO PERMITIDO", f"{nombre}\nLicencia: ACTIVA", "#005500", "#ffffff")
                self.agregar_log(f"🟢 ENTRADA: {nombre} (Huella #{id_huella})")
                self.registrar_log(member_id, 1)
                
                # AQUÍ PUEDES ENVIAR SEÑAL AL ARDUINO PARA ABRIR PUERTA
                # self.arduino.write(b"ABRIR\n")
                
            elif status == 'Vencida':
                self.actualizar_pantalla_estado("ACCESO DENEGADO", f"{nombre}\nLicencia: VENCIDA", "#aa0000", "#ffffff")
                self.agregar_log(f"🔴 DENEGADO: {nombre} - Membresía Vencida.")
                self.registrar_log(member_id, 0)
                
            elif status == 'Cancelada':
                self.actualizar_pantalla_estado("ACCESO DENEGADO", f"{nombre}\nLicencia: CANCELADA", "#aa0000", "#ffffff")
                self.agregar_log(f"🔴 DENEGADO: {nombre} - Membresía Cancelada.")
                self.registrar_log(member_id, 0)
        else:
            self.actualizar_pantalla_estado("ACCESO DENEGADO", f"Huella #{id_huella} no pertenece a este gimnasio", "#aa0000", "#ffffff")
            self.agregar_log(f"🔴 DENEGADO: Huella #{id_huella} ignorada (Pertenece a otro Gym).")
            self.registrar_log(None, 0)

    def registrar_log(self, member_id, acceso_concedido):
        """Guarda en la base de datos el intento de acceso"""
        try:
            cursor = self.conexion_db.cursor()
            query = """
                INSERT INTO access_logs 
                (member_id, gym_id, acceso_concedido, fecha_hora) 
                VALUES (%s, %s, %s, NOW())
            """
            cursor.execute(query, (member_id, self.gym_id, acceso_concedido))
            self.conexion_db.commit()
            cursor.close()
        except Error as e:
            self.agregar_log(f"Error SQL al guardar log: {e}")

    def cerrar_aplicacion(self):
        """Se ejecuta al darle a la 'X' de la ventana para cerrar todo de forma segura"""
        self.ejecutando = False
        if self.arduino and self.arduino.is_open:
            self.arduino.close()
        if self.conexion_db and self.conexion_db.is_connected():
            self.conexion_db.close()
        self.root.destroy()

if __name__ == "__main__":
    root = tk.Tk()
    app = ControlAccesoApp(root)
    root.mainloop()