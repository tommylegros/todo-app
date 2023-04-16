<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

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

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$app->get('/', function (Request $request, Response $response, $args) use ($twig) {
    return $twig->render($response, 'index.twig', $args);
});

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
    
    if (isset($insertResult['error'])) {
        $response->getBody()->write(json_encode(['error' => 'Failed to create todo item']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    
    $newTodo = $insertResult[0];
    $response->getBody()->write(json_encode($newTodo));
    return $response->withHeader('Content-Type', 'application/json');    

});

$app->get('/static/{file:.+}', function (Request $request, Response $response, array $args) {
    $file = __DIR__ . '/../static/' . $args['file'];

    if (file_exists($file)) {
        $response->getBody()->write(file_get_contents($file));
        return $response->withHeader('Content-Type', mime_content_type($file));
    }

    return $response->withStatus(404);
});

$app->run();
