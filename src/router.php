<?php
use Slight\router\Router;
use src\controller\UserController;

Router::get('user', UserController::class, 'init', [
	'TEST_RULE'
]);

Router::post('user', UserController::class, 'insert');
Router::post('user/put/session', UserController::class, 'putOnSession');

Router::put('user/:id/:name', UserController::class, 'update');