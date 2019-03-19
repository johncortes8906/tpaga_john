<?php
// Programado por:
// Juan Carlos Borrero
// juancabo@gmail.com
// Diciembre del 2.003
// Carga un arreglo con los dias festivos en colombia asi:
// $festivo[año][mes][dia] = true
// simplemente valide if (isset($festivo[año][mes][dia])) para saber si es festivo
//————————————————————————————
// Fecha del día
$hoy = date('d/m/y');
$y = date('Y');
// Dias festivos Colombia
// Primero. Los siempre fijos.  (6)
// $y es la variable para el año que se desea calcular
$festivo[$y][1][1]   = true;     // Primero de Enero
$festivo[$y][5][1]   = true;     // Dia del Trabajo 1 de Mayo
$festivo[$y][7][20]  = true;     // Independencia 20 de Julio
$festivo[$y][8][7]   = true;     // Batalla de Boyacá 7 de Agosto
$festivo[$y][12][8]  = true;     // Maria Inmaculada 8 diciembre (religiosa)
$festivo[$y][12][25] = true;     // Navidad 25 de diciembre
//————————————————————————————
// Segundo. Los de fecha fija y que por la ley Emiliani se mueven al siguiente
// lunes a menos que caigan en lunes   (7)
$dia_festivo=6;$mes_festivo=1;   calcula_emiliani();  // Reyes Magos Enero 6
$dia_festivo=19;$mes_festivo=3;  calcula_emiliani();  // San Jose Marzo 19
$dia_festivo=29;$mes_festivo=6;  calcula_emiliani();  // San Pedro y San Pablo Junio 29
$dia_festivo=15;$mes_festivo=8;  calcula_emiliani();  // Asunción Agosto 15
$dia_festivo=12;$mes_festivo=10; calcula_emiliani();  // Descubrimiento de América Oct 12
$dia_festivo=1;$mes_festivo=11;  calcula_emiliani();  // Todos los santos Nov 1
$dia_festivo=11;$mes_festivo=11; calcula_emiliani();  // Independencia de Cartagena Nov 11
//————————————————————————————-
// Tercero. Los de fecha variable que dependen del día de pascua y no se trasladan al
// siguiente lunes  (2)
// easter_date() es la función php que retorna el día de pascua o domingo santo
$pascua_mes=date("m", easter_date($y));
$pascua_dia=date("d", easter_date($y));
// Jueves Santo dia de pascua -3 días
$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-3,$y));
$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-3,$y));
$festivo[$y][$mes_festivo+0][$dia_festivo+0] = true;
// Viernes Santo dia de pascua -2 días
$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-2,$y));
$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-2,$y));
$festivo[$y][$mes_festivo+0][$dia_festivo+0] = true;
//————————————————————————————-
// Cuarto. Los de fecha variable que dependen del día de pascua y  se trasladan al
// siguiente lunes por la ley emiliani (3)
// Ascención el Señor pascua + 39 dias
$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia+39,$y))+0;
$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia+39,$y))+0;
calcula_emiliani();
// Corpus Cristi pascua + 60 días
$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia+60,$y))+0;
$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia+60,$y))+0;
calcula_emiliani();
// Sagrado Corazón  pascua + 68 días
$mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia+68,$y))+0;
$dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia+68,$y))+0;
calcula_emiliani();
//————————————————————————————
// Otros Eventos que pueden calcularse
// Miércoles de Ceniza  pascua – 46 dias (no es festivo)
// $mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-46,$y))+0;
// $dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-46,$y))+0;
// Lunes de Carnaval Barranquilla  pascua – 48 dias  (no es festivo nacional)
// $mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-48,$y))+0;
// $dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-48,$y))+0;
// Martes de Carnaval Barranquilla pascua – 47 dias  (no es festivo nacional)
// $mes_festivo = date("n", mktime(0,0,0,$pascua_mes,$pascua_dia-47,$y))+0;
// $dia_festivo = date("d", mktime(0,0,0,$pascua_mes,$pascua_dia-47,$y))+0;
function calcula_emiliani() 
{
	// funcion que mueve una fecha diferente a lunes al siguiente lunes en el
	// calendario y se aplica a fechas que estan bajo la ley emiliani
	global  $y,$dia_festivo,$mes_festivo,$festivo;
	// Extrae el dia de la semana
	// 0 Domingo … 6 Sábado
	$dd = date("w",mktime(0,0,0,$mes_festivo,$dia_festivo,$y));
	switch ($dd) 
	{
		case 0:                                    // Domingo
		$dia_festivo = $dia_festivo + 1;
		break;
		case 2:                                    // Martes.
		$dia_festivo = $dia_festivo + 6;
		break;
		case 3:                                    // Miércoles
		$dia_festivo = $dia_festivo + 5;
		break;
		case 4:                                     // Jueves
		$dia_festivo = $dia_festivo + 4;
		break;
		case 5:                                     // Viernes
		$dia_festivo = $dia_festivo + 3;
		break;
		case 6:                                     // Sábado
		$dia_festivo = $dia_festivo + 2;
		break;
	}
	// Nótese que el día puede pasar al mes siguiente.
	// Esto es válido pues el día 32 del mes de enero equivale al dia 1 del mes de febrero
	// Recalcula el verdadero día del mes para cerciorarnos de la fecha en caso de
	// pasar de un mes a otro…
	$mes = date("n", mktime(0,0,0,$mes_festivo,$dia_festivo,$y))+0;
	$dia = date("d", mktime(0,0,0,$mes_festivo,$dia_festivo,$y))+0;
	$festivo[$y][$mes][$dia] = true;
}
?>