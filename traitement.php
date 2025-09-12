<?php

//Fonction pour valider une adresse email
function validerEmail($email){
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/"; //regex pour un email valide
   return preg_match($reg, $email);
}

//Fonction pour supprimer les doublons
function supprimerDoublons($T){
$new = []; //nouveau tableau 
foreach($T as $email) {
    $existe = false;
    foreach($new as $element){
        if($element == $email){
            $existe = true;
            break;
        }
    }
    if($existe ==false){
        $new[] = $email;
    }
}
return $new;
}

//Fonction pour trier les emails
function trierEmails($T){
    $taille = count($T);
    for ($i = 0; $i < $taille -1; $i++){
        for ($j = $i +1; $j < $taille; $j++){
            if ($T[$i] > $T[$j]){
                $temp = $T[$i];
                $T[$i] = $T[$j];
                $T[$j] = $temp;
            }
        

        }
    }
    return $T;
}
// lecture du fichier
$emailsValides = [];
$emailsInvalides = [];
$fichier = fopen("Emails.txt", "r");
if($fichier){
    while(($ligne = fgets($fichier)) !== false){
        $ligne = rtrim($ligne, "\r\n"); //pour enlever les sauts de ligne
        if(validerEmail($ligne))
            {
                $emailsValides[] = $ligne;
            }
            else{
                $emailsInvalides[] = $ligne;
            }
    }
    fclose($fichier);
}
else{
    echo "Erreur lors de l'ouverture du fichier.";
    exit;
}

//Création du fichier des emails invalides
$fichierInv = fopen("adressesNonValides.txt", "w");
foreach($emailsInvalides as $email){ 
    fwrite($ficherInv, $email . "\n");
}
fclose($fichierInv);
//Création du fichier des emails valides sans doublons et triés
$emailsValides = supprimerDoublons($emailsValides);
$emailsValides = trierEmails($emailsValides);
$fichierV = fopen("EmailsT.txt", "w");
foreach($emailsValides as $email){ 
    fwrite($fichierV, $email . "\n");
}
fclose($fichierV);

//Séparation des emails par domaine
$emailsSepares = [];
foreach($emailsValides as $email){
    // positionner le @
    $position = strpos($email, "@");
    //extraire le domaine
    $domaine = substr($email, $position +1); //la fonction substr permet l'extraction d'une partie d'une chaine de caracteres
    if(!isset($emailsSepares[$domaine])){
        $emailsSepares[$domaine] = [];
    }
    $emailsSepares[$domaine][] = $email;
}
//Création des fichiers par domaine
foreach($emailsSepares as $domaine => $liste){
    $nom = "emailDeDomaine_" .$domaine . ".txt";
    $fichier=fopen($nom, "w");
    foreach($liste as $email){
        fwrite($fichier, $email . "\n");
    }
    fclose($fichier);
}
?>