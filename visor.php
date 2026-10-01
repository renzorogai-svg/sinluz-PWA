<?php
/*
    01/10/2026 desde PC
	aplicacion web progresiva
    Visor de estado activo del sistema
*/

if (isset($_GET['historial'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

	date_default_timezone_set('America/Caracas');
	$historial = [];
	$ultimaActualizacion = null;
	$fechaActual = date('Y-m-d');
	$registros = @file(__DIR__ . '/registros.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if ($registros !== false) {
		foreach ($registros as $registro) {
			if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+-\s+Activo:\s*(.*)$/i', $registro, $coincidencia)) {
				$ultimaActualizacion = DateTime::createFromFormat('Y-m-d H:i:s', $coincidencia[1]);
				$valorActivo = trim($coincidencia[2]);

				if (substr($coincidencia[1], 0, 10) !== $fechaActual || !is_numeric($valorActivo)) {
					continue;
				}

				$historial[] = [
					'tiempo' => $coincidencia[1],
					'activo' => (float) $valorActivo,
				];
			}
		}
	}

	if ($ultimaActualizacion !== null && time() - $ultimaActualizacion->getTimestamp() > 150) {
		$historial[] = [
			'tiempo' => date('Y-m-d H:i:s'),
			'activo' => 0,
		];
	}

	echo json_encode($historial);
	exit;
}

if (isset($_GET['estado'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

	date_default_timezone_set('America/Caracas');
	$activo = null;
	$ultimaActualizacion = null;
	$registros = @file(__DIR__ . '/registros.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if ($registros !== false) {
		foreach (array_reverse($registros) as $registro) {
			if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+-\s+Activo:\s*(.*)$/i', $registro, $coincidencia)) {
				$ultimaActualizacion = DateTime::createFromFormat('Y-m-d H:i:s', $coincidencia[1]);
				$activo = trim($coincidencia[2]) ?: null;
				break;
			}
		}
	}

	$segundosTranscurridos = $ultimaActualizacion !== null ? time() - $ultimaActualizacion->getTimestamp() : null;

	if ($segundosTranscurridos !== null && $segundosTranscurridos > 150) {
		$activo = 0;
	}

	echo json_encode([
		'activo' => $activo,
		'segundosTranscurridos' => $segundosTranscurridos,
		'ultimaActualizacion' => $ultimaActualizacion ? $ultimaActualizacion->format('c') : null,
	]);
	exit;
}

date_default_timezone_set('America/Caracas');
session_start();

if (!isset($_SESSION['ultimoRegistroAcceso']) || time() - $_SESSION['ultimoRegistroAcceso'] > 2) {
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
	$fecha = date('Y-m-d H:i');
	$log = "[$fecha] IP: $ip" . PHP_EOL;
	file_put_contents(__DIR__ . '/accesos.txt', $log, FILE_APPEND | LOCK_EX);
	$_SESSION['ultimoRegistroAcceso'] = time();
}
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#0d3b46">
	<meta name="description" content="Estado energético">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="Sin-luz">
	<link rel="manifest" href="manifest.json">
		<link rel="icon" type="image/png" sizes="192x192" href="fotos/icon-192x192.png">
		<link rel="apple-touch-icon" href="fotos/icon-192x192.png">
	<title>Estado</title>
	<style>
		body { background: #d4d4d4; color: #1f1f1f; font-family: "Segoe UI", sans-serif; margin: 0; }
		main { background: #f3f3f3; border: 1px solid #a6a6a6; box-shadow: 0 2px 6px #999; box-sizing: border-box; min-height: 100vh; padding: 1.5rem; width: 100%; }
		.encabezado { align-items: flex-start; display: flex; justify-content: space-between; gap: 1rem; }
		.boton-salir { background: #c50f1f; border: 1px solid #a30d1a; border-radius: 4px; color: #fff; cursor: pointer; font: inherit; padding: 0.4rem 0.8rem; }
		.boton-salir:hover { background: #a30d1a; }
		h3, p { margin: 0.2rem 0; }
		#activo { font-size: 1.2rem; font-weight: bold; }
		.con-energia { color: #107c10; }
		.sin-energia { color: #c50f1f; }
		#grafico { background: #fff; border: 1px solid #a6a6a6; width: 100%; height: 120px; }
		.carrusel { margin-top: 1rem; overflow: hidden; position: relative; width: 100%; }
		.carrusel-pista {
			position: relative;
			width: 100%;
			height: 420px;
			overflow: hidden;
			background: #f5f5f5;
			border: 1px solid #b9b9b9;
			box-sizing: border-box;
		}
		.carrusel-pista img {
			display: block;
			position: absolute;
			inset: 0;
			height: 100%;
			width: 100%;
			object-fit: contain;
			object-position: center;
			background: #f5f5f5;
			opacity: 0;
			transform: scale(1.04);
			transition: opacity 1.1s ease, transform 1.4s ease;
			z-index: 0;
		}

		@media (max-width: 480px) {
			.carrusel-pista {
				height: 380px;
			}
		}
		.carrusel-pista img.activo {
			opacity: 1;
			transform: scale(1);
			z-index: 1;
		}
	</style>
</head>
<body>
	<main>
		<div class="encabezado">
			<h3>Servicio eléctrico Res. Doña Maruja</h3>
			<button class="boton-salir" type="button" onclick="if (history.length > 1) { history.back(); } else { window.close(); }">Salir</button>
		</div>
		<p>Fecha: <?php date_default_timezone_set('America/Caracas'); echo date('d/m/Y'); ?></p>
		<p id="activo" aria-live="polite">Consultando...</p>
		<div id="desconexiones" aria-live="polite"></div>
		<canvas id="grafico" aria-label="Gráfico de activo en el tiempo"></canvas>
		<div class="carrusel" aria-label="Imágenes informativas">
			<div class="carrusel-pista" id="carrusel-pista">
				<img src="fotos/logo%20CCS2.jpg" alt="Logo CCS2">
				<img src="fotos/css2.jpeg" alt="Imagen CCS">
				<img src="fotos/css3.jpeg" alt="Imagen CCS3">
			</div>
		</div>
	</main>

	<script>
		const elementoActivo = document.getElementById('activo');
		const elementoDesconexiones = document.getElementById('desconexiones');
		const grafico = document.getElementById('grafico');
		const contexto = grafico.getContext('2d');
		const pistaCarrusel = document.getElementById('carrusel-pista');
		const imagenesCarrusel = Array.from(pistaCarrusel.querySelectorAll('img'));
		let imagenActual = 0;
		const formatearTiempoTranscurrido = segundos => {
			const horas = Math.floor(segundos / 3600);
			const minutos = Math.floor((segundos % 3600) / 60);

			if (horas > 0) {
				return `${horas} h ${minutos} min`;
			}

			return `${minutos} min`;
		};

		const formatearHora = timestamp => new Date(timestamp).toLocaleTimeString('es-VE', {
			hour: '2-digit',
			minute: '2-digit',
			hour12: false,
		});

		const obtenerTimestamp = tiempo => new Date(tiempo.replace(' ', 'T')).getTime();

		const crearDesconexion = (inicio, fin) => ({
			inicio,
			fin,
			duracion: fin === null ? null : Math.max(0, Math.floor((fin - inicio) / 1000)),
		});

		const formatearDuracion = segundos => {
			const minutos = Math.floor(segundos / 60);
			const horas = Math.floor(minutos / 60);
			const minutosRestantes = minutos % 60;

			return horas > 0 ? `${horas} h ${minutosRestantes} min` : `${minutos} min`;
		};

		function mostrarDesconexiones(datos) {
			const desconexiones = [];
			let energiaAnterior = null;
			let tiempoAnterior = null;
			let inicioDesconexion = null;

			datos.forEach(dato => {
				const energiaActual = Number(dato.activo);
				const tiempoActual = obtenerTimestamp(dato.tiempo);
				const hayInterrupcion = tiempoAnterior !== null && tiempoActual - tiempoAnterior > 150000;   // 150 seg. tiempo para declarar que es una desconexion

				if (hayInterrupcion && energiaAnterior === 1) {
					desconexiones.push(crearDesconexion(tiempoAnterior, tiempoActual));
					inicioDesconexion = null;
				} else if (energiaActual === 0 && energiaAnterior !== 0) {
					inicioDesconexion = tiempoActual;
				} else if (energiaActual === 1 && inicioDesconexion !== null) {
					desconexiones.push(crearDesconexion(inicioDesconexion, tiempoActual));
					inicioDesconexion = null;
				}

				energiaAnterior = energiaActual;
				tiempoAnterior = tiempoActual;
			});

			if (inicioDesconexion !== null) {
				desconexiones.push(crearDesconexion(inicioDesconexion, null));
			}

			elementoDesconexiones.replaceChildren(...desconexiones.map((desconexion, indice) => {
				const linea = document.createElement('p');
				linea.textContent = `Desconexión ${indice + 1}a las ${formatearHora(desconexion.inicio)} - Duración: ${desconexion.duracion === null ? 'En curso' : formatearDuracion(desconexion.duracion)}`;
				return linea;
			}));
		}

		async function actualizarActivo() {
			try {
				const respuesta = await fetch('visor.php?estado=1', { cache: 'no-store' });
				if (!respuesta.ok) throw new Error('No se pudo consultar el estado.');

				const datos = await respuesta.json();
				const activo = Number(datos.activo);
				elementoActivo.className = '';
				if (activo === 1) {
					elementoActivo.textContent = 'Con energía eléctrica';
					elementoActivo.classList.add('con-energia');
				} else if (activo === 0) {
					const tiempo = datos.segundosTranscurridos === null ? '' : ` (${formatearTiempoTranscurrido(Number(datos.segundosTranscurridos))})`;
					elementoActivo.textContent = `Sin electricidad${tiempo}`;
					elementoActivo.classList.add('sin-energia');
				} else {
					elementoActivo.textContent = 'Sin datos';
				}
			} catch (error) {
				elementoActivo.className = '';
				elementoActivo.textContent = 'Error al consultar';
			}
		}

		function dibujarGrafico(datos) {
			const ancho = grafico.clientWidth;
			const alto = grafico.clientHeight;
			const margen = { superior: 25, derecho: 20, inferior: 50, izquierdo: 45 };
			const escala = window.devicePixelRatio || 1;
			grafico.width = ancho * escala;
			grafico.height = alto * escala;
			contexto.setTransform(escala, 0, 0, escala, 0, 0);
			contexto.clearRect(0, 0, ancho, alto);

			if (!datos.length) {
				contexto.fillText('Sin datos para graficar', margen.izquierdo, margen.superior);
				return;
			}

			const areaAncho = ancho - margen.izquierdo - margen.derecho;
			const areaAlto = alto - margen.superior - margen.inferior;
			const maximo = Math.max(1, ...datos.map(dato => dato.activo));
			const x = tiempo => {
				const hora = Number(tiempo.slice(11, 13));
				const minuto = Number(tiempo.slice(14, 16));
				const segundo = Number(tiempo.slice(17, 19));
				return margen.izquierdo + ((hora * 3600 + minuto * 60 + segundo) / 86400) * areaAncho;
			};
			const y = valor => margen.superior + areaAlto - (valor / maximo) * areaAlto;

			contexto.strokeStyle = '#888';
			contexto.beginPath();
			contexto.moveTo(margen.izquierdo, margen.superior);
			contexto.lineTo(margen.izquierdo, alto - margen.inferior);
			contexto.lineTo(ancho - margen.derecho, alto - margen.inferior);
			contexto.stroke();
			contexto.fillText(maximo === 1 ? '120 V' : maximo, 8, margen.superior + 5);
			contexto.fillText('0', 25, alto - margen.inferior + 5);
			contexto.font = '12px sans-serif';
			for (let hora = 0; hora < 24; hora += 2) {
				const posicionX = margen.izquierdo + (hora / 24) * areaAncho;
				contexto.fillText(hora === 0 ? '12' : `${hora}`, posicionX - 14, alto - 15);
			}
			contexto.fillText('12 PM', ancho - margen.derecho - 18, alto - 15);

			const baseCero = y(0);
			const segmentosRojos = [];

			for (let indice = 0; indice < datos.length; indice++) {
				const actual = datos[indice];
				const valorActual = Number(actual.activo);
				const siguiente = datos[indice + 1];

				if (valorActual === 0) {
					let finCorte = null;
					for (let j = indice + 1; j < datos.length; j++) {
						if (Number(datos[j].activo) !== 0) {
							finCorte = datos[j].tiempo;
							break;
						}
					}

					if (finCorte !== null) {
						segmentosRojos.push({ inicio: actual.tiempo, fin: finCorte });
					}
				}

				if (siguiente) {
					const diferenciaMs = new Date(siguiente.tiempo).getTime() - new Date(actual.tiempo).getTime();
					if (diferenciaMs > 150000) {
						segmentosRojos.push({ inicio: actual.tiempo, fin: siguiente.tiempo });
					}
				}
			}

			if (datos.length > 0) {
				const ultimoTiempo = new Date(datos[datos.length - 1].tiempo).getTime();
				if (Date.now() - ultimoTiempo > 150000) {
					segmentosRojos.push({
						inicio: datos[datos.length - 1].tiempo,
						fin: new Date().toISOString().slice(0, 19).replace('T', ' '),
					});
				}
			}

			segmentosRojos.forEach(segmento => {
				contexto.fillStyle = 'rgba(197, 15, 31, 0.10)';
				contexto.fillRect(x(segmento.inicio), baseCero - 8, x(segmento.fin) - x(segmento.inicio), 16);

				contexto.strokeStyle = '#c50f1f';
				contexto.lineWidth = 3.5;
				contexto.beginPath();
				contexto.moveTo(x(segmento.inicio), baseCero);
				contexto.lineTo(x(segmento.fin), baseCero);
				contexto.stroke();
			});

			datos.forEach(dato => {
				const valor = Number(dato.activo);
				const xActual = x(dato.tiempo);

				if (valor === 0) {
					contexto.fillStyle = '#c50f1f';
					contexto.beginPath();
					contexto.arc(xActual, baseCero, 4, 0, Math.PI * 2);
					contexto.fill();
					return;
				}

				contexto.fillStyle = '#087f5b';
				contexto.beginPath();
				contexto.arc(xActual, y(valor), 4, 0, Math.PI * 2);
				contexto.fill();
			});
		}

		async function actualizar() {
			await actualizarActivo();
			try {
				const respuesta = await fetch('visor.php?historial=1', { cache: 'no-store' });
				if (!respuesta.ok) throw new Error('No se pudo consultar el historial.');
				const datos = await respuesta.json();
				mostrarDesconexiones(datos);
				dibujarGrafico(datos);
			} catch (error) {
				mostrarDesconexiones([]);
				dibujarGrafico([]);
			}
		}

		function mostrarImagenCarrusel(indice) {
			imagenesCarrusel.forEach((imagen, i) => {
				imagen.classList.toggle('activo', i === indice);
			});
		}

		if (imagenesCarrusel.length) {
			mostrarImagenCarrusel(0);
		}

		actualizar();
		setInterval(actualizar, 5000);
		setInterval(() => {
			if (!imagenesCarrusel.length) return;
			imagenActual = (imagenActual + 1) % imagenesCarrusel.length;
			mostrarImagenCarrusel(imagenActual);
		}, 7000);    // tiempo de cambio de imagen en el carrusel
		window.addEventListener('resize', actualizar);
	</script>
	<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', () => {
				navigator.serviceWorker.register('./sw.js').catch(error => {
					console.error('No se pudo registrar el service worker:', error);
				});
			});
		}
	</script>
</body>
</html>
