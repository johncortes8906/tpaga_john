<?php

class ElasticSearch{
    
    public $index = null;
    public $domain = '127.0.0.1';
    public $port = '9200';
    private $curl = null;

    public function __construct()
    {
    }

/**
* setUpCurl
*
* Configure the global object curl
*/
    private function setUpCurl()
    {
        if (!is_null($this->curl)) {
            $this->closeCurl();
        }
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,array("Content-Type: application/json"));
    }

/**
* setDomainCurl
*
* Set the domain to the curl with the global var domain and the given uri
*
* @var uri, string
*/
    private function setDomainCurl($uri)
    {
        $url = $this->domain . ':' . $this->port . $uri;
        curl_setopt($this->curl, CURLOPT_URL, $url);
    }

/**
* setPostParams
*
* @var params, array of params to send to the request
*/
    private function setParamsCurl($params)
    {
        $formated_params = json_encode($params);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS,  $formated_params);
    }

    private function closeCurl()
    {
        curl_close($this->curl);
        $this->curl = null;
    }

    private function runCurl()
    {
        $response = array();
        $response = curl_exec($this->curl);
        if (curl_errno($this->curl)) {
            throw new Exception(curl_error($this->curl), 1);
        }

        $response = json_decode($response, true);

        return $response;
    }

