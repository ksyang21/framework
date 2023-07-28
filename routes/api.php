<?php

use controllers\TestController;
use routes\Router;

$router = new Router();
$router->get('/test', [TestController::class, 'test']);
$router->handleRequest();

//->middleware(['user-access', 'admin-access'])