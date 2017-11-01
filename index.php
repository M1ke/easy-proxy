<?php
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/_config.php';

function log_to_file($line){
	$log_dir = __DIR__.'/log/';
	if (!file_exists($log_dir)){
		mkdir($log_dir);
	}
	$log_file = $log_dir.date('Y-m-d');
	$time = date('Y-m-d H:i:s').'.'.explode('.', microtime(true))[1];
	file_put_contents($log_file, "[$time] $line\n", FILE_APPEND);
}

log_to_file('Received a request to proxy');

$inbound_request = Request::createFromGlobals();

$uri = $inbound_request->getRequestUri();

if (!defined('BASE')){
	$message = "You must define a constant called BASE in _config.php to proxy requests";

	log_to_file($message);

	throw new Exception($message);
}

$request_url = BASE.$uri;

log_to_file("Request will go to $request_url");

$client = new Client();
$options = [];

$body = $inbound_request->getContent();
if (!empty($body)){
	$options['body'] = $body;
}

$form_data = $inbound_request->request->all();
if (!empty($form_data)){
	$options['form_params'] = $form_data;
}

if (!empty($options)){
	log_to_file("Options will be");
	log_to_file(print_r($options, true));
}

log_to_file('Sending request to intended recipient');

$returned_response = $client->request($inbound_request->getMethod(), $request_url, $options);

log_to_file("Returned response body is: ".$returned_response->getBody());

$response = new Response($returned_response->getBody(), $returned_response->getStatusCode());
$response->prepare($inbound_request);
$response->send();

log_to_file('Response sent back to original client');
