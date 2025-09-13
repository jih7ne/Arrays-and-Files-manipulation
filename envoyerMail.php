<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sujet = $_POST['sujet'];
    $message = $_POST['message'];

    // Charger les emails valides
    $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Paramètres de l'expéditeur (à adapter)
    $from = "From: monsite@example.com\r\n";  

    // Envoyer à chaque email
    foreach ($emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            mail($email, $sujet, $message, $from);
        }
    }

    echo "✅ Message envoyé à tous les emails valides ! <br>";
    echo "<a href='index.html'>Retour à l'accueil</a>";
}
?>
