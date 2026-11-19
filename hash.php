<?php

$contraseña='LunaAzul94$$';
echo $contraseña;

echo "<br/>";

$contraseñaHasheada=password_hash($contraseña, PASSWORD_DEFAULT);
echo $contraseñaHasheada;
?>