<?php

require_once('constantes.php');

function dbConnect() {
    try {
        $db = new PDO(
            'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8;port=' . DB_PORT,
            DB_USER, DB_PASSWORD
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        return null;
    }
}

function createdatabase($db) {
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $db->exec("DROP TABLE IF EXISTS paye_avec");
        $db->exec("DROP TABLE IF EXISTS de_type");
        $db->exec("DROP TABLE IF EXISTS prise");
        $db->exec("DROP TABLE IF EXISTS station");
        $db->exec("DROP TABLE IF EXISTS type_de_prise");
        $db->exec("DROP TABLE IF EXISTS type_paiement");
        $db->exec("DROP TABLE IF EXISTS Localisation");
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        $db->exec("
        CREATE TABLE IF NOT EXISTS type_de_prise (
        type_de_prise VARCHAR(50) NOT NULL,
        CONSTRAINT type_de_prise_PK PRIMARY KEY (type_de_prise)
         ) ENGINE=InnoDB;

        Ajout des types autorisés ici
        INSERT IGNORE INTO type_de_prise (type_de_prise) VALUES 
        ('Type 2'), ('Combo'), ('Chademo'), ('EF'), ('Type 3');
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
    } catch (PDOException $exception) {
        error_log('Request error: ' . $exception->getMessage());
        return false;
    }
    return true;
}

// ------------------------------------------------------------------ STATS

function dbCountStations($db) {
    $stmt = $db->query("SELECT COUNT(*) AS total_stations FROM station");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function dbCountPointsCharge($db) {
    $stmt = $db->query("SELECT SUM(nbre_pdc) AS total_points_charge FROM prise");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function dbTopDepartement($db) {
    $departements = [
        '01'=>'Ain','02'=>'Aisne','03'=>'Allier','04'=>'Alpes-de-Haute-Provence',
        '05'=>'Hautes-Alpes','06'=>'Alpes-Maritimes','07'=>'Ardèche','08'=>'Ardennes',
        '09'=>'Ariège','10'=>'Aube','11'=>'Aude','12'=>'Aveyron',
        '13'=>'Bouches-du-Rhône','14'=>'Calvados','15'=>'Cantal','16'=>'Charente',
        '17'=>'Charente-Maritime','18'=>'Cher','19'=>'Corrèze','21'=>'Côte-d\'Or',
        '22'=>'Côtes-d\'Armor','23'=>'Creuse','24'=>'Dordogne','25'=>'Doubs',
        '26'=>'Drôme','27'=>'Eure','28'=>'Eure-et-Loir','29'=>'Finistère',
        '2A'=>'Corse-du-Sud','2B'=>'Haute-Corse','30'=>'Gard','31'=>'Haute-Garonne',
        '32'=>'Gers','33'=>'Gironde','34'=>'Hérault','35'=>'Ille-et-Vilaine',
        '36'=>'Indre','37'=>'Indre-et-Loire','38'=>'Isère','39'=>'Jura',
        '40'=>'Landes','41'=>'Loir-et-Cher','42'=>'Loire','43'=>'Haute-Loire',
        '44'=>'Loire-Atlantique','45'=>'Loiret','46'=>'Lot','47'=>'Lot-et-Garonne',
        '48'=>'Lozère','49'=>'Maine-et-Loire','50'=>'Manche','51'=>'Marne',
        '52'=>'Haute-Marne','53'=>'Mayenne','54'=>'Meurthe-et-Moselle','55'=>'Meuse',
        '56'=>'Morbihan','57'=>'Moselle','58'=>'Nièvre','59'=>'Nord',
        '60'=>'Oise','61'=>'Orne','62'=>'Pas-de-Calais','63'=>'Puy-de-Dôme',
        '64'=>'Pyrénées-Atlantiques','65'=>'Hautes-Pyrénées','66'=>'Pyrénées-Orientales',
        '67'=>'Bas-Rhin','68'=>'Haut-Rhin','69'=>'Rhône','70'=>'Haute-Saône',
        '71'=>'Saône-et-Loire','72'=>'Sarthe','73'=>'Savoie','74'=>'Haute-Savoie',
        '75'=>'Paris','76'=>'Seine-Maritime','77'=>'Seine-et-Marne',
        '78'=>'Yvelines','79'=>'Deux-Sèvres','80'=>'Somme','81'=>'Tarn',
        '82'=>'Tarn-et-Garonne','83'=>'Var','84'=>'Vaucluse','85'=>'Vendée',
        '86'=>'Vienne','87'=>'Haute-Vienne','88'=>'Vosges','89'=>'Yonne',
        '90'=>'Territoire de Belfort','91'=>'Essonne','92'=>'Hauts-de-Seine',
        '93'=>'Seine-Saint-Denis','94'=>'Val-de-Marne','95'=>'Val-d\'Oise',
        '971'=>'Guadeloupe','972'=>'Martinique','973'=>'Guyane',
        '974'=>'La Réunion','976'=>'Mayotte'
    ];

    $stmt = $db->query("
        SELECT LEFT(l.consolidated_code_postal, 2) AS departement,
               SUM(p.nbre_pdc) AS total_points_charge
        FROM prise p
        JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
        GROUP BY departement
        ORDER BY total_points_charge DESC
        LIMIT 1
    ");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $code = str_pad($row['departement'], 2, '0', STR_PAD_LEFT);
    $row['nom_departement'] = $departements[$code] ?? ('Département ' . $code);
    return $row;
}

// ------------------------------------------------------------------ VISUALISATION

function dbGetPrises($db, $dep = '', $types = [], $limit = 50, $offset = 0) {
    $where  = [];
    $params = [];

    if ($dep !== '') {
        $where[]        = "LEFT(l.consolidated_code_postal, LENGTH(:dep)) = :dep";
        $params[':dep'] = $dep;
    }

    if (!empty($types)) {
        $typeConditions = [];
        foreach ($types as $i => $t) {
            $key = ':type' . $i;
            $typeConditions[] = "(dt.type_de_prise = $key
                OR dt.type_de_prise LIKE :like$i
                OR dt.type_de_prise LIKE :likestart$i
                OR dt.type_de_prise LIKE :likeend$i)";
            $params[$key]                = $t;
            $params[':like' . $i]        = '%-' . $t . '-%';
            $params[':likestart' . $i]   = $t . '-%';
            $params[':likeend' . $i]     = '%-' . $t;
        }
        $where[] = '(' . implode(' OR ', $typeConditions) . ')';
    }

    $sql = "
        SELECT
            p.id_prise,
            s.nom_station,
            s.implantation_station,
            l.consolidated_commune,
            l.consolidated_code_postal,
            p.nbre_pdc,
            p.puissance_nominale,
            p.condition_acces,
            p.reservation,
            GROUP_CONCAT(DISTINCT dt.type_de_prise  SEPARATOR '-') AS type_de_prise,
            GROUP_CONCAT(DISTINCT pa.type_de_paiement SEPARATOR '-') AS type_de_paiement
        FROM prise p
        JOIN station s      ON p.id_station = s.id_station
        JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
        LEFT JOIN de_type dt   ON p.id_prise = dt.id_prise
        LEFT JOIN paye_avec pa ON p.id_prise = pa.id_prise
    ";

    if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);

    $sql .= "
        GROUP BY p.id_prise, s.nom_station, s.implantation_station,
                 l.consolidated_commune, l.consolidated_code_postal,
                 p.nbre_pdc, p.puissance_nominale, p.condition_acces, p.reservation
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) $stmt->bindValue($key, $value);
    $stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function dbCountPrises($db, $dep = '', $types = []) {
    $where  = [];
    $params = [];

    if ($dep !== '') {
        $where[]        = "LEFT(l.consolidated_code_postal, LENGTH(:dep)) = :dep";
        $params[':dep'] = $dep;
    }

    if (!empty($types)) {
        $typeConditions = [];
        foreach ($types as $i => $t) {
            $key = ':type' . $i;
            $typeConditions[] = "(dt.type_de_prise = $key
                OR dt.type_de_prise LIKE :like$i
                OR dt.type_de_prise LIKE :likestart$i
                OR dt.type_de_prise LIKE :likeend$i)";
            $params[$key]              = $t;
            $params[':like' . $i]      = '%-' . $t . '-%';
            $params[':likestart' . $i] = $t . '-%';
            $params[':likeend' . $i]   = '%-' . $t;
        }
        $where[] = '(' . implode(' OR ', $typeConditions) . ')';
    }

    $sql = "
        SELECT COUNT(DISTINCT p.id_prise) AS total
        FROM prise p
        JOIN station s      ON p.id_station = s.id_station
        JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
        LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
    ";
    if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// ------------------------------------------------------------------ INSERTION
// function dbInsertInstallation($db, $data) {
//     try {
//         $db->beginTransaction();

//         // 1. Localisation
//         $stmtLoc = $db->prepare("INSERT IGNORE INTO Localisation (consolidated_code_postal, consolidated_commune) VALUES (?, ?)");
//         $stmtLoc->execute([(int)$data['code_postal'], 'Commune inconnue']);

//         // 2. Station
//         $stmt = $db->prepare("INSERT INTO station (implantation_station, nom_station, consolidated_latitude, consolidated_longitude) VALUES (?, ?, ?, ?)");
//         $stmt->execute([$data['implantation'], 'Nouvelle Station', $data['latitude'], $data['longitude']]);
//         $idStation = $db->lastInsertId();

//         // 3. Prise
//         $stmt = $db->prepare("INSERT INTO prise (nbre_pdc, puissance_nominale, condition_acces, reservation, date_mise_en_service, id_station, consolidated_code_postal) VALUES (?, ?, ?, ?, ?, ?, ?)");
//         $stmt->execute([
//             (int)($data['nb_points'] ?? 0), 0, $data['acces'] ?? 'Public',
//             ($data['reservation'] === 'TRUE' ? 1 : 0),
//             $data['date_service'] ?: date('Y-m-d'),
//             $idStation, (int)$data['code_postal']
//         ]);
//         $idPrise = $db->lastInsertId();

//         // 4. Insertion des relations (Types de prise) - S'assurer que le type existe
//         if (!empty($data['prise'])) {
//             $stmtType = $db->prepare("INSERT INTO de_type (type_de_prise, id_prise) VALUES (?, ?)");
//             foreach ($data['prise'] as $t) {
//                 // Cette ligne va maintenant réussir car le type existe dans la table mère
//                 $stmtType->execute([$t, $idPrise]);
//             }
//         }

//         $db->commit();
//         return true;
//     } catch (Exception $e) {
//         $db->rollBack();
//         return "Erreur SQL : " . $e->getMessage();
//     }
// }



// ------------------------------------------------------------------ GET / MODIFIER / SUPPRIMER

/**
 * Récupère toutes les infos d'une prise (utilisé par le formulaire de modification)
 */
function dbGetPrise($db, $idPrise) {
    try {
        $stmt = $db->prepare("
            SELECT p.id_prise, p.nbre_pdc, p.puissance_nominale, p.condition_acces,
                   p.reservation, p.date_mise_en_service,
                   s.nom_station, s.consolidated_latitude, s.consolidated_longitude,
                   s.implantation_station,
                   l.consolidated_code_postal
            FROM prise p
            JOIN station s      ON p.id_station = s.id_station
            JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
            WHERE p.id_prise = :id
        ");
        $stmt->execute([':id' => $idPrise]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Met à jour la station et la prise
 */
function dbModifierPrise($db, $data) {
    $idPrise           = $data['id_prise']             ?? null;
    $nbrePdc           = $data['nbre_pdc']             ?? null;
    $puissance         = $data['puissance_nominale']   ?? null;
    $conditionAcces    = $data['condition_acces']      ?? null;
    $reservation       = $data['reservation']          ?? null;
    $dateMiseEnService = $data['date_mise_en_service'] ?? null;
    $nomStation        = $data['nom_station']          ?? null;
    $longitude         = $data['longitude']            ?? null;
    $latitude          = $data['latitude']             ?? null;
    $implantation      = $data['implantation_station'] ?? null;

    if (!$idPrise || !$nbrePdc || !$puissance || !$conditionAcces
        || $reservation === null || !$dateMiseEnService
        || !$nomStation || !$longitude || !$latitude || !$implantation
    ) {
        return false;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT id_station FROM prise WHERE id_prise = ?");
        $stmt->execute([$idPrise]);
        $idStation = $stmt->fetchColumn();
        if (!$idStation) throw new Exception("Station introuvable.");

        $db->prepare("
            UPDATE station
            SET nom_station            = ?,
                consolidated_longitude = ?,
                consolidated_latitude  = ?,
                implantation_station   = ?
            WHERE id_station = ?
        ")->execute([$nomStation, $longitude, $latitude, $implantation, $idStation]);

        $db->prepare("
            UPDATE prise
            SET nbre_pdc             = ?,
                puissance_nominale   = ?,
                condition_acces      = ?,
                reservation          = ?,
                date_mise_en_service = ?
            WHERE id_prise = ?
        ")->execute([$nbrePdc, $puissance, $conditionAcces, $reservation, $dateMiseEnService, $idPrise]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Supprime une prise et ses dépendances
 */
function dbSupprimerPrise($db, $idPrise) {
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM de_type   WHERE id_prise = ?")->execute([$idPrise]);
        $db->prepare("DELETE FROM paye_avec WHERE id_prise = ?")->execute([$idPrise]);
        $db->prepare("DELETE FROM prise      WHERE id_prise = ?")->execute([$idPrise]);
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

//Ajoute les informations d'une prise à la base de donnée 
function dbAjoutPrise($db, $data) {
    $nbrePdc           = $data['nb_points']             ?? null;
    $puissance         = $data['puissance_nominale']   ?? null;
    $conditionAcces    = $data['acces']      ?? null;
    $reservation = isset($data['reservation']) ? (int)$data['reservation'] : null; //transforme les "O" et "1" en vrai int (les false/true du formulaire sont transmis commme des "0" et "1")
    $dateMiseEnService = $data['date_service'] ?? null;
    $nomStation        = $data['nom_station']          ?? null;
    $longitude         = $data['longitude']            ?? null;
    $latitude          = $data['latitude']             ?? null;
    $implantation      = $data['implantation'] ?? null;
    $paiement          = $data['paiement'] ?? null;
    $typeprise         = $data['prise'] ?? null;
    $codepostal         = $data['code_postal'] ?? null;


    if ( !$nbrePdc || !$puissance || !$conditionAcces
        || $reservation === null || !$dateMiseEnService
        || !$nomStation || !$longitude || !$latitude || !$implantation
        || empty($paiement) || empty($typeprise)
    ) {
        return false;
    }

    try {
        $db->beginTransaction();

        //Remplit chaque table une à une

        foreach ($paiement as $unPaiement) {
        $stmt = $db->prepare("INSERT IGNORE INTO type_paiement (type_de_paiement) VALUES (?)");
        $stmt->execute([$unPaiement]);
        }

        // Récupération automatique de la commune
        $stmt = $db->prepare("SELECT consolidated_commune FROM Localisation WHERE consolidated_code_postal = ?");
        $stmt->execute([$codepostal]);
        $commune = $stmt->fetchColumn();

        if (!$commune) {
            // Si pas trouvé, commune vide
            $commune = "";
            $stmt = $db->prepare("INSERT IGNORE INTO Localisation (consolidated_code_postal, consolidated_commune)
                                  VALUES (?, ?)");
            $stmt->execute([$codepostal, $commune]);
        }

        $stmt = $db->prepare("INSERT INTO station (implantation_station, nom_station, consolidated_latitude, consolidated_longitude)
                      VALUES (?, ?, ?, ?)");
        $stmt->execute([$implantation, $nomStation, $latitude, $longitude]);
        $id_station = $db->lastInsertId();   // récupère l'id de la dernière station créée (pour la clé étrangère)

        foreach ($typeprise as $unType) {
            $stmt = $db->prepare("INSERT IGNORE INTO type_de_prise (type_de_prise) VALUES (?)");
            $stmt->execute([$unType]);
        }

        $stmt = $db->prepare("INSERT INTO prise (nbre_pdc, puissance_nominale, condition_acces, reservation,
                                         date_mise_en_service, id_station, consolidated_code_postal)
                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nbrePdc,$puissance,$conditionAcces,$reservation,$dateMiseEnService,$id_station,$codepostal
        ]);
        $id_prise = $db->lastInsertId();   

        foreach ($typeprise as $unType) {
            $stmt = $db->prepare("INSERT INTO de_type (type_de_prise, id_prise) VALUES (?, ?)");
            $stmt->execute([$unType, $id_prise]);
        }

        foreach ($paiement as $unPaiement) {
            $stmt = $db->prepare("INSERT INTO paye_avec (type_de_paiement, id_prise) VALUES (?, ?)");
            $stmt->execute([$unPaiement, $id_prise]);
        }

        $db->commit();
        return true;
    } 
    
    catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        return false;
    }

}


// ------------------------------------------------------------------ STATISTIQUES

/**
 * Répartition par type de prise
 */
function dbCounttypeprise($db,$dep){
    try {
        $request = "SELECT id_station,    GROUP_CONCAT(DISTINCT type_de_prise ORDER BY type_de_prise SEPARATOR ', ') AS types_de_prise
FROM (
    SELECT  prise.id_station, prise.id_prise, de_type.type_de_prise, prise.consolidated_code_postal
	FROM prise
	INNER JOIN de_type ON prise.id_prise = de_type.id_prise
    WHERE prise.consolidated_code_postal LIKE :dep
	ORDER BY prise.id_station, prise.id_prise
) AS sub
GROUP BY id_station;";
        $statement = $db->prepare($request);
        $statement->execute([
            ":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}

/**
 * Répartition par type d'implantation
 */
function dbCountimplantation($db,$dep){
    try {
        $request = "SELECT station.implantation_station,
       COUNT(*) AS total
FROM station
INNER JOIN prise ON station.id_station = prise.id_station
WHERE prise.consolidated_code_postal LIKE :dep
GROUP BY station.implantation_station;
";
        $statement = $db->prepare($request);
        $statement->execute([
            ":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}

/**
 * Répartition par nombre de points de charge
 * CORRECTION : était en doublon avec dbCountimplantation, renommée dbCountnbpoints
 */
function dbCountnbpoints($db, $dep){
    try {
        $request = "SELECT nb_pdc, COUNT(*) AS total_de_pdc
FROM (
    SELECT id_station, MAX(nbre_pdc) AS nb_pdc, COUNT(*) AS total_prise  # ça renvoie un tableau avec l'id de la statino et le nb de pdc 
    FROM prise
    WHERE consolidated_code_postal LIKE :dep
    GROUP BY id_station
) AS sub
GROUP BY nb_pdc
";
        $statement = $db->prepare($request);
        $statement->execute([
            ":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}

/**
 * Répartition par puissance nominale
 * CORRECTION : WHERE était après GROUP BY → déplacé dans la sous-requête
 */
function dbCountpuissance($db, $dep) {
    try {
        $statement = $db->prepare("
            SELECT id_station,
                   GROUP_CONCAT(DISTINCT puissance_nominale ORDER BY puissance_nominale SEPARATOR ', ') AS puissances
            FROM (
                SELECT puissance_nominale, id_prise, id_station, consolidated_code_postal
                FROM prise
                WHERE consolidated_code_postal LIKE :dep
            ) AS sub
            GROUP BY id_station
        ");
        // CORRECTION : WHERE déplacé dans la sous-requête + paramètre lié
        $statement->execute([':dep' => $dep . '%']);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('dbCountpuissance error: ' . $e->getMessage());
        return false;
    }
}

function dbGetAllDepartements($db) {
    $stmt = $db->query("
        SELECT DISTINCT LEFT(consolidated_code_postal, 2) AS code
        FROM Localisation
        ORDER BY code ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function dbGetAllPrisesForClustering($db) {
    $stmt = $db->query("
        SELECT p.id_prise, s.nom_station, s.consolidated_latitude AS latitude, 
               s.consolidated_longitude AS longitude, p.nbre_pdc, p.puissance_nominale 
        FROM prise p 
        INNER JOIN station s ON p.id_station = s.id_station 
        WHERE s.consolidated_latitude IS NOT NULL 
        AND s.consolidated_longitude IS NOT NULL 
        LIMIT 300
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Récupère les points pour la carte (lat/long) en fonction du département et des types
 */
function dbGetPointsCarte($db, $dep, $types) {
    $where = [];
    $params = [];

    // 1. Construction des conditions
    if (!empty($dep)) {
        $where[] = "LEFT(l.consolidated_code_postal, LENGTH(:dep)) = :dep";
        $params[':dep'] = $dep;
    }

    if (!empty($types)) {
        $typeConditions = [];
        foreach ($types as $i => $t) {
            $key = ':type' . $i;
            $typeConditions[] = "dt.type_de_prise = $key";
            $params[$key] = $t;
        }
        $where[] = '(' . implode(' OR ', $typeConditions) . ')';
    }

    // 2. Construction de la requête
    $sql = "
        SELECT DISTINCT 
            s.nom_station, 
            s.consolidated_latitude AS latitude, 
            s.consolidated_longitude AS longitude
        FROM station s
        INNER JOIN prise p ON s.id_station = p.id_station
        INNER JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
        LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
    ";

    // Ajout unique du WHERE si nécessaire
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    // Ajout de la limite
    $sql .= " LIMIT 500";

    // 3. Exécution
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}