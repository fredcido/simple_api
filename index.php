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

$app->post('/expense', function (Request $request, Response $response, array $args) {
	$body = $request->getBody();
	$data = json_decode($body, true);

	$reports = [
		'EE1001' => ['status' => 'assessment'],
		'EE1002' => ['status' => 'approved', 'paymentDate' => '2018-01-14'],
		'EE1003' => ['status' => 'declined', 'reason' => 'Missing invoice file'],
	];

	if (empty($data)) {
		return $response->withStatus(400);
	} else if (empty($reports[$data['report']])) {
		return $response->withStatus(404);
	}

	$return = $reports[$data['report']];

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

function responseDialog($text, $events = []) {

	return [
		"fulfillmentText" => $text,
		//"speech" => $text,
		//"displayText" => $text,
		"source" => "one-desk-api",
		"followupEventInput" => $events,
		//"outputContexts" => [],
		//"payload" => [],
	];
};

function resetPwd($data) {
	$return = responseDialog("I could not communicate propertly with the backend");

	if (empty($data['parameters']['account_number'])) {
		$return = responseDialog("Sorry, you didn’t provide a valid account number, I was expecting something more like A9999");
	}

	$accountNumber = $data['parameters']['account_number'];

	$accounts = [
		'X9999' => 'unlocked',
		'T7777' => 'failed',
	];

	if (empty($accounts[$accountNumber])) {
		$event = [
			"name" => "account-number-invalid",
			"languageCode" => "en",
		];

		$return = responseDialog("Sorry, the account number you provided does not match with any valid record in my database", $event);
	} else if ('failed' == $accounts[$accountNumber]) {
		$return = responseDialog("Sorry, I was not able to unlock the account provided");
	} else {
		$return = responseDialog("Your account was successfully unlocked");
	}

	return $return;
}

function holidaysLeft($data) {
	$responseText = "I could not communicate propertly with the backend";

	if (empty($data['parameters']['account_number'])) {
		$responseText = "Sorry, you didn’t provide a valid employee number, I was expecting something more like A9999";
	}

	$accountNumber = $data['parameters']['account_number'];

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

	$result = [];
	if (!empty($data['queryResult'])) {
		$result = $data['queryResult'];
	} elseif (!empty($data['result'])) {
		$result = $data['result'];
	}

	$responseText = "No action identified";
	if (!empty($result['action'])) {
		switch ($result['action']) {
		case 'unlock.account':
			$return = resetPwd($result);
			break;
		case 'holidays.left':
			$return = holidaysLeft($result);
			break;
		default:
			$return = responseDialog(sprintf("The action '%s' is not supported", $result['action']));
		}
	}

	return $response->withJson($return)
		->withHeader('Content-Type', 'application/json');
});

$app->run();