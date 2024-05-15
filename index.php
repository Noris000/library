<?php
include 'db.php';
include 'navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Virtue Verse</title>
</head>
<body>
    <h1>Library</h1>
    <div class="card-container" id="cardContainer"></div>

    <script>
        async function fetchRandomBook() {
            // Fetch all book data
            const bookData = await fetchBookData();

            // Randomly select a book from the fetched data
            const randomIndex = Math.floor(Math.random() * bookData.length);
            const randomBook = bookData[randomIndex];

            // Redirect to book.php with the random book's details as query parameters
            const queryString = new URLSearchParams({
                title: randomBook.bookTitle,
                author: randomBook.bookAuthor,
                description: randomBook.bookDescription,
                publisher: randomBook.bookPublisher,
                url: randomBook.amazonBookUrl,
                book_id: randomBook.bookIsbn
            }).toString();
            window.location.href = `book.php?${queryString}`;
        }

        async function fetchBookData() {
            const url = 'https://all-books-api.p.rapidapi.com/getBooks';
            const options = {
                method: 'GET',
                headers: {
                    'X-RapidAPI-Key': '7ce71ed5a7msha4203985a4cda5bp174b0ajsnb14b7bd8b868',
                    'X-RapidAPI-Host': 'all-books-api.p.rapidapi.com'
                }
            };

            try {
                const response = await fetch(url, options);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error(error);
            }
        }

        function appendCard(book) {
            const cardContainer = document.getElementById('cardContainer');
            const card = document.createElement('div');
            card.className = 'card';

            card.innerHTML = `
                <img src="${book.bookImage}" alt="Book Cover">
                <div class="card-body">
                    <h5 class="card-title">${book.bookTitle}</h5>
                    <p class="card-text"><strong>Author:</strong> ${book.bookAuthor}</p>
                    <p class="card-text">${book.bookDescription}</p>
                    <p class="card-text"><strong>Publisher:</strong> ${book.bookPublisher}</p>
                    <a href="${book.amazonBookUrl}" class="btn btn-primary">Buy on Amazon</a>
                </div>
            `;

            cardContainer.appendChild(card);

            // Add click event listener
            card.addEventListener('click', () => handleBookClick(book));
        }

        function handleBookClick(book) {
            const queryString = new URLSearchParams({
                title: book.bookTitle,
                author: book.bookAuthor,
                description: book.bookDescription,
                publisher: book.bookPublisher,
                url: book.amazonBookUrl,
                book_id: book.bookIsbn
            }).toString();
            window.location.href = `book.php?${queryString}`;
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const bookData = await fetchBookData();
            bookData.forEach(book => {
                appendCard(book);
            });
        });
    </script>
</body>
</html>
