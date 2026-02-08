<?php
require 'config/db.php';

$books = [
    ['The Pragmatic Programmer', 'Andrew Hunt', 'Technology', 5],
    ['Introduction to Algorithms', 'Thomas H. Cormen', 'Technology', 3],
    ['Design Patterns', 'Erich Gamma', 'Technology', 4],
    ['You Don\'t Know JS', 'Kyle Simpson', 'Technology', 8],
    ['Head First Java', 'Kathy Sierra', 'Technology', 6],
    ['1984', 'George Orwell', 'Classic', 10],
    ['To Kill a Mockingbird', 'Harper Lee', 'Classic', 7],
    ['Pride and Prejudice', 'Jane Austen', 'Classic', 5],
    ['Moby Dick', 'Herman Melville', 'Classic', 2],
    ['War and Peace', 'Leo Tolstoy', 'Classic', 3],
    ['A Brief History of Time', 'Stephen Hawking', 'Science', 4],
    ['The Selfish Gene', 'Richard Dawkins', 'Science', 5],
    ['Cosmos', 'Carl Sagan', 'Science', 6],
    ['Sapiens', 'Yuval Noah Harari', 'History', 12],
    ['Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', 'Fiction', 15],
    ['The Hobbit', 'J.R.R. Tolkien', 'Fiction', 9],
    ['Dune', 'Frank Herbert', 'Sci-Fi', 8],
    ['The Alchemist', 'Paulo Coelho', 'Fiction', 10],
    ['Thinking, Fast and Slow', 'Daniel Kahneman', 'Psychology', 6],
    ['Rich Dad Poor Dad', 'Robert Kiyosaki', 'Finance', 20]
];

$count = 0;
foreach ($books as $book) {
    $title = mysqli_real_escape_string($conn, $book[0]);
    $author = mysqli_real_escape_string($conn, $book[1]);
    $category = mysqli_real_escape_string($conn, $book[2]);
    $stock = (int)$book[3];

    // Générer une URL d'image automatique basée sur le titre (OpenLibrary API)
    $title_encoded = str_replace(' ', '_', $book[0]);
    $cover_url = "https://covers.openlibrary.org/b/title/$title_encoded-L.jpg";

    // Vérifier si le livre existe déjà pour éviter les doublons
    $check = mysqli_query($conn, "SELECT id FROM books WHERE title = '$title'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO books (title, author, category, stock, cover_url) VALUES ('$title', '$author', '$category', $stock, '$cover_url')";
        if (mysqli_query($conn, $sql)) {
            $count++;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importation des Livres</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .card { border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0 text-center p-5">
                    <div class="mb-4 display-1">📚</div>
                    <h2 class="card-title mb-3 fw-bold text-success">Importation terminée !</h2>
                    <p class="card-text text-muted mb-4 fs-5"><?php echo $count; ?> nouveaux livres ajoutés avec succès.</p>
                    <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5">Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>