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

    'ajouter' => function() use ($db) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Méthode non autorisée'];
        }

        $postData = json_decode(file_get_contents('php://input'), true);

        // Modification ici pour récupérer l'erreur depuis la fonction
        try {
            $result = dbAjoutPrise($db, $postData);
            if ($result === true) {
                return ['success' => true];
            } else {
                return ['error' => $result]; // Renvoie le message d'erreur réel
            }
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    },

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
    'prediction/all' => function() use ($db) {
        $points = dbGetAllPrisesForClustering($db);
        foreach ($points as &$row) {
            $row["cluster"] = clusterRapidePourCarte($row["id_prise"]);
        }
        return ["success" => true, "points" => $points];
    },

    // Route pour la prédiction unitaire (utilise le vrai modèle Python/KMeans)
    'prediction/point' => function() use ($db) {
        $longitude = $_GET["longitude"] ?? null;
        $latitude  = $_GET["latitude"]  ?? null;

        if ($longitude === null || $latitude === null || $longitude === '' || $latitude === '') {
            return ["success" => false, "message" => "Coordonnées manquantes."];
        }

        try {
            $cluster   = predireClusterAvecPython($longitude, $latitude);
            $confiance = calculerConfiance($cluster);
            $stats     = calculerStatsClusterRapide($db, $cluster);

            return [
                "success"           => true,
                "cluster"           => $cluster,
                "confiance"         => $confiance,
                "puissance_moyenne" => $stats["puissance_moyenne"],
                "nombre_points"     => $stats["nombre_points"],
                "nombre_stations"   => $stats["nombre_stations"],
                "part_points"       => $stats["part_points"],
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    },
    // Route pour la prédiction unitaire basée sur un ID de prise (compatible avec votre JS)
'prediction/id' => function() use ($db) {
    $idPrise = $_GET["id_prise"] ?? null;

    if (!$idPrise) {
        return ["success" => false, "message" => "ID de prise manquant."];
    }

    // 1. Récupérer les coordonnées et les infos de la prise en base
    $stmt = $db->prepare("
        SELECT s.consolidated_latitude, s.consolidated_longitude, 
               p.nbre_pdc, p.puissance_nominale, p.condition_acces, 
               dt.type_de_prise, s.implantation_station
        FROM prise p
        JOIN station s ON p.id_station = s.id_station
        LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
        WHERE p.id_prise = ? LIMIT 1
    ");
    $stmt->execute([$idPrise]);
    $prise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prise) {
        return ["success" => false, "message" => "Prise introuvable."];
    }

    // 2. Appeler le modèle Python (ici on utilise les coordonnées récupérées)
    try {
        $puissancePredite = predirePuissanceAvecPython(
            $prise['nbre_pdc'], 
            $prise['type_de_prise'], 
            0, // gratuit (à adapter si besoin)
            $prise['consolidated_latitude'], 
            $prise['consolidated_longitude'], 
            $prise['implantation_station']
        );

        return [
            "success" => true,
            "id_prise" => $idPrise,
            "puissance_reelle" => $prise["puissance_nominale"],
            "puissance_predite" => $puissancePredite,
            "type_prise" => $prise["type_de_prise"],
            "implantation" => $prise["implantation_station"],
            "latitude" => $prise["consolidated_latitude"],
            "longitude" => $prise["consolidated_longitude"],
            "nbre_pdc" => $prise["nbre_pdc"]
        ];
    } catch (Exception $e) {
        return ["success" => false, "message" => $e->getMessage()];
    }
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
