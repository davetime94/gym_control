  <?php 
  require_once __DIR__ . "/../funciones/matrix.php"; 
  require_once __DIR__ . "/security.php";
  ?>
  
  <div class="card" style="flex-direction: column; gap: 50px;">

    <?php if($resultado_plan['status']=='Pending'): ?>
        <div>
            <h1>Tu cuenta está bajo revisión o aprobación</h1><br>
            <p>En este paso estamos haciendo la verificaión de tu gimasio, datos cómo la presencia del gym y tu información de contacto están siendo revisadas en este momento.</p><br>
            <p>Te avisaremos cuando tengamos el resultado, este proceso toma aproximadamente de 24 a 72 horas hábiles. </p>
           <a href="new_gym.php"> <button class="btn-search mover" style="animation: mover 4s infinite; width:30%; height: 50px;">Llena la información de tu Gym ahora!</button></a>
        </div>

        <div>
            <img src="../recursos/Editing body text-amico.png" style="height: 500px;  "   alt="">
        </div>

    <?php elseif($resultado_plan['status']=='Denied'): ?>
        <div style="flex-direction: row; display: flex;" >
            <div  >
                <h1>Tu cuenta ha sido rechazada</h1><br>
                <p>Por desición del negocio, tu cuenta no fue apta para poder aceptarla, te sugerimos crear una nueva y llenar toda la información correspondiente</p><br>
                <ul >
                    <li class="system-badge" style="width: fit-content;">Llena toda la información solicitada</li><br>
                    <li class="system-badge" style="width: fit-content;">Verifica que la información sea correcta</li><br>
                    <li class="system-badge" style="width: fit-content;">Responde de manera breve las solicitudes</li><br>
                    <li class="system-badge" style="width: fit-content;">Usa documentos especiales</li><br>
                    <li class="system-badge" style="width: fit-content;">Mantente atento a las actualizaciones de tu cuenta</li><br>
                </ul>
            </div>

            <div>
                <img src="../recursos/Forgot password-bro.png" style="height: 500px;  "   alt="">
            </div>
        </div>
    <?php endif; ?>




    </div>

