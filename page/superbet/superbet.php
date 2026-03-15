<?php
require_once __DIR__ . '/sportx.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superbet - SportX</title>
    <link rel="stylesheet" href="../../../assets/style.css">

    <script src="nota.js"></script>
    <script src="cote.js"></script>

    
</head>
<body>

<script>
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
                    fotoCell.innerHTML = `<a href="/sport2/manage/nou/uploads/${data.file}" target="_blank">Vezi</a>`;
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
    const popup = window.open("", "ImagePopup", "width=900,height=700");
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
</script>

<?php include __DIR__ . '../../../include/menu.php'; ?>

<div class="container">
    <h1>Meciuri Superbet</h1>

    <div class="calendar">
        <?php if (!empty($matches_by_day)): ?>
            <?php foreach ($matches_by_day as $date => $matches): ?>
                <?php if ($date !== 'no_date'): ?>
                    <div class="day-box <?= $date === $selected_day ? 'active' : '' ?>">
                        <a href="?zi=<?= htmlspecialchars(urlencode($date)) ?>">
                            <?= htmlspecialchars($date) ?>
                        </a>
                        <span class="match-count"><?= count($matches) ?></span>
                        <a href="note_zile.php?date=<?= htmlspecialchars(urlencode($date)) ?>" class="note-link" title="Editează nota pentru această zi">📝</a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (isset($matches_by_day['no_date'])): ?>
                <div class="day-box <?= $selected_day === 'no_date' ? 'active' : '' ?>">
                    <a href="?zi=no_date">Fără dată</a>
                    <span class="match-count"><?= count($matches_by_day['no_date']) ?></span>
                    <a href="note_zile.php?date=no_date" class="note-link" title="Editează nota pentru meciurile fără dată">📝</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="no-matches">Nu există meciuri disponibile</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($selected_day) && isset($matches_by_day[$selected_day])): ?>
        <h2>Superbet pentru <?= htmlspecialchars($selected_day === 'no_date' ? 'fără dată' : $selected_day) ?></h2>

        <form method="post" enctype="multipart/form-data" id="matches-form">
<div style="margin-bottom: 15px;">
    <button type="submit" name="muta_toate" class="btn btn-move"
        onclick="return confirm('Mut toate meciurile din ziua aceasta care au score completat?');">
        Mută toate cu score
    </button>
