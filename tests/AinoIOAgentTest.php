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

require_once(__DIR__.'/../vendor/aino.io.loader.php');

use io\aino\Agent;

ini_set('memory_limit', '2048M');

$hostnameRequested = NULL;
$portRequested = -1;
$errorNumber = NULL;
$errorString = NULL;
$timeoutForRequest = NULL;

$throwWarning = NULL;

$requestFile = '/tmp/request.txt';

$responseFile = NULL;

function fgets($handle, $length = NULL){
    global $responseFile;
    return \fgets($responseFile);
}

/**
 * Mock for fsockopen
 * 
 * @param type $hostname
 * @param type $port
 * @param type $errno
 * @param type $errstr
 * @param type $timeout
 */
function fsockopen($hostname, $port = -1, &$errno, &$errstr, $timeout = 10) {
    global $throwWarning;
    
    if ($throwWarning != NULL) {
        $errstr = 'Invalid hostname';
        $errno = $throwWarning;
        trigger_error($errstr, $errno);
        $throwWarning = NULL;
        return NULL;
    }
    
    global $hostnameRequested;
    $hostnameRequested = $hostname;
    
    global $portRequested;
    $portRequested = $port;
    
    global $errorNumber;
    $errorNumber = $errno;
    
    global $errorString;
    $errorString = $errstr;
    
    global $timeoutForRequest;
    $timeoutForRequest = $timeout;
    
    global $requestFile;
    return fopen($requestFile,'w+');
}

/**
 * Description of newSeleneseTest
 *
 */
class AinoIOAgentTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        global $requestFile;
        if (file_exists($requestFile)) {
           unlink($requestFile);
        }
    }

    public function tearDown() {
    }


    public function testWithDefaultHostnameAndApiVersion() {
        global $responseFile;
        $responseFile = fopen('tests/resources/errorResponse.txt','r');
        
        $ainoAgent = new Agent('invalid_api_key');
        $result = $ainoAgent->logTransaction('testESB', 'testDB', 'success');
        
        $this->assertTrue('failure' == $result['status']);
        
        global $hostnameRequested;
        $this->assertTrue('ssl://data.aino.io:443' == $hostnameRequested);
        
        global $portRequested;
        $this->assertTrue(-1 == $portRequested);
        
        global $errorNumber;
        $this->assertTrue(NULL == $errorNumber);
        
        global $errorString;
        $this->assertTrue(NULL == $errorString);
        
        global $timeoutForRequest;
        $this->assertTrue(30 == $timeoutForRequest);
        
        $request = $this->parseRequest();
        $this->assertTrue('/rest/v2.0/transaction' == $request['POST']);
        $this->assertTrue('data.aino.io' == $request['Host']);
        $this->assertTrue('apikey invalid_api_key' == $request['Authorization']);
        $this->assertTrue('application/json' == $request['Content-Type']);
        $this->assertTrue('Close' == $request['Connection']);
        
        $body = json_decode($request['body'], TRUE);
        $this->assertTrue(isset($body['transactions']));
        $transactions = $body['transactions'][0];
        $this->assertTrue('testESB' == $transactions['from']);
        $this->assertTrue('testDB' == $transactions['to']);
        $this->assertTrue('success' == $transactions['status']);
        $this->assertTrue(is_long($transactions['timestamp']));
    }
    
    public function testWithErrorHandling() {
        global $throwWarning;
        $throwWarning = E_WARNING;
        
        $ainoAgent = new Agent('invalid_api_key','blaablaa');
        $result = $ainoAgent->logTransaction('testESB', 'testDB', 'success');
        
        $this->assertTrue('error' == $result['status']);
        $this->assertTrue('Invalid hostname' == $result['message']);
    }

    public function testWithAllFields() {
        global $responseFile;
        $responseFile = fopen('tests/resources/successfulResponse.txt','r');
        
        $ainoAgent = new Agent('invalid_api_key','blaablaa','v2.1');
        $result = $ainoAgent->logTransaction('testESB', 'testDB', 'success',
                        'Successfully saved to DB', 'Store to DB',
                        'Data storing', '1234',
                        [['idType' => 'Dummy', 'values' => [1,2,3,4]]], 
                        [['name' => 'Meta extra information', 'value' => 'Working well']]);
        
        $this->assertTrue('success' == $result['status']);
        
        $this->assertTrue(isset($result['response']));
        
        $request = $this->parseRequest();
        $this->assertTrue('/rest/v2.1/transaction' == $request['POST']);
        $this->assertTrue('blaablaa' == $request['Host']);
        $this->assertTrue('apikey invalid_api_key' == $request['Authorization']);
        $this->assertTrue('application/json' == $request['Content-Type']);
        $this->assertTrue('Close' == $request['Connection']);
        
        $body = json_decode($request['body'], TRUE);
        $this->assertTrue(isset($body['transactions']));
        $transactions = $body['transactions'][0];
        $this->assertTrue('testESB' == $transactions['from']);
        $this->assertTrue('testDB' == $transactions['to']);
        $this->assertTrue('success' == $transactions['status']);
        $this->assertTrue(is_long($transactions['timestamp']));
        $this->assertTrue('Successfully saved to DB' == $transactions['message']);
        $this->assertTrue('Store to DB' == $transactions['operation']);
        $this->assertTrue('Data storing' == $transactions['payloadType']);
        $this->assertTrue('1234' == $transactions['flowId']);
        
        $ids = $transactions['ids'];
        $this->assertTrue(count($ids) == 1);
        $this->assertTrue('Dummy' == $ids[0]['idType']);
        $this->assertTrue(count(array_diff([1,2,3,4], $ids[0]['values'])) == 0);
        
        $metadata = $transactions['metadata'];
        $this->assertTrue(count($metadata) == 1);
        $this->assertTrue('Meta extra information' == $metadata[0]['name']);
        $this->assertTrue('Working well' == $metadata[0]['value']);
    }
    
    public function testOneMegaByteFile() {
        $ainoAgent = new Agent('invalid_api_key','blaablaa','2.1');
        $fileContent = file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $fileContent .= file_get_contents('tests/resources/test10Mb.db');
        $result = $ainoAgent->logTransaction('testESB', 'testDB', 'success',
                        $fileContent);
        
        $this->assertTrue('error' == $result['status']);
        $this->assertTrue('Body size exceeded limit of 1MB' == $result['message']);
    }
    
    private function parseRequest() {
        global $requestFile;
        $requestContent = file_get_contents($requestFile);
        $requestContentLines = explode("\r\n", $requestContent);
        $requestMethod = explode(' ',array_shift($requestContentLines));
        $contentMap = [trim("{$requestMethod[0]}") => trim($requestMethod[1])];
        
        $this->buildContentMap($contentMap, $requestContentLines);
        
        return $contentMap;
    }
    
    private function buildContentMap(array &$contentMap, array $requestContentLines) {
        $body = '';
        $isBodyPart = FALSE;
        foreach ($requestContentLines as $line){
            if (trim($line) === ''){//if line is not HTTP header
                $isBodyPart = TRUE;
            }
            
            if ($isBodyPart) {
                $body .= $line;
            } else {
                $keyValue = explode(':', $line);
                $key = array_shift($keyValue);
                $contentMap[trim($key)] = trim(implode(':',$keyValue));
            }
        }
        $contentMap['body'] = gzdecode($body);
    }
}
