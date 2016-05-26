# Aino PHP Agent

![Build status](https://circleci.com/gh/Aino-io/agent-php.svg?style=shield&circle-token=6f1907380979d4ae35e2dbaba408ac63f7c0bc00)

PHP implementation of Aino.io logging agent

## What is [Aino.io](http://aino.io) and what does this Agent have to do with it?

[Aino.io](http://aino.io) is an analytics and monitoring tool for integrated enterprise applications and digital business processes. Aino.io can help organizations manage, develop, and run the digital parts of their day-to-day business. Read more from our [web pages](http://aino.io).

Aino.io works by analyzing transactions between enterprise applications and other pieces of software. This Agent helps to store data about the transactions to Aino.io platform using Aino.io Data API (version 2.0). See [API documentation](http://www.aino.io/api) for detailed information about the API.

## Technical requirements
* PHP 5

## Example usage

### 1. Import to your project
Download the [latest release](https://github.com/Aino-io/agent-php/releases)

Import ```aino.io.loader.php``` in your PHP file:
```
require_once('/path/to/aino.io/agent/root/dir/vendor/aino.io.loader.php');

use io\aino\Agent;
```

### 2. Send a request to Aino.io:

#### Minimal example (only required fields)

```
$agent = new Agent($apiKey);

$from = 'Web shop';
$to = 'DB';
$status = 'success'; //success or failure or unknown

$result =  $agent->logTransaction($from,
                                   $to,
                                   $status);
```

#### Full example

```
$agent = new Agent($apiKey);

$from = 'Web shop';
$to = 'DB';
$status = 'success'; //success or failure or unknown
$message = 'Update users to master data.';
$operation = 'Update master data';
$payloadType = 'Users';
$flowId = '123456789';
$ids = [['idType' => 'UserId', 'values' => [1,2,3,4]]];
$metadata = [['name' => 'Script type', 'value' => 'PHP']];

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

## Debugging

```logTransaction``` function returns a status object that you can use to verify successful communication with Aino.io.

Successfull case:
```
array(
    'message' => "Request successfully sent",
    'status' => "success",
    'response' => "{"batchId":"6bf0f705-9431-475b-931d-064203d3d81b"}"
);
```

Example of an error case when using invalid/disabled API key:
```
array(
    'message' => "401 Unauthorized",
    'status' => "failure",
    'response' => "Invalid API key"
);
```

## Samples
Check samples directory.

## Contributing

### Technical requirements
* XDebug: PHPUnit requires also XDebug to produce JUnit log and other code coverage data.


### Contributors

* [Kreshnik Gunga](https://github.com/kgunga)
* [Ville Harvala](https://github.com/vharvala)
