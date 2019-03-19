<?php
// Based on this schedule structure
// L-J:11.00-14.20&&17.30-21.50;V-V:11.00-14.30&&17.30-22.20;S-S:11.59-14.50&&17.30-22.50;D-D:17.30-21.50;F-F:17.30-21.5
define('D', 7); // Domingos (Sundays)
define('DF', 8); // Domingos y Festivos (Sundays and Holidays)
define('F', 9); // Festivos (Holidays)

function get_festivos() {
	$function_festivos = Configure::read('Client.festivos');
	// Si está configurada la función de festivos se invoca, en caso contrario llama los festivos de Colombia
	if($function_festivos && !empty($function_festivos)) {
		return call_user_func('get_festivos_' . $function_festivos);
	}

	return call_user_func('get_festivos_colombia');
}
function get_festivos_ecuador() {
	return array();
}

function get_festivos_colombia() {
	/***************************************************************/
	// Programado por:
	// Juan Carlos Borrero
	// juancabo@gmail.com
	// Diciembre del 2.003
	// Carga un arreglo con los dias festivos en colombia asi:
	// $festivo[aÒo][mes][dia] = true
	// simplemente valide if (isset($festivo[aÒo][mes][dia])) para saber si es festivo
	//óóóóóóóóóóóóóóóóóóóóóóóóóóóó
	// Fecha del dÌa
	$hoy = date('d/m/y');
	$y = date('Y');
	// Dias festivos Colombia
	// Primero. Los siempre fijos.  (6)
	// $y es la variable para el aÒo que se desea calcular
	$festivo[$y][1][1]   = true;     // Primero de Enero
	$festivo[$y][5][1]   = true;     // Dia del Trabajo 1 de Mayo
	$festivo[$y][7][20]  = true;     // Independencia 20 de Julio
	$festivo[$y][8][7]   = true;     // Batalla de Boyac· 7 de Agosto
	$festivo[$y][12][8]  = true;     // Maria Inmaculada 8 diciembre (religiosa)
	$festivo[$y][12][25] = true;     // Navidad 25 de diciembre
	//óóóóóóóóóóóóóóóóóóóóóóóóóóóó
	// Segundo. Los de fecha fija y que por la ley Emiliani se mueven al siguiente
	// lunes a menos que caigan en lunes   (7)

	$fechas_emiliani[] = calcula_emiliani($y,6,1);  // Reyes Magos Enero 6
	$fechas_emiliani[] = calcula_emiliani($y,19,3);  // San Jose Marzo 19
	$fechas_emiliani[] = calcula_emiliani($y,29,6);  // San Pedro y San Pablo Junio 29
	$fechas_emiliani[] = calcula_emiliani($y,15,8);  // AsunciÛn Agosto 15
	$fechas_emiliani[] = calcula_emiliani($y,12,10);  // Descubrimiento de AmÈrica Oct 12
	$fechas_emiliani[] = calcula_emiliani($y,1,11);  // Todos los santos Nov 1
	$fechas_emiliani[] = calcula_emiliani($y,11,11);  // Independencia de Cartagena Nov 11
	//óóóóóóóóóóóóóóóóóóóóóóóóóóóó-
	// Tercero. Los de fecha variable que dependen del dÌa de pascua y no se trasladan al
	// siguiente lunes  (2)
	// easter_date() es la funciÛn php que retorna el dÌa de pascua o domingo santo
	$pascua_mes=date("m", get_easter_datetime($y)->getTimestamp());
	$pascua_dia=date("d", get_easter_datetime($y)->getTimestamp());
	// Jueves Santo dia de pascua -3 dÌas
	$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-3,$y));
	$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-3,$y));
	$festivo[$y][$mes_festivo+0][$dia_festivo+0] = true;
	// Viernes Santo dia de pascua -2 dÌas
	$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-2,$y));
	$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-2,$y));
	$festivo[$y][$mes_festivo+0][$dia_festivo+0] = true;
	//óóóóóóóóóóóóóóóóóóóóóóóóóóóó-
	// Cuarto. Los de fecha variable que dependen del dÌa de pascua y  se trasladan al
	// siguiente lunes por la ley emiliani (3)
	// AscenciÛn el SeÒor pascua + 39 dias
	$fechas_emiliani[] = calcula_emiliani($y, date("d", mktime(0,0,0,$pascua_mes,$pascua_dia+39,$y))+0, date("n", mktime(0,0,0,$pascua_mes,$pascua_dia+39,$y))+0);
	// Corpus Cristi pascua + 60 dÌas
	$fechas_emiliani[] = calcula_emiliani($y, date("d", mktime(0,0,0,$pascua_mes,$pascua_dia+60,$y))+0, date("n", mktime(0,0,0,$pascua_mes,$pascua_dia+60,$y))+0);
	// Sagrado CorazÛn  pascua + 68 dÌas
	$fechas_emiliani[] = calcula_emiliani($y, date("d", mktime(0,0,0,$pascua_mes,$pascua_dia+68,$y))+0, date("n", mktime(0,0,0,$pascua_mes,$pascua_dia+68,$y))+0);
	//óóóóóóóóóóóóóóóóóóóóóóóóóóóó
	// Otros Eventos que pueden calcularse

	// MiÈrcoles de Ceniza  pascua ñ 46 dias (no es festivo)
	// $mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-46,$y))+0;
	// $dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-46,$y))+0;
	// Lunes de Carnaval Barranquilla  pascua ñ 48 dias  (no es festivo nacional)
	// $mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-48,$y))+0;
	// $dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-48,$y))+0;
	// Martes de Carnaval Barranquilla pascua ñ 47 dias  (no es festivo nacional)
	// $mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-47,$y))+0;
	// $dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-47,$y))+0;
	foreach($fechas_emiliani as $f) {
		$festivo[$y][$f[0]][$f[1]]   = true;
	}

	return $festivo;
}

