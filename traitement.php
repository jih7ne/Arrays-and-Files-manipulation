<?php
session_start(); //demarrer une session pour pour garder l etat entre les pages 

// Fonction pour valider une adresse email
function validerEmail($email)
{
    $reg = "/^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/";
    return preg_match($reg, $email) === 1;  //verifier si l email follows the same pattern
}

// Fonction pour supprimer les doublons
function supprimerDoublons($T)
{
    $new = [];
    foreach ($T as $email) {
        if (!in_array($email, $new)) {
            $new[] = $email;
        }
    }
    return $new;
}

// Fonction pour trier les emails
function trierEmails(array $T)
{
    sort($T);
    return $T;
}

function trierLignes(array $lignes)
{
    sort($lignes, SORT_STRING);
    return $lignes;
}

// Fonction pour generer un token de vérification
function genererTokenVerification()
{
    return bin2hex(random_bytes(32)); // secured token de 64 caracteres
}

// Envoi de lien de verification 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function envoyerLienVerification(string $nom, string $prenom, string $email, $token)
{
    //building le lien de verification avec le token et l emaul
    $lien_verification = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]?action=verifier&token=$token&nom=" . urlencode($nom) . "&prenom=" . urlencode($prenom) . "&email=" . urlencode($email);

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
        $mail->Subject = 'Vérification de votre adresse email';
        $mail->Body    = "
            <h2>Verification d'adresse email</h2>
            <p>Bonjour $nom $prenom, </p>
            <p>Merci d'avoir ajoute votre adresse email à notre liste</p>
            <p>Pour confirmer votre adresse, veuillez cliquer sur le lien ci-dessous :</p>
            <p><a href='$lien_verification' style='background-color: #00ff00; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Verifier mon adresse email</a></p>
            <p>Ce lien expirera dans 24 heures</p>
           
        ";
        $mail->AltBody = "Veuillez verifier votre adresse email en cliquant sur ce lien: $lien_verification. Ce lien expirera dans 24 heures.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Verification du token (quand l'utilisateur clique sur le lien)
