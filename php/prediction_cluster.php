<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$action = $_GET["action"] ?? "";

/* Cluster provisoire selon coordonnées */
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

    /* =========================
       AFFICHAGE DES POINTS
    ========================= */
    if ($action === "all") {

        $stmt = $pdo->query("
            SELECT
                p.id_prise,
                s.id_station,
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
            LIMIT 3000
        ");

        $points = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row["cluster"] = calculerCluster(
                $row["longitude"],
                $row["latitude"]
            );

            $points[] = $row;
        }

        echo json_encode([
            "success" => true,
            "points" => $points
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /* =========================
       PRÉDICTION
    ========================= */
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

        $cluster = calculerCluster($longitude, $latitude);
        $confiance = calculerConfiance($cluster);

        $stmt = $pdo->query("
            SELECT
                p.id_prise,
                s.id_station,
                s.consolidated_longitude AS longitude,
                s.consolidated_latitude AS latitude,
                p.puissance_nominale
            FROM prise p
            INNER JOIN station s ON p.id_station = s.id_station
            WHERE
                s.consolidated_longitude IS NOT NULL
                AND s.consolidated_latitude IS NOT NULL
                AND p.puissance_nominale IS NOT NULL
        ");

        $nombrePoints = 0;
        $totalPoints = 0;
        $sommePuissance = 0;
        $stations = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totalPoints++;

            $clusterRow = calculerCluster(
                $row["longitude"],
                $row["latitude"]
            );

            if ($clusterRow == $cluster) {
                $nombrePoints++;
                $sommePuissance += floatval($row["puissance_nominale"]);
                $stations[$row["id_station"]] = true;
            }
        }

        $nombreStations = count($stations);
        $puissanceMoyenne = $nombrePoints > 0 ? round($sommePuissance / $nombrePoints, 1) : 0;
        $partPoints = $totalPoints > 0 ? round(($nombrePoints / $totalPoints) * 100, 1) : 0;

        echo json_encode([
            "success" => true,
            "cluster" => $cluster,
            "confiance" => $confiance,
            "puissance_moyenne" => $puissanceMoyenne,
            "nombre_points" => $nombrePoints,
            "nombre_stations" => $nombreStations,
            "part_points" => $partPoints
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