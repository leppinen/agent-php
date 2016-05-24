# Aino PHP Agent

![Build status](https://circleci.com/gh/Aino-io/agent-php.svg?style=shield&circle-token=6f1907380979d4ae35e2dbaba408ac63f7c0bc00)

PHP implementation of Aino.io logging agent

## What is [Aino.io](http://aino.io) and what does this Agent have to do with it?

[Aino.io](http://aino.io) is an analytics and monitoring tool for integrated enterprise applications and digital business processes. Aino.io can help organizations manage, develop, and run the digital parts of their day-to-day business. Read more from our [web pages](http://aino.io).

Aino.io works by analyzing transactions between enterprise applications and other pieces of software. This Agent helps to store data about the transactions to Aino.io platform using Aino.io Data API (version 2.0). See [API documentation](http://www.aino.io/api) for detailed information about the API.

## Technical requirements
* PHP5
* XDebug: PHPUnit requires also XDebug to produce JUnit log and other code coverage data.


## Installation
After project is cloned from github.com import aino.io.loader.php to your PHP file.
Example:

```
require_once('/path/to/aino.io/agent/root/dir/vendor/aino.io.loader.php');
```

## Usage

### 1. Import the agent
After aino.io.loader.php is imported into your PHP file use agent as the following:

```
use io\aino\Agent;

$agent = new Agent($apiKey);
```
Agent can be initiated also by API host and API version
```
$apiHost = 'https://data.aino.io:443'; // apiHost must contain port

$agent = new Agent($apiKey, $apiHost, $apiVersion);

or

$agent = new Agent($apiKey, $apiHost);
```

### 2. Send a request to Aino.io:

##### Sending with mandatory fields only

```
$from = 'Web shop';
$to = 'DB';
$status = 'success'; //success or failure or unknown

$result =  $agent->logTransaction($from,
                                   $to,
                                   $status);
```

##### Sending with all fields

```
$result =  $agent->logTransaction($from,
                                   $to,
                                   $status,
                                   $message,
                                   $operation,
                                   $payloadType,
                                   $flowId,
                                   $ids,
                                   $metadata);
```

##### Did we successfully send the request to Aino.io?
This information can be read from the result array that was returned from the ```logTransaction``` function.

Successfull case:
```
// $result contains successful response from Aino.io
array(
    'message' => "Request successfully sent",
    'status' => "success",
    'response' => "{"batchId":"6bf0f705-9431-475b-931d-064203d3d81b"}"
);
```

Error case:
```
// $result contains error response from Aino.io
array(
    'message' => "401 Unauthorized",
    'status' => "failure",
    'response' => "Invalid API key"
);
```

## Samples
See ```samples/AinoIoApiRunner.php``` how Aino.io PHP Agent is used. Do not use AinoIoApiRunner.php
directly in your project, it is just a sample. AinoIoApiRunner.php can be used to send log entries to real
Aino.io API. To send log entries to Aino.io using AinoIoApiRunner.php run the script as following:

```
php AinoIoApiRunner.php resources/example.request.json

```

Add your apiKey into resources/aino.io.api.conf before running the script.

## Contributors

* [Kreshnik Gunga](https://github.com/kgunga)
* [Ville Harvala](https://github.com/vharvala)
