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

	return new Response('Misconfigured server', Response::HTTP_INTERNAL_SERVER_ERROR);
}
if (!defined('KEY')){
	$message = "You must define a constant called KEY in _config.php to handle request auth";

	log_to_file($message);

	return new Response('Server not configured for auth', Response::HTTP_INTERNAL_SERVER_ERROR);
}

$request_url = BASE.$uri;

log_to_file("Request will go to $request_url");

$client = new Client();
$options = [];

$auth_uri = $uri[0]==='/' ? substr($uri, 1) : $uri;

$auth_string = $auth_uri;

$body = $inbound_request->getContent();
if (!empty($body)){
	$options['body'] = $body;
	$auth_string .= trim($body);
}

$form_data = $inbound_request->request->all();
if (!empty($form_data)){
	$options['form_params'] = $form_data;
	$auth_string .= json_encode($form_data);
}

$headers = $inbound_request->headers->all();
$xproxyauth = is_array($headers['x-proxy-auth']) ? reset($headers['x-proxy-auth']) : $headers['x-proxy-auth'];
unset($headers['x-proxy-auth']);

if (empty($xproxyauth)){
	log_to_file("Request did not sent x-proxy-auth header");

	return new Response('You must send an x-proxy-auth header', Response::HTTP_PROXY_AUTHENTICATION_REQUIRED);
}
log_to_file(print_r($headers, true));

$key = KEY;
$calculated_auth = hash_hmac('sha256', $auth_string, $key);
if ($calculated_auth!==$xproxyauth){
	log_to_file("Request auth header was {$xproxyauth} but calculated header was {$calculated_auth}");
	log_to_file("Auth string used to calculate was '$auth_string'");

	return new Response("The proxy auth header you sent ({$xproxyauth}) did not match the calculated value using the pre-shared key.", Response::HTTP_UNAUTHORIZED);
}

if (!empty($headers)){
	if (defined('HEADER_EXCLUDE')){
		$excludes = explode(',', HEADER_EXCLUDE);
		foreach ($excludes as $exclude){
			unset($headers[$exclude]);
		}
	}

	$options['headers'] = $headers;
}

if (!empty($options)){
	log_to_file("Options will be");
	log_to_file(print_r($options, true));
}

$method = $inbound_request->getMethod();

log_to_file("Sending $method request to intended recipient");
$returned_response = $client->request($method, $request_url, $options);

log_to_file("Returned response body is: ".$returned_response->getBody());

$response = new Response($returned_response->getBody(), $returned_response->getStatusCode());
$response->prepare($inbound_request);
$response->send();

log_to_file('Response sent back to original client');
