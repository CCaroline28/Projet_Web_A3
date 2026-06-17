<?php

function dbConnect() {
    $host     = 'localhost';
    $dbname   = 'mlenec28';
    $user     = 'mlenec28';
    $password = 'PncZ9n6YUYINXnG_';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

function createDatabase($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS type_paiement (
            type_de_paiement VARCHAR(50) NOT NULL,
            CONSTRAINT type_paiement_PK PRIMARY KEY (type_de_paiement)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS Localisation (
            consolidated_code_postal INT NOT NULL,
            consolidated_commune CHAR(50) NOT NULL,
            CONSTRAINT Localisation_PK PRIMARY KEY (consolidated_code_postal)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS station (
            id_station INT NOT NULL AUTO_INCREMENT,
            implantation_station CHAR(50) NOT NULL,
            nom_station CHAR(50) NOT NULL,
            consolidated_latitude FLOAT NOT NULL,
            consolidated_longitude FLOAT NOT NULL,
            CONSTRAINT station_PK PRIMARY KEY (id_station)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS type_de_prise (
            type_de_prise VARCHAR(50) NOT NULL,
            CONSTRAINT type_de_prise_PK PRIMARY KEY (type_de_prise)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS prise (
            id_prise INT NOT NULL AUTO_INCREMENT,
            nbre_pdc INT NOT NULL,
            puissance_nominale FLOAT NOT NULL,
            condition_acces CHAR(50) NOT NULL,
            reservation TINYINT(1) NOT NULL,
            date_mise_en_service DATETIME NOT NULL,
            id_station INT NOT NULL,
            consolidated_code_postal INT NOT NULL,
            CONSTRAINT prise_PK PRIMARY KEY (id_prise),
            CONSTRAINT prise_id_station_FK FOREIGN KEY (id_station) REFERENCES station(id_station),
            CONSTRAINT prise_consolidated_code_postal_FK FOREIGN KEY (consolidated_code_postal) REFERENCES Localisation(consolidated_code_postal)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS de_type (
            type_de_prise VARCHAR(50) NOT NULL,
            id_prise INT NOT NULL,
            CONSTRAINT de_type_PK PRIMARY KEY (type_de_prise, id_prise),
            CONSTRAINT de_type_type_de_prise_FK FOREIGN KEY (type_de_prise) REFERENCES type_de_prise(type_de_prise),
            CONSTRAINT de_type_id_prise_FK FOREIGN KEY (id_prise) REFERENCES prise(id_prise)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS paye_avec (
            type_de_paiement VARCHAR(50) NOT NULL,
            id_prise INT NOT NULL,
            CONSTRAINT paye_avec_PK PRIMARY KEY (type_de_paiement, id_prise),
            CONSTRAINT paye_avec_type_de_paiement_FK FOREIGN KEY (type_de_paiement) REFERENCES type_paiement(type_de_paiement),
            CONSTRAINT paye_avec_id_prise_FK FOREIGN KEY (id_prise) REFERENCES prise(id_prise)
        ) ENGINE=InnoDB;
    ");

    echo "Base de données créée avec succès.\n";
}

function importCSV($filename, $pdo) {

    if (!file_exists($filename)) {
        die("Fichier CSV introuvable.");
    }

    $handle = fopen($filename, "r");
    if (!$handle) {
        die("Impossible d'ouvrir le fichier CSV.");
    }

    // Lire l'en-tête et nettoyer les guillemets
    $header = fgetcsv($handle, 0, ",");
    $header = array_map(function($h) { return trim($h, " \"\t"); }, $header);

    $maxLines         = 10000;
    $lineCount        = 0;
    $stationsInserted = []; // cache nom_station → id_station

    while (($row = fgetcsv($handle, 0, ",")) !== false && $lineCount < $maxLines) {

        $lineCount++;
        $data = array_combine($header, $row);
        $data   = array_map(function($v) { return trim($v, "\""); },   $data);
        $cp = $data["consolidated_code_postal"];

// Ignorer les lignes sans code postal valide
if (!is_numeric($cp) || (int)$cp === 0) {
    echo "Ligne $lineCount ignorée : code postal invalide ('$cp')\n";
    continue;
}

$cp = (int)$cp;

        /* ---------------------------------------------------------
           1) Construire le type_de_prise combiné
        --------------------------------------------------------- */
        $typesPrise = [];
        if ($data["prise_type_2"]         === "True") $typesPrise[] = "2";
        if ($data["prise_type_combo_ccs"] === "True") $typesPrise[] = "combo_ccs";
        if ($data["prise_type_chademo"]   === "True") $typesPrise[] = "chademo";
        if ($data["prise_type_ef"]        === "True") $typesPrise[] = "ef";
        if ($data["prise_type_autre"]     === "True") $typesPrise[] = "autre";

        /* ---------------------------------------------------------
           2) Construire le type_de_paiement combiné
        --------------------------------------------------------- */
        $typesPaiement = [];
        if ($data["paiement_acte"]  === "True") $typesPaiement[] = "acte";
        if ($data["paiement_cb"]    === "True") $typesPaiement[] = "cb";
        if ($data["paiement_autre"] === "True") $typesPaiement[] = "autre";

        /* ---------------------------------------------------------
           3) Insérer dans Localisation (si pas déjà présent)
        --------------------------------------------------------- */
        $pdo->prepare("INSERT IGNORE INTO Localisation VALUES (?, ?)")
            ->execute([
                $cp,
                $data["consolidated_commune"]
            ]);

        /* ---------------------------------------------------------
           4) Insérer dans station (si pas déjà présent)
              id_station AUTO_INCREMENT : on ne le passe pas.
              Déduplication par nom_station via cache PHP.
        --------------------------------------------------------- */
        // Remplacer le bloc station (étape 4) par ceci :

$nomStation = $data["nom_station"];

if (!isset($stationsInserted[$nomStation])) {

    $stmtIns = $pdo->prepare("
        INSERT IGNORE INTO station (implantation_station, nom_station, consolidated_latitude, consolidated_longitude)
        VALUES (?, ?, ?, ?)
    ");
    $stmtIns->execute([
        $data["implantation_station"],
        $nomStation,
        $data["consolidated_latitude"],
        $data["consolidated_longitude"]
    ]);

    // Si INSERT a créé une ligne → lastInsertId() > 0
    // Si IGNORE a bloqué le doublon → lastInsertId() = 0, on fait un SELECT
    $idStation = (int) $pdo->lastInsertId();

    if ($idStation === 0) {
        $stmt = $pdo->prepare("SELECT id_station FROM station WHERE nom_station = ? LIMIT 1");
        $stmt->execute([$nomStation]);
        $idStation = (int) $stmt->fetchColumn();
    }

    $stationsInserted[$nomStation] = $idStation;
}

$idStation = $stationsInserted[$nomStation];

// Sécurité : si on n'a toujours pas d'id, on skip la ligne
if (!$idStation) {
    echo "Ligne $lineCount ignorée : station introuvable pour '$nomStation'\n";
    continue;
}

        /* ---------------------------------------------------------
           5) Insérer la prise
              id_prise AUTO_INCREMENT : on ne le passe pas.
        --------------------------------------------------------- */
        $pdo->prepare("
            INSERT INTO prise
                (nbre_pdc, puissance_nominale, condition_acces, reservation, date_mise_en_service, id_station, consolidated_code_postal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $data["nbre_pdc"],
            $data["puissance_nominale"],
            $data["condition_acces"],
            $data["reservation"] === "True" ? 1 : 0,
            $data["date_mise_en_service"],
            $idStation,
            $cp
        ]);

        $idPrise = $pdo->lastInsertId();

        /* ---------------------------------------------------------
           6) Insérer les types de prise dans type_de_prise + pivot
        --------------------------------------------------------- */
        foreach ($typesPrise as $t) {
            $pdo->prepare("INSERT IGNORE INTO type_de_prise VALUES (?)")->execute([$t]);
            $pdo->prepare("INSERT IGNORE INTO de_type VALUES (?, ?)")->execute([$t, $idPrise]);
        }

        /* ---------------------------------------------------------
           7) Insérer les types de paiement dans type_paiement + pivot
        --------------------------------------------------------- */
        foreach ($typesPaiement as $p) {
            $pdo->prepare("INSERT IGNORE INTO type_paiement VALUES (?)")->execute([$p]);
            $pdo->prepare("INSERT IGNORE INTO paye_avec VALUES (?, ?)")->execute([$p, $idPrise]);
        }
    }

    fclose($handle);
    echo "Import terminé : $lineCount lignes importées.\n";
}

// ============================================================
// POINT D'ENTRÉE
// ============================================================
$db = dbConnect();
createDatabase($db);
importCSV("export_IA.csv", $db);