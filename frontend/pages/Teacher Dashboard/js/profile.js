// <!-- edit for About section    -->

document.addEventListener('DOMContentLoaded', function() {
    const pencilIcons = document.querySelectorAll('.fa-pencil-alt');

    pencilIcons.forEach(function(icon) {
        icon.addEventListener('click', function() {
            const paragraph = icon.parentNode;

            // Check if the paragraph is not already in edit mode
            if (!paragraph.classList.contains('editing')) {
                // Add editing class to the paragraph
                paragraph.classList.add('editing');

                // Create an editable input element
                const input = document.createElement('input');
                input.type = 'text';
                input.value = paragraph.innerText.trim();
                input.classList.add('editable');

                // Replace the <p> element with the editable input
                paragraph.innerHTML = '';
                paragraph.appendChild(input);

                // Focus on the input for immediate editing
                input.focus();

                // Add blur event listener to save changes when the input loses focus
                input.addEventListener('blur', function() {
                    // Remove editing class from the paragraph
                    paragraph.classList.remove('editing');

                    // Replace the input with a new <p> element containing the updated text
                    paragraph.innerHTML = input.value + ' <i class="fas fa-pencil-alt"></i>';

                    // Add click event listener for future edits
                    paragraph.addEventListener('click', enableEditing);
                });
            }
        });
    });

    function enableEditing() {
        const paragraph = this;

        // Check if the paragraph is not already in edit mode
        if (!paragraph.classList.contains('editing')) {
            // Add editing class to the paragraph
            paragraph.classList.add('editing');

            // Create an editable input element
            const input = document.createElement('input');
            input.type = 'text';
            input.value = paragraph.innerText.trim();
            input.classList.add('editable');

            // Replace the <p> element with the editable input
            paragraph.innerHTML = '';
            paragraph.appendChild(input);

            // Focus on the input for immediate editing
            input.focus();

            // Add blur event listener to save changes when the input loses focus
            input.addEventListener('blur', function() {
                // Remove editing class from the paragraph
                paragraph.classList.remove('editing');

                // Replace the input with a new <p> element containing the updated text
                paragraph.innerHTML = input.value + ' <i class="fas fa-pencil-alt"></i>';

                // Add click event listener for future edits
                paragraph.addEventListener('click', enableEditing);
            });
        }
    }

    // Add click event listener for initial editing
    pencilIcons.forEach(function(icon) {
        icon.parentNode.addEventListener('click', enableEditing);
    });
});

// script for adding , update , delete a card
document.addEventListener('DOMContentLoaded', function() {
    var swiper = new Swiper('.swiper-container', {
        slidesPerView: 'auto',
        spaceBetween: 10,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });
});

function showPopup() {
    document.getElementById('popup').style.display = 'block';
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

function addCard() {
    var subjectIcon = document.getElementById('subjectIcon').value;
    var instructorID = document.getElementById('instructorID').value; // Get instructorID from hidden input
    var subjectName = document.getElementById('subjectName').value;
    var progressPercentage = document.getElementById('progressPercentage').value;
    var experience = document.getElementById('experience').value;

    // Create a new card element
    var newCard = document.createElement('div');
    newCard.className = 'eg';
    newCard.innerHTML = `
<span class="material-icons-sharp">${subjectIcon}</span>
<h3>${subjectName}</h3>
<div class="progress">
    <svg>
        <circle cx="38" cy="38" r="36"></circle>
    </svg>
    <div class="number">
        <p>${progressPercentage}%</p>
    </div>
</div>
<small class="text-muted">${experience}</small>
`;

    // Add click event listener to the new card for selecting
    newCard.addEventListener('click', function() {
        selectCard(this);
    });

    // Append the new card to the subjects container
    document.getElementById('subjectContainer').appendChild(newCard);

    // Close the popup
    closePopup();

    // Make an AJAX request to add the new card
    fetch('profile-dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add&subjectIcon=${subjectIcon}&instructorID=${instructorID}&subjectName=${subjectName}&progressPercentage=${progressPercentage}&experience=${experience}`,
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
}



function showUpdateForm() {
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
        document.getElementById('updatedIcon').value = selectedCard.querySelector('span').textContent;
        document.getElementById('updatedTitle').value = selectedCard.querySelector('h3').textContent;
        document.getElementById('updatedProgress').value = selectedCard.querySelector('.progress p').textContent.replace('%', '');
        document.getElementById('updatedDuration').value = selectedCard.querySelector('.text-muted').textContent;

        // Display the update form
        document.getElementById('updateForm').style.display = 'block';
    } else {
        alert('Please select a card to update.');
    }
}

function hideUpdateForm() {
    // Hide the update form
    document.getElementById('updateForm').style.display = 'none';
}

function updateCard() {
    var updatedTitle = document.getElementById('updatedTitle').value;
    var updatedIcon = document.getElementById('updatedIcon').value;
    var updatedProgress = document.getElementById('updatedProgress').value;
    var updatedDuration = document.getElementById('updatedDuration').value;

    var cardToUpdate = document.querySelector('.eg.selected');

    if (cardToUpdate) {
        cardToUpdate.querySelector('h3').innerText = updatedTitle;
        cardToUpdate.querySelector('span').innerText = updatedIcon;
        cardToUpdate.querySelector('.progress p').innerText = updatedProgress + '%';
        cardToUpdate.querySelector('.text-muted').innerText = updatedDuration;

        // Hide the update form after updating
        hideUpdateForm();
        // Remove the 'selected' class after updating
        cardToUpdate.classList.remove('selected');

        // Make an AJAX request to update the card
        fetch('profile-dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&id=${cardToUpdate.dataset.id}&subjectIcon=${updatedIcon}&subjectName=${updatedTitle}&progressPercentage=${updatedProgress}&experience=${updatedDuration}`,
            })
            .then(response => response.text())
            .then(data => console.log(data))
            .catch(error => console.error('Error:', error));
    }
}

function selectCard(card) {
    // Deselect any previously selected card
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
        selectedCard.classList.remove('selected');
    }

    // Select the clicked card
    card.classList.add('selected');
}

function deleteCard(deleteOverlay) {
    // Find the parent card and remove it
    var cardToDelete = deleteOverlay.parentElement;
    cardToDelete.remove();

    // Make an AJAX request to delete the card
    fetch('profile-dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${cardToDelete.dataset.id}`,
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
}

function deleteSelectedCard() {
    // Find the selected card and remove it
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
        selectedCard.remove();

        // Make an AJAX request to delete the selected card
        fetch('profile-dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&id=${selectedCard.dataset.id}`,
            })
            .then(response => response.text())
            .then(data => console.log(data))
            .catch(error => console.error('Error:', error));
    }
}






