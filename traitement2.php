<?php
session_start();

// Fonction pour valider une adresse email
function validerEmail($email){
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/";
    return preg_match($reg, $email) === 1;
}

// Fonction pour supprimer les doublons
function supprimerDoublons($T){
    $new = [];
    foreach($T as $ligne) {
        // Extraire l'email pour la comparaison
        $parts = explode(' ', $ligne);
        $email = end($parts);
        
        if(!in_array($ligne, $new)){
            $new[] = $ligne;
        }
    }
    return $new;
}

// Fonction pour trier les emails
function trierEmails($T){
    // Trier par email (dernier element de la ligne)
    usort($T, function($a, $b) {
        $aParts = explode(' ', $a);
        $bParts = explode(' ', $b);
        $aEmail = end($aParts);
        $bEmail = end($bParts);
        
        return strcmp($aEmail, $bEmail);
    });
    return $T;
}

// Fonction pour generer un token de verification
function genererTokenVerification() {
    return bin2hex(random_bytes(32));
}

// Envoi de lien de verification 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function envoyerLienVerification($email, $token, $nom = '', $prenom = '') {
    $lien_verification = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]?action=verifier&token=$token&email=" . urlencode($email);
    
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
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Verification de votre adresse email';
        
        // Personnaliser le message avec le nom et prenom si disponibles
        $salutation = ($nom && $prenom) ? "Cher $prenom $nom," : "Cher utilisateur,";
        
        $mail->Body    = "
            <h2>Verification d'adresse email</h2>
            <p>$salutation</p>
            <p>Merci d'avoir ajoute votre adresse email a notre liste</p>
            <p>Pour confirmer votre adresse, veuillez cliquer sur le lien ci-dessous :</p>
            <p><a href='$lien_verification' style='background-color: #0000ff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Verifier mon adresse email</a></p>
            <p>Ce lien expirera dans 24 heures</p>
        ";
        $mail->AltBody = "$salutation\n\nVeuillez verifier votre adresse email en cliquant sur ce lien: $lien_verification. Ce lien expirera dans 24 heures.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Verification du token
if (isset($_GET['action']) && $_GET['action'] === 'verifier' && isset($_GET['token']) && isset($_GET['email'])) {
    $email = urldecode($_GET['email']);
    $token = $_GET['token'];
    
    if (isset($_SESSION['tokens_verification'][$email]) && 
        $_SESSION['tokens_verification'][$email]['token'] === $token &&
        time() < $_SESSION['tokens_verification'][$email]['expiration']) {
        
        // Recuperer les donnees (nom, prenom, email)
        $nom = $_SESSION['tokens_verification'][$email]['nom'] ?? '';
        $prenom = $_SESSION['tokens_verification'][$email]['prenom'] ?? '';
        $ligne_complete = trim("$nom $prenom $email");
        
        // Ajouter au fichier
        if (file_exists("EmailsT.txt")) {
            $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } else {
            $emails = [];
        }
        
        // Verifier si l'email existe deja
        $emailExiste = false;
        foreach ($emails as $ligne) {
            $parts = explode(' ', $ligne);
            $existingEmail = end($parts);
            if ($existingEmail === $email) {
                $emailExiste = true;
                break;
            }
        }
        
        if (!$emailExiste) {
            $emails[] = $ligne_complete;
            $emails = trierEmails($emails);
            
            // Mettre a jour EmailsT.txt
            file_put_contents("EmailsT.txt", implode("\n", $emails) . "\n");
            
            // Supprimer les anciens fichiers de domaine
            $oldDomainFiles = glob("emailDeDomaine_*.txt");
            foreach ($oldDomainFiles as $f) {
                unlink($f);
            }
            
            // Recreer les fichiers par domaine
            $emailsSepares = [];
            foreach ($emails as $em) {
                $parts = explode(' ', $em);
                $emailOnly = end($parts);
                $domaine = substr(strrchr($emailOnly, "@"), 1);
                if (!isset($emailsSepares[$domaine])) {
                    $emailsSepares[$domaine] = [];
                }
                $emailsSepares[$domaine][] = $em;
            }
            
            foreach ($emailsSepares as $domaine => $liste) {
                $nomFichier = "emailDeDomaine_" . $domaine . ".txt";
                $f = fopen($nomFichier, "w");
                foreach ($liste as $em) {
                    fwrite($f, $em . "\n");
                }
                fclose($f);
            }
        }
        
        // Supprimer le token utilise
        unset($_SESSION['tokens_verification'][$email]);
        
        $_SESSION['message_ajout'] = "Email verifie et ajoute avec succes!";
    } else {
        $_SESSION['message_ajout'] = "Lien de verification invalide ou expire";
    }
    
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Traitement de l'upload du fichier emails.txt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichierEmails'])) {
    if ($_FILES['fichierEmails']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['fichierEmails']['tmp_name'];
        $fileName = $_FILES['fichierEmails']['name'];
        
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'txt') {
            move_uploaded_file($tmpName, 'emails.txt');
            $messageUpload = "Fichier uploadé avec succes!";
            
            // Traiter le fichier 
            $target = "emails.txt"; 
            if (file_exists($target)) {
                // Nettoyer les anciens fichiers 
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
                        $parts = explode(' ', $ligne);
                        
                        // Le dernier element est l'email
                        $email = end($parts);
                        
                        if(validerEmail($email)){
                            $emailsValides[] = $ligne;
                        } else {
                            $emailsInvalides[] = $ligne;
                        }
                    }
                    fclose($fichier);
                }

                // Fichier des emails invalides
                $fichierInv = fopen("adressesNonValides.txt", "w");
                foreach($emailsInvalides as $ligne){
                    fwrite($fichierInv, $ligne . "\n");
                }
                fclose($fichierInv);

                // Emails valides sans doublons et tries
                $emailsValides = supprimerDoublons($emailsValides);
                $emailsValides = trierEmails($emailsValides);

                $fichierV = fopen("EmailsT.txt", "w");
                foreach($emailsValides as $ligne){
                    fwrite($fichierV, $ligne . "\n");
                }
                fclose($fichierV);

                // Separation par domaine
                $emailsSepares = [];
                foreach($emailsValides as $ligne){
                    $parts = explode(' ', $ligne);
                    $email = end($parts);
                    $domaine = substr(strrchr($email, "@"), 1);
                    
                    if(!isset($emailsSepares[$domaine])){
                        $emailsSepares[$domaine] = [];
                    }
                    $emailsSepares[$domaine][] = $ligne;
                }

                // Creer les fichiers par domaine
                foreach($emailsSepares as $domaine => $liste){
                    $nom = "emailDeDomaine_" . $domaine . ".txt";
                    $f = fopen($nom, "w");
                    foreach($liste as $ligne){
                        fwrite($f, $ligne . "\n");
                    }
                    fclose($f);
                }
            }
        } else {
            $messageUpload = "Veuillez uploader un fichier texte (.txt)";
        }
    } else {
        $messageUpload = "Erreur lors de l'upload du fichier";
    }
}

