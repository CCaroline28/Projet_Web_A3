<?php 
require_once('database.php');

$db = dbConnect();
  if (!$db)
  {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
  }




  ############################################################## STATISTIQUE ##############################################################
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
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
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
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}

#Renvoie un tableau contenant le nb de point de charge  et son total dans la base
# il nous faut un graphe avec en absisse les nb de points de charges et en ordonnée le total
function dbCountimplantation($db, $dep){
    try {
        $request = "SELECT nb_pdc, COUNT(*) AS total_de_pdc
FROM (
    SELECT id_station, MAX(nbre_pdc) AS nb_pdc, COUNT(*) AS total_prise  # ça renvoie un tableau avec l'id de la statino et le nb de pdc 
    FROM prise
    WHERE consolidated_code_postal LIKE :dep'
    GROUP BY id_station
) AS sub
GROUP BY nb_pdc
";
        $statement = $db->prepare($request);
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
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
        $request = "SELECT implantation_station, GROUP_CONCAT(DISTINCT puissance_nominale ORDER BY puissance_nominale SEPARATOR ', ') AS puissances
FROM (
    SELECT  puissance_nominale, id_prise, id_station, consolidated_code_postal
	FROM prise
) AS sub
GROUP BY id_station
WHERE consolidated_code_postal LIKE :dep;";
        $statement = $db->prepare($request);
        $statement->execute();
        $stmt->execute([":dep" => $dep . "%"]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $exception) {
        error_log('Count request error: ' . $exception->getMessage());
        return false;
    }
}



?>
