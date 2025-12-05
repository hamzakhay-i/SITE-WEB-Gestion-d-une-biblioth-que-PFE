<?php 
// Nous incluons la connexion ici, mais la fermons immédiatement, car 
// la connexion sera gérée par le script AJAX (fetch_books.php) pour chaque requête.
include 'db_connect.php'; 
$conn->close(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Bibliothèque - Catalogue</title>
    <style>
        /* Styles de base - Idéalement dans un fichier style.css */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; }
        .header { background-color: #3f51b5; color: white; padding: 20px; text-align: center; }
        .container { width: 80%; margin: 20px auto; }
        .navbar { background-color: #303f9f; padding: 10px 0; text-align: center; }
        .navbar a { color: white; padding: 10px 15px; text-decoration: none; }
        #search-box { width: 100%; padding: 10px; margin-bottom: 20px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #c5cae9; }
        .loading { text-align: center; padding: 20px; color: #555; }
        .disponible { color: green; font-weight: bold; }
        .indisponible { color: red; }
    </style>
</head>
<body>

<div class="header">
    <h1>📚 Catalogue des Livres</h1>
</div>

<div class="navbar">
    <a href="index.php">Catalogue</a>
    <a href="register.php">S'inscrire</a>
    <a href="login.php">Connexion</a>
</div>

<div class="container">
    <h2>Rechercher un Livre</h2>
    
    <input type="text" id="search-box" placeholder="Entrez le titre, l'auteur ou l'ISBN..." onkeyup="fetchBooks()">

    <div id="book-results">
        <p class="loading">Chargement des livres...</p>
    </div>
</div>

<script>
/**
 * Fonction JavaScript pour appeler le script PHP (fetch_books.php) via AJAX.
 */
function fetchBooks() {
    // 1. Récupérer la valeur de recherche
    let search_query = document.getElementById('search-box').value;
    let results_container = document.getElementById('book-results');
    
    // Afficher un message de chargement temporaire
    results_container.innerHTML = '<p class="loading">Recherche en cours...</p>';

    // 2. Créer une nouvelle requête HTTP
    const xhr = new XMLHttpRequest();
    
    // 3. Configurer la requête (méthode GET, URL du script PHP, asynchrone)
    // encodeURIComponent assure que les caractères spéciaux dans la recherche sont gérés correctement
    xhr.open('GET', 'fetch_books.php?query=' + encodeURIComponent(search_query), true);

    // 4. Définir la fonction à exécuter lorsque la réponse est reçue
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Si la requête réussit (code 200), injecter la réponse HTML dans la page
            results_container.innerHTML = xhr.responseText;
        } else {
            // Gérer les erreurs de connexion
            results_container.innerHTML = '<p style="color:red; text-align: center;">Erreur serveur lors du chargement des données. Code: ' + xhr.status + '</p>';
        }
    };

    // 5. Envoyer la requête
    xhr.send();
}

// Lancer la recherche au chargement de la page pour afficher tous les livres initialement
window.onload = fetchBooks;
</script>

</body>
</html>