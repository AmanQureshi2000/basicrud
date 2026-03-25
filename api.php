<?php
header("Content-Type: application/json");

// --- Simple .env Loader ---
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . "=" . trim($value));
    }
}
loadEnv(__DIR__ . '/.env');

// --- Database Connection Logic ---
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT');

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// --- API Routing Logic (formerly api.php) ---
$method = $_SERVER['REQUEST_METHOD'];
// Read JSON body for POST and PUT
$input = json_decode(file_get_contents('php://input'), true);
// Get ID from query string (?id=123)
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            echo json_encode($result ?: ["message" => "Item not found"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM items");
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        if (!empty($input['name'])) {
            $sql = "INSERT INTO items (name, description) VALUES (?, ?)";
            $pdo->prepare($sql)->execute([$input['name'], $input['description'] ?? '']);
            http_response_code(201);
            echo json_encode(["message" => "Item created successfully"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Name is required"]);
        }
        break;

    case 'PUT':
        if ($id && !empty($input['name'])) {
            $sql = "UPDATE items SET name = ?, description = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['description'] ?? '', $id]);
            echo json_encode(["message" => "Item updated successfully"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID and Name are required for update"]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Item deleted successfully"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID is required for deletion"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}
?>