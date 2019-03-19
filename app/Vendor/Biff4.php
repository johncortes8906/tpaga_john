<?php
class Biff4 {
	private $fh = null;
	private $path = null;
	private $pos = 0;
	private $chunk = '';
	private $last_record = '';
	public $row = 0;
	public $col = 0;
	public $length = 0;

	private $record_type = array(
		'0402' => 'Sfila/Scolumna/H4ixf/slongitud/a*valor',
		'0302' => 'Sfila/Scolumna/H4ixf/dvalor'
	);

	/**
	*
	*
	* @param string $path ruta absoluta al archivo xls
	* @return void
	*/
	public function __construct($path) {
		$this->setPath($path);
		$this->open();
		$this->readHeader();
		$this->findFirstCell();
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function ignoreFirstRow() {
		$this->ignorar_primera_fila = false;
	}

	public function dontIgnoreFirstRow() {
		$this->ignorar_primera_fila = false;
	}

	private function open() {
		if(!is_readable($this->path)) {
			throw new Exception("Could not open " . $this->path . " for reading! File does not exist, or it is not readable.");
		}

		$this->fh = fopen($this->path, 'r');
	}

	private function readHeader() {
		fseek($this->fh, 0);
		$data = fread($this->fh, 10);
		$this->pos = 10;
		$header_format = 'H4version/H4type/H4zero/syear/H4npi';
		return unpack($header_format, $data);
	}

	private function findFirstCell() {
		while($record = $this->readRecord()) {
			if(is_array($record['content']) && array_key_exists('fila', $record['content'])  && $record['content']['fila'] !== '') {
				return $record;
			}
		}
		return false;
	}

	private function stepBack() {
		$record = $this->last_record;
		$this->pos = $this->pos - ($record['length'] + 4);
	}

	public function readRow() {
		$this->stepBack();
		$row = array();
		while(!feof($this->fh)) {
			$record = $this->readRecord();
			if(isset($record['content']['fila']) && $record['content']['fila'] == $this->row) {
				$this->col = $record['content']['columna'];
				$row[] = $record['content']['valor'];
			} else {
				$this->row++;
				$this->length++;
				return $row;
			}
		}
	}

	public function readRecord() {
		$record = $this->read();
		$this->last_record = $record;
		return $this->readContent($record);
	}

	private function readContent($record) {
		if(isset($this->record_type[$record['recordID']])) {
			$content = unpack($this->record_type[$record['recordID']], $record['content']);
			$record['content'] = $content;
		}

		return $record;
	}

	private function read() {
		fseek($this->fh, $this->pos);
		$data = fread($this->fh, $this->pos + 2);
		$record = unpack("H4recordID/Slength", $data);
		if($record['recordID'] == '0a00') {
			return $record;
		}

		$this->pos += 4;
		fseek($this->fh, $this->pos);
		$record['content'] = '';
		if($record['length'] > 0) {
			$content = fread($this->fh, $record['length']);
			$record['content'] = $content;
		}

		$this->pos += $record['length'];

		return $record;
	}

	public function close() {
		fclose($this->fh);
	}
}
