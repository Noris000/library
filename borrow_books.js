var cart = [];

// JavaScript function to handle adding books to the cart
function addToCart(bookId) {
    // Check if the book is already in the cart
    if (cart.indexOf(bookId) === -1) {
        // If not, add the book to the cart
        cart.push(bookId);

        // Update the cart icon and display
        updateCartDisplay();
    } else {
        // If the book is already in the cart, display a message or take appropriate action
        alert("This book is already in the cart.");
    }
}

// JavaScript function to remove checked books from the cart
function removeCheckedBooks() {
    // Create an array to store the indices of checked books
    var checkedIndices = [];

    // Find the indices of checked books
    for (var i = 0; i < cart.length; i++) {
        var checkbox = document.getElementById('book-checkbox-' + cart[i]);
        if (checkbox.checked) {
            checkedIndices.push(i);
        }
    }

    // Remove the checked books from the cart in reverse order
    for (var i = checkedIndices.length - 1; i >= 0; i--) {
        cart.splice(checkedIndices[i], 1);
    }

    // Update the cart icon and display
    updateCartDisplay();
}

// JavaScript function to borrow checked books from the cart
function borrowCheckedBooks() {
    var checkedBooks = [];
    var checkboxes = document.getElementsByClassName('book-checkbox');

    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            checkedBooks.push(cart[i]);
        }
    }

    if (checkedBooks.length === 0) {
        alert("No books selected for borrowing.");
        return;
    }

    // Prepare the data to be sent as JSON
    var data = {
        borrow_books: '1',
        checked_books: checkedBooks
    };
    console.log(data);
    // Send an AJAX request to the PHP script to handle book borrowing
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'borrow_books.php', true);
    
    // Set the request headers
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // Add a custom header to identify AJAX requests

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                // alert(xhr.responseText);

                // Handle the response from the server
                var responseText = xhr.responseText;
                // var responseElement = document.createElement('div');
                // responseElement.innerHTML = responseText;

                // // Append the response to the body
                // document.body.appendChild(responseElement);

                checkedBooks.forEach(function(bookId) {
                    // Find the title element with the matching id
                    var titleElement = document.getElementById('title-' + bookId);
                    
                    // Check if the title element exists
                    if (titleElement) {
                        // Get the parent element of the title and remove it
                        var parentElement = titleElement.parentElement;
                        if (parentElement) {
                            parentElement.remove();
                        }
                    }
                });
                

                // Clear the cart or update the cart display as needed
                cart = [];
                updateCartDisplay();
            } else {
                // Handle errors if the request fails
                alert("An error occurred while processing the request.");
            }
        }
    };

    // Send the JSON data
    xhr.send(JSON.stringify(data));
}


// JavaScript function to update the cart icon and display
function updateCartDisplay() {
    var cartIcon = document.getElementById('cart-icon');
    var cartPopover = document.getElementById('cart-popover');
    var cartItemsDiv = document.getElementById('cart-items');

    // Update the cart icon
    if (cart.length > 0) {
        cartIcon.classList.add('has-items');
    } else {
        cartIcon.classList.remove('has-items'); // Remove the class if the cart is empty
    }

    // Update the cart items in the popover
    if (cart.length > 0) {
        cartItemsDiv.innerHTML = ''; // Clear the cart items first
        cartItemsDiv.innerHTML += '<strong>Cart:</strong><br>';
        for (var i = 0; i < cart.length; i++) {
            var bookTitle = document.getElementById('title-' + cart[i]).textContent;
            cartItemsDiv.innerHTML += '<label><input type="checkbox" class="book-checkbox" id="book-checkbox-' + cart[i] + '"> ' + bookTitle + '</label><br>';
        }

        // Adds a container div for buttons
        cartItemsDiv.innerHTML += '<div class="cart-buttons">' +
            '<button class="remove-button" onclick="removeCheckedBooks()">Remove Checked</button>' +
            '<button class="borrow-button" onclick="borrowCheckedBooks()">Borrow Checked</button>' +
            '</div>';
    } else {
        cartItemsDiv.innerHTML = 'No items in the cart.';
    }

    // Show or hide the cart popover
    if (cart.length > 0) {
        cartPopover.style.display = 'block';
    } else {
        cartPopover.style.display = 'none';
    }
}