if (isset($_GET['action']) && $_GET['action'] === 'verifier' && isset($_GET['token']) && isset($_GET['email'])) {
    $email = urldecode($_GET['email']);
    $nom = "";
    $prenom = "";
    if (isset($_GET['nom']) && isset($_GET['prenom'])) {
        $nom = urldecode($_GET['nom']);
        $prenom = urldecode($_GET['prenom']);
    }
    $token = $_GET['token'];

    // verifier si le token est valide et not outdated
    if (
        isset($_SESSION['tokens_verification'][$email]) &&
        $_SESSION['tokens_verification'][$email]['token'] === $token &&
        time() < $_SESSION['tokens_verification'][$email]['expiration']
    ) {

        // Ajouter l email au fichier *EmailsT.txt*
        if (file_exists("EmailsT.txt")) {
            $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } else {
            $emails = [];
        }

        if (!in_array($email, $emails)) {
            $emails[] = $email;
            $emails = trierEmails($emails);

            // update EmailsT.txt
            file_put_contents("EmailsT.txt", implode("\n", $emails) . "\n");

            // Supprimer les anciens fichiers de domaine
            $oldDomainFiles = glob("emailDeDomaine_*.txt");
            foreach ($oldDomainFiles as $f) {
                unlink($f);
            }

            // Recreer les fichiers par domaine
            $emailsSepares = [];
            foreach ($emails as $em) {
                $domaine = substr(strrchr($em, "@"), 1);
                if (!isset($emailsSepares[$domaine])) {
                    $emailsSepares[$domaine] = [];
                }
                $emailsSepares[$domaine][] = $em;
            }

            foreach ($emailsSepares as $domaine => $liste) {
                $nom = "emailDeDomaine_" . $domaine . ".txt";
                $f = fopen($nom, "w");
                foreach ($liste as $em) {
                    fwrite($f, $em . "\n");
                }
                fclose($f);
            }
        }

        //Ajouter le nom, le prenom et l'email au fichier *EmailsTNomPrenom.txt*
        if (file_exists("EmailsTNomPrenom.txt")) {
            $lignes = file("EmailsTNomPrenom.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } else {
            $lignes = [];
        }

        $emailExiste = false;
        foreach ($lignes as $key => $ligne) {
            $parts = preg_split('/\s+/', $ligne);
            foreach ($parts as $key => $part) {
                if (isset($part) && $part === $email) {
                    $emailExiste = true;
                    break;
                }
            }
        }
        if ($emailExiste != true && !empty($nom) && !empty($prenom)) {
            $lignes[] = "$nom\t$prenom\t$email";
            $lignes = trierLignes($lignes);

            // update EmailsTNomPrenom.txt
            file_put_contents("EmailsTNomPrenom.txt", implode("\n", $lignes) . "\n");

            // Supprimer les anciens fichiers de domaine
            $oldDomainFiles = glob("emailDeDomaine_*.txt");
            foreach ($oldDomainFiles as $f) {
                unlink($f);
            }

            $emails = [];
            foreach ($lignes as $key => $ligne) {
                // $parts[0] = nom, $parts[1] = prenom, $parts[2] = email
                $parts = preg_split('/\s+/', $ligne);
                foreach ($parts as $key => $part) {
                    if (str_contains($part, '@')) {
                        $emails[] = $part;
                        continue;
                    }
                }
            }

            // Recreer les fichiers par domaine
            $emailsSepares = [];
            foreach ($emails as $em) {
                $domaine = substr(strrchr($em, "@"), 1);
                if (!isset($emailsSepares[$domaine])) {
                    $emailsSepares[$domaine] = [];
                }
                $emailsSepares[$domaine][] = $em;
            }

            foreach ($emailsSepares as $domaine => $liste) {
                $nom = "emailDeDomaine_" . $domaine . ".txt";
                $f = fopen($nom, "w");
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
        echo 'token expiré: ';
        var_dump($_SESSION['tokens_verification']);
        //var_dump()
        $_SESSION['message_ajout'] = "Lien de verification invalide ou expire";
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Traitement de lupload du fichier emails.txt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichierEmails'])) {
    if ($_FILES['fichierEmails']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['fichierEmails']['tmp_name'];
        $fileName = $_FILES['fichierEmails']['name'];

        // verifier l extension
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'txt') {
            move_uploaded_file($tmpName, 'emails.txt');
            $messageUpload = "Fichier uploadé avec succès!";

            // Traiter le fichier 
            $target = "emails.txt";
            if (file_exists($target)) {
                // Nettoyer les anciens fichiers 
                $files = glob("EmailsT.txt");
                foreach ($files as $f) {
                    unlink($f);
                }

                $files = glob("adressesNonValides.txt");
                foreach ($files as $f) {
                    unlink($f);
                }

                $files = glob("emailDeDomaine_*.txt");
                foreach ($files as $f) {
                    unlink($f);
                }

                $emailsValides = [];
                $emailsInvalides = [];
                $fichier = fopen($target, "r");
                if ($fichier) {
                    while (($ligne = fgets($fichier)) !== false) {
                        $ligne = rtrim($ligne, "\r\n");
                        if (validerEmail($ligne)) {
                            $emailsValides[] = $ligne;
                        } else {
                            $emailsInvalides[] = $ligne;
                        }
                    }
                    fclose($fichier);
                }

                // Fichier des emails invalides
                $fichierInv = fopen("adressesNonValides.txt", "w");
                foreach ($emailsInvalides as $email) {
                    fwrite($fichierInv, $email . "\n");
                }
                fclose($fichierInv);

                // Emails valides sans doublons et tries
                $emailsValides = supprimerDoublons($emailsValides);
                $emailsValides = trierEmails($emailsValides);

                $fichierV = fopen("EmailsT.txt", "w");
                foreach ($emailsValides as $email) {
                    fwrite($fichierV, $email . "\n");
                }
                fclose($fichierV);

                // Separation par domaine
                $emailsSepares = [];
                foreach ($emailsValides as $email) {
                    $domaine = substr(strrchr($email, "@"), 1);
                    if (!isset($emailsSepares[$domaine])) {
                        $emailsSepares[$domaine] = [];
                    }
                    $emailsSepares[$domaine][] = $email;
                }

                // les fichiers par domaine
                foreach ($emailsSepares as $domaine => $liste) {
                    $nom = "emailDeDomaine_" . $domaine . ".txt";
                    $f = fopen($nom, "w");
                    foreach ($liste as $email) {
                        fwrite($f, $email . "\n");
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
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];

    if (!validerEmail($email)) {
        $_SESSION['message_ajout'] = "Adresse email invalide";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    //Verifier si les noms et prenoms ne sont pas vides
    if (
        !isset($_POST['nom']) || !isset($_POST['prenom']) ||
        !trim($_POST['nom']) || !trim($_POST['prenom'])
    ) {
        $_SESSION['message_ajout'] = "Nom ou prenom n'existe pas.";
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit();
    }

    // verifier si l email existe deja
    if (file_exists("EmailsT.txt")) {
        $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($email, $emails)) {
            $_SESSION['message_ajout'] = "Cet email existe ";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // generer et envoyer le token de verification
    $token = genererTokenVerification();

    // Initialiser le tableau de tokens 
    if (!isset($_SESSION['tokens_verification'])) {
        $_SESSION['tokens_verification'] = [];
    }

    // Stocker le token 
    $_SESSION['tokens_verification'][$email] = [
        'token' => $token,
        'expiration' => time() + 86400 // 24 heures
    ];

    if (envoyerLienVerification($nom, $prenom, $email, $token)) {
        $_SESSION['message_ajout'] = "Un lien de vérification a été envoyé à $email. Veuillez vérifier votre boîte mail.";
    } else {
        $_SESSION['message_ajout'] = "Erreur lors de l'envoi du lien de vérification";
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

    $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($emails)) {
        $_SESSION['message_envoi'] = "Aucun email valide";
        header("Location: " . $_SERVER['PHP_SELF']);
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
        $_SESSION['message_envoi'] = "Message envoyé avec succès";
    } catch (Exception $e) {
        $_SESSION['message_envoi'] = "Erreur lors de l'envoi: {$mail->ErrorInfo}";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// recuperer les messages de session
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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
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
            color: #00ff00;
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
            background-color: #0f0;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0f0;
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
                <label for="fichierEmails">Sélectionner le fichier emails.txt:</label>
                <input type="file" name="fichierEmails" id="fichierEmails" accept=".txt" required>
                <input type="submit" value="Uploader">
            </form>
        </div>

        <!-- Section d'ajout d'email avec vérification -->
        <div class="section">
            <h2>Ajouter une adresse email</h2>
            <?php if (!empty($message_ajout)): ?>
                <div class="message <?php echo strpos($message_ajout, 'Erreur') !== false || strpos($message_ajout, 'invalide') !== false || strpos($message_ajout, 'existe déjà') !== false || strpos($message_ajout, 'incorrect') !== false || strpos($message_ajout, 'expiré') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message_ajout; ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" onsubmit="return validerFormulaire()">
                <input type="hidden" name="action" value="demander_ajout">
                <div class="field">
                    <label for="email">Nouvel email :</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="field">
                    <label for="nom">Entrer le nom :</label>
                    <input type="text" name="nom" id="nom" required>
                </div>
                <div class="field">
                    <label for="prenom">Entrer le prenom :</label>
                    <input type="text" name="prenom" id="prenom" required>
                </div>
                <input type="submit" value="Vérifier l'email">
            </form>

            <?php if (isset($_SESSION['tokens_verification']) && !empty($_SESSION['tokens_verification'])): ?>
                <div class="verification-section">
                    <p>Un lien de vérification a été envoyé à votre adresse email. Veuillez vérifier votre boîte mail.</p>
                    <p>Si vous n'avez pas reçu l'email, vérifiez votre dossier de spam.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section d'envoi d'emails -->
        <div class="section">
            <h2>Envoyer un message à tous les emails</h2>
            <?php if (!empty($message_envoi)): ?>
                <div class="message <?php echo strpos($message_envoi, 'Erreur') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message_envoi; ?>
                </div>
            <?php endif; ?>
            <form action="" method="post">
                <input type="hidden" name="action" value="envoyer_emails">
                <label for="sujet">Sujet :</label>
                <input type="text" name="sujet" id="sujet" required>
                <label for="message">Message :</label>
                <textarea name="message" id="message" rows="5" required></textarea>
                <input type="submit" value="Envoyer à tous">
            </form>
        </div>

        <!-- Section des fichiers générés -->
        <div class="section">
            <h2>Fichiers générés</h2>
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
                foreach ($domaineFiles as $fichierDomaine):
                    $nomFichier = basename($fichierDomaine);
                ?>
                    <div class="fichier-item">
                        <a href='<?php echo $nomFichier; ?>' download><?php echo $nomFichier; ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
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