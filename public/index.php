<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;
use Slim\Middleware\BodyParsingMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$supabaseUrl = getenv('SUPABASE_URL');
$supabaseKey = getenv('SUPABASE_ANON_KEY');

$service = new PHPSupabase\Service(
    $supabaseKey, 
    $supabaseUrl
);

$db = $service->initializeDatabase('test_todo', 'id');

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->post('/todos', function (Request $request, Response $response, $args) use ($db) {
    $input = $request->getParsedBody();
    $title = $input['title'] ?? null;
    $completed = isset($input['completed']) ? (bool)$input['completed'] : false;

    if (empty($title)) {
        $response->getBody()->write(json_encode(['error' => 'Title cannot be empty']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $insertResult = $db->insert([
        'title' => $title,
        'completed' => $completed,
    ]);
    
    if ($insertResult->error()) {
        $response->getBody()->write(json_encode(['error' => 'Failed to create todo item']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    
    $newTodo = $insertResult->data()[0];
    $response->getBody()->write(json_encode($newTodo));
    return $response->withHeader('Content-Type', 'application/json');    

});

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->run();
