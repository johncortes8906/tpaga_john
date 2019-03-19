<?php

class QualtricsException extends Exception {

    public function __construct($message = "", $code = 0, Exception $previous = NULL) {
        // Construct message from JSON if required.
        if (substr($message, 0, 1) == '{') {
          $message_obj = json_decode($message);
          $http_status = $message_obj->meta->httpStatus;
          $error_message = $message_obj->meta->error->errorMessage;
          $error_code = $message_obj->meta->error->errorCode;
          $message = $http_status . ': ' . $error_message . ' - ' . $error_code;
          if (!empty($message_obj->meta->error)) {
            $message .= ' ' . serialize($message_obj->meta->error);
          }
        }
        parent::__construct($message, $code, $previous);
    }
}