function get_festivos_peru() {
	$hoy = date('d/m/y');
	$y = date('Y');

	// Dias festivos lima
	// Primero. Los siempre fijos.
	// $y es la variable para el año que se desea calcular
	$festivo[$y][1][1]   = true;     // Primero de Enero
	$festivo[$y][5][1]   = true;     // Dia del Trabajo 1 de Mayo
	$festivo[$y][6][29]  = true;     // San Pedro y San Pablo
	$festivo[$y][7][28]  = true;     // Independencia Perú
	$festivo[$y][7][29]  = true;     // Fiestas patrias
	$festivo[$y][8][30]  = true;     // Santa Rosa de Lima
	$festivo[$y][10][8]  = true;     // Combate NAval de Angamos
	$festivo[$y][11][1]  = true;     // Día de todos los santos
	$festivo[$y][12][8]  = true;     // Inmaculada concepción
	$festivo[$y][12][25] = true;     // Navidad 25 de diciembre

	// easter_date() es la funciÛn php que retorna el dÌa de pascua o domingo santo
	$pascua_mes = date("m", get_easter_datetime($y)->getTimestamp());
	$pascua_dia = date("d", get_easter_datetime($y)->getTimestamp());
	// Jueves Santo dia de pascua -3 dÌas
	$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-3,$y));
	$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-3,$y));
	$festivo[$y][$mes_festivo+0][$dia_festivo+0] = true;
	// Viernes Santo dia de pascua -2 dÌas
	$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-2,$y));
	$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-2,$y));
	$festivo[$y][$mes_festivo+0][$dia_festivo+0] = true;

	return $festivo;
}

function get_festivos_argentina() {
	return array();
}

function calcula_emiliani($y, $dia_festivo, $mes_festivo) {
	// funcion que mueve una fecha diferente a lunes al siguiente lunes en el
	// calendario y se aplica a fechas que estan bajo la ley emiliani

	// Extrae el dia de la semana
	// 0 Domingo Ö 6 S·bado
	$dd = date("w",mktime(0,0,0,$mes_festivo,$dia_festivo,$y));
	switch ($dd)
	{
		case 0:                                    // Domingo
		$dia_festivo = $dia_festivo + 1;
		break;
		case 2:                                    // Martes.
		$dia_festivo = $dia_festivo + 6;
		break;
		case 3:                                    // MiÈrcoles
		$dia_festivo = $dia_festivo + 5;
		break;
		case 4:                                     // Jueves
		$dia_festivo = $dia_festivo + 4;
		break;
		case 5:                                     // Viernes
		$dia_festivo = $dia_festivo + 3;
		break;
		case 6:                                     // S·bado
		$dia_festivo = $dia_festivo + 2;
		break;
	}
	// NÛtese que el dÌa puede pasar al mes siguiente.
	// Esto es v·lido pues el dÌa 32 del mes de enero equivale al dia 1 del mes de febrero
	// Recalcula el verdadero dÌa del mes para cerciorarnos de la fecha en caso de
	// pasar de un mes a otroÖ
	$mes = date("n", mktime(0,0,0,$mes_festivo,$dia_festivo,$y))+0;
	$dia = date("d", mktime(0,0,0,$mes_festivo,$dia_festivo,$y))+0;
	return array($mes, $dia);
}
/***************************************************************/

