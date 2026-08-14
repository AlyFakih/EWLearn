// <!-- edit for About section    -->

document.addEventListener('DOMContentLoaded', function() {
    function showToast(title, message, type) {
        const bg = type === 'danger' ? 'var(--danger)' : type === 'success' ? 'var(--success)' : 'var(--info)';
        const toast = document.createElement('div');
        toast.style.cssText = 'background:var(--surface);border-left:4px solid ' + bg + ';border-radius:var(--radius-md);box-shadow:var(--shadow-md);padding:12px 16px;margin-bottom:10px;min-width:260px;max-width:360px;color:var(--text);';
        const header = document.createElement('div');
        header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:12px;';
        const titleEl = document.createElement('strong');
        titleEl.textContent = title;
        const closeEl = document.createElement('span');
        closeEl.className = 'toast-close';
        closeEl.style.cssText = 'cursor:pointer;opacity:0.7;';
        closeEl.innerHTML = '&times;';
        header.appendChild(titleEl);
        header.appendChild(closeEl);

        const body = document.createElement('div');
        body.style.cssText = 'font-size:13px;color:var(--text-muted);margin-top:4px;';
        body.textContent = message;

        toast.appendChild(header);
        toast.appendChild(body);

        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;';
            document.body.appendChild(container);
        }
        container.appendChild(toast);

        const remove = function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 200);
        };
        toast.querySelector('.toast-close').addEventListener('click', remove);
        setTimeout(remove, 4000);
    }

    // --- Inline field editing (Name / Contact / Email / Country) ---
    // Persists to the server via php/update_profile.php - previously this only
    // updated the DOM, so the old value always came back after a refresh.
    document.querySelectorAll('.about p[data-field]').forEach(function(paragraph) {
        const field = paragraph.dataset.field;
        const icon = paragraph.querySelector('.fa-pencil-alt');

        function startEditing() {
            if (paragraph.classList.contains('editing')) {
                return;
            }
            paragraph.classList.add('editing');

            const valueSpan = paragraph.querySelector('.field-value');
            const currentValue = valueSpan.textContent.trim();

            const input = document.createElement('input');
            input.type = field === 'email' ? 'email' : 'text';
            input.value = currentValue;
            input.classList.add('editable');
            valueSpan.replaceWith(input);
            input.focus();

            let finished = false;
            function finishEditing() {
                if (finished) {
                    return;
                }
                finished = true;
                paragraph.classList.remove('editing');

                const newValue = input.value.trim();
                const span = document.createElement('span');
                span.className = 'field-value';
                span.textContent = newValue === '' ? currentValue : newValue;
                input.replaceWith(span);

                if (newValue === '' || newValue === currentValue) {
                    return;
                }

                fetch('php/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(newValue)
                })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            showToast('Saved', 'Your profile was updated.', 'success');
                        } else {
                            span.textContent = currentValue;
                            showToast('Error', data.message || 'Failed to update profile.', 'danger');
                        }
                    })
                    .catch(function() {
                        span.textContent = currentValue;
                        showToast('Error', 'Failed to update profile. Please try again.', 'danger');
                    });
            }

            input.addEventListener('blur', finishEditing);
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.blur();
                } else if (e.key === 'Escape') {
                    input.value = currentValue;
                    input.blur();
                }
            });
        }

        icon.addEventListener('click', startEditing);
        paragraph.querySelector('.field-value').addEventListener('click', startEditing);
    });

    // --- Profile photo upload ---
    // The pencil icon over the photo is now a <label for="profileImageInput">,
    // so clicking it opens the native file picker instead of turning the photo
    // area into an editable text field.
    const photoInput = document.getElementById('profileImageInput');
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            const file = photoInput.files[0];
            if (!file) {
                return;
            }

            const formData = new FormData();
            formData.append('profile_image', file);

            fetch('php/update_profile.php', { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('profileImage').src = data.image + '?t=' + Date.now();
                        showToast('Saved', 'Profile photo updated.', 'success');
                    } else {
                        showToast('Error', data.message || 'Failed to update photo.', 'danger');
                    }
                })
                .catch(function() {
                    showToast('Error', 'Failed to update photo. Please try again.', 'danger');
                })
                .finally(function() {
                    photoInput.value = '';
                });
        });
    }
});

// script for adding , update , delete a card

function showPopup() {
    document.getElementById('popup').classList.add('open');
}

function closePopup() {
    document.getElementById('popup').classList.remove('open');
}

function addCard() {
    var subjectIcon = document.getElementById('subjectIcon').value;
    // instructorID comes from $_SESSION['user_id'] server-side, no hidden input needed
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
        <circle cx="38" cy="38" r="36" style="--pct: ${progressPercentage};"></circle>
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

    // Append the new card to the subjects container (replacing the
    // "no skills yet" empty state if this is the first one)
    var container = document.getElementById('subjectContainer');
    var emptyState = container.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }
    container.appendChild(newCard);

    // Close the popup
    closePopup();

    // Make an AJAX request to add the new card
    fetch('profile-dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add&subjectIcon=${subjectIcon}&subjectName=${subjectName}&progressPercentage=${progressPercentage}&experience=${experience}`,
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
        document.getElementById('updateForm').classList.add('open');
    } else {
        alert('Please select a card to update.');
    }
}

function hideUpdateForm() {
    // Hide the update form
    document.getElementById('updateForm').classList.remove('open');
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
        cardToUpdate.querySelector('.progress circle').style.setProperty('--pct', updatedProgress);
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






