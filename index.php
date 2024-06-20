<?php
include 'db.php';
include 'navbar.php';

function fetchAndStoreBookData($dbConnection) {
    $apiUrl = 'https://all-books-api.p.rapidapi.com/getBooks';
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'X-RapidAPI-Key: 7ce71ed5a7msha4203985a4cda5bp174b0ajsnb14b7bd8b868',
                'X-RapidAPI-Host: all-books-api.p.rapidapi.com'
            ]
        ]
    ];
    $context = stream_context_create($options);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        return;
    }

    $bookData = json_decode($response, true);

    if (!$bookData) {
        return;
    }

    foreach ($bookData as $book) {
        $isbn = mysqli_real_escape_string($dbConnection, $book['bookIsbn']);
        $title = mysqli_real_escape_string($dbConnection, $book['bookTitle']);
        $author = mysqli_real_escape_string($dbConnection, $book['bookAuthor']);
        $publisher = mysqli_real_escape_string($dbConnection, $book['bookPublisher']);
        $description = mysqli_real_escape_string($dbConnection, $book['bookDescription']);
        $url = mysqli_real_escape_string($dbConnection, $book['amazonBookUrl']);
        $cover = mysqli_real_escape_string($dbConnection, $book['bookImage']);

        $checkQuery = "SELECT * FROM books 
                    WHERE isbn = '$isbn'
                    AND title = '$title'
                    AND author = '$author'
                    AND publisher = '$publisher'
                    AND description = '$description'
                    AND url = '$url'
                    AND cover = '$cover'";
        $checkResult = mysqli_query($dbConnection, $checkQuery);

        if (mysqli_num_rows($checkResult) == 0) {
            $insertQuery = "INSERT INTO books (isbn, title, author, publisher, description, url, cover) 
                            VALUES ('$isbn', '$title', '$author', '$publisher', '$description', '$url', '$cover')";
            mysqli_query($dbConnection, $insertQuery);
        }
    }
}

fetchAndStoreBookData($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Virtue Verse</title>
</head>
<body>
    <h1 class="hidden" id="libraryTitle">Library</h1>
    <div class="loading-container" id="loadingContainer">
        <div class="spinner" id="spinner"></div>
    </div>
    <div class="card-container hidden" id="cardContainer"></div>

    <script>
        async function fetchRandomBook() {
            const bookData = await fetchBookData();

            if (!bookData || bookData.length === 0) {
                alert('The API is down. Please try again later.');
                return;
            }

            const randomIndex = Math.floor(Math.random() * bookData.length);
            const randomBook = bookData[randomIndex];

            const queryString = new URLSearchParams({
                title: randomBook.bookTitle,
                cover: randomBook.bookImage,
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

                if (!response.ok) {
                    throw new Error(`Failed to load resource: the server responded with a status of ${response.status}`);
                }

                const data = await response.json();
                return data;
            } catch (error) {
                console.error(error);
                displayErrorMessage("The API is down.", "Please try again later.");
                return null;
            }
        }

        function appendCard(book) {
            const cardContainer = document.getElementById('cardContainer');
            if (!cardContainer) return;

            const card = document.createElement('div');
            card.className = 'card';

            const imageUrl = book.bookImage ? book.bookImage : 'https://bookstoreromanceday.org/wp-content/uploads/2020/08/book-cover-placeholder.png';

            card.innerHTML = `
                <img src="${imageUrl}" alt="Book Cover">
                <div class="card-body">
                    <h2 class="card-title">${book.bookTitle}</h2>
                    <p class="card-text"><strong>Author:</strong> ${book.bookAuthor}</p>
                    <p class="card-text">${book.bookDescription}</p>
                    <p class="card-text"><strong>Publisher:</strong> ${book.bookPublisher}</p>
                </div>
            `;

            cardContainer.appendChild(card);

            card.addEventListener('click', () => handleBookClick(book));
        }

        function handleBookClick(book) {
            const queryString = new URLSearchParams({
                title: book.bookTitle,
                cover: book.bookImage,
                author: book.bookAuthor,
                description: book.bookDescription,
                publisher: book.bookPublisher,
                url: book.amazonBookUrl,
                book_id: book.bookIsbn
            }).toString();
            window.location.href = `book.php?${queryString}`;
        }

        function displayErrorMessage(message, subtext) {
            const loadingContainer = document.getElementById('loadingContainer');
            loadingContainer.innerHTML = `<div class="error-message-container"><p class="error-message">${message}</p><p class="error-message-subtext">${subtext}</p></div>`;
            document.getElementById('cardContainer').style.display = 'none';
            document.getElementById('libraryTitle').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const bookData = await fetchBookData();
            const cardContainer = document.getElementById('cardContainer');
            const loadingContainer = document.getElementById('loadingContainer');
            const libraryTitle = document.getElementById('libraryTitle');

            if (!bookData || bookData.length === 0) {
                displayErrorMessage("The API is down.", "Please try again later.");
            } else {
                const uniqueBooks = Array.from(new Map(bookData.map(book => [book.bookIsbn, book])).values());

                uniqueBooks.forEach(book => {
                    appendCard(book);
                });

                loadingContainer.style.display = 'none';
                cardContainer.style.display = 'flex';
                libraryTitle.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>