function estaAbierto($horario, $mktime = null) {
	$abierto24h = true; // Cambiar a false para desactivar las 24 horas.

	if(isset($mktime)) {
		//Dia de la semana
		$diaHoy    = (int)date('N', $mktime);
		$horaHoy   = (int)date('G', $mktime);
		$minutoHoy = (int)date('i', $mktime);

		//Dia del mes
		$diaMes = (int)date('j', $mktime);
		$mes    = (int)date('n', $mktime);
		$anio   = (int)date('Y', $mktime);
	} else {
		//Dia de la semana
		$diaHoy    = (int)date('N');
		$horaHoy   = (int)date('G');
		$minutoHoy = (int)date('i');

		//Dia del mes
		$diaMes = (int)date('j');
		$mes    = (int)date('n');
		$anio   = (int)date('Y');
	}

	$festivo = get_festivos();
	if(isset($festivo[$anio][$mes][$diaMes])) {
		$diaHoy = 8;
	}

	// Si es fin de semana o puente es 24h
	if(in_array($diaHoy, array(6, 7, 8))) {
		$abierto24h = true;
	}

	$abierto = false;

	//print_r(array($diaHoy, $horaHoy, $minutoHoy));

	$HorarioEstablecimiento = obtenerHorario($horario);
	if(is_array($HorarioEstablecimiento)) {
		foreach($HorarioEstablecimiento as $Horario) {
			// Valido que el día de hoy esté entre los días que abre el restaurante
			if($abierto) continue; // Si ya se validó y está abierto en algún horario para qué seguir revisando.
			if( in_array($diaHoy, $Horario['dias']) ||
				($diaHoy == 7 && in_array(8, $Horario['dias'])) ||
				($diaHoy == 8 && in_array(9, $Horario['dias'])) ) {

				foreach($Horario['horarios'] as $rangoHora) {
					if( $horaHoy >= (int)$rangoHora['desde']['hora'] && $horaHoy <= (int)$rangoHora['hasta']['hora']) {
						$abierto = true;
						// Si la hora actual es igual a la hora que cierra, hasta que los minutos no pasen est· abierto, si no, cerrado
						if($horaHoy == (int)$rangoHora['hasta']['hora'] && $minutoHoy >= (int)$rangoHora['hasta']['minutos'] ) {
							$abierto = false;
						}

						// Si la hora es igual a la  hora que abre y los minutos aún no alcanzan el mínimo, entonces cerrado
						if($horaHoy == (int)$rangoHora['desde']['hora'] && $minutoHoy < (int)$rangoHora['desde']['minutos'] ) {
							$abierto = false;
						}
					}
				}
			}
		}

		// Si está abierto pero pasa la hora de cierre, cerrar hasta la hora de paertura
		$horaCierre = array(0, 59, 7); //Configure::read('Client.hora_cierre');

		// Cierres programados para diciembre:
		// 24 y 31 : de 7 am a 9:30 pm
		if( $mes == 12 && ($diaMes == 24 || $diaMes == 31)) {
			$horaCierre = array(21, 30, 7);
		} else if(($mes == 12 && $diaMes == 25) || ($mes == 1 && $diaMes == 1) ){
			$abierto24h = false;
			$horaCierre = array(23, 59, 8);
		}

		if( $abierto === true && $abierto24h == false) {
			// Si es la hora de cierre y pasamos los minutos de cierre, entonces está cerrado
			if( ($horaHoy == $horaCierre[0] && $minutoHoy >= $horaCierre[1])
				// Si es mayor a la hora de cierre o menor a la hora de apertura, entonces está cerrado
				|| ($horaCierre[0] > $horaCierre[2] && (($horaHoy > $horaCierre[0] && $horaHoy <= 23) || ($horaHoy >= 0 && $horaHoy < $horaCierre[2])))
				|| ($horaCierre[0] < $horaCierre[2] && ($horaHoy > $horaCierre[0] && $horaHoy < $horaCierre[2]))
				) {
				$abierto = false;
			}
		}

		// Marca como abierto si se puede prepedir;
		if($abierto === false && prepedir($HorarioEstablecimiento)) {
			$abierto = 2;
		}

		return $abierto;
	} return false; // No es un horario
}

