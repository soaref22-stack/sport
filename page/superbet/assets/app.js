document.addEventListener("change", function(event) {
    if (event.target.classList.contains("foto-upload")) {
        const input = event.target;
        const file = input.files[0];
        const id = input.dataset.id;

        if (!file) {
            alert("❌ Selectează un fișier!");
            return;
        }

        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert("❌ Format fișier invalid. Folosește doar JPEG, PNG sau GIF.");
            input.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert("❌ Fișierul este prea mare. Maxim 2MB permis.");
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append("id", id);
        formData.append("foto", file);

        fetch("../../foto/foto_rezultate2.php", {
            method: "POST",
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert("✅ Încărcare reușită!");
                const fotoCell = document.querySelector(`#foto-${id}`);
                if (fotoCell) {
                    fotoCell.innerHTML = `<a href="/sport2/manage/nou/uploads/${data.file}" target="_blank" onclick="openPopup(this.href); return false;">Vezi</a>`;
                }
            } else {
                throw new Error(data.error || "Eroare necunoscută");
            }
        })
        .catch(error => {
            console.error("❌ Eroare la încărcare:", error);
            alert(`❌ Eroare: ${error.message}`);
        });
    }
});

function openPopup(imageUrl) {
    const popup = window.open("", "ImagePopup", "width=600,height=600");
    popup.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Imagine</title>
            <style>
                body { margin:0; text-align:center; background:#f0f0f0; }
                img { max-width:100%; max-height:90vh; margin-top:20px; box-shadow:0 0 10px rgba(0,0,0,0.3); }
            </style>
        </head>
        <body>
            <img src="${imageUrl}" alt="Preview imagine" />
        </body>
        </html>
    `);
    popup.document.close();
}

function validateMoveSelection() {
    const checkboxes = document.querySelectorAll('.match-checkbox:checked');
    const destination = document.getElementById('destination_table').value;

    if (checkboxes.length === 0) {
        alert('Selectează cel puțin un meci pentru a muta!');
        return false;
    }

    if (!destination) {
        alert('Selectează tabela destinație!');
        return false;
    }

    return confirm(`Sigur dorești să muți ${checkboxes.length} meciuri?`);
}

function openNoteWindow(id, currentNote) {
    const escapedNote = (currentNote || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const noteWindow = window.open('', '_blank', 'width=600,height=400');
    noteWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Notă pentru Meciul #${id}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    background: #f5f5f5;
                    margin: 0;
                }
                .container {
                    background: white;
                    padding: 20px;
                    border-radius: 5px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    height: calc(100vh - 40px);
                    box-sizing: border-box;
                    display: flex;
                    flex-direction: column;
                }
                h2 {
                    margin-top: 0;
                    color: #333;
                }
                textarea {
                    width: 100%;
                    flex-grow: 1;
                    padding: 10px;
                    margin: 10px 0;
                    border: 1px solid #ddd;
                    font-size: 14px;
                    resize: none;
                    font-family: inherit;
                }
                .buttons {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    margin-top: 10px;
                }
                button {
                    padding: 8px 15px;
                    cursor: pointer;
                    border: none;
                    border-radius: 4px;
                    font-weight: bold;
                    min-width: 80px;
                }
                .save-btn { background: #4CAF50; color: white; }
                .cancel-btn { background: #f44336; color: white; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Notă pentru Meciul #${id}</h2>
                <textarea id="noteContent" placeholder="Scrie observații aici...">${escapedNote}</textarea>
                <div class="buttons">
                    <button onclick="window.close()" class="cancel-btn">Închide</button>
                    <button onclick="saveNote()" class="save-btn">Salvează</button>
                </div>
            </div>
            <script>
                function saveNote() {
                    const content = document.getElementById('noteContent').value;
                    if (window.opener && !window.opener.closed) {
                        window.opener.postMessage({
                            type: 'saveNote',
                            matchId: ${id},
                            note: content
                        }, '*');
                        setTimeout(() => window.close(), 100);
                    } else {
                        alert('Eroare: Fereastra principală nu este disponibilă.');
                    }
                }
            <\/script>
        </body>
        </html>
    `);
    noteWindow.document.close();
}

window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'saveNote') {
        const form = document.getElementById('matches-form');
        if (!form) return;

        let noteInput = form.querySelector(`input[name="nota[${event.data.matchId}]"]`);
        if (!noteInput) {
            noteInput = document.createElement('input');
            noteInput.type = 'hidden';
            noteInput.name = 'nota[' + event.data.matchId + ']';
            form.appendChild(noteInput);
        }
        noteInput.value = event.data.note;

        let updateInput = form.querySelector(`input[name="update[${event.data.matchId}]"]`);
        if (!updateInput) {
            updateInput = document.createElement('input');
            updateInput.type = 'hidden';
            updateInput.name = 'update[' + event.data.matchId + ']';
            form.appendChild(updateInput);
        }
        updateInput.value = '1';

        form.submit();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('click', function(e) {
            document.querySelectorAll('.match-checkbox').forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    }

    document.querySelectorAll('[name^="update"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const idMatch = this.name.match(/\[(\d+)\]/);
            if (!idMatch) return;

            const id = idMatch[1];
            const psfCheckbox = document.getElementById('psf_check_' + id);

            if (psfCheckbox && psfCheckbox.checked && !confirm('Sigur doriți să actualizați doar cotele PSF?')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value && parseFloat(this.value) < 0) {
                alert('Valoarea trebuie să fie mai mare sau egală cu 0');
                this.value = '';
                this.focus();
            }
        });
    });

    document.querySelectorAll('.note-btn').forEach(btn => {
        const note = btn.getAttribute('title') || btn.textContent;
        if (note) {
            btn.setAttribute('title', note.length > 50 ? note.substring(0, 50) + '...' : note);
        }
    });
});
