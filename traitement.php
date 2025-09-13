<?php

// Fonction pour valider une adresse email
function validerEmail($email){
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/";
    return preg_match($reg, $email) === 1; //pour s'assurer que c'est soit 0 soit 1
}

// Fonction pour supprimer les doublons
function supprimerDoublons($T){
    $new = [];
    foreach($T as $email) {
        $existe = false;
        foreach($new as $element){
            if($element === $email){
                $existe = true;
                break;
            }
        }
        if(!$existe){
            $new[] = $email;
        }
    }
    return $new;
}

// Fonction pour trier les emails
function trierEmails($T){
    $taille = count($T);
    for ($i = 0; $i < $taille - 1; $i++){
        for ($j = $i + 1; $j < $taille; $j++){
            if ($T[$i] > $T[$j]){
                $temp = $T[$i];
                $T[$i] = $T[$j];
                $T[$j] = $temp;
            }
        }
    }
    return $T;
}
//uplod du fichier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['file'])) {
    $target = "Emails.txt"; 
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo "Fichier uploadé avec succès<br>";
    } else {
        echo "Erreur lors de l’upload du fichie.<br>";
        exit();
    }
}

// Lecture du fichier 
$emailsValides = [];
$emailsInvalides = [];

$fichier = fopen("Emails.txt", "r");
if($fichier){
    while(($ligne = fgets($fichier)) !== false){
        $ligne = rtrim($ligne, "\r\n"); //pour enlever les sauts de ligne
        if(validerEmail($ligne)){
            $emailsValides[] = $ligne;
        } else {
            $emailsInvalides[] = $ligne;
        }
    }
    fclose($fichier);
} else {
    echo "Erreur lors de l'ouverture du fichier.";
    exit();
}

// Fichier des emails invalides
$fichierInv = fopen("adressesNonValides.txt", "w");
foreach($emailsInvalides as $email){
    fwrite($fichierInv, $email . "\n");
}
fclose($fichierInv);

//Creation du fichier des emails valides sans doublons et tries
$emailsValides = supprimerDoublons($emailsValides);
$emailsValides = trierEmails($emailsValides);

$fichierV = fopen("EmailsT.txt", "w");
foreach($emailsValides as $email){
    fwrite($fichierV, $email . "\n");
}
fclose($fichierV);

//Separation des emails par domaine
$emailsSepares = [];
foreach($emailsValides as $email){
     // positionner le @
    $position = strpos($email, "@");
    //extraire le domaine
    $domaine = substr($email, $position + 1); //la fonction substr permet l'extraction d'une partie d'une chaine de caracteres
    if(!isset($emailsSepares[$domaine])){
        $emailsSepares[$domaine] = [];
    }
    $emailsSepares[$domaine][] = $email;
}

// Création des fichiers par domaine
foreach($emailsSepares as $domaine => $liste){
    $nom = "emailDeDomaine_" . $domaine . ".txt";
    $f = fopen($nom, "w");
    foreach($liste as $email){
        fwrite($f, $email . "\n");
    }
    fclose($f);
}



//Ajouter un email
if (isset($_POST['action']) && $_POST['action'] === "ajouter") {
    //on recupere l email
    $email = $_POST['email']; 

    // verification cote serveur
    if (!validerEmail($email)) {
        echo "Adresse email invalide";
        exit();
    }

    //Parcours du fichiers contenant les emails valides triés
    $emails = [];
    $f = fopen("EmailsT.txt", "r");
    if ($f) {
        while (($ligne = fgets($f)) !== false) {
            $ligne = rtrim($ligne, "\r\n"); // enlever retour à la ligne
            if ($ligne !== "") {
                $emails[] = $ligne;
            }
        }
        fclose($f);
    }

    // verifier si l email existe deja (cote serveur)
    $existe = false;
    foreach ($emails as $em) {
        if ($em === $email) {
            $existe = true;
            break;
        }
    }

    if ($existe) {
        echo "Cet email existe déjà";
        exit();
    }

    // Ajouter et trier
    $emails[] = $email;
    $emails = trierEmails($emails);

    
    $f = fopen("EmailsT.txt", "w");
    foreach ($emails as $em) {
        fwrite($f, $em . "\n");
    }
    fclose($f);

    echo " Email ajouté avec succès ";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat du traitement</title>

</head>
<body>
    <h2> Traitement terminé avec succès !</h2>
    <p>Les fichiers générés :</p>
    <ul>
        <li><a href="EmailsT.txt" download>EmailsT.txt</a></li>
        <li><a href="adressesNonValides.txt" download>adressesNonValides.txt</a></li>
        <?php
        // afficher dynamiquement les fichiers créés par domaine
        $domaineFiles = glob("emailDeDomaine_*.txt"); //glob pour chercher les fichiers qui suivent la meme pattern
        foreach($domaineFiles as $fichierDomaine){
            $nomFichier = basename($fichierDomaine); //basename() pour avoir juste le nom du fichier au lieu de son path
            echo "<li><a href='$nomFichier' download>$nomFichier</a></li>";
        }
        ?>
    </ul>
    <a href="index.html"><button>Retour à l'accueil</button></a>
</body>
</html>


