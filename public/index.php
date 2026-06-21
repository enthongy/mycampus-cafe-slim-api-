<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/security.php';

// ---------- CREATE SLIM APP ----------
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// ---------- GLOBAL CORS MIDDLEWARE (Handles all responses, including OPTIONS) ----------
$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
});

// ---------- HELPER ----------
function jsonResponse(Response $response, $data, int $status = 200): Response
{
    $payload = json_encode($data, JSON_PRETTY_PRINT);
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

// ---------- ROOT ----------
$app->get('/', function (Request $request, Response $response) {
    return jsonResponse($response, ['message' => 'MyCampus Cafe API Running']);
});

// ---------- PUBLIC ROUTES ----------
$app->get('/api/menu', function (Request $request, Response $response) {
    try {
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->query("SELECT * FROM menu");
        $menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return jsonResponse($response, $menu);
    } catch (PDOException $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
});

$app->get('/api/menu/{id}', function (Request $request, Response $response, array $args) {
    try {
        $id = $args['id'];
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT * FROM menu WHERE menu_id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            return jsonResponse($response, ['message' => 'Menu item not found'], 404);
        }
        return jsonResponse($response, $item);
    } catch (PDOException $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
});

// ---------- LOGIN ----------
$app->post('/api/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    if (empty($data['username']) || empty($data['password'])) {
        return jsonResponse($response, [
            'status' => 'fail',
            'message' => 'Username and password are required'
        ], 400);
    }

    try {
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT staff_id, username, bcrypt_password, role FROM staff WHERE username = :username");
        $stmt->execute([':username' => $data['username']]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff || !bcryptVerify($data['password'], $staff['bcrypt_password'])) {
            return jsonResponse($response, [
                'status' => 'fail',
                'message' => 'Invalid username or password'
            ], 401);
        }

        $token = createJwtToken($staff);
        return jsonResponse($response, [
            'status' => 'success',
            'token' => $token,
            'staff' => [
                'staff_id' => $staff['staff_id'],
                'username' => $staff['username'],
                'role' => $staff['role']
            ]
        ], 200);
    } catch (PDOException $e) {
        return jsonResponse($response, [
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// ---------- JWT MIDDLEWARE ----------
$jwtMiddleware = function (Request $request, RequestHandler $handler) {
    $authHeader = $request->getHeaderLine('Authorization');
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $response = new \Slim\Psr7\Response();
        return jsonResponse($response, [
            'status' => 'unauthorized',
            'message' => 'Bearer token is required'
        ], 401);
    }

    try {
        $token = $matches[1];
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALG));
        $request = $request->withAttribute('staff', $decoded);
        return $handler->handle($request);
    } catch (Exception $e) {
        $response = new \Slim\Psr7\Response();
        return jsonResponse($response, [
            'status' => 'unauthorized',
            'message' => 'Invalid or expired token'
        ], 401);
    }
};

// ---------- PROTECTED ROUTES ----------
$app->post('/api/menu', function (Request $request, Response $response) {
    try {
        $data = $request->getParsedBody();
        $menu_name = $data['menu_name'] ?? '';
        $category = $data['category'] ?? '';
        $price = $data['price'] ?? 0;
        $availability = $data['availability'] ?? 'Available';

        $db = new DB();
        $conn = $db->connect();
        $sql = "INSERT INTO menu (menu_name, category, price, availability) 
                VALUES (:menu_name, :category, :price, :availability)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':menu_name' => $menu_name,
            ':category' => $category,
            ':price' => $price,
            ':availability' => $availability
        ]);
        return jsonResponse($response, ['message' => 'Menu added successfully'], 201);
    } catch (PDOException $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
})->add($jwtMiddleware);

$app->put('/api/menu/{id}', function (Request $request, Response $response, array $args) {
    try {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $menu_name = $data['menu_name'] ?? '';
        $category = $data['category'] ?? '';
        $price = $data['price'] ?? 0;
        $availability = $data['availability'] ?? 'Available';

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
        return jsonResponse($response, ['message' => 'Menu updated successfully']);
    } catch (PDOException $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
})->add($jwtMiddleware);

$app->delete('/api/menu/{id}', function (Request $request, Response $response, array $args) {
    try {
        $id = $args['id'];
        $db = new DB();
        $conn = $db->connect();
        $sql = "DELETE FROM menu WHERE menu_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return jsonResponse($response, ['message' => 'Menu deleted successfully']);
    } catch (PDOException $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
})->add($jwtMiddleware);

// ---------- DEBUG ROUTE (optional) ----------
$app->get('/test-auth', function (Request $request, Response $response) {
    $authHeader = $request->getHeaderLine('Authorization');
    return jsonResponse($response, [
        'received_authorization' => $authHeader,
        'all_headers' => $request->getHeaders()
    ]);
});

// ---------- CATCH-ALL (404) – Exclude OPTIONS to avoid duplicate ----------
$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function (Request $request, Response $response) {
    return jsonResponse($response, ['error' => 'Route not found'], 404);
});

// ---------- RUN ----------
$app->run();