// Traitement de la demande d'ajout d'email
if (isset($_POST['action']) && $_POST['action'] === "demander_ajout") {
    $email = $_POST['email']; 
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    
    if (!validerEmail($email)) {
        $_SESSION['message_ajout'] = "Adresse email invalide";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Verifier si l'email existe deja
    if (file_exists("EmailsT.txt")) {
        $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($emails as $ligne) {
            $parts = explode(' ', $ligne);
            $existingEmail = end($parts);
            if ($existingEmail === $email) {
                $_SESSION['message_ajout'] = "Cet email existe deja";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }

    // Generer et envoyer le token de verification
    $token = genererTokenVerification();
    
    // Initialiser le tableau de tokens 
    if (!isset($_SESSION['tokens_verification'])) {
        $_SESSION['tokens_verification'] = [];
    }
    
    // Stocker le token avec les informations de nom et prenom
    $_SESSION['tokens_verification'][$email] = [
        'token' => $token,
        'expiration' => time() + 86400, // 24 heures
        'nom' => $nom,
        'prenom' => $prenom
    ];
    
    if (envoyerLienVerification($email, $token, $nom, $prenom)) {
        $_SESSION['message_ajout'] = "Un lien de verification a ete envoye a $email. Veuillez verifier votre boite mail.";
    } else {
        $_SESSION['message_ajout'] = "Erreur lors de l'envoi du lien de verification";
        unset($_SESSION['tokens_verification'][$email]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Envoi d'emails avec PHPMailer
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "envoyer_emails" && !empty($_POST['sujet']) && !empty($_POST['message'])) {
    $sujet = $_POST['sujet'];
    $message = $_POST['message'];
    
    if (!file_exists("EmailsT.txt")) {
        $_SESSION['message_envoi'] = "Aucun email valide";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $lignes = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($lignes)) {
        $_SESSION['message_envoi'] = "Aucun email valide";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Envoyer un email séparé à chaque destinataire pour personnalisation
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($lignes as $ligne) {
        $parts = explode(' ', $ligne);
        $email = end($parts);
        
        // Extraire nom et prenom (tous les elements sauf le dernier)
        $nomPrenomParts = array_slice($parts, 0, -1);
        $nom = count($nomPrenomParts) > 1 ? end($nomPrenomParts) : '';
        $prenom = count($nomPrenomParts) > 0 ? implode(' ', array_slice($nomPrenomParts, 0, -1)) : '';
        
        $salutation = ($nom && $prenom) ? "Bonjour $prenom $nom," : "Bonjour,";
        
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
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = $sujet;
            
            // Personnaliser le message pour chaque destinataire
            $messagePersonnalise = "<p><strong>$salutation</strong></p>" . nl2br($message);
            
            $mail->Body = $messagePersonnalise;
            $mail->AltBody = "$salutation\n\n$message";

            if ($mail->send()) {
                $successCount++;
            } else {
                $errorCount++;
            }
        } catch (Exception $e) {
            $errorCount++;
        }
    }
    
    if ($errorCount === 0) {
        $_SESSION['message_envoi'] = "Messages envoyes avec succes a $successCount destinataires";
    } else {
        $_SESSION['message_envoi'] = "Envoi partiellement reussi: $successCount succes, $errorCount echecs";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Recuperer les messages de session
$message_ajout = isset($_SESSION['message_ajout']) ? $_SESSION['message_ajout'] : '';
$message_envoi = isset($_SESSION['message_envoi']) ? $_SESSION['message_envoi'] : '';
$message_upload = isset($messageUpload) ? $messageUpload : '';

// Effacer les messages de session apres les avoir recuperes
unset($_SESSION['message_ajout']);
unset($_SESSION['message_envoi']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Emails</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #0000ff;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #ff0000;
            border: 1px solid #f5c6cb;
        }
        form {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], 
        input[type="email"], 
        input[type="file"], 
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #0000ff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0000cc;
        }
        .fichiers {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }
        .fichier-item {
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 4px;
            text-align: center;
        }
        .verification-section {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px 15px;
            margin: 15px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestion des Emails</h1>
        
        <!-- Section d'upload du fichier emails.txt -->
        <div class="section">
            <h2>Uploader le fichier emails.txt</h2>
            <?php if (!empty($message_upload)): ?>
                <div class="message <?php echo strpos($message_upload, 'Erreur') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message_upload; ?>
                </div>
            <?php endif; ?>
            <form action="" method="post" enctype="multipart/form-data">
                <label for="fichierEmails">Selectionner le fichier emails.txt (format: Nom Prenom email):</label>
                <input type="file" name="fichierEmails" id="fichierEmails" accept=".txt" required>
                <input type="submit" value="Uploader">
            </form>
        </div>
                <!-- Section des fichiers generes -->
        <div class="section">
            <h2>Fichiers generes</h2>
            <div class="fichiers">
                <?php if (file_exists("emails.txt")): ?>
                    <div class="fichier-item">
                        <a href="emails.txt" download>emails.txt</a>
                    </div>
                <?php endif; ?>
                
                <?php if (file_exists("EmailsT.txt")): ?>
                    <div class="fichier-item">
                        <a href="EmailsT.txt" download>EmailsT.txt</a>
                    </div>
                <?php endif; ?>
                
                <?php if (file_exists("adressesNonValides.txt")): ?>
                    <div class="fichier-item">
                        <a href="adressesNonValides.txt" download>adressesNonValides.txt</a>
                    </div>
                <?php endif; ?>
                
                <?php
                $domaineFiles = glob("emailDeDomaine_*.txt");
                foreach($domaineFiles as $fichierDomaine):
                    $nomFichier = basename($fichierDomaine);
                ?>
                    <div class="fichier-item">
                        <a href='<?php echo $nomFichier; ?>' download><?php echo $nomFichier; ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Section d'ajout d'email avec verification -->
        <div class="section">
            <h2>Ajouter une adresse email</h2>
            <?php if (!empty($message_ajout)): ?>
                <div class="message <?php echo strpos($message_ajout, 'Erreur') !== false || strpos($message_ajout, 'invalide') !== false || strpos($message_ajout, 'existe deja') !== false || strpos($message_ajout, 'incorrect') !== false || strpos($message_ajout, 'expire') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message_ajout; ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" onsubmit="return validerFormulaire()">
                <input type="hidden" name="action" value="demander_ajout">
                
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" name="nom" id="nom">
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prenom :</label>
                    <input type="text" name="prenom" id="prenom">
                </div>
                
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <input type="submit" value="Verifier l'email">
            </form>
            
            <?php if (isset($_SESSION['tokens_verification']) && !empty($_SESSION['tokens_verification'])): ?>
                <div class="verification-section">
                    <p>Un lien de verification a ete envoye a votre adresse email. Veuillez verifier votre boite mail.</p>
                    <p>Si vous n'avez pas recu l'email, verifiez votre dossier de spam.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Section d'envoi d'emails -->
        <div class="section">
            <h2>Envoyer un message a tous les emails</h2>
            <?php if (!empty($message_envoi)): ?>
                <div class="message <?php echo strpos($message_envoi, 'Erreur') !== false || strpos($message_envoi, 'echecs') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message_envoi; ?>
                </div>
            <?php endif; ?>
            <form action="" method="post">
                <input type="hidden" name="action" value="envoyer_emails">
                <label for="sujet">Sujet :</label>
                <input type="text" name="sujet" id="sujet" required>
                <label for="message">Message :</label>
                <textarea name="message" id="message" rows="5" required></textarea>
                <input type="submit" value="Envoyer a tous">
            </form>
        </div>
        

    </div>

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
</body>
</html>