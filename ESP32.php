<?php 
/*31-08-2026 desde PC
*/
date_default_timezone_set('america/caracas');
$fecha = date('Y-m-d');
$hora = date('H:i:s'); // con segundos para el registro

$activo = $_GET['activo'] ?? 0;  // <-- Esto asegura que capture el 1 o el 0 sin dejarlo vacío

if(isset($_GET['rssi'])) {
   $rssi = $_GET['rssi'];
} else $rssi = '0';

if(isset($_GET['ssid'])) {
   $ssid = $_GET['ssid'];
} else $ssid = 'No';

// Registrar en el archivo registros.txt la hora y el valor de activo
$linea = "$fecha $hora - Activo: $activo" . PHP_EOL;
file_put_contents(__DIR__ . '/registros.txt', $linea, FILE_APPEND);

echo "Activo: $activo<br>";
echo "RSSI: $rssi<br>";
echo "SSID: $ssid<br>";
?>