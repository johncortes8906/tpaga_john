<?php

require 'QualtricsException.php';

class Qualtrics {

    /**
        * @var string $apiKey
        * The Qualtrics API KEY to authenticate with.
    */
    private $apiKey;

    /**
        * @var string $datacenterId
        * The Qualtrics datacenter associated to the account.
    */
    private $datacenterId = 'eu';

    /**
        * @var string $baseUrl
        * The Qualtrics' API URL
    */
    private $baseUrl = '.qualtrics.com/API/v3/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function createMailingList($mailingList)
    {
        return $this->makeCall('mailinglists', $mailingList, 'POST');
    }

    public function importUserToMailingList($mailingListId, $user)
    {
        return $this->makeCall('mailinglists/' . $mailingListId . '/contacts', $user, 'POST');
    }

    public function importFileUsersToMailingList($mailingListId, $pathFile)
    {
        $data = array(
            'pathFile' => $pathFile,
            'fileName' => end(explode('/', $pathFile)),
            'mime' => 'application/octet-stream'
        );
        return $this->makeCall('mailinglists/' . $mailingListId . '/contactimports', array(), 'POST', $data);
    }

    public function createSurveyDistribution($distribution)
    {
        return $this->makeCall('distributions', $distribution, 'POST');
    }

    public function getContactsImportProgress($mailingListId, $importId)
    {
        $uri = 'mailinglists/' . $mailingListId . '/contactimports/' . $importId;
        $stateProgress = $this->makeCall($uri);
        if (
            $stateProgress['result']['percentComplete'] == 100
            && $stateProgress['result']['status'] == 'complete'
        ) {
            return true;
        }

        return false;
    }

    public function getSurveyDistribution($surveyId, $offset = 0)
    {
        $data = array(
            'surveyId' => $surveyId,
            'sendStartDate' => date('c', strtotime('-1 day')),
            'sendEndDate' => date('c'),
            'offset' => $offset
        );
        return $this->makeCall('distributions', $data);
    }

    public function getContactsForDistribution($distributionId, $pageSize = 100, $offset = 0)
    {
        $directoryId = 'POOL_5auVgHqXcvNps9f';
        $uri = 'directories/' . $directoryId . '/mailinglists/' . $distributionId . '/contacts';
        $data = array(
            'pageSize' => $pageSize,
            'offset' => $offset
        );
        return $this->makeCall($uri, $data);
    }

    private function makeCall($uri, $data = array(), $method = 'GET', $file = null)
    {
        $url = 'https://' . $this->datacenterId . $this->baseUrl . $uri;
        $ch = curl_init();
        $headers = array(
            'x-api-token: ' . $this->apiKey 
        );

        switch ($method)
        {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    $jsonData = json_encode($data, JSON_FORCE_OBJECT);   
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'Content-Length: ' . strlen($jsonData);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                } else if (!empty($file)) {
                    if (function_exists('curl_file_create')) { // php 5.5+
                      $cFile = curl_file_create($file['pathFile']);
                    } else {
                      $cFile = '@' . $file['pathFile'] . ';filename=' . $file['fileName'] . ';type=' . $file['mime'];
                    }
                    $data = array('file' => $cFile);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new QualtricsException($error, $httpCode);
        }

        $result = json_decode($result, true);
        if (isset($result['meta']['error'])) {
            throw new QualtricsException($result['meta']['error']['errorMessage'], $httpCode);
        }

        return $result;
    } 
}