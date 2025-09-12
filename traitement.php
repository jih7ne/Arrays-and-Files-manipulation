<?php

//Fonction pour valider une adresse email
function validerEmail($email){
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/"; //regex pour un email valide
   return preg_match($reg, $email);
}
?>