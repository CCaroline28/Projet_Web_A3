<?php

header("Content-Type: application/json; charset=utf-8");
require_once "config.php";

$action = $_GET["action"] ?? "";

$departementsNoms = [
    "01"=>"Ain","02"=>"Aisne","03"=>"Allier","04"=>"Alpes-de-Haute-Provence",
    "05"=>"Hautes-Alpes","06"=>"Alpes-Maritimes","07"=>"Ardèche","08"=>"Ardennes",
    "09"=>"Ariège","10"=>"Aube","11"=>"Aude","12"=>"Aveyron","13"=>"Bouches-du-Rhône",
    "14"=>"Calvados","15"=>"Cantal","16"=>"Charente","17"=>"Charente-Maritime",
    "18"=>"Cher","19"=>"Corrèze","21"=>"Côte-d'Or","22"=>"Côtes-d'Armor",
    "23"=>"Creuse","24"=>"Dordogne","25"=>"Doubs","26"=>"Drôme","27"=>"Eure",
    "28"=>"Eure-et-Loir","29"=>"Finistère","30"=>"Gard","31"=>"Haute-Garonne",
    "32"=>"Gers","33"=>"Gironde","34"=>"Hérault","35"=>"Ille-et-Vilaine",
    "36"=>"Indre","37"=>"Indre-et-Loire","38"=>"Isère","39"=>"Jura","40"=>"Landes",
    "41"=>"Loir-et-Cher","42"=>"Loire","43"=>"Haute-Loire","44"=>"Loire-Atlantique",
    "45"=>"Loiret","46"=>"Lot","47"=>"Lot-et-Garonne","48"=>"Lozère","49"=>"Maine-et-Loire",
    "50"=>"Manche","51"=>"Marne","52"=>"Haute-Marne","53"=>"Mayenne","54"=>"Meurthe-et-Moselle",
    "55"=>"Meuse","56"=>"Morbihan","57"=>"Moselle","58"=>"Nièvre","59"=>"Nord",
    "60"=>"Oise","61"=>"Orne","62"=>"Pas-de-Calais","63"=>"Puy-de-Dôme",
    "64"=>"Pyrénées-Atlantiques","65"=>"Hautes-Pyrénées","66"=>"Pyrénées-Orientales",
    "67"=>"Bas-Rhin","68"=>"Haut-Rhin","69"=>"Rhône","70"=>"Haute-Saône",
    "71"=>"Saône-et-Loire","72"=>"Sarthe","73"=>"Savoie","74"=>"Haute-Savoie",
    "75"=>"Paris","76"=>"Seine-Maritime","77"=>"Seine-et-Marne","78"=>"Yvelines",
    "79"=>"Deux-Sèvres","80"=>"Somme","81"=>"Tarn","82"=>"Tarn-et-Garonne",
    "83"=>"Var","84"=>"Vaucluse","85"=>"Vendée","86"=>"Vienne","87"=>"Haute-Vienne",
    "88"=>"Vosges","89"=>"Yonne","90"=>"Territoire de Belfort","91"=>"Essonne",
    "92"=>"Hauts-de-Seine","93"=>"Seine-Saint-Denis","94"=>"Val-de-Marne","95"=>"Val-d'Oise"
];

try {

    if ($action === "filtres") {

        $stmt = $pdo->query("
            SELECT DISTINCT LPAD(LEFT(consolidated_code_postal, 2), 2, '0') AS code
            FROM Localisation
            ORDER BY code
        ");

        $departements = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = $row["code"];

            $departements[] = [
                "code" => $code,
                "nom" => $code . " - " . ($departementsNoms[$code] ?? "Département")
            ];
        }

        $types = $pdo->query("
            SELECT type_de_prise
            FROM type_de_prise
            ORDER BY type_de_prise
        ")->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            "success" => true,
            "departements" => $departements,
            "types" => $types
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($action === "points") {

        $departement = $_GET["departement"] ?? "";
        $type = $_GET["type"] ?? "";

        $page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
        if ($page < 1) {
            $page = 1;
        }

        $limit = 50;
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];

        if ($departement !== "") {
            $conditions[] = "LPAD(LEFT(l.consolidated_code_postal, 2), 2, '0') = ?";
            $params[] = $departement;
        }

        if ($type !== "") {
            $conditions[] = "dt.type_de_prise = ?";
            $params[] = $type;
        }

        $where = "";

        if (count($conditions) > 0) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        $sqlTotal = "
            SELECT COUNT(DISTINCT p.id_prise) AS total
            FROM prise p
            INNER JOIN station s ON p.id_station = s.id_station
            INNER JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
            LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
            LEFT JOIN paye_avec pa ON p.id_prise = pa.id_prise
            LEFT JOIN type_paiement tp ON pa.type_de_paiement = tp.type_de_paiement
            $where
        ";

        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = intval($stmtTotal->fetch(PDO::FETCH_ASSOC)["total"]);

        $totalPages = max(1, ceil($total / $limit));

        $sql = "
            SELECT
                p.id_prise,
                s.id_station,
                s.nom_station,
                s.consolidated_longitude AS longitude,
                s.consolidated_latitude AS latitude,
                LPAD(LEFT(l.consolidated_code_postal, 2), 2, '0') AS departement,
                l.consolidated_code_postal AS code_postal,
                tp.type_de_paiement AS type_paiement,
                dt.type_de_prise,
                p.nbre_pdc,
                p.puissance_nominale,
                s.implantation_station,
                p.reservation,
                p.condition_acces,
                DATE_FORMAT(p.date_mise_en_service, '%d/%m/%Y') AS date_mise_en_service
            FROM prise p
            INNER JOIN station s ON p.id_station = s.id_station
            INNER JOIN Localisation l ON p.consolidated_code_postal = l.consolidated_code_postal
            LEFT JOIN de_type dt ON p.id_prise = dt.id_prise
            LEFT JOIN paye_avec pa ON p.id_prise = pa.id_prise
            LEFT JOIN type_paiement tp ON pa.type_de_paiement = tp.type_de_paiement
            $where
            GROUP BY p.id_prise
            ORDER BY p.id_prise
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $points = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "points" => $points,
            "total" => $total,
            "page" => $page,
            "totalPages" => $totalPages
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    echo json_encode([
        "success" => false,
        "message" => "Action inconnue."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Erreur : " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}