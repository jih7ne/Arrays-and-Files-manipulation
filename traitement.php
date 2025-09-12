<?php

//Fonction pour valider une adresse email
function validerEmail($email){
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/"; //regex pour un email valide
   return preg_match($reg, $email);
}

//Fonction pour supprimer les doublons
function supprimerDoublons($T){
$new = [];
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

?>