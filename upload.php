<?php
// Empêcher les warnings de casser la redirection
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (isset($_FILES['file'])) {
    $target = "Emails.txt";

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        // Après upload, rediriger vers result.php qui fera le traitement
        header("Location: result.php");
        exit();
    } else {
        echo "<h2>❌ Erreur lors de l'upload du fichier.</h2>";
        echo "<a href='index.html'><button>Retour à l'accueil</button></a>";
    }
} else {
    echo "<h2>❌ Aucun fichier sélectionné.</h2>";
    echo "<a href='index.html'><button>Retour à l'accueil</button></a>";
}
?>
