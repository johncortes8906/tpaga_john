<?php
App::import('Vendor', 'phpQuery', array('file' => 'phpQuery/phpQuery.php'));

/**
 * Sitemap
 *
 * Permite crear el sitemap pasándo la url y los niveles que se quieren bajar
 * <code>
 * <?php
 * $url = 'http://dominio.com/';
 *
 * $Sitemap = new SiteMap($url);
 * $Sitemap->debugON();
 * $Sitemap->setNivel(3);
 * $urls = $Sitemap->procesar();
 *
 * print $Sitemap->guardar();
 * ?>
 * </code>
 * @example /path/to/example.php How to use this function
 * @example anotherexample.inc This example is in the "examples" subdirectory
 */
App::uses('BuscarComponent', 'Controller/Component');

class SiteMap extends AppShell {
	public $uses = array('Ciudade', 'Paise', 'Establecimiento', 'CategoriasEstablecimiento');
	public $components = array('Buscar');

	private $domainMapper = array(
		'domicilios.com' => 1,
		'domicilios.com.ec' => 4,
		'domicilios.com.pe' => 2
	); 

	private $activeCountry;
	private $domain;
	private $links = array();

	function __construct($domain) {
		$this->domain = $domain;
		$this->activeCountry = $this->domainMapper[$domain];
	}


	/**
	 * This function search in the DB for all the active establishments in a set of cities and returns an array like
	 * 
	 * [
	 * 	1 => 'bogota'
	 * ]
	 */
	private function getCities($countryId) {
		return array_map(function($city){
			return array(
				'id' =>$city['Ciudade']['id'] ,
				'platform' =>$city['Ciudade']['plataforma'] 
			); 
		}, $this->Ciudade->find('all', array(
			'fields' => array(
				'Ciudade.id',
				'Ciudade.plataforma'
			),
			'conditions' => array(
				'Ciudade.pais_id' => $countryId,
				'Ciudade.inactivo' => 0
			),
			'recursive' => -1
		)));
	}

	/**
	 * This function search in the DB for all the active establishments in a set of cities and returns an array like
	 * 
	 * [
	 * 	[friendlyUrl => 'friendly-url', city => 1]
	 * ]
	 */
	private function getEstablishments($cities_id) {
		$establishmentsParsed = array();

		$establishments = $this->Establecimiento->find('all', array(
			'fields' => array(
				'Establecimiento.id', 'Establecimiento.friendly_url', 'Establecimiento.ciudad_id'
			),
			'conditions' => array(
				'Establecimiento.aprobado' => 1,
				'Establecimiento.ciudad_id' => $cities_id,
			),
			'recursive' => -1
		));	

		// Clean categories from special characters and convert all to lowercase
		foreach($establishments as $establishment){
			array_push($establishmentsParsed, array(
				'friendlyUrl' => $this->cleanString($establishment['Establecimiento']['friendly_url']),
				'cityId' => $establishment['Establecimiento']['ciudad_id']
				)
			);
		}

		return $establishmentsParsed;
	}

	/**
	 * This function search in the DB for all categories in a specified country and returns an array like
	 * 
	 * [
	 * 	'friendly-url'
	 * ]
	 */
	private function getCategories($countryId) {
		$categoriesParsed = array();

		$categories = $this->CategoriasEstablecimiento->find('all', array(
			'fields' => array(
				'CategoriasEstablecimiento.friendly_url'
			),
			'conditions' => array(
				'CategoriasEstablecimiento.pais_id' => $countryId
			),
			'recursive' => -1
		));

		// Clean categories from special characters and convert all to lowercase
		foreach($categories as $category){
			array_push($categoriesParsed, $this->cleanString($category['CategoriasEstablecimiento']['friendly_url']));
		}

		return $categoriesParsed;
	}

	/**
	 * Función principal
	 * @return array con las urls
	 * @internal param string $url of the url to check.
	 */
	public function procesar() {
		$cities = $this->getCities($this->activeCountry);
		$categories = $this->getCategories($this->activeCountry);
		$establishments = $this->getEstablishments(Hash::extract($cities, '{n}.id'));
		$specialUrls = array(
			'https://'.$this->domain.'/contactenos/publicar_establecimiento',
		);

		$this->links = array_merge(
			$this->generateLinksForCities($cities), 
			$this->generateLinksForCategories($categories, null),
			$this->generateLinksForCategoriesForEachCity($categories, $cities),
			$this->generateLinksForEstablishments($establishments, $cities),
			$this->generateLinksForEstablishments($establishments),
			$specialUrls
		);

		echo "Added => " . count($this->links) . " links \n";
		return $this->links;
	}

	private function generateLinksForCities($cities) {
		$links = array();
		
		foreach($cities as $city){
			array_push($links, 'https://'.$this->domain.'/'.$city['platform']);
		}

		return $links;
	}

	private function generateLinksForCategories($categories, $city) {
		$links = array();

		foreach($categories as $category){
			$cityUrl = $city ? $city['platform'].'/comidas/' : 'comidas/';	
			array_push($links, 'https://'.$this->domain.'/'.$cityUrl.$category);
		}

		return $links;
	}

	private function generateLinksForCategoriesForEachCity($categories, $cities){
		$links = array();

		foreach($cities as $key => $city){
			$links = array_merge($links, $this->generateLinksForCategories($categories, $city));
		}

		return $links;
	}