function cuantoFalta($horario, $mktime = null)
{
    if (!isset($mktime)) {
        $mktime = time();
    }

	$minutesLeft = minutesLeft(obtenerHorario($horario), $mktime);

	$timeLeft = '';
	$hourLeft = floor($minutesLeft / 60);
	$minutesHourLeft = ($hourLeft * 60) - $minutesLeft;

	if($minutesHourLeft < 0) $minutesHourLeft *= -1;
	if($hourLeft > 0) {
		$timeLeft = $hourLeft . ' {hora' . (($hourLeft > 1) ? 's' : '') . '}';
	}

	if($hourLeft > 0)  $timeLeft .= ' ';
	if($minutesHourLeft) {
		$timeLeft .= $minutesHourLeft . ' {minuto' . (($minutesHourLeft > 1) ? 's' : '') . '}';
	}

	return str_replace(array('{hora}', '{minuto}', '{horas}', '{minutos}'), array(__('hora'), __('minuto'), __('horas'), __('minutos')), $timeLeft);
}

function prepedir($HorarioEstablecimiento) {
	//$minutesLeft = minutesLeft($HorarioEstablecimiento);
	return false; //($minutesLeft <= 60 && $minutesLeft > 0); // Devuelve verdadero si hay menos de 6 horas hasta que abra
}

