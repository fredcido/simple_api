<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

require 'vendor/autoload.php';

$config = [
	'settings' => [
		'displayErrorDetails' => true,

		'logger' => [
			'name' => 'slim-app',
			'level' => Monolog\Logger::DEBUG,
			'path' => __DIR__ . '/app.log',
		],
	],
];

$app = new Slim\App($config);

$app->post('/pwd', function (Request $request, Response $response, array $args) {
	$body = $request->getBody();
	$data = json_decode($body, true);

	$accounts = [
		'X9999' => 'unlocked',
		'T7777' => 'failed',
	];

	if (empty($data)) {
		return $response->withStatus(400);
	} else if (empty($accounts[$data['account']])) {
		return $response->withStatus(404);
	}

	$return = ['status' => $accounts[$data['account']]];

	return $response->withJson($return);
});

$app->get('/hr/{employee}', function (Request $request, Response $response, array $args) {

	$employees = [
		'E12345' => 20,
		'E99999' => 25,
	];

	if (empty($args['employee'])) {
		return $response->withStatus(400);
	} else if (empty($employees[$args['employee']])) {
		return $response->withStatus(404);
	}

	$return = ['days' => $employees[$args['employee']]];

	return $response->withJson($return);
});

function responseDialog($text) {
	return [
		"fulfillmentText" => $text,
		"speech" => $text,
		"displayText" => $text,
		"webhookSource" => "one-desk-api",
		//"contextOut" => [],
		//"data" => [],
	];
};

function resetPwd($data) {
	$responseText = "I could not communicate propertly with the backend";

	if (empty($data['result']['parameters']['account_number'])) {
		$responseText = "Sorry, you didn’t provide a valid account number, I was expecting something more like A9999";
	}

	$accountNumber = $data['result']['parameters']['account_number'];

	$accounts = [
		'X9999' => 'unlocked',
		'T7777' => 'failed',
	];

	if (empty($accounts[$accountNumber])) {
		$responseText = "Sorry, the account number you provided does not match with any valid record in my database";
	} else if ('failed' == $accounts[$accountNumber]) {
		$responseText = "Sorry, I was not able to unlock the account provided";
	} else {
		$responseText = "Your account was successfully unlocked";
	}

	return $responseText;
}

function holidaysLeft($data) {
	$responseText = "I could not communicate propertly with the backend";

	if (empty($data['result']['parameters']['account_number'])) {
		$responseText = "Sorry, you didn’t provide a valid employee number, I was expecting something more like A9999";
	}

	$accountNumber = $data['result']['parameters']['account_number'];

	$accounts = [
		'X9999' => 20,
		'T7777' => 0,
	];

	if (!isset($accounts[$accountNumber])) {
		$responseText = "Sorry, the employee number you provided does not match with any valid record in my database";
	} else if (empty($accounts[$accountNumber])) {
		$responseText = "It seems that there is no more leave days remaining for you, what a bummer";
	} else {
		$responseText = sprintf("You still have %d leave days remaining", $accounts[$accountNumber]);
	}

	return $responseText;
}

$app->post('/dlg', function ($request, $response) use ($app) {
	$body = $request->getBody();
	$data = json_decode($body, true);

	file_put_contents('api.log', print_r($data, true));

	$responseText = "No action identified";
	if (!empty($data['result']['action'])) {
		switch ($data['result']['action']) {
		case 'unlock.account':
			$responseText = resetPwd($data);
			break;
		case 'holidays.left':
			$responseText = holidaysLeft($data);
			break;
		default:
			$responseText = sprintf("The action '%s' is not supported", $data['result']['action']);
		}
	}

	$return = responseDialog($responseText);
	return $response->withJson($return);
});

$app->run();