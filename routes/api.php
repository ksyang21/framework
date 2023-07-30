<?php

use controllers\TestController;
use routes\Router;

$router = new Router();
$router->middleware(['user-access', 'admin-access'])->get('/test', [TestController::class, 'test']);
$router->handleRequest();