</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Match ID</th>
                            <th>Compară</th>
                            <th>Id_OFF</th>
                            <th>Ora</th>
                            <th>Țara</th>
                            <th>Gazda</th>
                            <th>SB 1</th>
                            <th>SB X</th>
                            <th>SB 2</th>
                            <th>Bet 1</th>
                            <th>Bet X</th>
                            <th>Bet 2</th>
                            <th>Off 1</th>
                            <th>Off X</th>
                            <th>Off 2</th>
                            <th>Oaspeți</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>1 G</th>
                            <th>2 G</th>
                            <th>1P</th>
                            <th>2P</th>
                            <th>P2.5</th>
                            <th>S2.5</th>
                            <th>GG</th>
                            <th>NGG</th>
                            <th>PSF 1</th>
                            <th>PSF X</th>
                            <th>PSF 2</th> 
                            <th>Foto</th>
                            <th>Nota</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $counter = 1;
                    foreach ($matches_by_day[$selected_day] as $row):
                        $row['id'] = $row['id'] ?? '';
                        $row['match_id'] = $row['match_id'] ?? '';
                        $row['id_off'] = $row['id_off'] ?? '';
                        $row['ora_meci'] = $row['ora_meci'] ?? '';
                        $row['gazda'] = $row['gazda'] ?? '';
                        $row['oaspeti'] = $row['oaspeti'] ?? '';
                        $row['score'] = $row['score'] ?? '';
                        $row['status_curent'] = $row['status_curent'] ?? '';
                        $row['foto'] = $row['foto'] ?? '';
                        $row['tara'] = $row['tara'] ?? '';
                        $row['competitie'] = $row['competitie'] ?? '';
                    ?>
                    <tr class="<?= (!empty($row['superbet_1']) || !empty($row['superbet_x']) || !empty($row['superbet_2'])) ? 'sb-highlight' : '' ?>">
                        <td><?= $counter ?></td>

                        <td>
                            <input type="text"
                                   id="match_<?= $row['id'] ?>"
                                   name="match_id[<?= $row['id'] ?>]"
                                   value="<?= htmlspecialchars($row['match_id']) ?>"
                                   class="match-id-input">

                            <a href="#"
                               onclick="let val=document.getElementById('match_<?= $row['id'] ?>').value;
                                        if(val){window.open('https://www.flashscore.com/match/'+val+'/#/odds-comparison/1x2-odds/full-time','popup','width=1000,height=700,resizable=yes,scrollbars=yes');}
                                        return false;">
                               🔗
                            </a>
                        </td>

                        <td>
                            <button type="button"
                                    class="btn-compare"
                                    onclick='window.open("/sport2/manage/nou/pages/calendar/cautaid_off.php?id_off=<?= urlencode($row['id_off']) ?>&gazda=<?= urlencode($row['gazda']) ?>", "_blank")'>
                                Compară
                            </button>
                        </td>

                        <td>
                            <input type="text" name="id_off[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['id_off']) ?>">
                        </td>

                        <td>
                            <input type="text" name="ora[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['ora_meci']) ?>">
                        </td>

                        <td><?= htmlspecialchars($row['tara']) ?></td>
                        

                        <td>
                            <input type="text" name="gazda[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['gazda']) ?>">
                        </td>

                        <td><input type="number" step="0.01" min="0" name="superbet_1[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['superbet_1'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="superbet_x[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['superbet_x'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="superbet_2[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['superbet_2'] ?? '') ?>"></td>

                        <td><input type="number" step="0.01" min="0" name="betano_1[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['betano_1'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="betano_x[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['betano_x'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="betano_2[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['betano_2'] ?? '') ?>"></td>

                        <td><input type="number" step="0.01" min="0" name="ofline1[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['offline_1'] ?? '') ?>"></td>
			<td><input type="number" step="0.01" min="0" name="oflinex[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['offline_x'] ?? '') ?>"></td>
			<td><input type="number" step="0.01" min="0" name="ofline2[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['offline_2'] ?? '') ?>"></td>


                        <td>
                            <input type="text" name="oaspeti[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['oaspeti']) ?>">
                        </td>

                        <td>
                            <input type="text" name="score[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['score']) ?>" pattern="\d+-\d+">
                        </td>

                        <td>
                            <input type="text" name="status[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['status_curent']) ?>">
                        </td>

                        <td><input type="number" step="1" min="0" name="home_goals[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['home_goals'] ?? '') ?>"></td>
                        <td><input type="number" step="1" min="0" name="away_goals[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['away_goals'] ?? '') ?>"></td>
                        <td><input type="number" step="1" min="0" name="home_goals_1h[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['home_goals_1h'] ?? '') ?>"></td>
                        <td><input type="number" step="1" min="0" name="away_goals_1h[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['away_goals_1h'] ?? '') ?>"></td>

                        <td><input type="number" step="0.01" min="0" name="p2_5[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['peste_2_5'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="s2_5[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['sub_2_5'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="da_gg[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['gg_da'] ?? '') ?>"></td>
                        <td><input type="number" step="0.01" min="0" name="nu_gg[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['gg_nu'] ?? '') ?>"></td>
                        
                        
                        <td><input type="number" step="0.01" min="0" name="cota_psf_1[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['cota_psf_1'] ?? '') ?>"></td>
			<td><input type="number" step="0.01" min="0" name="cota_psf_x[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['cota_psf_x'] ?? '') ?>"></td>
			<td><input type="number" step="0.01" min="0" name="cota_psf_2[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['cota_psf_2'] ?? '') ?>"></td>

                        <td id="foto-<?= htmlspecialchars($row['id']) ?>">
                            <?php if (!empty($row['foto'])): ?>
                                <a href="/sport2/manage/nou/uploads/<?= htmlspecialchars($row['foto']) ?>" target="_blank" onclick="openPopup(this.href); return false;">Vezi</a>
                            <?php else: ?>
                                <input type="file" class="foto-upload" data-id="<?= htmlspecialchars($row['id']) ?>" accept="image/jpeg,image/png,image/gif">
                            <?php endif; ?>
                        </td>

                        <td>
                            <button type="button"
                                onclick="openNoteWindow(<?= $row['id'] ?>, `<?= addslashes($row['nota'] ?? '') ?>`)"
                                class="note-btn"
                                data-id="<?= htmlspecialchars($row['id']) ?>"
                                data-note="<?= htmlspecialchars($row['nota'] ?? '') ?>"
                                title="<?= !empty($row['nota']) ? htmlspecialchars(mb_substr($row['nota'], 0, 50, 'UTF-8')) . '...' : 'Adaugă notă' ?>">
                                <?= (!empty($row['nota'])) ? '📝' : '➕' ?>
                            </button>
                        </td>

                        <td class="actions-cell">
    <button type="submit" name="update[<?= $row['id'] ?>]" class="btn btn-update">Update</button>
    <button type="submit" name="delete[<?= $row['id'] ?>]" class="btn btn-delete"
        onclick="return confirm('Sigur vrei să ștergi acest meci?');">
        Șterge
    </button>
</td>
                    </tr>
                    <?php
                    $counter++;
                    endforeach;
                    ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else: ?>
        <p class="no-selection">Selectează o zi din calendar pentru a afișa meciurile.</p>
    <?php endif; ?>
</div>

<script>
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
                .save-btn {
                    background: #4CAF50;
                    color: white;
                }
                .cancel-btn {
                    background: #f44336;
                    color: white;
                }
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

        const noteInput = document.createElement('input');
        noteInput.type = 'hidden';
        noteInput.name = 'nota[' + event.data.matchId + ']';
        noteInput.value = event.data.note;
        form.appendChild(noteInput);

        const updateInput = document.createElement('input');
        updateInput.type = 'hidden';
        updateInput.name = 'update[' + event.data.matchId + ']';
        updateInput.value = '1';
        form.appendChild(updateInput);

        form.submit();
    }
});

document.addEventListener('DOMContentLoaded', function() {
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
</script>

<?php
if (!empty($messages)) {
    echo '<div class="messages">';
    foreach ($messages as $message) {
        echo "<p>" . htmlspecialchars($message) . "</p>";
    }
    echo '</div>';
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>
</body>
</html>
