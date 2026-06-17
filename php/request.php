<?php
require_once 'database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function sendJsonData($data, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

$db = dbConnect();
if (!$db) sendJsonData(['error' => 'Erreur de connexion à la base'], 500);

// Définition UNIQUE du tableau des routes
$routes = [
    'stats/stations'          => function() use ($db) { return dbCountStations($db); },
    'stats/points-charge'     => function() use ($db) { return dbCountPointsCharge($db); },
    'stats/top-departement'   => function() use ($db) { return dbTopDepartement($db); },
    
    'visu/prises' => function() use ($db) {
        $dep   = $_GET['dep'] ?? '';
        $types = isset($_GET['types']) && $_GET['types'] !== '' ? explode(',', $_GET['types']) : [];
        
        $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(200, max(1, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;

        return [
            'data'  => dbGetPrises($db, $dep, $types, $limit, $offset),
            'page'  => $page,
            'limit' => $limit
        ];
    },

    'installation' => function() use ($db) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ['error' => 'Méthode non autorisée'];
        
        // On insère les données reçues via POST
        $success = dbInsertInstallation($db, $_POST);
        return $success ? ['success' => true] : ['error' => 'Erreur lors de l\'enregistrement'];
    },
];

// Récupération de l'URI
$uri = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $uri = trim($_SERVER['PATH_INFO'], " /");
} else {
    $uri = trim($_GET['route'] ?? '', " /");
}

// Exécution de la route
if (isset($routes[$uri])) {
    try {
        $result = $routes[$uri]();
        if ($result === false) {
            sendJsonData(['error' => 'Erreur lors de la récupération des données'], 500);
        } else {
            sendJsonData($result);
        }
    } catch (PDOException $e) {
        sendJsonData(['error' => 'Erreur lors de la requête : ' . $e->getMessage()], 500);
    }
} else {
    sendJsonData(['error' => 'Ressource non trouvée : ' . $uri], 404);
}
?>