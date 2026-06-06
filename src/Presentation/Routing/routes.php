<?php

use App\Presentation\Controller\ThreadController;
use App\Presentation\Controller\PlaygroundController;
use App\Presentation\Routing\Router;

/**  @var Router $router */
$router->get('/thread', [ThreadController::class, 'index']);
$router->get('/playground', [PlaygroundController::class, 'index']);
