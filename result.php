<?php
// Empêcher l'affichage des warnings
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Inclure le traitement
include("traitement.php");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Traitement terminé</title>
</head>
<body>
    <h2>✅ Traitement terminé avec succès !</h2>
    <p>Les fichiers générés :</p>
    <ul>
        <li><a href="EmailsT.txt" download>EmailsT.txt</a></li>
        <li><a href="adressesNonValides.txt" download>adressesNonValides.txt</a></li>
        <?php
        // Afficher dynamiquement les fichiers par domaine
        $domaineFiles = glob("emailDeDomaine_*.txt");
        foreach($domaineFiles as $fichierDomaine){
            $nomFichier = basename($fichierDomaine);
            echo "<li><a href='$nomFichier' download>$nomFichier</a></li>";
        }
        ?>
    </ul>
    <a href="index.html"><button>Retour à l'accueil</button></a>
</body>
</html>
