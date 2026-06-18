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

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM de_type WHERE id_prise = ?");
    $stmt->execute([$idPrise]);

    $stmt = $pdo->prepare("DELETE FROM paye_avec WHERE id_prise = ?");
    $stmt->execute([$idPrise]);

    $stmt = $pdo->prepare("DELETE FROM prise WHERE id_prise = ?");
    $stmt->execute([$idPrise]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Point de charge supprimé."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Erreur suppression : " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}