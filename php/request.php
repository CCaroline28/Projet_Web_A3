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

$routes = [
    'stats/stations'         => function() use ($db) { return dbCountStations($db); },
    'stats/points-charge'    => function() use ($db) { return dbCountPointsCharge($db); },
    'stats/top-departement'  => function() use ($db) { return dbTopDepartement($db); },
    'departements'           => function() use ($db) { return dbGetAllDepartements($db); },
    'visu/points' => function() use ($db) {
        $dep = $_GET['dep'] ?? '';
        $types = isset($_GET['types']) && $_GET['types'] !== '' ? explode(',', $_GET['types']) : [];
        
        // Appelez la fonction qui récupère les points de la base de données
        // Assurez-vous que cette fonction existe dans votre fichier database.php
        return dbGetPointsCarte($db, $dep, $types);
    },
    'statistique/rep_implantation' => function() use ($db) {
        $dep = $_GET['departement'] ?? '';
        return dbCountimplantation($db, $dep);
    },
    'statistique/rep_type' => function() use ($db) {
        $dep = $_GET['departement'] ?? '';
        return dbCounttypeprise($db, $dep);
    },
    'statistique/rep_puissance' => function() use ($db) {
        $dep = $_GET['departement'] ?? '';
        return dbCountpuissance($db, $dep);
    },
    'statistique/nb_point_charge' => function() use ($db) {
        $dep = $_GET['departement'] ?? '';
        return dbCountnbpoints($db, $dep);
    },

    'visu/prises' => function() use ($db) {
        $dep   = $_GET['dep'] ?? '';
        $types = isset($_GET['types']) && $_GET['types'] !== ''
            ? explode(',', $_GET['types'])
            : [];
        $page   = isset($_GET['page'])  ? max(1,   (int)$_GET['page'])          : 1;
        $limit  = isset($_GET['limit']) ? min(200, max(1, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;

        return [
            'data'  => dbGetPrises($db, $dep, $types, $limit, $offset),
            'total' => dbCountPrises($db, $dep, $types),
            'page'  => $page,
            'limit' => $limit,
        ];
    },

    #Fonction pour ajouter une prise à la BDD
   'ajouter' => function() use ($db) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Méthode non autorisée'];
        }

        $input = json_decode(file_get_contents('php://input'), true); //lit le JSON envoyé
        $result = dbAjoutPrise($db, $input);


        echo json_encode(
        $result
            ? ['success' => true,  'message' => 'Prise ajoutée avec succès']
            : ['success' => false, 'message' => 'Erreur lors de l\'ajout de la prise']
    );
    exit; 
    },

#version pour quand ça bug
// 'ajouter' => function() use ($db) {
//     $input = json_decode(file_get_contents('php://input'), true);
//     $result = dbAjoutPrise($db, $input);
    
//     ob_clean();
//     if ($result === true) {
//         echo json_encode(['success' => true, 'message' => 'Prise ajoutée avec succès']);
//     } elseif (is_array($result) && isset($result['error'])) {
//         echo json_encode(['success' => false, 'message' => $result['error']]); // ← message exact
//     } else {
//         echo json_encode(['success' => false, 'message' => "Erreur lors de l'ajout"]);
//     }
//     exit;
// },

        
    // Modification ici pour récupérer l'erreur depuis la fonction
    // try {
    //     $result = dbInsertInstallation($db, $postData);
    //     if ($result === true) {
    //         return ['success' => true];
    //     } else {
    //         return ['error' => $result]; // Renvoie le message d'erreur réel
    //     }
    // } catch (Exception $e) {
    //     return ['error' => $e->getMessage()];
    // },

    // Route pour récupérer une prise (utilisée par modification.js)
    'prise/get' => function() use ($db) {
        $idPrise = $_GET['id_prise'] ?? null;
        if (!$idPrise) return ['success' => false, 'message' => 'ID prise manquant.'];
        $prise = dbGetPrise($db, $idPrise);
        if (!$prise) return ['success' => false, 'message' => 'Prise introuvable.'];
        return ['success' => true, 'prise' => $prise];
    },

    // Route pour modifier une prise
    'prise/modifier' => function() use ($db) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['error' => 'Méthode non autorisée'];
        }
        $result = dbModifierPrise($db, $_POST);
        return $result
            ? ['success' => true,  'message' => 'Modification réussie.']
            : ['success' => false, 'message' => 'Erreur lors de la modification.'];
    },

    // Route pour supprimer une prise
    'prise/supprimer' => function() use ($db) {
        $idPrise = $_GET['id_prise'] ?? null;
        if (!$idPrise) return ['success' => false, 'message' => 'ID prise manquant.'];
        $result = dbSupprimerPrise($db, $idPrise);
        return $result
            ? ['success' => true,  'message' => 'Point de charge supprimé.']
            : ['success' => false, 'message' => 'Erreur lors de la suppression.'];
    },
    // Route pour charger les points pour la carte cluster
    'predict/all' => function() use ($db) {
        $points = dbGetAllPrisesForClustering($db);
        foreach ($points as &$row) {
            $row["cluster"] = calculerCluster($row["longitude"], $row["latitude"]);
        }
        return ["success" => true, "points" => $points];
    },

    // Route pour la prédiction unitaire
    'predict/point' => function() use ($db) {
        $longitude = $_GET["longitude"] ?? null;
        $latitude = $_GET["latitude"] ?? null;
        if (!$longitude || !$latitude) return ["success" => false, "message" => "Coordonnées manquantes."];
        
        $cluster = calculerCluster($longitude, $latitude);
        return [
            "success" => true,
            "cluster" => $cluster,
            "confiance" => calculerConfiance($cluster)
        ];
    },
];

// Routage
$uri = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $uri = trim($_SERVER['PATH_INFO'], " /");
} else {
    $uri = trim($_GET['route'] ?? '', " /");
}

if (isset($routes[$uri])) {
    try {
        $result = $routes[$uri]();
        sendJsonData($result === false ? ['error' => 'Erreur serveur'] : $result,
                     $result === false ? 500 : 200);
    } catch (PDOException $e) {
        sendJsonData(['error' => 'Erreur : ' . $e->getMessage()], 500);
    }
} else {
    sendJsonData(['error' => 'Ressource non trouvée'], 404);
}
function calculerCluster($longitude, $latitude) {
    $longitude = floatval($longitude);
    $latitude = floatval($latitude);

    if ($latitude > 48.5) {
        return 2;
    }

    if ($longitude < 0) {
        return 1;
    }

    if ($longitude > 4) {
        return 3;
    }

    return 4;
}

function calculerConfiance($cluster) {
   if ($cluster == 1) return 78;
    if ($cluster == 2) return 82;
    if ($cluster == 3) return 75;
    return 69;
}