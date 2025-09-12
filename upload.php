<?php
// Empêcher les warnings de casser l'affichage
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Variable pour message d'erreur
$message = "";

// Vérifier si un fichier a été envoyé
if (isset($_FILES['file'])) {

    $target = "Emails.txt";

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {

        // Lancer le traitement (traitement.php)
        include("traitement.php");

        // Après traitement, on affichera le résultat plus bas

    } else {
        $message = "❌ Erreur lors de l'upload du fichier.";
    }

} else {
    $message = "❌ Aucun fichier sélectionné.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat du traitement</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        button { margin-top: 10px; }
    </style>
</head>
<body>
    <?php if($message): ?>
        <h2><?php echo $message; ?></h2>
        <a href="index.html"><button>Retour à l'accueil</button></a>
    <?php else: ?>
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
    <?php endif; ?>
</body>
</html>
