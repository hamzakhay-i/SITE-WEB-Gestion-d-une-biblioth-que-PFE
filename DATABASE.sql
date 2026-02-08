-- Create Database
CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrowings Table
CREATE TABLE borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM('borrowed', 'returned') DEFAULT 'borrowed',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Insert a default Admin (Password: admin123)
INSERT INTO users (name, email, password, role) 
VALUES ('Super Admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert some dummy books
INSERT INTO books (title, author, category, stock) VALUES 
('Clean Code', 'Robert C. Martin', 'Technology', 5),
('The Great Gatsby', 'F. Scott Fitzgerald', 'Classic', 3),
('Intro to PHP', 'John Doe', 'Education', 10),
('The Pragmatic Programmer', 'Andrew Hunt', 'Technology', 5),
('Introduction to Algorithms', 'Thomas H. Cormen', 'Technology', 3),
('Design Patterns', 'Erich Gamma', 'Technology', 4),
('You Don\'t Know JS', 'Kyle Simpson', 'Technology', 8),
('Head First Java', 'Kathy Sierra', 'Technology', 6),
('1984', 'George Orwell', 'Classic', 10),
('To Kill a Mockingbird', 'Harper Lee', 'Classic', 7),
('Pride and Prejudice', 'Jane Austen', 'Classic', 5),
('Moby Dick', 'Herman Melville', 'Classic', 2),
('War and Peace', 'Leo Tolstoy', 'Classic', 3),
('A Brief History of Time', 'Stephen Hawking', 'Science', 4),
('The Selfish Gene', 'Richard Dawkins', 'Science', 5),
('Cosmos', 'Carl Sagan', 'Science', 6),
('Sapiens', 'Yuval Noah Harari', 'History', 12),
('Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', 'Fiction', 15),
('The Hobbit', 'J.R.R. Tolkien', 'Fiction', 9),
('Dune', 'Frank Herbert', 'Sci-Fi', 8),
('The Alchemist', 'Paulo Coelho', 'Fiction', 10),
('Thinking, Fast and Slow', 'Daniel Kahneman', 'Psychology', 6),
('Rich Dad Poor Dad', 'Robert Kiyosaki', 'Finance', 20);
