import tkinter as tk
from tkinter import ttk, messagebox
from tkinter import font as tkfont
import mysql.connector
from mysql.connector import Error
import bcrypt
import serial
import time
import threading

# --- PALETA DE COLORES MODERNAS (Dark Theme) ---
BG_MAIN = "#0F172A"       # Fondo general (Slate 900)
BG_CARD = "#1E293B"       # Fondo de tarjetas/paneles (Slate 800)
BG_INPUT = "#0F172A"      # Fondo de las cajas de texto
FG_TEXT = "#F8FAFC"       # Texto principal (Blanco puro)
FG_MUTED = "#94A3B8"      # Texto secundario (Gris claro)
ACCENT = "#38BDF8"        # Color de acento (Azul claro)
ACCENT_HOVER = "#7DD3FC"  # Color de acento al pasar el mouse
SUCCESS_BG = "#064E3B"    # Verde oscuro elegante para fondo
SUCCESS_FG = "#34D399"    # Verde esmeralda para texto
ERROR_BG = "#7F1D1D"      # Rojo oscuro elegante para fondo
ERROR_FG = "#F87171"      # Rojo suave para texto

BAUDIOS = 9600

class ControlAccesoApp:
    def __init__(self, root):
        self.root = root
        self.root.title("GYMSYNC - Sistema de Control de Acceso")
        self.root.geometry("950x700")
        self.root.configure(bg=BG_MAIN)
        
        # Configurar tipografías globales
        self.font_title = tkfont.Font(family="Segoe UI", size=28, weight="bold")
        self.font_subtitle = tkfont.Font(family="Segoe UI", size=14)
        self.font_normal = tkfont.Font(family="Segoe UI", size=12)
        self.font_hud = tkfont.Font(family="Segoe UI", size=42, weight="bold")
        
        self.configurar_estilos_ttk()

        # Variables globales
        self.conexion_db = None
        self.arduino = None
        self.gym_id = None
        self.puerto_seleccionado = None
        self.hilo_arduino = None
        self.ejecutando = True
        
        self.ultimo_id = -1
        self.tiempo_ultima_lectura = 0

        self.conectar_bd()
        self.crear_pantalla_login()
        self.root.protocol("WM_DELETE_WINDOW", self.cerrar_aplicacion)

    def configurar_estilos_ttk(self):
        """Mejora el diseño del combobox para que coincida con el modo oscuro"""
        style = ttk.Style()
        style.theme_use('clam')
        style.configure("TCombobox", 
                        fieldbackground=BG_INPUT, 
                        background=BG_CARD, 
                        foreground=FG_TEXT,
                        arrowcolor=ACCENT,
                        bordercolor=BG_CARD,
                        lightcolor=BG_CARD,
                        darkcolor=BG_CARD)

    def conectar_bd(self):
        try:
            self.conexion_db = mysql.connector.connect(
                host='localhost', database='gym3', user='root', password=''
            )
        except Error as e:
            messagebox.showerror("Error de Base de Datos", f"No se pudo conectar: {e}")

    def crear_pantalla_login(self):
        self.frame_login_container = tk.Frame(self.root, bg=BG_MAIN)
        self.frame_login_container.place(relx=0.5, rely=0.5, anchor="center")

        # Tarjeta de Login (Card)
        self.card_login = tk.Frame(self.frame_login_container, bg=BG_CARD, padx=50, pady=50, bd=0, highlightthickness=1, highlightbackground="#334155")
        self.card_login.pack(padx=20, pady=20)

        # Logo o Título
        lbl_titulo = tk.Label(self.card_login, text="ACCESO ADMIN", font=self.font_title, fg=FG_TEXT, bg=BG_CARD)
        lbl_titulo.pack(pady=(0, 30))

        # Inputs
        self._crear_input_login("Email del Administrador:", "email")
        self._crear_input_login("Contraseña:", "password")

        # Selector de Puerto
        lbl_puerto = tk.Label(self.card_login, text="Puerto del Lector:", font=self.font_normal, fg=FG_MUTED, bg=BG_CARD)
        lbl_puerto.pack(anchor="w", pady=(15, 5))
        
        self.combo_puerto = ttk.Combobox(self.card_login, font=self.font_normal, width=28, state="readonly")
        self.combo_puerto['values'] = [f"COM{i}" for i in range(1, 16)]
        self.combo_puerto.current(4) 
        self.combo_puerto.pack(ipady=5, pady=(0, 20))

        # Botón elegante con Hover
        self.btn_login = tk.Button(self.card_login, text="INICIAR SISTEMA", font=("Segoe UI", 12, "bold"), 
                                   bg=ACCENT, fg="#000000", activebackground=ACCENT_HOVER, activeforeground="#000000",
                                   relief="flat", cursor="hand2", command=self.verificar_login)
        self.btn_login.pack(fill="x", ipady=12, pady=(10, 0))
        
        # Efectos Hover
        self.btn_login.bind("<Enter>", lambda e: self.btn_login.config(bg=ACCENT_HOVER))
        self.btn_login.bind("<Leave>", lambda e: self.btn_login.config(bg=ACCENT))

    def _crear_input_login(self, label_text, tipo):
        lbl = tk.Label(self.card_login, text=label_text, font=self.font_normal, fg=FG_MUTED, bg=BG_CARD)
        lbl.pack(anchor="w", pady=(10, 5))
        
        entry = tk.Entry(self.card_login, font=self.font_normal, bg=BG_INPUT, fg=FG_TEXT, 
                         insertbackground=FG_TEXT, relief="flat", highlightthickness=1, highlightbackground="#334155")
        entry.pack(fill="x", ipady=8)
        
        if tipo == "email":
            self.entry_email = entry
        else:
            entry.config(show="•")
            self.entry_pass = entry

    def verificar_login(self):
        if not self.conexion_db or not self.conexion_db.is_connected():
            messagebox.showerror("Error", "No hay conexión a la base de datos.")
            return

        email = self.entry_email.get()
        password_ingresada = self.entry_pass.get()
        self.puerto_seleccionado = self.combo_puerto.get()

        cursor = self.conexion_db.cursor(dictionary=True)
        cursor.execute("SELECT password, gym_id FROM users_admin WHERE email = %s", (email,))
        admin = cursor.fetchone()
        cursor.close()

        if admin:
            hash_db = admin['password']
            if hash_db.startswith('$2y$'): hash_db = hash_db.replace('$2y$', '$2b$')

            if bcrypt.checkpw(password_ingresada.encode('utf-8'), hash_db.encode('utf-8')):
                self.gym_id = admin['gym_id']
                self.frame_login_container.destroy()
                self.crear_hud()
                self.iniciar_arduino()
            else:
                messagebox.showerror("Error", "Contraseña incorrecta.")
        else:
            messagebox.showerror("Error", "Administrador no encontrado.")

    def crear_hud(self):
        # Header (Barra superior)
        frame_header = tk.Frame(self.root, bg=BG_CARD, height=70, highlightthickness=1, highlightbackground="#1E293B", highlightcolor="#1E293B")
        frame_header.pack(fill="x", side="top")
        frame_header.pack_propagate(False) # Mantener altura fija
        
        tk.Label(frame_header, text="CONTROL DE ACCESO", font=("Segoe UI", 16, "bold"), fg=FG_TEXT, bg=BG_CARD).pack(side="left", padx=25, pady=20)
        
        self.lbl_estado_arduino = tk.Label(frame_header, text="Iniciando...", font=self.font_normal, fg=ACCENT, bg=BG_CARD)
        self.lbl_estado_arduino.pack(side="right", padx=25, pady=20)

        # Contenedor Principal (HUD)
        self.frame_estado = tk.Frame(self.root, bg=BG_CARD, highlightthickness=2, highlightbackground="#334155")
        self.frame_estado.pack(fill="both", expand=True, padx=40, pady=30)

        self.lbl_mensaje_principal = tk.Label(self.frame_estado, text="ESPERANDO LECTURA", font=self.font_hud, fg=FG_MUTED, bg=BG_CARD)
        self.lbl_mensaje_principal.pack(expand=True)

        self.lbl_datos_usuario = tk.Label(self.frame_estado, text="Por favor, coloque su huella en el sensor", font=self.font_subtitle, fg=FG_MUTED, bg=BG_CARD)
        self.lbl_datos_usuario.pack(pady=(0, 50))

        # Historial de Registros
        frame_historial = tk.Frame(self.root, bg=BG_MAIN)
        frame_historial.pack(fill="x", side="bottom", padx=40, pady=(0, 30))
        
        tk.Label(frame_historial, text="ÚLTIMOS MOVIMIENTOS", font=("Segoe UI", 10, "bold"), fg=FG_MUTED, bg=BG_MAIN).pack(anchor="w", pady=(0, 5))
        
        self.text_log = tk.Text(frame_historial, height=6, bg=BG_CARD, fg=FG_TEXT, font=("Consolas", 10), 
                                relief="flat", state="disabled", highlightthickness=1, highlightbackground="#334155")
        self.text_log.pack(fill="x")

    def agregar_log(self, mensaje, tipo="info"):
        self.text_log.config(state="normal")
        hora_actual = time.strftime("%H:%M:%S")
        
        # Colores en el texto según el tipo
        color = ACCENT if tipo == "info" else (SUCCESS_FG if tipo == "ok" else ERROR_FG)
        self.text_log.tag_config(tipo, foreground=color)
        
        self.text_log.insert("1.0", f"[{hora_actual}] {mensaje}\n", tipo)
        self.text_log.config(state="disabled")

    def actualizar_pantalla_estado(self, mensaje_principal, datos_usuario, color_fondo, color_texto):
        self.frame_estado.config(bg=color_fondo, highlightbackground=color_texto)
        self.lbl_mensaje_principal.config(text=mensaje_principal, bg=color_fondo, fg=color_texto)
        self.lbl_datos_usuario.config(text=datos_usuario, bg=color_fondo, fg=FG_TEXT)
        self.root.after(3500, self.resetear_pantalla_estado)

    def resetear_pantalla_estado(self):
        self.frame_estado.config(bg=BG_CARD, highlightbackground="#334155")
        self.lbl_mensaje_principal.config(text="ESPERANDO LECTURA", bg=BG_CARD, fg=FG_MUTED)
        self.lbl_datos_usuario.config(text="Por favor, coloque su huella en el sensor", bg=BG_CARD, fg=FG_MUTED)

    def iniciar_arduino(self):
        try:
            self.arduino = serial.Serial(self.puerto_seleccionado, BAUDIOS, timeout=1)
            time.sleep(2)
            self.lbl_estado_arduino.config(text=f"🟢 En línea ({self.puerto_seleccionado})", fg=SUCCESS_FG)
            self.agregar_log("Sistema iniciado correctamente. Lector activo.", "info")
            
            self.hilo_arduino = threading.Thread(target=self.escuchar_arduino, daemon=True)
            self.hilo_arduino.start()
            
        except serial.SerialException:
            self.lbl_estado_arduino.config(text=f"🔴 Desconectado ({self.puerto_seleccionado})", fg=ERROR_FG)
            self.agregar_log(f"ERROR: No se pudo conectar al puerto {self.puerto_seleccionado}. Cierra el Monitor Serie.", "error")

    def escuchar_arduino(self):
        while self.ejecutando and self.arduino and self.arduino.is_open:
            if self.arduino.in_waiting > 0:
                try:
                    linea = self.arduino.readline().decode('utf-8', errors='ignore').strip()
                    if not linea: continue
                    
                    if "Found ID #" in linea:
                        parte_id = linea.split("Found ID #")[1] 
                        id_huella = int(parte_id.split(" ")[0])
                        
                        tiempo_actual = time.time()
                        if id_huella != self.ultimo_id or (tiempo_actual - self.tiempo_ultima_lectura) > 3.0:
                            self.root.after(0, self.procesar_huella, id_huella)
                            self.ultimo_id = id_huella
                            self.tiempo_ultima_lectura = tiempo_actual
                            
                    elif "Did not find a match" in linea:
                        tiempo_actual = time.time()
                        if (tiempo_actual - self.tiempo_ultima_lectura) > 3.0:
                            self.root.after(0, self.actualizar_pantalla_estado, "DEDO DESCONOCIDO", "La huella no está registrada en el sensor físico", ERROR_BG, ERROR_FG)
                            self.root.after(0, self.agregar_log, "ALERTA: Intento de acceso con huella no registrada.", "error")
                            self.tiempo_ultima_lectura = tiempo_actual

                except Exception as e:
                    pass 

    def procesar_huella(self, id_huella):
        if not self.conexion_db.is_connected(): self.conectar_bd()

        cursor = self.conexion_db.cursor(dictionary=True)
        cursor.execute("SELECT * FROM members WHERE huella = %s AND gym_id = %s", (id_huella, self.gym_id))
        miembro = cursor.fetchone()
        cursor.close()

        if miembro:
            member_id = miembro['id']
            nombre = miembro['nombre_completo']
            status = miembro['status_licencia']
            
            if status == 'Activa':
                self.actualizar_pantalla_estado("ACCESO PERMITIDO", f"Bienvenido, {nombre}\nEstado: Membresía Activa", SUCCESS_BG, SUCCESS_FG)
                self.agregar_log(f"ENTRADA: {nombre} (Huella #{id_huella})", "ok")
                self.registrar_log(member_id, 1)
                # self.arduino.write(b"ABRIR\n") # Descomentar para abrir puerta
                
            elif status == 'Vencida':
                self.actualizar_pantalla_estado("ACCESO DENEGADO", f"{nombre}\nEstado: Membresía Vencida", ERROR_BG, ERROR_FG)
                self.agregar_log(f"DENEGADO: {nombre} - Membresía Vencida.", "error")
                self.registrar_log(member_id, 0)
                
            elif status == 'Cancelada':
                self.actualizar_pantalla_estado("ACCESO DENEGADO", f"{nombre}\nEstado: Membresía Cancelada", ERROR_BG, ERROR_FG)
                self.agregar_log(f"DENEGADO: {nombre} - Membresía Cancelada.", "error")
                self.registrar_log(member_id, 0)
        else:
            self.actualizar_pantalla_estado("ACCESO DENEGADO", f"El ID de huella #{id_huella} no pertenece a este gimnasio", ERROR_BG, ERROR_FG)
            self.agregar_log(f"DENEGADO: Huella #{id_huella} ignorada (Pertenece a otro Gym).", "error")
            self.registrar_log(None, 0)

    def registrar_log(self, member_id, acceso_concedido):
        try:
            cursor = self.conexion_db.cursor()
            cursor.execute("INSERT INTO access_logs (member_id, gym_id, acceso_concedido, fecha_hora) VALUES (%s, %s, %s, NOW())", 
                           (member_id, self.gym_id, acceso_concedido))
            self.conexion_db.commit()
            cursor.close()
        except Error as e:
            self.agregar_log(f"Error SQL: {e}", "error")

    def cerrar_aplicacion(self):
        self.ejecutando = False
        if self.arduino and self.arduino.is_open: self.arduino.close()
        if self.conexion_db and self.conexion_db.is_connected(): self.conexion_db.close()
        self.root.destroy()

if __name__ == "__main__":
    root = tk.Tk()
    app = ControlAccesoApp(root)
    root.mainloop()