function minutesLeft($HorarioEstablecimiento, $mktime = null)
{
    if (!isset($mktime)) {
        $mktime = time();
    }
	//Dia de la semana
	$diaHoy = (int)date('N', $mktime);
	$horaHoy = (int)date('G', $mktime);
	$minutoHoy = (int)date('i', $mktime);

	//Dia del mes
	$diaMes = (int)date('j', $mktime);
	$mes    = (int)date('n', $mktime);
	$anio   = (int)date('Y', $mktime);

	$festivo = get_festivos();
	if(isset($festivo[$anio][$mes][$diaMes])) {
		$diaHoy = 8;
	}

	// Este parámetro se supone que es un arreglo $HorarioEstablecimiento, si llega un string intenta obtener el horario
	if(!is_array($HorarioEstablecimiento)) {
		$HorarioEstablecimiento = obtenerHorario($HorarioEstablecimiento);
	}

	$minutesLeft = 0;
	foreach($HorarioEstablecimiento as $Horario) {
		// Si abre dentro del rango
		if( in_array($diaHoy, $Horario['dias'])
			|| ($diaHoy == 7 && in_array(8, $Horario['dias']))
			|| ($diaHoy == 8 && in_array(9, $Horario['dias'])) ) {
			// Valido los horarios en los que abra y me de el tiempo left
			$minutesLeft = minutosEnHorarios($Horario['horarios'], $horaHoy, $minutoHoy);
		}
	}

	// No abre en un rango de hoy, miro el siguiente dia que abra
	if($minutesLeft != -1 && $minutesLeft == 0) {
		if($diaHoy == 8) $diaHoy = date('N', $mktime);

		$diasSemana = array();
		$horarioDia = array();

		foreach($HorarioEstablecimiento as $Horario) {
			foreach($Horario['dias'] as $dia) {
				$diasSemana[] = $dia;
				$horarioDia[$dia] = $Horario['horarios'];
			}
		}

		$buscarDiaSiguiente = false;

		// Si el dia de hoy es el último día de la semana y ya sé que no abre hoy
		// cuento los minutos hasta que acabe el día y empiezo en lunes
		if($diaHoy == 6) {
			$minutosHastaQueAcabeElDia = ((23 - $horaHoy) * 60) + (59 - $minutoHoy);
			$minutesLeft += $minutosHastaQueAcabeElDia;

			$diaHoy = 0;
			// Cuanto falta para el primer rango del día de hoy
			if(isset($horarioDia[$diaHoy])) {
				$minutosDiaLunes = minutosEnHorarios($horarioDia[$diaHoy], 0, 0);

				if($minutosDiaLunes != 0) $minutesLeft += $minutosDiaLunes;
				else $buscarDiaSiguiente = true;
			} else {
				$buscarDiaSiguiente = true;
			}
		} else {
			// Si abre es un dia anterior, debo contar los dias hasta que acabe la semana y que se convierta en lunes:
			$abreEstaSemana = false;
			foreach($diasSemana as $dia) {
				if($diaHoy < $dia) {
					$abreEstaSemana = true;
				}
			}

			//echo (($abreEstaSemana) ? 'Abre esta semana' : 'No abre esta semana') . '<br/>';

			if(!$abreEstaSemana) {
				$diasLeft = 0;

				for($i = $diaHoy; $i < 7; $i++) {
					$diasLeft++;
				}

				$diaHoy = 0;
				$minutesLeft += $diasLeft * 1440;

				// Cuanto falta para el primer rango del día de hoy
				if(isset($horarioDia[$diaHoy])) {
					$minutosDiaLunes = minutosEnHorarios($horarioDia[$diaHoy], 0, 0);

					if($minutosDiaLunes != 0) $minutesLeft += $minutosDiaLunes;
					else $buscarDiaSiguiente = true;
				} else {
					$buscarDiaSiguiente = true;
				}
			} else {
				$buscarDiaSiguiente = true;
			}
		}

		// Si aún no abre el lunes, si cuento los días hasta que abre
		if($buscarDiaSiguiente) {
			$diasSemana = array_unique(array_values($diasSemana));
			foreach($diasSemana as $dia) {
				if($dia > $diaHoy) {
					$diasLeft = -1;
                    if ($dia == F && $diaHoy == D) {
                        $diaHoy = F - 1;
                    }

					for($i = $diaHoy; $i < $dia; $i++) {
						$diasLeft++;
					}

					$minutesLeft += $diasLeft * 1440;

					// Cuanto falta para que acabe hoy:
					// Dia de hoy (lunes)
					$minutosHastaQueAcabeElDia = ((23 - $horaHoy) * 60) + (59 - $minutoHoy);
					$minutesLeft += $minutosHastaQueAcabeElDia;

					// Cuanto falta para el primer rango
					if(isset($horarioDia[$dia])) {
						$minutesLeft += minutosEnHorarios($horarioDia[$dia], 0, 0);
					}

					return $minutesLeft;
				}
			}
		}
	}

	return ($minutesLeft == -1) ? 0 : $minutesLeft;
}

function minutosEnHorarios($horarios, $horaHoy, $minutoHoy) {
	if(!is_array($horarios)) return 0;

	$minutos = 0;
	foreach($horarios as $i=>$rangoHora) {
		if($minutos != 0) continue;

		$abierto = false;
		if( $horaHoy >= (int)$rangoHora['desde']['hora'] && $horaHoy <= (int)$rangoHora['hasta']['hora']) {
			$abierto = true;
			// Si la hora actual es igual a la hora que cierra, hasta que los minutos no pasen est· abierto, si no, cerrado
			if($horaHoy == (int)$rangoHora['hasta']['hora'] && $minutoHoy >= (int)$rangoHora['hasta']['minutos'] ) {
				$abierto = false;
			}

			// Si la hora es igual a la  hora que abre y los minutos aún no alcanzan el mínimo, entonces cerrado
			if($horaHoy == (int)$rangoHora['desde']['hora'] && $minutoHoy < (int)$rangoHora['desde']['minutos'] ) {
				$abierto = false;
			}
		}

		if($abierto) return -1;

		if( $horaHoy < $rangoHora['desde']['hora'] || ( $horaHoy == $rangoHora['desde']['hora'] && $minutoHoy < $rangoHora['desde']['minutos'] ) ) {
			$minutosHoy = $horaHoy * 60 + $minutoHoy;
			$rangoMinutos = (int)$rangoHora['desde']['hora'] * 60 + (int)$rangoHora['desde']['minutos'];

			$minutos += $rangoMinutos - $minutosHoy;
		}
	}

	return $minutos;
}

