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
        function fetchBookData(page) {
            return fetch(`https://books-api7.p.rapidapi.com/books?p=${page}`, {
                method: 'GET',
                headers: {
                    'X-RapidAPI-Host': 'books-api7.p.rapidapi.com',
                    'X-RapidAPI-Key': '7ce71ed5a7msha4203985a4cda5bp174b0ajsnb14b7bd8b868',
                },
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                return data;
            })
            .catch(error => console.error('Error fetching data:', error));
        }

        function appendCard(book) {
            const cardContainer = document.getElementById('cardContainer');

            if (!book) {
                if (cardContainer.querySelectorAll('.card').length === 0) {
                    const noMoreBooksMessage = document.createElement('p');
                    noMoreBooksMessage.innerText = 'No more books available';
                    cardContainer.appendChild(noMoreBooksMessage);
                }
                return;
            }

            const card = document.createElement('div');
            card.className = 'card';

            card.addEventListener('click', () => handleBookClick(book));

            card.innerHTML = `
                <img src="${book.cover}" alt="Book Cover">
                <div class="card-body">
                    <h5 class="card-title">${book.title}</h5>
                    <p class="card-text"><strong>Author:</strong> ${book.author.first_name} ${book.author.last_name}</p>
                    <p class="card-text"><strong>Pages:</strong> ${book.pages}</p>
                    <p class="card-text"><strong>Genres:</strong> ${book.genres.join(', ')}</p>
                    <a href="${book.url}" class="btn btn-primary">Buy the book</a>
                </div>
            `;

            cardContainer.appendChild(card);
        }

        function handleBookClick(book) {
            window.location.href = `book.php?title=${encodeURIComponent(book.title)}&author=${encodeURIComponent(book.author.first_name + ' ' + book.author.last_name)}&pages=${book.pages}&genres=${encodeURIComponent(book.genres.join(','))}&cover=${encodeURIComponent(book.cover)}&url=${encodeURIComponent(book.url)}&plot=${encodeURIComponent(book.plot)}&rating=${book.rating}`;
        }

        function handleScroll() {
            const scrollThreshold = 200;
            const scrolledToBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - scrollThreshold;

            if (scrolledToBottom) {
                currentPage++;
                fetchBookData(currentPage)
                    .then(data => {
                        if (data && data.length > 0) {
                            data.forEach(book => {
                                appendCard(book);
                            });
                        } else {
                            appendCard();
                        }
                    });
            }
        }

        window.addEventListener('scroll', handleScroll);

        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', () => {
            fetchBookData(currentPage)
                .then(data => {
                    data.forEach(book => {
                        appendCard(book);
                    });
                });
        });
    </script>
</body>
</html>
