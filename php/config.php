<?php

/*
|--------------------------------------------------------------------------
| Configuration de la base de données
|--------------------------------------------------------------------------
| À modifier selon votre serveur.
*/

$host = "localhost";
$dbname = "lef_irve";
$user = "root";
$password = "";

try {
    /*
    |--------------------------------------------------------------------------
    | Connexion PDO en utf8mb4
    |--------------------------------------------------------------------------
    | utf8mb4 permet de gérer correctement :
    | - les accents : é, è, à, ç, ô
    | - les apostrophes
    | - certains caractères spéciaux
    */

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode(
        [
            "success" => false,
            "message" => "Erreur de connexion à la base de données."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}