function obtenerHorario($horario) {
	$festivo = get_festivos();

	if(empty($horario)) return false;
	if(substr($horario, -1) == ';') $horario = substr($horario, 0, -1);

	$semana = array('', 'L', 'M', 'W', 'J', 'V', 'S', 'D', 'DF', 'F');
	$compatibilidadAnterior = array('TD' => 'L-DF', 'ES' => 'L-V', 'FS' => 'S-DF', 'XY' => 'L-J',
									'EP' => 'L-W', 'OI' => 'J-V', 'WZ' => 'V-D', 'UQ' => 'L-S',
									'R' => 'S-S', 'HK' => 'V-S', 'MN' => 'DF-DF',
									'MS' => 'M-S', 'AP' => 'L-W', 'LI' => 'M-J', 'Hk' => 'V-S',
									'Mn' => 'DF-DF', 'MM' => 'DF-DF', 'D' => 'D-D');

	// Divido los horarios en rangos
	$rangosHorario = explode(';', $horario);

	$HorarioEstablecimiento = array();
	// Interpretamos el horario en un array
	foreach($rangosHorario as $rango) {
		$arrayBloques = explode(':', $rango);

		// Si es cÛdigo anterior
		if(strpos($arrayBloques[0], '-') === FALSE)
			$arrayBloques[0] = str_replace(array_keys($compatibilidadAnterior), array_values($compatibilidadAnterior),  $arrayBloques[0]);

		$arrayDias    = explode('-', $arrayBloques[0]);
		$arrayRangos  = (strpos($arrayBloques[1], '&&')) ? explode('&&', $arrayBloques[1]) : array($arrayBloques[1]);

		$arrayHoras   = array();
		foreach($arrayRangos as $rangoHora) {
			$arrayHora    = explode('-', $rangoHora);

			$horaInicial  = explode('.', $arrayHora[0]);
			$horaFinal    = explode('.', $arrayHora[1]);

			if(!isset($horaInicial[1])) $horaInicial[1] = 0;
			if(!isset($horaFinal[1])) $horaFinal[1] = 0;

			if((int)$horaInicial[0] > (int)$horaFinal[0]) {
				// Si la hora final es menor, esto se toma como un salto al otro día, se llega a las 23 y se pasa hasta la otra hora
				$arrayHoras[] = array('desde' => array('hora' => (int)$horaInicial[0], 'minutos' => (int)$horaInicial[1]),
								  	  'hasta' => array('hora' => 23, 'minutos' => 59));

				$arrayHoras[] = array('desde' => array('hora' => 0, 'minutos' => 1),
								  	  'hasta' => array('hora' => (int)$horaFinal[0], 'minutos' => (int)$horaFinal[1]));
			} else {
				$arrayHoras[] = array('desde' => array('hora' => (int)$horaInicial[0], 'minutos' => (int)$horaInicial[1]),
								  	  'hasta' => array('hora' => (int)$horaFinal[0], 'minutos' => (int)$horaFinal[1]));
			}
		}

		if(!isset($arrayDias[1])) $arrayDias[1] = $arrayDias[0];
		$HorarioEstablecimiento[] = array('dias' => entreDias(array_search($arrayDias[0], $semana), array_search($arrayDias[1], $semana)),
										  'horarios' => $arrayHoras);
	}

	return $HorarioEstablecimiento;
}

function entreDias($desde, $hasta) {
	$dias = array();

	if(!is_int($desde)) $desde = (is_int($hasta)) ? $hasta : 0;
	if($desde > $hasta) {
		if($desde != 8 && $desde != 9) {
			for($i = $desde; $i <= 7; $i++) {
				$dias[] = $i;
			}
		} else {
			$dias[] = $desde;
		}
		for($i = 1; $i <= $hasta; $i++) {
			$dias[] = $i;
		}
		//print_r(array('desde' => $desde, 'hasta' => $hasta, 'dias' => $dias));
	}
	else if($desde < $hasta) {
		for($i = $desde; $i <= $hasta; $i++) $dias[] = $i;
	}
	else if($desde == $hasta) $dias[] = $desde;

	return $dias;
}

