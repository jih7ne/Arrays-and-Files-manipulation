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

$fichier = fopen($target, "r");
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

// envoyer un email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// loader phpmailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['sujet']) && !empty($_POST['message'])) {
    $sujet = $_POST['sujet'];
    $message = $_POST['message'];

   
    $emails = [];
    $f = fopen("EmailsT.txt", "r");
    if ($f) {
        while (($ligne = fgets($f)) !== false) {
            $ligne = trim($ligne);
            if ($ligne !== "") {
                $emails[] = $ligne;
            }
        }
        fclose($f);
    }

    if (empty($emails)) {
        echo "Aucun email valide";
        exit();
    }

    // Config phpmailer
    $mail = new PHPMailer(true);

    try {
        //settings smtp
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; //smtp setrver
        $mail->SMTPAuth   = true;
        $mail->Username   = '0theentirepopulationoftexas0@gmail.com'; 
        $mail->Password   = 'vzkn jdjs jtta cdnp'; // APP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        $mail->Port       = 587;

       
        $mail->setFrom('0theentirepopulationoftexas0@gmail.com', 'TP0');

        foreach ($emails as $email) {
            $mail->addBCC($email); // BCC
        }
//content
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = nl2br($message); // convert \n en <br>
        $mail->AltBody = $message; 

        $mail->send();
        echo " Message envoyé";
    } catch (Exception $e) {
        echo " Erreur  {$mail->ErrorInfo}";
    }
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
    <!-- Formulaire pour ajouter une nouvelle adresse email -->
<h2>Ajouter une adresse email</h2>
<form action="traitement.php" method="post" onsubmit="return validerFormulaire()">
        <input type="hidden" name="action" value="ajouter">
    <label for="email">Nouvel email :</label><br>
    <input type="email" name="email" id="email" required><br>
    <input type="submit" value="Ajouter">
</form>

<script>
// verif cote serveur
function validerFormulaire() {
    let email = document.getElementById("email").value;
    let regex = /^[a-zA-Z0-9._-]+@[a-z0-9.-]+\.[a-z]{2,}$/;

    if (!regex.test(email)) {
        alert("Adresse email invalide !");
        return false;
    }
    return true;
}
</script>
<!-- Formulaire pour envoyer un message -->
<h2>Envoyer un message à tous les emails</h2>
<form action="traitement.php" method="post">
    <label for="sujet">Sujet :</label><br>
    <input type="text" name="sujet" id="sujet" required><br><br>

    <label for="message">Message :</label><br>
    <textarea name="message" id="message" rows="5" cols="40" required></textarea><br>
    
    <input type="submit" value="Envoyer à tous">
</form>
    <a href="index.html"><button>Retour à l'accueil</button></a>
</body>
</html>


