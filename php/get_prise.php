<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

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
        DATE_FORMAT(p.date_mise_en_service, '%Y-%m-%d') AS date_mise_en_service,

        s.nom_station,
        s.consolidated_longitude,
        s.consolidated_latitude,
        s.implantation_station,

        l.consolidated_code_postal
    FROM prise p
    INNER JOIN station s
        ON p.id_station = s.id_station
    INNER JOIN Localisation l
        ON p.consolidated_code_postal = l.consolidated_code_postal
    WHERE p.id_prise = ?
");

$stmt->execute([$idPrise]);
$prise = $stmt->fetch();

if (!$prise) {
    echo json_encode([
        "success" => false,
        "message" => "Point introuvable."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    "success" => true,
    "prise" => $prise
], JSON_UNESCAPED_UNICODE);