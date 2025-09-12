<?php

// Fonction pour valider une adresse email
function validerEmail($email){
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/";
    return preg_match($reg, $email) === 1;
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

// Lecture du fichier Emails.txt
$emailsValides = [];
$emailsInvalides = [];

$fichier = fopen("Emails.txt", "r");
if($fichier){
    while(($ligne = fgets($fichier)) !== false){
        $ligne = rtrim($ligne, "\r\n");
        if(validerEmail($ligne)){
            $emailsValides[] = $ligne;
        } else {
            $emailsInvalides[] = $ligne;
        }
    }
    fclose($fichier);
} else {
    die("Erreur lors de l'ouverture du fichier Emails.txt");
}

// Fichier des emails invalides
$fichierInv = fopen("adressesNonValides.txt", "w");
foreach($emailsInvalides as $email){
    fwrite($fichierInv, $email . "\n");
}
fclose($fichierInv);

// Fichier des emails valides triés et sans doublons
$emailsValides = supprimerDoublons($emailsValides);
$emailsValides = trierEmails($emailsValides);

$fichierV = fopen("EmailsT.txt", "w");
foreach($emailsValides as $email){
    fwrite($fichierV, $email . "\n");
}
fclose($fichierV);

// Séparation par domaine
$emailsSepares = [];
foreach($emailsValides as $email){
    $position = strpos($email, "@");
    $domaine = substr($email, $position + 1);
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
?>
