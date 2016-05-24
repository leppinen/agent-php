<?php
/**
 * Copyright 2016 Aino.io
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace io\aino;


/*
 * PHP util class to log (fire & forget style) to Aino.io.
 */
class Agent
{
    private $apiHost; // Hostname to the API
    private $apiKey; // Unique key to access API
    private $apiVersion; // API version
    
    /**
     * Class constructor.
     *
     * @param type $apiKey Unique API key
     * @param type $apiHost Hostname of the API
     * @param type $apiVersion API version
     */
    public function __construct($apiKey, 
                                $apiHost = 'https://data.aino.io:443', 
                                $apiVersion = 'v2.0') {
        $this->apiKey = $apiKey;
        $this->apiHost = str_replace('https:','ssl:',rtrim($apiHost,'/'));
        $this->apiVersion = $apiVersion;
    }
    
    /**
     * Method to log transaction to Aino.io.
     * 
     * @param string $from Mandatory field which contains name of the source system
     * @param string $to Mandatory field which contains name of the target system
     * @param string $status Mandatory field which contains operation status (error/success/unknown)
     * @param string $message Optional field which contains short message, ie. Successfully saved to DB
     * @param string $operation Optional field which contains "title" of the operation, ie. Store to DB
     * @param string $payloadType Optional field which contains payload type name, ie. Data storing
     * @param string $flowId Optional field which contains unique uuid, ie. af75d5da-5a5c-4cf2-bd6e-3be813ea2145
     * @param array $ids Optional field which contains an array, ie. [['idType' => 'Customer ID','values' => ['123', '456', '789']]]
     * @param array $metadata Optional field which contains an array, ie. [['name' => 'Extra information', 'value' => 'Hello World!'],['name' => 'Support ticketing', 'value' => 'https://example.com/ticket/123abc']]
     * @return array Associative array containing message (string) and status (success/error), ie. ['message' => 'Body size exceeded limit of 1MB', 'status' => 'error']
     */
    public function logTransaction($from, $to, $status,
                        $message = NULL, $operation = NULL,
                        $payloadType = NULL, $flowId = NULL,
                        $ids = NULL, $metadata = NULL) {
        // build request body
        $body = $this->buildRequestBody($from, $to, $status, $message, 
                                        $operation, $payloadType, $flowId, 
                                        $ids, $metadata);
        
        $body = gzencode($body, -1, FORCE_GZIP);        
        $bodySize = strlen($body); //strlen = number of bytes
       
        // https://www.aino.io/api
        // The batch size is currently limited to 1 MB in both 
        // gzipped and uncompressed API requests.
        if ($bodySize > 1024 * 1024) {
            // can't do, return
            return ['message' => 'Body size exceeded limit of 1MB', 'status' => 'error'];
        }
        return $this->postRequestToAinoIO($bodySize, $body);
    }
    
    /**
     * Helper method to build request body for Aino.io.
     * 
     * @param string $from Mandatory field which contains name of the source system
     * @param string $to Mandatory field which contains name of the target system
     * @param string $status Mandatory field which contains operation status (error/success/unknown)
     * @param string $message Optional field which contains short message, ie. Successfully saved to DB
     * @param string $operation Optional field which contains "title" of the operation, ie. Store to DB
     * @param string $payloadType Optional field which contains payload type name, ie. Data storing
     * @param string $flowId Optional field which contains unique uuid, ie. af75d5da-5a5c-4cf2-bd6e-3be813ea2145
     * @param array $ids Optional field which contains an array, ie. [['idType' => 'Customer ID','values' => ['123', '456', '789']]]
     * @param array $metadata Optional field which contains an array, ie. [['name' => 'Extra information', 'value' => 'Hello World!'],['name' => 'Support ticketing', 'value' => 'https://example.com/ticket/123abc']]
     * @return array Associative array containing message (string) and status (success [request fired and forgot]/error [validation check failure]), ie. ['message' => 'Body size exceeded limit of 1MB', 'status' => 'error']
     */
    private function buildRequestBody($from, $to, $status,
                        $message = NULL, $operation = NULL,
                        $payloadType = NULL, $flowId = NULL,
                        $ids = NULL, $metadata = NULL) {
        $transactions = ['transactions' => [[
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'timestamp' => time()
        ]]];
        $this->setOptionalFields($transactions, $message, $operation, 
                          $payloadType, $flowId, $ids, $metadata);
        return json_encode($transactions);
    }
    