	private function generateLinksForEstablishments($establishments, $cities = null) {
		$links = array();

		foreach($establishments as $establishment){
			$cityName = '';

			if(is_null($cities)){
				$link = 'https://'.$this->domain.'/'.$establishment['friendlyUrl'];
				if($this->activeCountry == 4){
					$link .= '-a-domicilio';
				}

				if($this->activeCountry == 1 || $this->activeCountry == 4){
					$link .= '.html';
				}

				array_push($links, $link);
				continue;
			}

			foreach($cities as $city){
				if($city['id'] == $establishment['cityId']){
					$cityName = $city['platform'];
				}
			}

			array_push($links, 'https://'.$this->domain.'/'.$cityName.'/restaurantes/'.$establishment['friendlyUrl']);
		}

		return $links;
	}

	/**
	 * Retorna string del xml de las direcciones encontradas
	 *
	 * @return string con el formato xml o false si no tiene urls en el momento
	 */
	public function asXML() {
		if (!$this->links) {
			return false;
		}

		$xml = array();
		foreach ($this->links as $url) {
			$lastmod = date('Y-m-d');
			$xml_url = array(
				'loc' => $url,
				'lastmod' => $lastmod,
				'changefreq' => 'weekly',
				'priority' => '0.5'
			);
			$xml[] = array('url' => $xml_url);
		}

		$tmp_xml = new SimpleXMLElement('<urlset  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');
		$this->array_to_xml($xml, $tmp_xml);

		$dom = new DOMDocument("1.0");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($tmp_xml->asXML());

		return $dom->saveXML();
	}

	/**
	 * Obtiene la versión string del xml y lo escribe en disco
	 *
	 * @param string $path ruta y nombre del archivo donde se escribirá el xml
	 * @return bool true si fue todo ok, Exception en caso contrario
	 * @throws Exception
	 */
	public function guardar($path = './sitemap.xml') {

		$this->write('', $path);

		$xml = $this->asXML();
		if (!is_writable($path)) {
			throw new Exception(sprintf('No hay permisos de escritura en : (%s)', $path));
		}

		return $this->write($xml, $path);
	}

	/**
	 * Escribe a disco
	 *
	 * @param $string
	 * @param string $path ruta y nombre del archivo donde se escribirá el xml
	 * @return bool true si fue todo ok, Exception en caso contrario
	 * @throws Exception
	 */
	private function write($string, $path) {
		$fh = fopen($path, "w");
		if ($fh) {
			try {
				fwrite($fh, $string);
				fclose($fh);
			} catch (Exception $e) {
				throw new Exception(sprintf('No se pudo escribir el archivo : (%s)', $path));
			}
		}

		return true;
	}

	/**
	 * Activa debug
	 */
	public function debugON() {
		$this->debug = true;
	}

	/**
	 * Desactiva debug
	 */
	public function debugOFF() {
		$this->debug = false;
	}

	/**
	 * Hace la magia de convertir un array en xml
	 *
	 * @param array $data la info del xml
	 * @param object &$xml el objeto simple xml
	 */
	private function array_to_xml($data, &$xml) {
		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				$this->array_to_xml($value, $xml);
			} else if (is_string($key) && is_array($value)) {
				$subnode = $xml->addChild("$key");
				$this->array_to_xml($value, $subnode);
			} else {
				$xml->addChild($key, htmlspecialchars($value));
			}
		}
	}

	/**
	 * Función encargada limpiar strings según los parametros pasados
	 *
	 * @return array Retorna un string formateado en base al parametro recibido
	 * @param string $string , cadena a limpiar
	 * @param string $type , (opcional) se puede pasar: 'both' limpia caracteres extraños, 'alph' deja solo letras, 'num' deja solo numeros, en cualquier otro caso devuelve el string igual
	 * @param int $limit , (opcional) cantidad de caracteres a retornar en el string, tener en cuenta que retorna solo los ultimos caracteres del string
	 */
	public function cleanString($string, $type = 'both', $limit = NULL) {
		$texto = '';
		if (!mb_detect_encoding($string, 'UTF-8', true)) {
			$string = utf8_encode($string);
		}

		switch ($type) {
			case 'both':
				$utf8 = array(
					'/[áàâãªä@]/u' => 'a',
					'/[ÁÀÂÃÄ]/u' => 'A',
					'/[ÍÌÎÏ]/u' => 'I',
					'/[íìîï]/u' => 'i',
					'/[éèêë]/u' => 'e',
					'/[ÉÈÊË]/u' => 'E',
					'/[óòôõºö]/u' => 'o',
					'/[ÓÒÔÕÖ]/u' => 'O',
					'/[úùûü]/u' => 'u',
					'/[ÚÙÛÜ]/u' => 'U',
					'/ç/' => 'c',
					'/Ç/' => 'C',
					'/ñ/' => 'n',
					'/Ñ/' => 'N',
					'/–/' => '-', // UTF-8 hyphen to "normal" hyphen
					'/[’‘‹›‚]/u' => ' ', // Literally a single quote
					'/[“”«»„]/u' => ' ', // Double quote
					'/ /' => ' ', // nonbreaking space (equiv. to 0x160)
					'/&/' => 'y',
					'/&amp;/' => 'y',
					'/¼/' => '1/4',
					'/½/' => '1/2'
				);
				$texto = preg_replace(array_keys($utf8), array_values($utf8), $string);
				$texto = str_replace(array(',', '\'', '"'), ' ', $texto);
				break;
		}

		if (!is_nan($limit)) {
			$texto = substr($texto, -$limit);
		}

		return strtolower($texto);
	}
}