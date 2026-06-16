<?php 
require_once('database.php');

$db = dbConnect();
  if (!$db)
  {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
  }

// createdatabase($db);


  //Viens de https://zonetuto.fr/php/lire-un-fichier-ligne-par-ligne-et-recuperer-son-contenu-avec-php/#google_vignette
// ouverture du fichier
// $fh = fopen('monfichier.csv', 'r');
// // tant que je ne suis pas à la fin du fichier
// while (!feof($fh)) {
//     // je récupère la ligne courante
//     $ligne = fgets($fh);
//     // j'affiche le contenu de la ligne
//     echo 'Contenu de la ligne : ' . $ligne . "\n";
// }
// // je ferme mon fichier
// fclose($fh);


$fh = fopen('data.csv', 'r');
$a=false;
$b = 0;
while (!feof($fh) && $b < 10) {
    if($a){ //pour le reste
      $ligne = fgets($fh);
      $tab = explode(",", $ligne);
      $requestRessource = array_shift($tab);
      var_dump($tab);
      $b += 1;
    }
    else
    {
      $a=true;
      $ligne = fgets($fh); //le premier va ici
      var_dump('ici');
      $b = 1;

    }
}
fclose($fh);





// var_dump('ça passe');


?>