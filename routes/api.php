<?php

use app\Router;
use controllers\TestController;

$router = new Router();
$router->middleware(['user-access', 'admin-access'])->get('/test', [TestController::class, 'test']);
$router->handleRequest();