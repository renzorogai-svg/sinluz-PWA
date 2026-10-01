<?php
/*
10-09-2026 desde  PC
*/
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

date_default_timezone_set('America/Caracas');

$rutaRegistros = __DIR__ . '/registros.txt';
$registros = @file($rutaRegistros, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($registros !== false) {
	$fechaActual = date('Y-m-d');
	$registrosActuales = array_filter($registros, static function (string $registro) use ($fechaActual): bool {
		return substr($registro, 0, 10) === $fechaActual;
	});

	@file_put_contents(
		$rutaRegistros,
		$registrosActuales ? implode(PHP_EOL, $registrosActuales) . PHP_EOL : '',
		LOCK_EX
	);
}

$rutaAccesos = __DIR__ . '/accesos.txt';
$rutaNombresIp = __DIR__ . '/nombres_ip.json';
$nombresIp = [];

if (is_readable($rutaNombresIp)) {
	$contenidoNombresIp = file_get_contents($rutaNombresIp);
	$nombresIp = json_decode($contenidoNombresIp, true);
	if (!is_array($nombresIp)) {
		$nombresIp = [];
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$ip = trim($_POST['ip'] ?? '');
	$nombre = trim($_POST['nombre'] ?? '');

	if ($ip !== '') {
		if ($nombre === '') {
			unset($nombresIp[$ip]);
		} else {
			$nombresIp[$ip] = $nombre;
		}

		file_put_contents(
			$rutaNombresIp,
			json_encode($nombresIp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL,
			LOCK_EX
		);
	}

	header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/ver_visitas.php', '?'));
	exit;
}

$accesos = is_readable($rutaAccesos)
	? file($rutaAccesos, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
	: [];

if ($accesos !== false) {
	$accesos = array_reverse($accesos);
}

$ips = [];
foreach ($accesos as $acceso) {
	if (preg_match('/IP:\s*([^\s]+)/', $acceso, $coincidencia)) {
		$ips[$coincidencia[1]] = true;
	}
}
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Visitas registradas</title>
	<style>
		body { background: #d4d4d4; color: #1f1f1f; font-family: "Segoe UI", sans-serif; margin: 0; }
		main { background: #f3f3f3; border: 1px solid #a6a6a6; box-shadow: 0 2px 6px #999; box-sizing: border-box; min-height: 100vh; padding: 1.5rem; }
		h1 { font-size: 1.4rem; margin: 0 0 1rem; }
		p { margin: 0; }
		ul { list-style: none; margin: 1rem 0 0; padding: 0; }
		li { background: #fff; border-bottom: 1px solid #d4d4d4; padding: 0.65rem; word-break: break-word; }
	</style>
</head>
<body>
	<main>
		<h1>Nombres de IP</h1>
		<form method="post">
			<label for="ip">IP:</label>
			<select id="ip" name="ip" required>
				<option value="">Seleccionar IP</option>
				<?php foreach (array_keys($ips) as $ip): ?>
					<option value="<?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></option>
				<?php endforeach; ?>
			</select>
			<label for="nombre">Nombre:</label>
			<input id="nombre" name="nombre" type="text" maxlength="80" placeholder="Ejemplo: Casa">
			<button type="submit">Guardar</button>
		</form>

		<?php if (!$accesos): ?>
			<p>No hay accesos registrados todavía.</p>
		<?php else: ?>
			<ul>
				<?php foreach ($accesos as $acceso): ?>
					<?php
					$accesoVisible = $acceso;
					if (preg_match('/IP:\s*([^\s]+)/', $acceso, $coincidencia) && isset($nombresIp[$coincidencia[1]])) {
						$accesoVisible .= ' (' . $nombresIp[$coincidencia[1]] . ')';
					}
					?>
					<li><?= htmlspecialchars($accesoVisible, ENT_QUOTES, 'UTF-8') ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</main>
</body>
</html>
