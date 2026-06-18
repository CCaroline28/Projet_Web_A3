<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$idPrise = $_POST["id_prise"] ?? null;

$nbrePdc = $_POST["nbre_pdc"] ?? null;
$puissance = $_POST["puissance_nominale"] ?? null;
$conditionAcces = $_POST["condition_acces"] ?? null;
$reservation = $_POST["reservation"] ?? null;
$dateMiseEnService = $_POST["date_mise_en_service"] ?? null;

$nomStation = $_POST["nom_station"] ?? null;
$longitude = $_POST["longitude"] ?? null;
$latitude = $_POST["latitude"] ?? null;
$implantation = $_POST["implantation_station"] ?? null;

if (
    !$idPrise ||
    !$nbrePdc ||
    !$puissance ||
    !$conditionAcces ||
    $reservation === null ||
    !$dateMiseEnService ||
    !$nomStation ||
    !$longitude ||
    !$latitude ||
    !$implantation
) {
    echo json_encode([
        "success" => false,
        "message" => "Champs manquants."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    // Récupérer la station liée à cette prise
    $stmt = $pdo->prepare("
        SELECT id_station
        FROM prise
        WHERE id_prise = ?
    ");
    $stmt->execute([$idPrise]);
    $idStation = $stmt->fetchColumn();

    if (!$idStation) {
        throw new Exception("Station introuvable pour cette prise.");
    }

    // Modifier les informations de la station
    $stmtStation = $pdo->prepare("
        UPDATE station
        SET
            nom_station = ?,
            consolidated_longitude = ?,
            consolidated_latitude = ?,
            implantation_station = ?
        WHERE id_station = ?
    ");

    $stmtStation->execute([
        $nomStation,
        $longitude,
        $latitude,
        $implantation,
        $idStation
    ]);

    // Modifier les informations de la prise
    $stmtPrise = $pdo->prepare("
        UPDATE prise
        SET
            nbre_pdc = ?,
            puissance_nominale = ?,
            condition_acces = ?,
            reservation = ?,
            date_mise_en_service = ?
        WHERE id_prise = ?
    ");

    $stmtPrise->execute([
        $nbrePdc,
        $puissance,
        $conditionAcces,
        $reservation,
        $dateMiseEnService,
        $idPrise
    ]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Modification réussie."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Erreur modification : " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}