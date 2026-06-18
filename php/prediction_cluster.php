<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$action = $_GET["action"] ?? "";

/*
    Version simple :
    Ici on simule la prédiction du cluster.
    Plus tard, tu pourras remplacer la fonction calculerCluster()
    par un appel à ton script Python avec exec().
*/

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

try {

    if ($action === "all") {

        $stmt = $pdo->query("
            SELECT
                p.id_prise,
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
            LIMIT 300
        ");

        $points = [];

        foreach ($stmt->fetchAll() as $row) {
            $cluster = calculerCluster(
                $row["longitude"],
                $row["latitude"]
            );

            $row["cluster"] = $cluster;
            $points[] = $row;
        }

        echo json_encode([
            "success" => true,
            "points" => $points
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($action === "predict") {
        $longitude = $_GET["longitude"] ?? null;
        $latitude = $_GET["latitude"] ?? null;

        if (!$longitude || !$latitude) {
            echo json_encode([
                "success" => false,
                "message" => "Coordonnées manquantes."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cluster = calculerCluster($longitude, $latitude);

        echo json_encode([
            "success" => true,
            "cluster" => $cluster,
            "confiance" => calculerConfiance($cluster)
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