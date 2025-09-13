<?php
// Fonction de validation côté serveur
function validerEmail($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Récupération du champ email
if (isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!validerEmail($email)) {
        echo "❌ Adresse email invalide.";
        exit();
    }

    // Lire le fichier des emails valides
    $emails = file("EmailsT.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Vérifier si l'email existe déjà
    if (in_array($email, $emails)) {
        echo "⚠️ Cet email existe déjà dans la liste.";
        exit();
    }

    // Ajouter l'email
    $emails[] = $email;
    sort($emails); // garder trié
    file_put_contents("EmailsT.txt", implode("\n", $emails));

    echo "✅ Email ajouté avec succès ! <br>";
    echo "<a href='index.html'>Retour à l'accueil</a>";
}
?>
