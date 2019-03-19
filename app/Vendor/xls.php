<?php
/**
 * Class genXLS
 * Librería lite para la generación de EXCEL en formato binario BIFF5 para
 * evitar usar PHPExcel que consume mucha memoria.
 * @author Ariel Patiño <ariel@ficticio.com>
 */
class genXLS {

	var $nombre;
	var $contenido;
	var $fila;
	var $columna;

	var $fh;
	var $escribirEnDisco = false;
	var $rutaXLS;
	var $zipFile;
	var $xlsTmp;
	var $path;
	var $formato;
	var $totalRows;

	//metodos
	function genXLS($nombre, $path = "", $formato = "XLS") {
		//Si el total de argumentos es 3 quiere decir que viene el total de filas
		if(func_num_args() == 4) {
			$this->totalRows = func_get_arg(3);
		}

		if($path != "") {
			$this->rutaXLS = $path.$nombre;
			$this->escribirEnDisco = true;
			$this->path = $path;
			$this->xlsTmp = tempnam($path, $nombre);
			$this->fh = fopen($this->xlsTmp, 'wb');
			$this->formato = $formato;
		}

		$this->nombre = $nombre;
		$this->genXLS_enviaHead();
		$this->genXLS_BOF();
		$this->genXLS_WRITEACCESS();
		$this->genXLS_INDEX();
		$this->genXLS_CODEPAGE();
		$this->genXLS_CALCMODE();
		$this->genXLS_CALCCOUNT();
		$this->genXLS_DESCONOCIDO();
		$this->genXLS_ITERATION();
		$this->genXLS_DELTA();
		$this->genXLS_SAVERECALC();
		$this->genXLS_PRECISION();
		$this->genXLS_DATEMODE();
		$this->genXLS_PRINTHEADERS();
		$this->genXLS_PRINTGRIDLINES();
		$this->genXLS_GRIDSET();
		$this->genXLS_GUTS();
		$this->genXLS_DEFAULTROWHEIGHT();
		$this->genXLS_COUNTRY();
		$this->genXLS_HIDEOBJ();
		$this->genXLS_WSBOOL();
		for($i = 0; $i < 3; $i++) {
			$this->genXLS_FONT(array('size' => 12));
		}
		//Titulos
		$this->genXLS_FONT(array('bold'=>true, 'color' => 18, 'size' => 12));
		//Headers
		$this->genXLS_FONT(array('bold'=>true, 'color' => 1, 'size' => 12));
		//Numeros
		$this->genXLS_FONT(array('font'=>'Courier', 'size' => 12));

		$this->genXLS_HEADER();
		$this->genXLS_FOOTER();
		$this->genXLS_HCENTER();
		$this->genXLS_VCENTER();
		$this->genXLS_LEFTMARGIN();
		$this->genXLS_RIGHTMARGIN();
		$this->genXLS_TOPMARGIN();
		$this->genXLS_BOTTOMMARGIN();
		$this->genXLS_DESCONOCIDO2();
		$this->genXLS_SETUP();
		$this->genXLS_BACKUP();
		$this->genXLS_BUILTINFMTCOUNT();
		$this->genXLS_FORMAT();
		$this->genXLS_WINDOWPROTECTED();
		$this->genXLS_XF();
		$this->genXLS_XF();
		$this->genXLS_XF();
		$this->genXLS_XF();
		//$this->genXLS_XF(array('font' => 1));
		//Titulos
		//4304 0c00 05 00 0100 22 78 c581 00000000
		//4304 0c00 05 00 0100 22 58 81c5 00000000
		//05		index font
		//00		index format
		//0100	hidden locked
		//22		100010, align
		//58		1011000, xf_used_attrib
		//			 24		22		1
		//c581 , 11000 10110 000001, xf_area_34
			//5-0 003FH Fill pattern (?3.11)
			//10-6 07C0H Colour index (?6.70) for pattern colour
			//15-11 F800H Colour index (?6.70) for pattern background
			//hAlign
			//vAlign
			//orientation

			//fillPattern
			//fillColorPattern
			//fillColorBackground
		//Izquierda (4)
		$this->genXLS_XF(null, array('borderRight'=> 1,
																'borderBottom'=> 2,
																'borderTop'=> 2,
																'borderLeft'=> 2,
																'font' => 5,
																'hAlign' => 2, //center
																'vAlign' => 2,
																//'orientation' => 0,
																'fillPattern' => 1,
																'fillColorPattern' => 23, //22 = 0x16 EGA Silver
																'fillColorBackground' => 24
																));
		//Centro (5)
		$this->genXLS_XF(null, array('borderRight'=>1,
																'borderBottom'=>2,
																'borderTop'=>2,
																'borderLeft'=> 1,
																'font' => 5,
																'hAlign' => 2, //center
																'vAlign' => 2,
																//'orientation' => 0,
																'fillPattern' => 1,
																'fillColorPattern' => 23, //22 = 0x16 EGA Silver
																'fillColorBackground' => 24));
		//Derecha (6)
		$this->genXLS_XF(null, array('borderRight'=>2,
																'borderBottom'=>2,
																'borderTop'=>2,
																'borderLeft'=> 1,
																'font' => 5,
																'hAlign' => 2, //center
																'vAlign' => 2,
																//'orientation' => 0,
																'fillPattern' => 1,
																'fillColorPattern' => 23, //22 = 0x16 EGA Silver
																'fillColorBackground' => 24));

/*
*
$arrFormat[] = 'General';
$arrFormat[] = '0';
$arrFormat[] = '0.00';
$arrFormat[] = '#.##0';
$arrFormat[] = '#.##0.00';
$arrFormat[] = '#,##0;\-#,##0';
$arrFormat[] = '#,##0;[Red]\-#,##0';
$arrFormat[] = '#,##0.00;\-#,##0.00';
$arrFormat[] = '#,##0.00;[Red]\-#,##0.00';
$arrFormat[] = '"$"\ #,##0;"$"\ \-#,##0';
$arrFormat[] = '"$"\ #,##0;[Red]"$"\ \-#,##0';
$arrFormat[] = '"$"\ #,##0.00;"$"\ \-#,##0.00';
$arrFormat[] = '"$"\ #,##0.00;[Red]"$"\ \-#,##0.00';
$arrFormat[] = '0%';
$arrFormat[] = '0.00%';
$arrFormat[] = '0.00E+00';
$arrFormat[] = '#\ ?/?';
$arrFormat[] = '#\ ??/??';
$arrFormat[] = 'dd/mm/yyyy';
$arrFormat[] = 'dd\-mmm\-yy';
$arrFormat[] = 'dd\-mmm';
$arrFormat[] = 'mmm\-yy';
$arrFormat[] = 'hh:mm\ AM/PM';
$arrFormat[] = 'hh:mm:ss\ AM/PM';
$arrFormat[] = 'hh:mm';
$arrFormat[] = 'hh:mm:ss';
$arrFormat[] = 'dd/mm/yyyy\ hh:mm';
$arrFormat[] = '##0.0E+0';
$arrFormat[] = 'mm:ss';
$arrFormat[] = '@';
* */

		//Textos
		//Izquierda (7)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1,	'font' => 2));
		//Centro (8)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1,	'font' => 2));
		//Derecha (9)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1,	'font' => 2));

		//Números numero
		//Izquierda (10)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 1));
		//Centro (11)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 1));
		//Derecha (12)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 1));

		//4304 0c00 05 00 0100 26 18 00ce 000000
		//BLANK (13)
		$this->genXLS_XF(null, array('font' => 4,
																'hAlign' => 6, //center across selection
																'vAlign' => 2,
																'fillPattern' => 0, //0
																'fillColorPattern' => 24, //24, 0x09 white
																'fillColorBackground' => 25 //25
																));
		//Histórico (14)
		$this->genXLS_XF(null, array('font' => 4));

		//Moneda float
		//Izquierda (15)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 4));
		//Centro (16)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 4));
		//Derecha (17)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 4));

		//Zero fill
		//Izquierda (18)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 34));
		//Centro (19)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 34));
		//Derecha (20)
		$this->genXLS_XF(null, array('borderRight'=>1, 'borderBottom'=>1, 'borderLeft'=> 1, 'font' => 6, 'flagNumber' => true, 'format' => 34));


		$this->genXLS_STYLE();
		$this->genXLS_DEFCOLWIDTH(20);
		$this->genXLS_DIMENSIONS();
		$this->genXLS_ROW();

		$this->fila = 0;
		$this->columna = 0;
	}

