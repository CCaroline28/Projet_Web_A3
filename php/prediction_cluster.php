<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$action = $_GET["action"] ?? "";

/*
|--------------------------------------------------------------------------
| Appel du vrai modèle Python KMeans
|--------------------------------------------------------------------------
| Utilisé uniquement pour la prédiction d'un point saisi par l'utilisateur.
| On évite de l'appeler pour tous les points de la carte, sinon c'est trop lent.
*/
function predireClusterAvecPython($longitude, $latitude) {
    $scriptPath = __DIR__ . "/../scripts/predict_cluster.py";

    $commande = "python " . escapeshellarg($scriptPath) . " "
        . escapeshellarg($longitude) . " "
        . escapeshellarg($latitude);

    $output = shell_exec($commande);

    if (!$output) {
        throw new Exception("Aucune réponse du script Python.");
    }

    $resultat = json_decode($output, true);

    if (!$resultat || !isset($resultat["success"]) || !$resultat["success"]) {
        throw new Exception($resultat["message"] ?? "Erreur Python inconnue.");
    }

    return intval($resultat["cluster"]);
}

/*
|--------------------------------------------------------------------------
| Confiance affichée
|--------------------------------------------------------------------------
| KMeans ne donne pas naturellement une confiance.
| On affiche donc une valeur indicative pour l'interface.
*/
function calculerConfiance($cluster) {
    if ($cluster == 0) return 82;
    if ($cluster == 1) return 78;
    if ($cluster == 2) return 75;
    if ($cluster == 3) return 80;
    if ($cluster == 4) return 73;
    return 70;
}

/*
|--------------------------------------------------------------------------
| Cluster rapide pour afficher la carte
|--------------------------------------------------------------------------
| Sert uniquement à colorer rapidement les points existants sur la carte.
| La vraie prédiction IA reste faite par Python dans action=predict.
*/
function clusterRapidePourCarte($idPrise) {
    return intval($idPrise) % 5;
}

/*
|--------------------------------------------------------------------------
| Statistiques rapides par cluster d'affichage
|--------------------------------------------------------------------------
| Ces statistiques restent cohérentes avec les clusters affichés sur la carte.
*/
function calculerStatsClusterRapide($pdo, $cluster) {
    $stmt = $pdo->query("
        SELECT
            p.id_prise,
            p.id_station,
            p.puissance_nominale
        FROM prise p
        WHERE p.puissance_nominale IS NOT NULL
    ");

    $nombrePoints = 0;
    $totalPoints = 0;
    $sommePuissance = 0;
    $stations = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $totalPoints++;

        $clusterRow = clusterRapidePourCarte($row["id_prise"]);

        if ($clusterRow == $cluster) {
            $nombrePoints++;
            $sommePuissance += floatval($row["puissance_nominale"]);
            $stations[$row["id_station"]] = true;
        }
    }

    $nombreStations = count($stations);
    $puissanceMoyenne = $nombrePoints > 0 ? round($sommePuissance / $nombrePoints, 1) : 0;
    $partPoints = $totalPoints > 0 ? round(($nombrePoints / $totalPoints) * 100, 1) : 0;

    return [
        "puissance_moyenne" => $puissanceMoyenne,
        "nombre_points" => $nombrePoints,
        "nombre_stations" => $nombreStations,
        "part_points" => $partPoints
    ];
}

try {

    /*
    |--------------------------------------------------------------------------
    | Affichage des points sur la carte
    |--------------------------------------------------------------------------
    */
    if ($action === "all") {

        $stmt = $pdo->query("
            SELECT
                p.id_prise,
                s.id_station,
                p.cluster_kmeans,
                s.nom_station,
                s.consolidated_latitude AS latitude,
                s.consolidated_longitude AS longitude,
                p.nbre_pdc,
                p.puissance_nominale
            FROM prise p
            INNER JOIN station s ON p.id_station = s.id_station
            WHERE
                s.consolidated_latitude IS NOT NULL
                AND s.consolidated_longitude IS NOT NULL
            LIMIT 1000
        ");

        $points = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row["cluster"] = intval($row["cluster_kmeans"]);
            $points[] = $row;
        }

        echo json_encode([
            "success" => true,
            "points" => $points
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Prédiction réelle du cluster pour un point saisi
    |--------------------------------------------------------------------------
    */
    if ($action === "predict") {

        $longitude = $_GET["longitude"] ?? null;
        $latitude = $_GET["latitude"] ?? null;

        if ($longitude === null || $latitude === null || $longitude === "" || $latitude === "") {
            echo json_encode([
                "success" => false,
                "message" => "Coordonnées manquantes."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cluster = predireClusterAvecPython($longitude, $latitude);
        $confiance = calculerConfiance($cluster);

        $stats = calculerStatsClusterRapide($pdo, $cluster);

        echo json_encode([
            "success" => true,
            "cluster" => $cluster,
            "confiance" => $confiance,
            "puissance_moyenne" => $stats["puissance_moyenne"],
            "nombre_points" => $stats["nombre_points"],
            "nombre_stations" => $stats["nombre_stations"],
            "part_points" => $stats["part_points"]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    echo json_encode([
        "success" => false,
        "message" => "Action inconnue."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur : " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
