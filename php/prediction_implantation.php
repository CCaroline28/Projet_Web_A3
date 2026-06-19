<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$action = $_GET["action"] ?? "";

function predireImplantationAvecPython($prise) {
    $python = "C:\\Users\\ayael\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe";
    $script = realpath(__DIR__ . "/../scripts/predict_implantation.py");

    $puissance = floatval($prise["puissance_nominale"]);
    $puissanceRapide = $puissance > 50 ? 1 : 0;
    $nbrePdc = intval($prise["nbre_pdc"]);
    $combo = intval($prise["combo_ccs"]);
    $chademo = intval($prise["chademo"]);
    $estPayant = ($prise["type_de_paiement"] === "Gratuit") ? 0 : 1;
    $latitude = floatval($prise["consolidated_latitude"]);
    $longitude = floatval($prise["consolidated_longitude"]);

    $commande =
        escapeshellarg($python) . " " .
        escapeshellarg($script) . " " .
        escapeshellarg($puissance) . " " .
        escapeshellarg($puissanceRapide) . " " .
        escapeshellarg($nbrePdc) . " " .
        escapeshellarg($combo) . " " .
        escapeshellarg($chademo) . " " .
        escapeshellarg($estPayant) . " " .
        escapeshellarg($latitude) . " " .
        escapeshellarg($longitude);

    exec($commande . " 2>&1", $output, $code);

    $json = implode("", $output);
    $result = json_decode($json, true);

    if (!$result) {
        throw new Exception("Réponse Python invalide : " . $json);
    }

    if (!$result["success"]) {
        throw new Exception($result["message"]);
    }

    return $result;
}
try {
    if ($action !== "predict") {
        echo json_encode([
            "success" => false,
            "message" => "Action inconnue."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idPrise = $_GET["id_prise"] ?? null;

    if (!$idPrise) {
        throw new Exception("ID prise manquant.");
    }

    $stmt = $pdo->prepare("
        SELECT
            p.id_prise,
            p.nbre_pdc,
            p.puissance_nominale,
            s.nom_station,
            s.consolidated_latitude,
            s.consolidated_longitude,
            s.implantation_station AS implantation_reelle,

            MAX(CASE WHEN dt.type_de_prise = 'Combo CCS' THEN 1 ELSE 0 END) AS combo_ccs,
            MAX(CASE WHEN dt.type_de_prise = 'CHAdeMO' THEN 1 ELSE 0 END) AS chademo,
            MAX(tp.type_de_paiement) AS type_de_paiement

        FROM prise p
        INNER JOIN station s ON p.id_station = s.id_station
        LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
        LEFT JOIN paye_avec pa ON p.id_prise = pa.id_prise
        LEFT JOIN type_paiement tp ON pa.type_de_paiement = tp.type_de_paiement
        WHERE p.id_prise = ?
        GROUP BY p.id_prise
        LIMIT 1
    ");

    $stmt->execute([$idPrise]);
    $prise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prise) {
        throw new Exception("Point de charge introuvable.");
    }

    $prediction = predireImplantationAvecPython($prise);

    echo json_encode([
        "success" => true,
        "id_prise" => $prise["id_prise"],
        "nom_station" => $prise["nom_station"],
        "implantation_predite" => $prediction["implantation"],
        "implantation_reelle" => $prise["implantation_reelle"],
        "confiance" => $prediction["confiance"],
        "puissance_nominale" => $prise["puissance_nominale"],
        "nbre_pdc" => $prise["nbre_pdc"],
        "latitude" => $prise["consolidated_latitude"],
        "longitude" => $prise["consolidated_longitude"],
        "est_payant" => ($prise["type_de_paiement"] === "Gratuit") ? "Non" : "Oui"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur : " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