//This is the function that tell us if a point is inside a coberage
function inZone($pCobertura, $pLat, $pLng)
{
        $isInZone = 0;

        //Convertimos la cobertura en un arreglo de puntos donde cada punto es un arreglo que contiene lat y lng
        $arrayCobertura = explode(';', $pCobertura);

        $points = array();

        for ($i=0; $i<sizeof($arrayCobertura); $i++)
        {
                $arrayLatLng = explode(',', $arrayCobertura[$i]);
                if(sizeof($arrayLatLng) == 2) {
                        $points[$i][0] = trim($arrayLatLng[0]);
                        $points[$i][1] = trim($arrayLatLng[1]);
                }
        }

        $j = sizeof($points)-1;

        for($i = 0; $i<sizeof($points); $j = $i++)
        {
                if (((($points[$i][1] <= $pLng) && ($pLng < $points[$j][1])) || (($points[$j][1] <= $pLng) && ($pLng < $points[$i][1]))) &&
            ($pLat < ($points[$j][0] - $points[$i][0]) * ($pLng - $points[$i][1]) / ($points[$j][1] - $points[$i][1]) + $points[$i][0]))
                $isInZone++;
    }

        return $isInZone%2;
}

function get_easter_datetime($year) {
	$base = new DateTime("$year-03-21");
	$days = easter_days($year);
	return $base->add(new DateInterval("P{$days}D"));
}

/**
 * Converts the schedule string to an object and returns the value calculated by minutesToClose method
 *
 * @param string $schedule This value is the schedule stored in establecimientos_menus.horario
 * @param string|timestamp|null $this_datetime optional Date string date (Y-m-d H:i:s) or timestamp
 *
 * @return int|false How many minutes left. If time is out of schedule this value is false otherwise
 * returns int value (minutes left)
 *
 */
function timeLeftOpen($schedule, $this_datetime = null)
{
    if (isset($this_datetime) && (string) (int)$this_datetime !== (string)$this_datetime) {
        $this_datetime = strtotime($this_datetime);
    }

    if (empty($this_datetime)) {
        $this_datetime = time();
    }

    $schedule_establishment = obtenerHorario($schedule);

    return minutesToClose($schedule_establishment, $this_datetime);
}

/**
 * Finds the schedule that matches the given day and hour also check holidays
 *
 * @param mixed[] $schedule value returned by obtenerHorario method
 * @param int $timestamp
 *
 * @return int|false How many minutes left. If time is out of schedule this value is false otherwise
 * returns int value (minutes left)
 *
 */
function minutesToClose($schedules, $timestamp)
{
    $year = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day = (int)date('d', $timestamp);
    $hour = (int)date('H', $timestamp);
    $minute = (int)date('i', $timestamp);
    $day_of_week = (int)date('N', $timestamp);

    $holidays = get_festivos();
    $today_is_holiday = false;

    if (isset($holidays[$year][$month][$day])) {
        $today_is_holiday = true;
        $day_of_week = DF;
    }

    if (!is_array($schedules)) {
        $schedules = array();
    }

    $minutes = false;
    foreach ($schedules as $key => $schedule) {
        if (!in_array($day_of_week, $schedule['dias'])) {
            if ($today_is_holiday && $day_of_week == DF && in_array(F, $schedule['dias'])) {
                $day_of_week = F;
            } elseif ($day_of_week == D && in_array(DF, $schedule['dias']))  {
                $day_of_week = DF;
            }
        }

        if (in_array($day_of_week, $schedule['dias'])) {
            foreach ($schedule['horarios'] as $key_schedule => $range) {
                if ($hour >= $range['desde']['hora'] && $hour <= $range['hasta']['hora']) {
                    if (($hour == $range['desde']['hora'] && $minute < $range['desde']['minutos'])
                        || ($hour == $range['hasta']['hora'] && $minute > $range['hasta']['minutos'])
                    ) {
                        continue;
                    }

                    $minutes_time = $hour * 60 + $minute;
                    $range_minutes = (int)$range['hasta']['hora'] * 60 + (int)$range['hasta']['minutos'];

                    $minutes = $range_minutes - $minutes_time;
                    break 2;
                }
            }
        }
    }

    return $minutes;
}
