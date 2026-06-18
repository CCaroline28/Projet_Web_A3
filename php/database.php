<?php

require_once('constantes.php');

function dbConnect() {
    try {
        $db = new PDO(
            'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8;port=' . DB_PORT,
            DB_USER,
            DB_PASSWORD
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
        error_log('Connection error: ' . $exception->getMessage());
        return false;
    }
    return $db;
}

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
        '01' => 'Ain', '02' => 'Aisne', '03' => 'Allier', '04' => 'Alpes-de-Haute-Provence',
        '05' => 'Hautes-Alpes', '06' => 'Alpes-Maritimes', '07' => 'Ardèche', '08' => 'Ardennes',
        '09' => 'Ariège', '10' => 'Aube', '11' => 'Aude', '12' => 'Aveyron',
        '13' => 'Bouches-du-Rhône', '14' => 'Calvados', '15' => 'Cantal', '16' => 'Charente',
        '17' => 'Charente-Maritime', '18' => 'Cher', '19' => 'Corrèze', '21' => 'Côte-d\'Or',
        '22' => 'Côtes-d\'Armor', '23' => 'Creuse', '24' => 'Dordogne', '25' => 'Doubs',
        '26' => 'Drôme', '27' => 'Eure', '28' => 'Eure-et-Loir', '29' => 'Finistère',
        '2A' => 'Corse-du-Sud', '2B' => 'Haute-Corse', '30' => 'Gard', '31' => 'Haute-Garonne',
        '32' => 'Gers', '33' => 'Gironde', '34' => 'Hérault', '35' => 'Ille-et-Vilaine',
        '36' => 'Indre', '37' => 'Indre-et-Loire', '38' => 'Isère', '39' => 'Jura',
        '40' => 'Landes', '41' => 'Loir-et-Cher', '42' => 'Loire', '43' => 'Haute-Loire',
        '44' => 'Loire-Atlantique', '45' => 'Loiret', '46' => 'Lot', '47' => 'Lot-et-Garonne',
        '48' => 'Lozère', '49' => 'Maine-et-Loire', '50' => 'Manche', '51' => 'Marne',
        '52' => 'Haute-Marne', '53' => 'Mayenne', '54' => 'Meurthe-et-Moselle', '55' => 'Meuse',
        '56' => 'Morbihan', '57' => 'Moselle', '58' => 'Nièvre', '59' => 'Nord',
        '60' => 'Oise', '61' => 'Orne', '62' => 'Pas-de-Calais', '63' => 'Puy-de-Dôme',
        '64' => 'Pyrénées-Atlantiques', '65' => 'Hautes-Pyrénées', '66' => 'Pyrénées-Orientales',
        '67' => 'Bas-Rhin', '68' => 'Haut-Rhin', '69' => 'Rhône', '70' => 'Haute-Saône',
        '71' => 'Saône-et-Loire', '72' => 'Sarthe', '73' => 'Savoie', '74' => 'Haute-Savoie',
        '75' => 'Paris', '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne',
        '78' => 'Yvelines', '79' => 'Deux-Sèvres', '80' => 'Somme', '81' => 'Tarn',
        '82' => 'Tarn-et-Garonne', '83' => 'Var', '84' => 'Vaucluse', '85' => 'Vendée',
        '86' => 'Vienne', '87' => 'Haute-Vienne', '88' => 'Vosges', '89' => 'Yonne',
        '90' => 'Territoire de Belfort', '91' => 'Essonne', '92' => 'Hauts-de-Seine',
        '93' => 'Seine-Saint-Denis', '94' => 'Val-de-Marne', '95' => 'Val-d\'Oise',
        '971' => 'Guadeloupe', '972' => 'Martinique', '973' => 'Guyane',
        '974' => 'La Réunion', '976' => 'Mayotte'
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
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $code = str_pad($row['departement'], 2, '0', STR_PAD_LEFT);
    $row['nom_departement'] = isset($departements[$code]) ? $departements[$code] : 'Département ' . $code;

    return $row;
}

function dbGetPrises($db, $dep = '', $types = [], $limit = 50, $offset = 0) {
    $where  = [];
    $params = [];

    if ($dep !== '') {
        $where[] = "LEFT(l.consolidated_code_postal, LENGTH(:dep)) = :dep";
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
            s.nom_station,
            s.implantation_station,
            l.consolidated_commune,
            l.consolidated_code_postal,
            p.nbre_pdc,
            p.puissance_nominale,
            p.condition_acces,
            p.reservation,
            GROUP_CONCAT(DISTINCT dt.type_de_prise SEPARATOR '-') AS type_de_prise,
            GROUP_CONCAT(DISTINCT pa.type_de_paiement SEPARATOR '-') AS type_de_paiement
        FROM prise p
        JOIN station s      ON p.id_station = s.id_station
        JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
        LEFT JOIN de_type dt   ON p.id_prise = dt.id_prise
        LEFT JOIN paye_avec pa ON p.id_prise = pa.id_prise
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    // Correction : GROUP BY nécessaire avec GROUP_CONCAT
    $sql .= "
        GROUP BY
            p.id_prise,
            s.nom_station,
            s.implantation_station,
            l.consolidated_commune,
            l.consolidated_code_postal,
            p.nbre_pdc,
            p.puissance_nominale,
            p.condition_acces,
            p.reservation
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Correction : le double return en bas a été supprimé
}

function dbCountPrises($db, $dep = '', $types = []) {
    $where  = [];
    $params = [];

    if ($dep !== '') {
        $where[] = "LEFT(l.consolidated_code_postal, LENGTH(:dep)) = :dep";
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

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}
function dbInsertInstallation($db, $data) {
    try {
        $db->beginTransaction();

        // 1. Insertion de la station
        $stmt = $db->prepare("INSERT INTO station (nom_station, implantation_station, consolidated_latitude, consolidated_longitude) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Station', $data['implantation'], $data['latitude'], $data['longitude']]);
        $idStation = $db->lastInsertId();

        // 2. Insertion de la prise (Note: j'ai mis des valeurs par défaut pour les champs manquants dans votre formulaire)
        $stmt = $db->prepare("INSERT INTO prise (nbre_pdc, puissance_nominale, condition_acces, reservation, date_mise_en_service, id_station, consolidated_code_postal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['nb_points_charge'] ?? 0, 
            0, // puissance_nominale par défaut
            $data['condition_acces'] ?? 'Public', 
            ($data['reservation'] === 'TRUE' ? 1 : 0), 
            $data['date_mise_en_service'], 
            $idStation, 
            $data['code_postal']
        ]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

#################################################################################STATISTIQUE###########################################################################
    #Renvoie un tableau contenant l'id de la station et le type de ses prises (séparé par une virgule)
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


#Renvoie un tableau contenant le nombre du type de station et son total dans la base
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

#Renvoie un tableau contenant le nb de point de charge  et son total dans la base
# il nous faut un graphe avec en absisse les nb de points de charges et en ordonnée le total
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

#Renvoie un tableau contenant l'id de la station et la puissance de ses prises (séparé par une virgule)
function dbCountpuissance($db,$dep){
    try {
        $request = "SELECT id_station, GROUP_CONCAT(DISTINCT puissance_nominale ORDER BY puissance_nominale SEPARATOR ', ') AS puissances
FROM (
    SELECT  puissance_nominale, id_prise, id_station, consolidated_code_postal
	FROM prise
) AS sub
WHERE consolidated_code_postal LIKE :dep
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
?>
