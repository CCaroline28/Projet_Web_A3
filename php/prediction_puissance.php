<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$action = $_GET["action"] ?? "";

function predirePuissanceAvecPython($nbrePdc, $typePrise, $gratuit, $latitude, $longitude, $implantation) {
    $scriptPath = __DIR__ . "/../scripts/predict_puissance.py";

    $commande = "python " . escapeshellarg($scriptPath) . " "
        . escapeshellarg($nbrePdc) . " "
        . escapeshellarg($typePrise) . " "
        . escapeshellarg($gratuit) . " "
        . escapeshellarg($latitude) . " "
        . escapeshellarg($longitude) . " "
        . escapeshellarg($implantation);

    $output = shell_exec($commande);

    if (!$output) {
        throw new Exception("Aucune réponse du script Python.");
    }

    $resultat = json_decode($output, true);

    if (!$resultat || !$resultat["success"]) {
        throw new Exception($resultat["message"] ?? "Erreur Python.");
    }

    return $resultat["puissance"];
}

try {
    if ($action === "predict") {
        $idPrise = $_GET["id_prise"] ?? null;

        if (!$idPrise) {
            echo json_encode([
                "success" => false,
                "message" => "ID prise manquant."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT
                p.id_prise,
                p.nbre_pdc,
                p.puissance_nominale,
                p.condition_acces,
                p.reservation,
                s.consolidated_latitude,
                s.consolidated_longitude,
                s.implantation_station,
                dt.type_de_prise,
                tp.type_de_paiement
            FROM prise p
            INNER JOIN station s ON p.id_station = s.id_station
            LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
            LEFT JOIN paye_avec pa ON p.id_prise = pa.id_prise
            LEFT JOIN type_paiement tp ON pa.type_de_paiement = tp.type_de_paiement
            WHERE p.id_prise = ?
            LIMIT 1
        ");

        $stmt->execute([$idPrise]);
        $prise = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prise) {
            throw new Exception("Point de charge introuvable.");
        }

        $typeMapping = [
            "Type EF" => "prise_type_ef",
            "Type 2" => "prise_type_2",
            "Combo CCS" => "prise_type_combo_ccs",
            "CHAdeMO" => "prise_type_chademo",
            "Autre" => "prise_type_autre"
        ];

        $typePrise = $typeMapping[$prise["type_de_prise"]] ?? "prise_type_autre";
        $gratuit = ($prise["type_de_paiement"] === "Gratuit") ? 1 : 0;

        $puissancePredite = predirePuissanceAvecPython(
            $prise["nbre_pdc"],
            $typePrise,
            $gratuit,
            $prise["consolidated_latitude"],
            $prise["consolidated_longitude"],
            $prise["implantation_station"]
        );

        echo json_encode([
            "success" => true,
            "id_prise" => $prise["id_prise"],
            "puissance_reelle" => $prise["puissance_nominale"],
            "puissance_predite" => $puissancePredite,
            "type_prise" => $prise["type_de_prise"],
            "implantation" => $prise["implantation_station"],
            "latitude" => $prise["consolidated_latitude"],
            "longitude" => $prise["consolidated_longitude"],
            "nbre_pdc" => $prise["nbre_pdc"]
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