    /**
     * Helper method to init optional fields to request body.
     * 
     * @param array $transactions Field container
     * @param string $message Optional field which contains short message, ie. Successfully saved to DB
     * @param string $operation Optional field which contains "title" of the operation, ie. Store to DB
     * @param string $payloadType Optional field which contains payload type name, ie. Data storing
     * @param string $flowId Optional field which contains unique uuid, ie. af75d5da-5a5c-4cf2-bd6e-3be813ea2145
     * @param array $ids Optional field which contains an array, ie. [['idType' => 'Customer ID','values' => ['123', '456', '789']]]
     * @param array $metadata Optional field which contains an array, ie. [['name' => 'Extra information', 'value' => 'Hello World!'],['name' => 'Support ticketing', 'value' => 'https://example.com/ticket/123abc']]
     */
    private function setOptionalFields(&$transactions,
                        $message = NULL, $operation = NULL,
                        $payloadType = NULL, $flowId = NULL,
                        $ids = NULL, $metadata = NULL) {
        if ($message != NULL) {
            $transactions['transactions'][0]['message'] = $message;
        }
        
        if ($operation != NULL) {
            $transactions['transactions'][0]['operation'] = $operation;
        }
        
        if ($payloadType != NULL) {
            $transactions['transactions'][0]['payloadType'] = $payloadType;
        }
        
        if ($flowId != NULL) {
            $transactions['transactions'][0]['flowId'] = $flowId;
        }
        
        if ($ids != NULL && is_array($ids) && !empty($ids)) {
            $transactions['transactions'][0]['ids'] = $ids;
        }
        
        if ($metadata != NULL && is_array($metadata) && !empty($metadata)) {
            $transactions['transactions'][0]['metadata'] = $metadata;
        }
    }
    
    /**
     * Sends fire & forget POST method request to Aino.io.
     * 
     * @param integer $bodySize Length of the body
     * @param string $body Request body
     * @param string $contentType Request's content type, default application/json
     * @return array Operation result either success or error
     */
    
    private function postRequestToAinoIO($bodySize, $body) {
        $errno = NULL;
        $errstr = NULL;
        $connection = @fsockopen($this->apiHost, -1, $errno, $errstr, 30);
        if (!$connection) {
            return ['message' => $errstr, 'errorCode' => $errno, 'status' => 'error'];
        }
        $strippedHost = explode(':', str_replace('ssl://','',
                                    str_replace('https://', '', $this->apiHost)));
        $host = array_shift($strippedHost);
        // create output request
        $output = $this->preparePostRequest($host, $body, $bodySize);
        @fwrite($connection, $output);
       
        $response = $this->readResponse($connection);
        @fclose($connection);
        return ['message' => $response['status'] == 'success'?
                            'Request successfully sent' : 
                            $response['http_code'] . ' ' . $response['http_message'], 
                'status' => $response['status'],
                'response' => $response['body']];
    }
    
    /**
     * Helper method to prepare POST request.
     * 
     * @param string $host Host to be set to Host-header
     * @param string $body Body content of the request
     * @param int $bodySize Size of the body content
     * @return string Prepared HTTP request
     */
    private function preparePostRequest($host, $body, $bodySize) {
        $request  = "POST /rest/{$this->apiVersion}/transaction HTTP/1.1\r\n";
        $request .= "Host: $host\r\n";
        $request .= "Authorization: apikey {$this->apiKey}\r\n";
        $request .= "Content-Type: application/json\r\n";
        $request .= "Content-Length: $bodySize\r\n";
        $request .= "Content-Encoding: gzip\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $request .= $body;
        return $request;
    }
    
    /**
     * Helper method to read server's response from connection.
     * 
     * @param Socket $connection Server connection
     * @return array Read response
     */
    private function readResponse($connection) {
        $response = array();
        $body = '';
        $isBodyPart = FALSE;
        $firstLine = TRUE;
        while ($line = fgets($connection)) {
            $trimmedLine = trim($line);
            if ($firstLine) { // HTTP response code and message
                $this->parseHttpLine($response, $trimmedLine);
                $firstLine = FALSE;
                continue;
            }
            if ($trimmedLine === ''){//if line is not HTTP header
                $isBodyPart = TRUE;
            }
            if ($isBodyPart) {
                $body .= $trimmedLine;
            } else {
                $keyValue = explode(':', $trimmedLine);
                $key = array_shift($keyValue);
                $response[trim($key)] = trim(implode(':',$keyValue));
            }
        }
        $response['body'] = $body;
        return $response;
    }
    
    /**
     * Helper method to parse first line of HTTP response.
     * 
     * @param array $response Associative array containing response
     * @param string $line Line to be parsed
     */
    private function parseHttpLine(&$response, $line){
        $splittedLine = explode(' ', $line);
        $protocolAndVersion = explode('/', array_shift($splittedLine));
        $response['protocol'] = trim(array_shift($protocolAndVersion));
        $response['protocol_version'] = trim(array_shift($protocolAndVersion));
        $httpCode = trim(array_shift($splittedLine));
        $response['http_code'] = $httpCode;
        $response['http_message'] = trim(array_shift($splittedLine));
        $response['status'] = 200 <= $httpCode && $httpCode <= 299?'success':'failure';
    }
}