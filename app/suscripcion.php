<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../data/security.php';
require_once __DIR__ . '/../data/matrix.php';

// Validar que la sesión contenga gym_id
if (!isset($_SESSION['user_id']) || empty($_SESSION['gym_id'])) {
    header("Location: index.php");
    exit();
}

$gym_id_actual = (int)$_SESSION['gym_id']; 
$gym_nombre_actual = isset($_SESSION['nombre_gym']) ? $_SESSION['nombre_gym'] : ($resultado_gym['nombre_gym'] ?? 'Mi Gimnasio');
$gym_email_actual = isset($_SESSION['email_corporativo']) ? $_SESSION['email_corporativo'] : ($resultado_gym['email_corporativo'] ?? 'correo@gimnasio.com');

$mensaje = '';
$tipo_mensaje = ''; // 'success' o 'error'

// =========================================================================
// ⚙️ BACKEND PHP - PROCESAMIENTO DEL PAGO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    
    $access_token = MP_ACCESS_TOKEN;
    $plan_id_mp = MP_PLAN_ID;

    // 3. RECIBIR DATOS Y ASIGNAR ID DESDE SESIÓN (PREVENCIÓN DE IDOR)
    $token_tarjeta = $_POST['token'];
    $gym_id = $gym_id_actual; // Forzar uso de sesión autenticada
    $payer_email = !empty($_POST['email_corporativo']) ? trim($_POST['email_corporativo']) : $gym_email_actual;
    
    $plan_seleccionado = 'Enterprise'; 

    // 4. PETICIÓN A MERCADO PAGO
    $url = "https://api.mercadopago.com/preapproval";
    $data = [
        "preapproval_plan_id" => $plan_id_mp,
        "payer_email" => $payer_email,
        "card_token_id" => $token_tarjeta,
        "status" => "authorized"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token,
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    // 5. EVALUAR RESPUESTA Y ACTUALIZAR BASE DE DATOS
    if ($http_code === 201 || $http_code === 200) {
        $nueva_expiracion = date('Y-m-d', strtotime('+1 month'));

        try {
            require_once __DIR__ . '/../data/conn.php'; 
            $conexion = isset($conn) ? $conn : null;
            
            if ($conexion) {
                $sql = "UPDATE gyms 
                        SET plan_saas = :plan, 
                            expiracion = :exp, 
                            status = 'Active' 
                        WHERE id = :id";
                
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':plan' => $plan_seleccionado,
                    ':exp' => $nueva_expiracion,
                    ':id' => $gym_id
                ]);

                $mensaje = "¡Suscripción activada! Tu Gym ahora es $plan_seleccionado. Próximo cobro: $nueva_expiracion.";
                $tipo_mensaje = 'success';
            } else {
                throw new Exception("Error interno de conexión.");
            }
        } catch (Exception $e) {
            error_log("Error al actualizar suscripción para Gym ID $gym_id: " . $e->getMessage());
            $mensaje = "El cobro se realizó con éxito, pero ocurrió un error al actualizar el estado en el sistema. Contacte a soporte.";
            $tipo_mensaje = 'error';
        }

    } else {
        $error_detalle = isset($result['message']) ? $result['message'] : 'Revisa los datos de la tarjeta.';
        $mensaje = "El pago fue rechazado. Detalle: " . htmlspecialchars($error_detalle);
        $tipo_mensaje = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción SaaS | Activar Plan</title>
    
    <!-- SDK de Mercado Pago v2 -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    
    <!-- Tailwind CSS para diseño hermoso y premium -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo o Icono representativo -->
        <div class="mx-auto h-12 w-12 bg-indigo-600 text-white rounded-xl flex items-center justify-center shadow-lg">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Mejora tu Gym
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Estás suscribiendo a: <span class="font-semibold text-indigo-600"><?php echo htmlspecialchars($gym_nombre_actual); ?></span>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100">
            
            
            <!-- Resumen del Plan -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-indigo-900 font-bold text-lg">Plan Enterprise</h3>
                    <p class="text-indigo-700 text-sm">Suscripción SaaS Mensual</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-extrabold text-indigo-900">$299</span>
                    <span class="text-indigo-700 text-sm font-medium">/mes</span>
                </div>
            </div>
            
            

            <!-- Mostrar Alertas de PHP (Éxito o Error) -->
            <?php if ($mensaje): ?>
                <div class="mb-6 p-4 rounded-xl text-sm font-medium <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <div class="flex items-center gap-2">
                        <?php if($tipo_mensaje === 'success'): ?>
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <?php else: ?>
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Alerta dinámica generada por JS para validaciones frontend -->
            <div style="display:none;" id="js-alert" class="hidden-element mb-6 p-4 rounded-xl text-sm font-medium bg-red-50 text-red-800 border border-red-200 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>				</svg>
                <span  id="js-alert-text"></span>
            </div>

            <form id="form-checkout" method="POST" action="" class="space-y-5">
                
                <!-- ⚙️ DATOS OCULTOS QUE VIAJAN AL BACKEND PHP -->
                <input type="hidden" name="token" id="token" value="">
                <input type="hidden" name="gym_id" value="<?php echo $gym_id_actual; ?>">
                
                <!-- Email Corporativo (Lectura y envío a MP) -->
                <div hidden>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo de facturación</label>
                    <input type="email" id="email" name="email_corporativo" value="<?php echo htmlspecialchars($gym_email_actual); ?>" readonly 
                           class="appearance-none block w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed sm:text-sm focus:outline-none">
                </div>

                <?php if ($resultado_gym['plan_saas']=='Enterprise'): ?>
                <h2>Ya eres enterprise</h2>
                <?php else: ?>
                <div class="relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-3 bg-white text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Información de Pago Segura
                        </span>
                    </div>
                </div>

                <!-- Tarjeta de Crédito (Iframe MP) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de tarjeta</label>
                    <div class="relative">
                        <div id="cardNumber" class="mp-input-container appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-shadow sm:text-sm h-[46px]"></div>
                        <!-- Icono decorativo -->
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <!-- Fecha de Expiración -->
                    <div class="w-1/2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiración</label>
                        <div id="expirationDate" class="mp-input-container appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-shadow sm:text-sm h-[46px]"></div>
                    </div>
                    <!-- CVV -->
                    <div class="w-1/2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">CVC</label>
                        <div id="securityCode" class="mp-input-container appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-shadow sm:text-sm h-[46px]"></div>
                    </div>
                </div>

                <!-- Botón de Envío -->
                <div class="pt-2">
                    <button type="submit" id="btn-submit" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        <span id="btn-text">Suscribirme por $299</span>
                        
                        <!-- Spinner Animado -->
                        <svg id="btn-spinner" class="hidden-element animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
                                    

                <div class="text-center mt-4 flex items-center justify-center gap-1.5 text-xs text-gray-500">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    <span>Datos encriptados y procesados de forma segura.</span>
                </div>
            </form></br><?php endif; ?>
            <a class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all"  href="clientes.php">Regresar al menu</a>
        </div>
    </div>


<script>
    // =========================================================================
    // 🚨 3. TU PUBLIC KEY DE MERCADO PAGO
    // =========================================================================
    // 👉 REEMPLAZA ESTO con tu llave pública real (APP_USR-...). Es seguro ponerla aquí.
    const mp = new MercadoPago("<?php echo MP_PUBLIC_KEY; ?>");

    // Estilos personalizados para inyectar en los iframes de MP (que hagan match con Tailwind)
    const customStyles = {
        style: {
            fontSize: '14px', /* sm:text-sm */
            fontFamily: 'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            color: '#111827', /* text-gray-900 */
            placeholderColor: '#9ca3af' /* text-gray-400 */
        }
    };

    // Montar componentes de tarjeta
    const cardNumberElement = mp.fields.create('cardNumber', {
        placeholder: "0000 0000 0000 0000",
        style: customStyles.style
    }).mount('cardNumber');

    const expirationDateElement = mp.fields.create('expirationDate', {
        placeholder: "MM/YY",
        style: customStyles.style
    }).mount('expirationDate');

    const securityCodeElement = mp.fields.create('securityCode', {
        placeholder: "123",
        style: customStyles.style
    }).mount('securityCode');

    // DOM Elements
    const form = document.getElementById('form-checkout');
    const alertBox = document.getElementById('js-alert');
    const alertText = document.getElementById('js-alert-text');
    const btnSubmit = document.getElementById('btn-submit');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    // Función elegante para mostrar errores
    function showError(message) {
        alertText.textContent = message;
        alertBox.classList.remove('hidden-element');
        
        // Restaurar botón
        btnText.textContent = 'Suscribirme por $299';
        btnSpinner.classList.add('hidden-element');
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
    }

    form.addEventListener('submit', async function(event) {
        event.preventDefault(); // Detener envío natural
        
        // Estado de "Cargando"
        alertBox.classList.add('hidden-element');
        btnSubmit.disabled = true;
        btnSubmit.classList.add('opacity-70', 'cursor-not-allowed');
        btnText.textContent = 'Procesando pago...';
        btnSpinner.classList.remove('hidden-element');

        try {
            // Petición de Token a Mercado Pago
            const tokenResponse = await mp.fields.createCardToken({
                cardholderName: "Suscripcion Gym", 
                identificationType: "",
                identificationNumber: ""
            });

            if (tokenResponse.id) {
                // Token exitoso -> Inyectar al form
                document.getElementById('token').value = tokenResponse.id;
                
                // Enviar formulario real al Backend PHP
                form.submit();
            } else {
                showError("Verifica los datos de la tarjeta. Hay un error en la información ingresada.");
            }
        } catch (error) {
            console.error("Error SDK MP:", error);
            showError("Ocurrió un error al procesar la tarjeta. Por favor, inténtalo nuevamente.");
        }
    });
</script>

</body>
</html>