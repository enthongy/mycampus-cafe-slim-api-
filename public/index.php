<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db.php';

$app = AppFactory::create();

$app->setBasePath('/mycampus-cafe-slim-api/public');

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, true, true);

$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

$app->add(function (Request $request, RequestHandler $handler): Response {

    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

function jsonResponse(Response $response, $data, int $status = 200): Response {

    $payload = json_encode($data, JSON_PRETTY_PRINT);

    $response->getBody()->write($payload);

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

$app->get('/', function (Request $request, Response $response) {

    $data = [
        "message" => "MyCampus Cafe Slim API is running"
    ];

    return jsonResponse($response, $data);
});

$app->get('/api/menu', function (Request $request, Response $response) {

    try {

        $db = new DB();
        $conn = $db->connect();

        $sql = "SELECT * FROM menu";

        $stmt = $conn->query($sql);

        $menu = $stmt->fetchAll();

        return jsonResponse($response, $menu);

    } catch (PDOException $e) {

        return jsonResponse($response, [
            "error" => $e->getMessage()
        ], 500);
    }
});

$app->get('/api/menu/{id}', function (Request $request, Response $response, array $args) {

    try {

        $id = $args['id'];

        $db = new DB();
        $conn = $db->connect();

        $sql = "SELECT * FROM menu WHERE menu_id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        $menu = $stmt->fetch();

        if (!$menu) {
            return jsonResponse($response, [
                "message" => "Menu not found"
            ], 404);
        }

        return jsonResponse($response, $menu);

    } catch (PDOException $e) {

        return jsonResponse($response, [
            "error" => $e->getMessage()
        ], 500);
    }
});

$app->post('/api/menu', function (Request $request, Response $response) {

    try {

        $data = $request->getParsedBody();

        $menu_name = $data['menu_name'];
        $category = $data['category'];
        $price = $data['price'];
        $availability = $data['availability'];

        $db = new DB();
        $conn = $db->connect();

        $sql = "INSERT INTO menu 
        (menu_name, category, price, availability)
        VALUES
        (:menu_name, :category, :price, :availability)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':menu_name' => $menu_name,
            ':category' => $category,
            ':price' => $price,
            ':availability' => $availability
        ]);

        return jsonResponse($response, [
            "message" => "Menu added successfully"
        ], 201);

    } catch (PDOException $e) {

        return jsonResponse($response, [
            "error" => $e->getMessage()
        ], 500);
    }
});

$app->delete('/api/menu/{id}', function (Request $request, Response $response, array $args) {

    try {

        $id = $args['id'];

        $db = new DB();
        $conn = $db->connect();

        $sql = "DELETE FROM menu WHERE menu_id = :id";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return jsonResponse($response, [
            "message" => "Menu deleted successfully"
        ]);

    } catch (PDOException $e) {

        return jsonResponse($response, [
            "error" => $e->getMessage()
        ], 500);
    }
});

$app->put('/api/menu/{id}', function (Request $request, Response $response, array $args) {

    try {

        $id = $args['id'];

        $data = $request->getParsedBody();

        $menu_name = $data['menu_name'];
        $category = $data['category'];
        $price = $data['price'];
        $availability = $data['availability'];

        $db = new DB();
        $conn = $db->connect();

        $sql = "UPDATE menu SET
                    menu_name = :menu_name,
                    category = :category,
                    price = :price,
                    availability = :availability
                WHERE menu_id = :id";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':menu_name' => $menu_name,
            ':category' => $category,
            ':price' => $price,
            ':availability' => $availability,
            ':id' => $id
        ]);

        return jsonResponse($response, [
            "message" => "Menu updated successfully"
        ]);

    } catch (PDOException $e) {

        return jsonResponse($response, [
            "error" => $e->getMessage()
        ], 500);
    }
});

$app->run();