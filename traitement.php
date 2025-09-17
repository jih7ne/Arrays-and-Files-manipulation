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
        if(!in_array($email, $new)){
            $new[] = $email;
        }
    }
    return $new;
}

// Fonction pour trier les emails
function trierEmails($T){
    sort($T);
    return $T;
}

// Lecture du fichier emails.txt 
$target = "emails.txt"; 
if (file_exists($target)) {
    // Nettoyer les anciens fichiers générés
$files = glob("EmailsT.txt"); 
foreach($files as $f) { unlink($f); }

$files = glob("adressesNonValides.txt"); 
foreach($files as $f) { unlink($f); }

$files = glob("emailDeDomaine_*.txt"); 
foreach($files as $f) { unlink($f); }

    $emailsValides = [];
    $emailsInvalides = [];

    $fichier = fopen($target, "r");
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
    }

    // Fichier des emails invalides
    $fichierInv = fopen("adressesNonValides.txt", "w");
    foreach($emailsInvalides as $email){
        fwrite($fichierInv, $email . "\n");
    }
    fclose($fichierInv);

    // Emails valides sans doublons et triés
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
        $domaine = substr(strrchr($email, "@"), 1);
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
}

// Ajout d’un email
if (isset($_POST['action']) && $_POST['action'] === "ajouter") {
    $email = $_POST['email']; 
    if (!validerEmail($email)) {
        echo "Adresse email invalide";
        exit();
    }

    $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (in_array($email, $emails)) {
        echo "Cet email existe déjà";
        exit();
    }

    $emails[] = $email;
    $emails = trierEmails($emails);

    file_put_contents("EmailsT.txt", implode("\n", $emails) . "\n");

    echo "Email ajouté avec succès";
}

// ---- Envoi d’emails avec PHPMailer ----
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['sujet']) && !empty($_POST['message'])) {
    $sujet = $_POST['sujet'];
    $message = $_POST['message'];

    $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($emails)) {
        echo "Aucun email valide";
        exit();
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '0theentirepopulationoftexas0@gmail.com'; 
        $mail->Password   = 'vzkn jdjs jtta cdnp'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('0theentirepopulationoftexas0@gmail.com', 'TP0');
        foreach ($emails as $email) {
            $mail->addBCC($email);
        }

        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = nl2br($message);
        $mail->AltBody = $message;

        $mail->send();
        echo "Message envoyé";
    } catch (Exception $e) {
        echo "Erreur {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Emails</title>
</head>
<body>
<h1>Gestion des Emails</h1>

<!-- Lien pour télécharger emails.txt -->
<p><a href="emails.txt" download>Télécharger emails.txt</a></p>

<h2>Fichiers générés</h2>
<ul>
    <li><a href="EmailsT.txt" download>EmailsT.txt</a></li>
    <li><a href="adressesNonValides.txt" download>adressesNonValides.txt</a></li>
    <?php
    $domaineFiles = glob("emailDeDomaine_*.txt");
    foreach($domaineFiles as $fichierDomaine){
        $nomFichier = basename($fichierDomaine);
        echo "<li><a href='$nomFichier' download>$nomFichier</a></li>";
    }
    ?>
</ul>

<h2>Ajouter une adresse email</h2>
<form action="traitement.php" method="post" onsubmit="return validerFormulaire()">
    <input type="hidden" name="action" value="ajouter">
    <label for="email">Nouvel email :</label><br>
    <input type="email" name="email" id="email" required><br>
    <input type="submit" value="Ajouter">
</form>

<script>
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

<h2>Envoyer un message à tous les emails</h2>
<form action="traitement.php" method="post">
    <label for="sujet">Sujet :</label><br>
    <input type="text" name="sujet" id="sujet" required><br><br>
    <label for="message">Message :</label><br>
    <textarea name="message" id="message" rows="5" cols="40" required></textarea><br>
    <input type="submit" value="Envoyer à tous">
</form>
</body>
</html>
