<?php

use Curl\Curl;
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

class APIExecution
{
    public $curl = null;
    public $url = null;
    public $max_retries = 3;
    private $model_api4u_log_request = null;

    public function __construct($model_api4u_log_request)
    {
        $this->model_api4u_log_request = $model_api4u_log_request;
    }

    public function __destruct()
    {
        $this->curl = null;
        $this->url = null;
    }

    public function checkToken($data = array()): bool
    {
        ## Check if token has expired ##

        $response = $this->initializeAndExecuteCurl($data);
        if (!isset($response) || $response->Status == 'ERROR')
        {
            log_error("[API4U]:", "$response->Status: $response->Error");
            return false;
        }

        return true;
    }

    public function login($data = array()): array
    {
        ## Get access token ##

        $response_array = array();
        $response = $this->initializeAndExecuteCurl($data);

        if (isset($response) && $response->Status == 'OK')
        {
            $response_decode = json_decode($response->Result, TRUE);
            $response_array = isset($response_decode) ? array(
                'status' => 'success',
                'token' => $response_decode['cookie'],
                'created' => $response_decode['created'],
                'expired' => $response_decode['expired']
            ) : $response_array;
        }
        else
        {
            log_error('[API4U]: ', $response->Status . $response->Message);
        }

        return $response_array;
    }

    public function getData($data = array()): array
    {
        ## Get items from api4u script##

        $response_array = array();
        $response = $this->initializeAndExecuteCurl($data);

        if (isset($response) && $response->Status == 'OK')
        {
            $response_decode = json_decode($response->Result, TRUE);
            $response_array = isset($response_decode) && gettype($response_decode) == 'array' ? $response_decode : $response_array;
        }
        elseif (isset($response) && $response->Status == 'ERROR')
        {
            $response_array = array();
            log_error("[API4U]:", "$response->Status: $response->Error");
        }
        else
        {
            $response_array = array();
            log_error("[API4U]:", '{' . $response . '}');
        }

        return $response_array;
    }

    public function postData($data = array()): array
    {
        $response_array = array();
        $response = $this->initializeAndExecuteCurl($data);

        if (isset($response) && $response->Status == 'OK')
        {
            $response_decode = json_decode($response->Result, TRUE);
            $response_array = isset($response_decode) && gettype($response_decode) == 'array' ? $response_decode : $response_array;
        }
        elseif (isset($response) && $response->Status == 'ERROR')
        {
            $response_array = array();
            log_error("[API4U]:", "$response->Status: $response->Error");
        }
        else
        {
            $response_array = array();
            log_error("[API4U]:", '{' . $response . '}');
        }

        return $response_array;
    }

    public function initializeAndExecuteCurl($data): ?object
    {
        $post = IS_POST_OR_GET;
        $response = null;
        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
        $this->curl->setOpt(CURLOPT_ENCODING, '');
        $this->curl->setHeader('Content-Type', 'application/json');
        // $this->curl->setHeader('token', API_TOKEN); IN CASE THERE IS STATIC TOKEN
        $this->curl->setDefaultTimeout();
        $this->curl->setRetry(function ($instance) {
            $retry_status = $instance->retries < $this->max_retries && in_array($instance->httpStatusCode, [500, 503], true);
            if ($retry_status)
            {
                sleep(5);
            }
            return $retry_status;
        });

        if ($post == true) {
            $this->curl->post($this->url, $data);
        } else {
            $this->curl->get($this->url, $data);
        }
        $this->curl->close();

        $params = '';
        $datatype = gettype($data);
        if ($datatype == 'object' || $datatype == 'array') 
        {
            $params = json_encode($data);
        } 
        elseif ($datatype != 'NULL' && $datatype != 'unknown type') 
        {
            $params = $data;
        }

        $response_array = array(
            'url' => $this->curl->url,
            'parameters' => $params,
            'code' => $this->curl->httpStatusCode,
            'raw_response_header' => $this->curl->rawResponseHeaders,
            'raw_response' => $this->curl->rawResponse,
            'error' => '{error code:' . $this->curl->errorCode . ', error message:' . $this->curl->errorMessage . '}',
            'attempts' => $this->curl->attempts,
            'retries' => $this->curl->retries
        );

        if ($this->curl->error)
        {
            log_error("[API4U]:", 'Error: ' . $this->curl->errorCode . ': ' . $this->curl->errorMessage);
        }
        $response = is_object($this->curl->response) ? $this->curl->response : $response;
        $this->model_api4u_log_request->log($response_array);

        return $response;
    }
}