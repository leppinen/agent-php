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

require_once(__DIR__.'/../vendor/aino.io.loader.php');

use io\aino\Agent;

class AinoIoApiRunner {
    private $apiConf = [];
    
    public function __construct() {
        $this->initConf();
    }
    
    public function run($transactions) {
        $agent = $this->initAgent();
        $results = array();
        foreach($transactions as $transaction){
            $from = $transaction['from'];
            $to = $transaction['to'];
            $status = $transaction['status'];
            $message = $transaction['message'];
            $operation = $transaction['operation'];
            $payloadType = $transaction['payloadType'];
            $flowId = $transaction['flowId'];
            $ids = $transaction['ids'];
            $metadata = $transaction['metadata'];
            
            $result =  $agent->logTransaction($from,
                                   $to,
                                   $status,
                                   $message,
                                   $operation,
                                   $payloadType,
                                   $flowId,
                                   $ids,
                                   $metadata);
            array_push($results, $result);
        }
        return $results;
    }
    
    private function initConf() {
        $conf = file_get_contents(__DIR__ . '/resources/aino.io.api.conf');
        $confLines = explode("\n", $conf);
        foreach ($confLines as $line){
            $line = trim($line);
            if (empty($line) || $line[0] == '#' || strpos($line, '=') === FALSE) {
                continue;
            }
            $keyValue = explode("=", $line);
            $value = trim($keyValue[1]);
            if ($value[0] == '#' || empty($value)) {
                continue;
            }
            $this->apiConf[trim($keyValue[0])] = $value;
        }
        if (!isset($this->apiConf['apiKey']) || $this->apiConf['apiKey'] == '') {
            throw new Exception('API Key is mandatory field');
        }
    }
    
    private function initAgent() {
        if (isset($this->apiConf['apiHost']) && isset($this->apiConf['apiVersion'])){
            return new Agent($this->apiConf['apiKey'], 
                             $this->apiConf['apiHost'], 
                             $this->apiConf['apiVersion']);
        }
        if (isset($this->apiConf['apiHost'])) {
            return new Agent($this->apiConf['apiKey'], 
                             $this->apiConf['apiHost']);
        }
        
        if (isset($this->apiConf['apiVersion'])) {
            return new Agent($this->apiConf['apiKey'], 
                             'https://data.aino.io:443',
                             $this->apiConf['apiVersion']);
        }
        
        return new Agent($this->apiConf['apiKey']);
    }
}

$runner = new AinoIoApiRunner();
$fileContent = file_get_contents($argv[1]);

$transactions = json_decode($fileContent, true);
$result = $runner->run($transactions['transactions']);

var_dump($result);