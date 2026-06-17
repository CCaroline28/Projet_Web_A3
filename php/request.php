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




  ############################################################## STATISTIQUE ##############################################################
  #Renvoie un tableau contenant l'id de la station et le type de ses prises (séparé par une virgule)
function dbCounttypeprise($db,$dep){
    try {
        $request = "SELECT id_station,    GROUP_CONCAT(DISTINCT type_de_prise ORDER BY type_de_prise SEPARATOR ', ') AS types_de_prise
FROM (
    SELECT  prise.id_station, prise.id_prise, de_type.type_de_prise, prise.consolidated_code_postal
	FROM prise
	INNER JOIN de_type ON prise.id_prise = de_type.id_prise
    WHERE prise.consolidated_code_postal LIKE :dep
	ORDER BY prise.id_station, prise.id_prise
) AS sub
GROUP BY id_station;";
        $statement = $db->prepare($request);
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}


#Renvoie un tableau contenant le nombre du type de station et son total dans la base
function dbCountimplantation($db,$dep){
    try {
        $request = "SELECT station.implantation_station,
       COUNT(*) AS total
FROM station
INNER JOIN prise ON station.id_station = prise.id_station
WHERE prise.consolidated_code_postal LIKE :dep
GROUP BY station.implantation_station;
";
        $statement = $db->prepare($request);
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}

#Renvoie un tableau contenant le nb de point de charge  et son total dans la base
# il nous faut un graphe avec en absisse les nb de points de charges et en ordonnée le total
function dbCountimplantation($db, $dep){
    try {
        $request = "SELECT nb_pdc, COUNT(*) AS total_de_pdc
FROM (
    SELECT id_station, MAX(nbre_pdc) AS nb_pdc, COUNT(*) AS total_prise  # ça renvoie un tableau avec l'id de la statino et le nb de pdc 
    FROM prise
    WHERE consolidated_code_postal LIKE :dep'
    GROUP BY id_station
) AS sub
GROUP BY nb_pdc
";
        $statement = $db->prepare($request);
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}

#Renvoie un tableau contenant l'id de la station et la puissance de ses prises (séparé par une virgule)
function dbCountpuissance($db,$dep){
    try {
        $request = "SELECT implantation_station, GROUP_CONCAT(DISTINCT puissance_nominale ORDER BY puissance_nominale SEPARATOR ', ') AS puissances
FROM (
    SELECT  puissance_nominale, id_prise, id_station, consolidated_code_postal
	FROM prise
) AS sub
GROUP BY id_station
WHERE consolidated_code_postal LIKE :dep;";
        $statement = $db->prepare($request);
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}


?>