/**
* setVerboseCurl
*
* @var method, string with the correspond verbose to send the request
*/
    private function setVerboseCurl($method)
    {
        if (!is_string($method)) {
            throw new Exception("Invalid string type for method arg[0]", 1);
        }
        $method = strtoupper($method);
        if (!in_array($method, array('PUT', 'POST', 'DELETE', 'GET'))) {
            throw new Exception(
                "Invalid value for method, allowed: 'PUT', 'POST', 'DELETE', 'GET'",
                1
            );
        }
        if (in_array($method, array('PUT', 'DELETE'))) {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (in_array($method, array('POST'))) {
            curl_setopt($this->curl, CURLOPT_POST, 1);
        }
        if (in_array($method, array('GET'))) {
            curl_setopt($this->curl, CURLOPT_HTTPGET, 1);
        }
    }

/**
* createIndex
*
* Create a elastic search index
*
* @var index, string with the name of the index to be created
* @var shards, number of shards
* @var replicas, number of replicas
*/
    public function createIndex($index, $shards = 5, $replicas = 1)
    {
        if (is_nan($shards)) {
            throw new Exception("Invalid number type for shards arg[1]", 1);
        }
        if (is_nan($replicas)) {
            throw new Exception("Invalid number type for replicas arg[2]", 1);
        }
        $options = array(
            'settings' => array(
                'index' => array(
                    'number_of_shards' => $shards,
                    'number_of_replicas' => $replicas
                )
            )
        );

        $this->setUpCurl();
        $this->setDomainCurl("/$index");
        $this->setVerboseCurl('PUT');
        $this->setParamsCurl($options);

        return $this->runCurl();
    }

/**
* setDomain
*
* @var domain, string with the structure: 'http://ip_or_domain'
*/
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

/**
* setPort
*
* @var port, integer that correspond to the port of the elastic search server
*/
    public function setPort($port)
    {
        $this->port = $port;
    }

/**
* mapping
*
* Create a imap of type in the elastic search server
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var properties, array with the values to be mapped in the given index.
*       ex. 
*           array(
*               'field1' => array('type' => 'text'),
*               'fieldsito' => array('type' => 'long'),
*               "location" => array("type" => "geo_shape")
*           )
* @var shards, number of shards
*/
    public function mapping($index, $type, $properties, $shards = 1)
    {
        if (!is_array($properties)) {
            throw new Exception("Invalid array type for properties arg[2]", 1); 
        }

        foreach ($properties as $key => $property) {
            if ($key != 'location') {
                $property['index'] = "not_analyzed";
                $properties[$key] = $property;    
            }
        }

        $options = array(
            "mappings" => array(
                "$type" => array(
                    'properties' => $properties
                )
            )
        );

        $this->setUpCurl();
        $this->setDomainCurl("/$index");
        $this->setVerboseCurl('PUT');
        $this->setParamsCurl($options);

        return $this->runCurl();
    }

/**
* index
*
* Index a document in the given index and type
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var content, associative array with the information to be saved. IE.
*           array(
*               'field1' => 'Hello World',
*               'fieldsito' => 975312468,
*               "location" => array(
*                   "type" => "polygon",
*                   "coordinates"=> array(
*                       array(
*                           array(100.0, 0.0),
*                           array(101.0, 0.0),
*                           array(101.0, 1.0),
*                           array(100.0, 1.0),
*                           array(100.0, 0.0)
*                       )
*                   )
*               )
*           )
*/
    public function index($index, $type, $content, $id = null, $parent = null)
    {
        if (!is_array($content)) {
            throw new Exception("Invalid array type for content arg[2]", 1);    
        }

        $query = '';
        if (!is_null($parent)) {
            $query = '?parent=' . $parent;
        }

        $this->setUpCurl();
        if (is_null($id)) {
            $url = "/$index/$type" . $query;
        } else {
            $url = "/$index/$type/$id" . $query;
        }

        $this->setDomainCurl($url);
        $this->setVerboseCurl('POST');
        $this->setParamsCurl($content);

        $result = $this->runCurl();
        if (isset($result['error']) && !empty($result['error'])) {
            CakeLog::write('es_error_index', json_encode(array(
                'method' => 'POST',
                'content' => $content,
                'url' => $url,
                'result' => $result,
            )));
        }
        return $result;
    }

/**
* update
*
* Update a document in the given index and type with the specified id
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var content, associative array with the information to be saved. IE.
*           array(
*               'field1' => 'Hello World',
*               'fieldsito' => 975312468,
*               "location" => array(
*                   "type" => "polygon",
*                   "coordinates"=> array(
*                       array(
*                           array(100.0, 0.0),
*                           array(101.0, 0.0),
*                           array(101.0, 1.0),
*                           array(100.0, 1.0),
*                           array(100.0, 0.0)
*                       )
*                   )
*               )
*           )
* @var id, integer
*/
    public function update($index, $type, $content, $id, $parent = null)
    {
        if (!is_array($content)) {
            throw new Exception("Invalid array type for content arg[2]", 1);    
        }

        if (empty($id)) {
            throw new Exception("The field should be a valid id arg[3]", 1);    
        }

        $query = '';
        if (!is_null($parent)) {
            $query = '?parent=' . $parent;
        }

        $content = array('doc' => $content);
        $url = "/$index/$type/$id/_update" . $query;
        $this->setUpCurl();
        $this->setDomainCurl($url);
        $this->setVerboseCurl('POST');
        $this->setParamsCurl($content);

        $result = $this->runCurl();
        if (isset($result['error']) && !empty($result['error'])) {
            CakeLog::write('es_error_update', json_encode(array(
                'method' => 'POST',
                'content' => $content,
                'url' => $url,
                'result' => $result,
            )));
        }
        return $result;
    }

/**
* getDocument
*
* retrives a document
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var id, integer with the document id
* @var params, array with extra params to be sent in url (ex. parent)
*/
    public function getDocument($index, $type, $id, $params = array())
    {
        $this->setUpCurl();
        $extra = http_build_query($params);
        $url = (empty($params)) ? "/$index/$type/$id" : "/$index/$type/$id".'?'.$extra;
        $this->setDomainCurl($url);
        $this->setVerboseCurl('GET');

        return $this->runCurl();
    }

/**
* getIndex
*
* retrives a index
*
* @var index, string with the name of the index to be created
*/
    public function getIndex($index)
    {
        $this->setUpCurl();
        $this->setDomainCurl("/$index");
        $this->setVerboseCurl('GET');

        return $this->runCurl();
    }

/**
* search
*
* Search indexes that fulfill with the conditions
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var conditions, associative array with the parameter to search. IE
*           array('field1' => 'Hello World', 'fieldsito' => 975312468)
* @var fields, array with the name of the expected fields
* @var size, max number of documents to retrieve
*/
    public function search($index, $type, $conditions, $fields = null, $size = 10, $sort = null)
    {
        $options = array();
        $empty_object = new stdClass();

        if (!is_array($conditions)) {
            throw new Exception("Invalid array type for conditions arg[2]", 1);
        }

        if (!empty($fields) && is_array($fields)) {
            $options['_source'] = $fields;
        }

        $options['size'] = $size;

        if (empty($conditions)) {
            $options['query'] = array('match_all' => $empty_object);
        } elseif (!isset($conditions['query']) && empty($conditions['query'])) {
            $options['query'] = $this->queryBuilder($conditions); 
        } else {
            $options['query'] = $conditions['query'];
        }

        if (!is_null($sort)) {
            $options['sort'] = $sort;
        }

        $url = "/$index/$type/_search";
        $this->setUpCurl();
        $this->setDomainCurl($url);
        $this->setVerboseCurl('POST');
        $this->setParamsCurl($options);

        $result = $this->runCurl();
        if (isset($result['error']) && !empty($result['error'])) {
            CakeLog::write('es_error_search', json_encode(array(
                'method' => 'POST',
                'content' => $options,
                'url' => $url,
                'result' => $result,
            )));
        }
        return $result;
    }

/**
* searchPointInPolygon
*
* Search indexes that fulfill with the geo query
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var conditions, associative array with the parameter to search. IE
*           array('field1' => 'Hello World', 'fieldsito' => 975312468)
* @var fields, array with the name of the expected fields
* @var point, associative array with the keys latitude and longitude and the 
*       array values should be float values.
* @var size, max number of documents to retrieve
*/
    public function searchPointInPolygon($index, $type, $point, $fields = null, $size = 10)
    {
        $options = array();

        if (!is_array($point)) {
            throw new Exception("Invalid array type for point arg[2]", 1);
        }
        if (!isset($point['latitude'])) {
            throw new Exception("The key latitude doesn't exists in point arg[2]", 1);
        }
        if (!isset($point['longitude'])) {
            throw new Exception("The key latitude doesn't exists in point arg[2]", 1);
        }

        $conditions = array(
            'bool' => array(
                'must' => array(
                    'match' => array(
                        'active' => true
                    )
                ),
                'filter' => array(
                    'geo_shape' => array(
                        'location' => array(
                            'relation' => 'CONTAINS',
                            'shape' => array(
                                'type' => 'point',
                                'coordinates' => array($point['longitude'], $point['latitude'])
                            )
                        )
                    )
                )
            )
        );

        $options['size'] = $size;

        if (!empty($fields) && is_array($fields)) {
            $options['_source'] = $fields;
        }

        $options['query'] = $conditions;
        
        $this->setUpCurl();
        $this->setDomainCurl("/$index/$type/_search");
        $this->setVerboseCurl('POST');
        $this->setParamsCurl($options);

        return $this->runCurl();
    }

/**
* deleteDocument
*
* delete a document
*
* @var index, string with the name of the index to be created
* @var type, string with the type string to be mapped
* @var id, integer with the document id
*/
    public function deleteDocument($index, $type, $id, $parent = null)
    {
        if (is_nan($id)) {
            throw new Exception("Invalid number type for id arg[2]", 1);
        }

        $query = '';
        if (!is_null($parent)) {
            $query = '?parent=' . $parent;
        }

        $this->setUpCurl();        
        if (is_null($id)) {
            $url = "/$index/$type" . $query;
        } else {
            $url = "/$index/$type/$id" . $query;
        }
        $this->setDomainCurl($url);
        $this->setVerboseCurl('DELETE');

        return $this->runCurl();
    }


    /**
    * deleteByQuery
    *
    * Delete coverage by id
    *
    * @var index, string with the name of the index to be created
    * @var type, string with the type string to be mapped
    * @var coverage_id
    * @var parent, parent_id
    */
    public function deleteCoverageByQuery($index, $type, $id, $parent = null)
    {
        $options = array();

        if(empty($id)){
            throw new Exception("Empty value for id arg[2]", 1);
        }

        if (isset($id) && !is_null($id)) {
            $options = array(
                'query' => array(
                    'match' => array('_id' => $id)
                )
            );
        }

        if(!empty($parent)) {
            $options = array(
                'query' => array(
                    'bool' => array(
                        'must' => array(
                            array("match" => array( "coverage_id" => $id )),
                            array("match" => array( "establishment_id" => $parent ))
                        )
                    )
                )
            );
        }

        $url = "/$index/$type/_delete_by_query";
        $this->setUpCurl();
        $this->setDomainCurl($url);
        $this->setVerboseCurl('POST');
        $this->setParamsCurl($options);
        return $this->runCurl();
    }

/**
* deleteIndex
*
* delete a index
*
* @var index, string with the name of the index to be deleted
*/
    public function deleteIndex($index)
    {
        $this->setUpCurl();
        $this->setDomainCurl("/$index");
        $this->setVerboseCurl('DELETE');

        return $this->runCurl();
    }

/**
* deleteById
*
* delete a index
*
* @var index, string with the name of the index to be deleted
*/
    public function deleteById($index, $type, $id, $params = array())
    {
        $this->setUpCurl();

        $extra = http_build_query($params);
        $url = (empty($params)) ? "/$index/$type/$id" : "/$index/$type/$id".'?'.$extra;
        $this->setDomainCurl($url);
        $this->setVerboseCurl('DELETE');

        return $this->runCurl();
    }

/**
* queryBuilder
*
* return a formated basic query for elastic
*
* @var fields, associative array with the fields to search
*/
    private function queryBuilder($fields)
    {
        $filtered_fields = array();
        foreach ($fields as $key => $value) {
            $filtered_fields[]['term'] = array($key => $value);
        }

        $query = array(
            'constant_score' => array(
                'filter' => array(
                    'bool' => array(
                        'must' => array($filtered_fields)
                    )
                )
            )
        );

        return $query;
    }
}
