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

$app->group('/dlg', function () use ($app) {

	function responseDialog($text) {
		return [
			"speech" => $text,
			"displayText" => $text,
			"source" => "one-desk-api",
			"contextOut" => [],
			"data" => [],
		];
	};

	$app->post('/pwd', function ($request, $response) {
		$body = $request->getBody();
		$data = json_decode($body, true);

		file_put_contents('api.log', print_r($data, true));

		$return = responseDialog("I could not communicate propertly with the backend");

		if (empty($data['queryResult']['parameters']['account_number'])) {
			$return = responseDialog("Sorry, you didn’t provide a valid account number, I was expecting something more like A999999");
		}

		$accountNumber = $data['queryResult']['parameters']['account_number'];

		$accounts = [
			'X9999' => 'unlocked',
			'T7777' => 'failed',
		];

		if (empty($accounts[$accountNumber])) {
			$return = responseDialog("Sorry, the account number you provided does not match with any valid record in my database");
		} else if ('failed' == $accounts[$accountNumber]) {
			$return = responseDialog("Sorry, I was not able to unlock the account provided");
		} else {
			$return = responseDialog("Your account was successfully unlocked");
		}

		return $response->withJson($return);
	});

});

$app->run();