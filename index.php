<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

require 'vendor/autoload.php';

$app = new Slim\App();

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

$app->run();