	function genXLS_getfh() {
		return $this->fh;
	}

	function genXLS_getTmp() {
		return $this->xlsTmp;
	}

	function genXLS_BOF() {
		//$info = pack("vvvv", 0x0409, 0x0004, 0x6, 0x10);
		//Excel 4.0
		$info = pack("vvvvv", 0x0409, 0x0006, 0x00000, 0x0010, 0x1fc6);
		if(!$this->escribirEnDisco) {
				print $info;
		} else {
				//fwrite($this->fh, $info);
				fwrite($this->fh, $info);
		}
	}

	function genXLS_EOF() {
		$info = pack("vv", 0x000A, 0x0000);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			//Cierra el documento xls
			fwrite($this->fh, $info);
			//Libera la memoria
			$info = "";
			//Cierra el apuntador al archivo excel
			fclose($this->fh);

			$tmp_new = $this->path.$this->nombre;
			set_time_limit(0);
			copy($this->xlsTmp, $tmp_new);
			unlink($this->xlsTmp);
		}
		return;
	}

	function genXLS_NuevaFila() {
		$this->fila++;
		$this->columna = 0;
	}

	function genXLS_NuevaColumna() {
		$this->columna = $this->columna+1;
	}

	function genXLS_WRITEACCESS() {
		$titulo = 'ARIEL PATIÑO - ariel@ficticio.com';
		$strlen = strlen($titulo);
		$info = pack("vvc", 0x005c, $strlen + 1, $strlen);
		$info .= $titulo;
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_INDEX() {
		//$info = pack("vvLvvLL", 0x020b, 0x0010, 0x000004fa, 0x0,0x01, 0x00000500, 0x000006a4);
		$info = pack("vvLvvLL", 0x020b, 0x0010, 0x000004fa, 0x0,0x01, 0x00000500, 0x000006a4);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_CODEPAGE() {
		$info = pack("vvv", 0x0042, 0x02, 0x04e4);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_CALCMODE() {
		$info = pack("vvv", 0x000d, 0x02, 0x01);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_CALCCOUNT() {
		$info = pack("vvv", 0x000c, 0x02, 0x64);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_DESCONOCIDO() {
		$info = pack("vvv", 0x000f, 0x02, 0x01);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_ITERATION() {
		$info = pack("vvv", 0x0011, 0x02, 0x0);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_DELTA() {
		$info = pack("vvL*", 0x0010, 0x08, 0xd2f1a9fc, 0x3f50624d);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_SAVERECALC(){
		$info = pack("vvv", 0x005f, 0x02, 0x01);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_PRECISION() {
		$info = pack("vvv", 0x000e, 0x02, 0x01);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_DATEMODE() {
		$info = pack("vvv", 0x0022, 0x02, 0x0);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_PRINTHEADERS() {
		$info = pack("vvv", 0x002a, 0x02, 0x0);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_PRINTGRIDLINES() {
		$info = pack("vvv", 0x002b, 0x02, 0x0);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_GRIDSET() {
		$info = pack("vvv", 0x0082, 0x02, 0x01);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_GUTS() {
		$info = pack("vvL*", 0x0080, 0x08, 0x0, 0x0);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_DEFAULTROWHEIGHT(){
		$info = pack("vvL", 0x0225, 0x04, 0x00ff0000);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_COUNTRY() {
		$info = pack("vvL", 0x008c, 0x04, 0x00390022);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_HIDEOBJ() {
		$info = pack("vvv", 0x008d, 0x02, 0x0);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_WSBOOL() {
		$info = pack("vvv", 0x0081, 0x02, 0x04c1);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_FONT() {
		$_ = func_get_args();

		$record = 0x0231;
		$font = "Arial";
		if($_[0]['font']) {
			$font = $_[0]['font'];
		}
		$cch = strlen($font);
		$length = 0x07 + $cch;
		$reserved = 0x00;
		$grbit		 |= 0x00; //0x00
		$size = 10;
		$icv = 0x7FFF; //color

		if($_[0]['color']) {
			$icv = dechex($_[0]['color']);
		}

		if($_[0]['size']) {
			$size = $_[0]['size'];
		}

		$dyHeight = $size * 20; //size

		//bold
		if($_[0]['bold']) {
			$grbit		 |= 0x01;
		}

		if ($_[0]['italic']) {
			$grbit		 |= 0x02;
		}

		if ($_[0]['font_strikeout']) {
			$grbit		 |= 0x08;
		}

		if ($_[0]['font_outline']) {
			$grbit		 |= 0x10;
		}

		if ($_[0]['font_shadow']) {
			$grbit		 |= 0x20;
		}

		$header	= pack("vv", $record, $length);
		$data = pack("vvvC", $dyHeight, $grbit, $icv, $cch);

		//return($header . $data . $this->_font);
		//fwrite($this->fh, $header.$data.$font);
		if(!$this->escribirEnDisco) {
			//print $info;
			print $header.$data.$font;
		} else {
			//fwrite($this->fh, $info);
			fwrite($this->fh, $header.$data.$font);
		}
	}
	function genXLS_HEADER() {
		$info = pack("vv", 0x0014, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_FOOTER() {
		$info = pack("vv", 0x0015, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_HCENTER() {
		$info = pack("vvv", 0x0083, 0x02, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_VCENTER() {
		$info = pack("vvv", 0x0084, 0x02, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_LEFTMARGIN() {
		$info = pack("vvL*", 0x0026, 0x08, 0xc9ae345b, 0x3fe93264);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_RIGHTMARGIN() {
		$info = pack("vvL*", 0x0027, 0x08, 0xc9ae345b, 0x3fe93264);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_TOPMARGIN() {
		$info = pack("vvL*", 0x0028, 0x08, 0xfc3c1d8a, 0x3fef7efd);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_BOTTOMMARGIN() {
		$info = pack("vvL*", 0x0029, 0x08, 0xfc3c1d8a, 0x3fef7efd);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_DESCONOCIDO2() {
		$info = pack("vvL*", 0x004d, 0x0c, 0x00010000, 0x00640001, 0x02580258);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_SETUP() {
		$info = pack("vvvvvvvvvvvL*", 0x00a1, 0x22, 0x01, 0x64, 0x01, 0x01, 0x01, 0x02, 0x0258, 0x0258, 0x00, 0x00, 0x00, 0x00, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_BACKUP() {
		$info = pack("vvv", 0x0040, 0x02, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_BUILTINFMTCOUNT() {
		$info = pack("vvv", 0x0056, 0x02, 0x1b);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
				print $info;
		} else {
				fwrite($this->fh, $info);
		}
	}
	function genXLS_FORMAT() {
		$arrFormat = array();
		$arrFormat[] = 'General';
		$arrFormat[] = '0';
		$arrFormat[] = '0.00';
		$arrFormat[] = '#.##0';
		$arrFormat[] = '#.##0.00';
		$arrFormat[] = '#,##0;\-#,##0';
		$arrFormat[] = '#,##0;[Red]\-#,##0';
		$arrFormat[] = '#,##0.00;\-#,##0.00';
		$arrFormat[] = '#,##0.00;[Red]\-#,##0.00';
		$arrFormat[] = '"$"\ #,##0;"$"\ \-#,##0';
		$arrFormat[] = '"$"\ #,##0;[Red]"$"\ \-#,##0';
		$arrFormat[] = '"$"\ #,##0.00;"$"\ \-#,##0.00';
		$arrFormat[] = '"$"\ #,##0.00;[Red]"$"\ \-#,##0.00';
		$arrFormat[] = '0%';
		$arrFormat[] = '0.00%';
		$arrFormat[] = '0.00E+00';
		$arrFormat[] = '#\ ?/?';
		$arrFormat[] = '#\ ??/??';
		$arrFormat[] = 'dd/mm/yyyy';
		$arrFormat[] = 'dd\-mmm\-yy';
		$arrFormat[] = 'dd\-mmm';
		$arrFormat[] = 'mmm\-yy';
		$arrFormat[] = 'hh:mm\ AM/PM';
		$arrFormat[] = 'hh:mm:ss\ AM/PM';
		$arrFormat[] = 'hh:mm';
		$arrFormat[] = 'hh:mm:ss';
		$arrFormat[] = 'dd/mm/yyyy\ hh:mm';
		$arrFormat[] = '##0.0E+0';
		$arrFormat[] = 'mm:ss';
		$arrFormat[] = '@';
		$arrFormat[] = '_ "$"\ * #,##0_ ;_ "$"\ * \-#,##0_ ;_ "$"\ * "-"_ ;_ @_ ';
		$arrFormat[] = '_ * #,##0_ ;_ * \-#,##0_ ;_ * "-"_ ;_ @_ ';
		$arrFormat[] = '_ "$"\ * #,##0.00_ ;_ "$"\ * \-#,##0.00_ ;_ "$"\ * "-"??_ ;_ @_ ';
		$arrFormat[] = '_ * #,##0.00_ ;_ * \-#,##0.00_ ;_ * "-"??_ ;_ @_ ';
		$arrFormat[] = '000000000000000';
		foreach($arrFormat as $key => $format) {
			$ccb = strlen($format);
			$notused = 0x00;
			$info = pack("vvvC", 0x041e, 3 + $ccb, $notused, $ccb);
			//fwrite($this->fh, $info.$format);
			if(!$this->escribirEnDisco) {
				//print $info;
				print $info.$format;
			} else {
				//fwrite($this->fh, $info);
				fwrite($this->fh, $info.$format);
			}
		}
	}
	function genXLS_WINDOWPROTECTED() {
		$info = pack("vvv", 0x0019, 0x02, 0x00);
		//fwrite($this->fh, $info);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
	}
	function genXLS_XF() {
		$_ = func_get_args();
		$record =	0x0443;
		$length = 0x0c;

		//Flags
		$fNumber = 0;
		$fFont = 0;
		$fHorizontalAlign = 0;
		$fBorder = 0;
		$fBackgroundAreaStyle = 0;
		$fCellProtect = 0;

		$indexFontRecord = 0x00;
		if($_[1]['font']) {
			$indexFontRecord = $_[1]['font'];
			$fFont = 1;
		}
		$indexFormatRecord = 0x00;
		if($_[1]['format']) {
			$indexFormatRecord = $_[1]['format'];
		}
		$locked = 1;
		$hidden = 0;
		if($_[0] == 'style') {
			$style = 0xFFF5;
		} else {
			$style	 = $locked;
			$style	|= $hidden << 1;
		}

		////4304 0c00 00 02 0100 20 04 00ce 00000000
		//00						ifont
		//02						iformat
		//0100					xf_type
		//20						flags								 100000
		//04						xf_attrib								100
		//00ce					xf_area		 1100111000000000
		//00000000			xf_border

		//Align
		$xf_hor_align = 0; //00 General,01 Left, 02 Centred, 03 Right, 04 Filled, 05 Justified(Biff4), 06 Centred across selection(biff4)
		if(isset($_[1]['hAlign'])) {
			$xf_hor_align = $_[1]['hAlign'];
		}
		$text_wraped_right = 0;
		$xf_ver_align = 0; //00 Top, 01 Centred, 02 Bottom
		if(isset($_[1]['vAlign'])) {
			$xf_ver_align = $_[1]['vAlign'];
		}
		$xf_orientation = 0; //00 Not rotated, 01 Letters are stacked top-to-bottom, but not rotated, 02 rotated 90 degrees counterclockwise, 03 rotated 90 degrees clockwise
		if(isset($_[1]['orientation'])) {
			$xf_orientation = $_[1]['orientation'];
		}

		if(isset($_[1]['hAlign']) || isset($_[1]['vAlign']) || isset($_[1]['orientation'])) {
			$fHorizontalAlign = 1;
		}

		$align	= $xf_hor_align;//00010000
		$align |= $text_wraped_right << 3;
		$align |= $xf_ver_align << 4;
		$align |= $xf_orientation << 6;

		//XF used atrib
		//100000
		//101000
		if($_[1]['flagNumber']) {
			$fNumber = 1;
		}

		//BACKGROUND PATTERN
		$fillPattern = 0x00; //00 no pattern, 01 solid (6 bits)
		if(isset($_[1]['fillPattern'])) {
			$fillPattern = $_[1]['fillPattern'];
		}
		$fillColorPattern = 0x00; //00 black, 01 white, 02 red, 03 green, 04 blue, 05 yellow, 06 magenta, 07 cyan, 5 bits
		if(isset($_[1]['fillColorPattern'])) {
			$fillColorPattern = $_[1]['fillColorPattern']; //00 black, 01 white, 02 red, 03 green, 04 blue, 05 yellow, 06 magenta, 07 cyan, 5 bits
		}
		$fillColorBakcground = 0x00;//5 bits
		if(isset($_[1]['fillColorBackground'])) {
			$fillColorBakcground = $_[1]['fillColorBackground'];//5 bits
		}
		if(isset($_[1]['fillPattern']) || isset($_[1]['fillColorPattern']) || isset($_[1]['fillColorBackground'])) {
			$fBackgroundAreaStyle = 1;
		}

		//11001 11000 000000
		$xf_area_34	= $fillPattern;
		$xf_area_34 |= $fillColorPattern << 6;
		$xf_area_34 |= $fillColorBakcground << 11;
		//76543210 76543210 76543210 76543210
		//00000001 00000001 00000001 00000001
		$lineTop = 0x00;
		$colorTop = 0x00;
		if($_[1]['borderTop']) {
			if($_[1]['borderTop'] < 3) {
				$lineTop = $_[1]['borderTop']; //00 none, 01 thin, 02 medium, 03, 02 dotted
			}
			$colorTop = 0x18; //00 black, 01 white, 02 red, 03 green, 04 blue, 05 yellow, 06 magenta, 07 cyan, 5 bits
		}
		$xf_border_34	= $lineTop;
		$xf_border_34 |= $colorTop << 3;

		$lineLeft = 0x00;
		$colorLeft = 0x00;
		if($_[1]['borderLeft']) {
			$lineLeft = 0x01; //00 none, 01 thin, 02 medium, 03, 02 dotted
			if($_[1]['borderLeft'] < 3) {
				$lineLeft = $_[1]['borderLeft']; //00 none, 01 thin, 02 medium, 03, 02 dotted
			}
			$colorLeft = 0x18; //00 black, 01 white, 02 red, 03 green, 04 blue, 05 yellow, 06 magenta, 07 cyan, 5 bits
		}
		$xf_border_34 |= $lineLeft << 8;
		$xf_border_34 |= $colorLeft << 11;

		$lineBottom = 0x00;
		$colorBottom = 0x00;
		if($_[1]['borderBottom']) {
			$lineBottom = 0x01; //00 none, 01 thin, 02 medium, 03, 02 dotted
			if($_[1]['borderBottom'] < 3) {
				$lineBottom = $_[1]['borderBottom']; //00 none, 01 thin, 02 medium, 03, 02 dotted
			}
			$colorBottom = 0x18; //00 black, 01 white, 02 red, 03 green, 04 blue, 05 yellow, 06 magenta, 07 cyan, 5 bits
		}
		$xf_border_34 |= $lineBottom << 16;
		$xf_border_34 |= $colorBottom << 19;

		$lineRight = 0x00;
		$colorRight = 0x00;
		if($_[1]['borderRight']) {
			$lineRight = 0x01; //00 none, 01 thin, 02 medium, 03, 02 dotted
			if($_[1]['borderRight'] < 3) {
				$lineRight = $_[1]['borderRight']; //00 none, 01 thin, 02 medium, 03, 02 dotted
			}
			$colorRight = 0x18; //00 black, 01 white, 02 red, 03 green, 04 blue, 05 yellow, 06 magenta, 07 cyan, 5 bits
		}
		$xf_border_34 |= $lineRight << 24;
		$xf_border_34 |= $colorRight << 27;

		if($_[1]['borderTop'] || $_[1]['borderLeft'] || $_[1]['borderBottom'] || $_[1]['borderRight']) {
			$fBorder = 1;
		}
		//100000 10000001 00000001
		//01000 000
		$xf_used_attrib = 0;
		$xf_used_attrib |= $fNumber << 2;
		$xf_used_attrib |= $fFont << 3;
		$xf_used_attrib |= $fHorizontalAlign << 4;
		$xf_used_attrib |= $fBorder << 5;
		$xf_used_attrib |= $fBackgroundAreaStyle << 6;
		$xf_used_attrib |= $fCellProtect << 7;

		$header	= pack("vv",$record, $length);
		$data = pack("CCvCCvL", $indexFontRecord, $indexFormatRecord, $style, $align,
																				$xf_used_attrib, $xf_area_34,
																				$xf_border_34);
		//fwrite($this->fh, $header.$data);
		if(!$this->escribirEnDisco) {
			print $header.$data;
		} else {
			fwrite($this->fh, $header.$data);
		}
	}
	function genXLS_STYLE() {
		$record = 0x0293;
		$length = 4;
		$indexXF = 0x00;
		$indexXF |=	1 << 15; //Builtin style
		$identifierBuiltin = 0x00; //00 normal, 01 RowLevel, 02 ColLevel, 03 Comma, 04 Currency, 05 Percent, 06 Comma, 07 Currency
		$leveForRowLevel = 0xff;//FF cualquier otro
		$header	= pack("vv", $record, $length);
		$data = pack("vCC", $indexXF, $identifierBuiltin, $leveForRowLevel);
		//fwrite($this->fh, $header.$data);
		if(!$this->escribirEnDisco) {
			print $header.$data;
		} else {
			fwrite($this->fh, $header.$data);
		}
	}
	function genXLS_DEFCOLWIDTH($size = 10) {
		$record = 0x0055;
		$length = 2;
		$header	= pack("vv", $record, $length);
		$data = pack("v", $size);
		//fwrite($this->fh, $header.$data);
		if(!$this->escribirEnDisco) {
			print $header.$data;
		} else {
			fwrite($this->fh, $header.$data);
		}
	}
	function genXLS_DIMENSIONS() {
		$record = 0x0200;
		$length = 10;
		/*
		0 2 Index to first used row
		2 2 Index to last used row, increased by 1
		4 2 Index to first used column
		6 2 Index to last used column, increased by 1
		8 2 Not used
		*/
		$indexFirstUsedRow = 0;
		$indexLastUsedRow = 1;
		$indexFirstUsedCol = 0;
		$indexLastUsedCol = 1;
		$notUsed = 0;
		$header	= pack("vv", $record, $length);
		$data = pack("v*", $indexFirstUsedRow, $indexLastUsedRow, $indexFirstUsedCol, $indexLastUsedCol, $notUsed);
		//fwrite($this->fh, $header.$data);
		if(!$this->escribirEnDisco) {
			print $header.$data;
		} else {
			fwrite($this->fh, $header.$data);
		}
	}
	function genXLS_ROW() {
		$record = 0x0208;
		$length = 16;
		//h		le		ir	 ic	 ilc	he	 nu	 rof	flags
		//0802 1000	0000 0000 0100 ff00 0000 0000 00010f00
		$indexRow = 0;
		$indexColumnFirstCell = 0;
		$indexColumnLastCell = 1;

		$heightRow = 0xff;
		$rowHasCustomHeight = 0;

		$infoRow = $heightRow;
		$infoRow |= $rowHasCustomHeight << 15;

		$notUsed = 0;
		$relativeOffset = 0;

		$outlineLevel = 0;					//3 bits
		$outlineGroup = 0;					//1 bit, 1 == outline group starts or ends here
		$rowHidden = 0;						 //1 bit, 1 == row is hidden
		$rowHeight = 0;						 //1 bit, 1 == row height and default font height don't match
		$rowExplicit = 0;					 //1 bit(fl), 1 == has explicit default format
		$alwais1 = 1;							 //1 bit
		$indexDefaultXF = 1;				//12 bits, if fl == 1: index to default XF record
		$aditionalSpaceAbove = 0;	 //1 bit, 1 set space
		$aditionalSpaceBelow = 0;	 //1 bit, 1 set space

		$optionFlag = $outlineLevel;
		$optionFlag |= $outlineGroup << 4;
		$optionFlag |= $rowHidden << 5;
		$optionFlag |= $rowHeight << 6;
		$optionFlag |= $rowExplicit << 7;
		$optionFlag |= $alwais1 << 8;
		$optionFlag |= $indexDefaultXF << 16;
		$optionFlag |= $aditionalSpaceAbove << 28;
		$optionFlag |= $aditionalSpaceBelow << 1;
		//0 0000000001111 0000000100000000
		$header	= pack("vv", $record, $length);
		$data = pack("vvvvvvL", $indexRow, $indexColumnFirstCell, $indexColumnLastCell, $infoRow, $notUsed, $relativeOffset, $optionFlag);
		//fwrite($this->fh, $header.$data);
		if(!$this->escribirEnDisco) {
			print $header.$data;
		} else {
			fwrite($this->fh, $header.$data);
		}
	}

	function genXLS_insBlank($fila,$columna, $iXF = 0) {
		$info = pack( "v*", 0x0201, 6, $fila, $columna, $iXF);
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
		$this->fila = $fila;
		$this->columna = $columna;
	}

	function genXLS_insNumero($fila, $columna, $iXF = 0, $valor) {
		//$this->contenido.= pack( "sssssd", 0x0203, 14, $fila, $columna, 0x0000, $valor );
		$info =	pack( "vvvvvd", 0x0203, 14, $fila, $columna, $iXF, $valor );
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}
		$this->fila = $fila;
		$this->columna = $columna;
	}

	function genXLS_insTexto($fila, $columna, $iXF = 0, $valor) {
		$valor = utf8_decode($valor);
		$longitud = strlen($valor);
		if($longitud	> 255) {
			$longitud = 255;
			$valor = substr ($valor, 0,255);
		}

		$info = pack( "v*", 0x0204, 8 + $longitud, $fila, $columna, $iXF, $longitud );
		$info .= $valor;
		if(!$this->escribirEnDisco) {
			print $info;
		} else {
			fwrite($this->fh, $info);
		}

		$this->fila = $fila;
		$this->columna = $columna;
	}

	function genXLS_enviaHead() {
		if(!$this->escribirEnDisco) {
			header("Expires: 0");
			//header ( "Pragma: no-cache" );
			header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
			header ( "Cache-Control: private, must-revalidate" );
			header ( "Cache-Control: post-check=0, pre-check=0", false);
			header ( "Content-type: application/x-msexcel" );
			header ( "Content-Disposition: attachment; filename=".$this->nombre );
			header ( "Content-Description: PHP Generated XLS Data" );
		} else {
			//Crea el objeto para comprimir
			//$this->zipFile = new zipfile($this->nombre.".zip");
		}
	}
}
