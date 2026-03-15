<?php
require_once __DIR__ . '/../../include/conection.php';
include __DIR__ . '/../../include/menu.php';
require_once __DIR__ . '/lib_import.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$existingDates = get_existing_import_dates($conn);
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$ok       = isset($_GET['ok']) ? (int)$_GET['ok'] : 0;
$dataInfo = $_GET['data_import'] ?? '';
$total    = isset($_GET['total']) ? (int)$_GET['total'] : 0;
$inserted = isset($_GET['inserted']) ? (int)$_GET['inserted'] : 0;
$updated  = isset($_GET['updated']) ? (int)$_GET['updated'] : 0;
$skipped  = isset($_GET['skipped']) ? (int)$_GET['skipped'] : 0;

$deleted  = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;
$uploaded = isset($_GET['uploaded']) ? (int)$_GET['uploaded'] : 0;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Superbet - CSV</title>
    <link rel="stylesheet" href="../../assets/sport.css">
    <link rel="stylesheet" href="import.css">
    <style>
        .stats-box, .pdf-box, .delete-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f7f7f7;
            border: 1px solid #ddd;
        }
        .delete-box { background: #fff5f5; border-color: #f5b5b5; }
        .pdf-box { background: #f5f8ff; border-color: #b9c8ff; }
        .stats-line { margin: 6px 0; }
        .btn-danger {
            background: #d9534f;
            border: none;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-danger:hover { background: #c9302c; }
        .btn-secondary {
            background: #5b74e8;
            border: none;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-secondary:hover { background: #445fd6; }
        .msg.warn { background: #fff8e5; border: 1px solid #f0d98a; }
    </style>
</head>
<body>

<div class="container">
    <h1>Import Superbet - Primul CSV</h1>
    <p class="small">Momentan importăm doar oferta CSV în tabelele noi: <b>import_zile</b> și <b>import_oferte</b>.</p>

    <?php if ($ok === 1): ?>
        <div class="msg ok">
            <b>Import reușit pentru:</b> <?= htmlspecialchars($dataInfo) ?><br>
            Total rânduri: <b><?= $total ?></b><br>
            Inserate: <b><?= $inserted ?></b><br>
            Actualizate: <b><?= $updated ?></b><br>
            Sărite: <b><?= $skipped ?></b>
        </div>
    <?php endif; ?>

    <?php if ($uploaded === 1): ?>
        <div class="msg ok">
            PDF-urile au fost salvate cu succes.
        </div>
    <?php endif; ?>

    <?php if ($deleted === 1): ?>
        <div class="msg warn">
            Ziua selectată a fost ștearsă împreună cu ofertele și PDF-urile ei.
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <div>
            <label for="month">Luna:</label>
            <select id="month" onchange="generateCalendar()">
                <option value="0">Ianuarie</option>
                <option value="1">Februarie</option>
                <option value="2">Martie</option>
                <option value="3">Aprilie</option>
                <option value="4">Mai</option>
                <option value="5">Iunie</option>
                <option value="6">Iulie</option>
                <option value="7">August</option>
                <option value="8">Septembrie</option>
                <option value="9">Octombrie</option>
                <option value="10">Noiembrie</option>
                <option value="11">Decembrie</option>
            </select>
        </div>

        <div>
            <label for="year">Anul:</label>
            <input type="number" id="year" value="<?= $selectedYear ?>" min="2020" max="2035" onchange="generateCalendar()">
        </div>

        <div>
            <button class="btn" type="button" onclick="goToday()">Azi</button>
        </div>
    </div>

    <div class="calendar-wrap">
        <table id="calendar"></table>
    </div>

    <div id="importBox" class="import-box">
        <h3>Import CSV pentru data: <span id="selectedDateText"></span></h3>

        <form action="procesare_oferta.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="data_import" id="data_import">

            <div>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>

            <button type="submit" class="btn">Importă CSV</button>
        </form>

        <div id="statsBox" class="stats-box" style="display:none;">
            <h3>Statistici zi</h3>
            <div class="stats-line">Total meciuri: <b id="stats_total">0</b></div>
            <div class="stats-line">Cu PSF complet: <b id="stats_cu_psf">0</b></div>
            <div class="stats-line">Fără PSF complet: <b id="stats_fara_psf">0</b></div>
            <div class="stats-line">PDF ofertă: <b id="stats_pdf_oferta">-</b></div>
            <div class="stats-line">PDF PSF: <b id="stats_pdf_psf">-</b></div>
        </div>

        <div class="pdf-box">
            <h3>Încarcă PDF-uri pentru ziua selectată</h3>
            <form action="upload_pdfs.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="data_import" id="pdf_data_import">

                <div style="margin:10px 0;">
                    <label>PDF ofertă:</label><br>
                    <input type="file" name="pdf_oferta" accept=".pdf">
                </div>

                <div style="margin:10px 0;">
                    <label>PDF PSF:</label><br>
                    <input type="file" name="pdf_psf" accept=".pdf">
                </div>

                <button type="submit" class="btn-secondary">Salvează PDF-urile</button>
            </form>
        </div>

        <div class="delete-box">
            <h3>Ștergere zi import</h3>
            <form action="sterge_zi.php" method="post" onsubmit="return confirm('Sigur vrei să ștergi ziua selectată, toate ofertele și PDF-urile?');">
                <input type="hidden" name="data_import" id="delete_data_import">
                <button type="submit" class="btn-danger">Șterge ziua</button>
            </form>
        </div>
    </div>
</div>

<script>
const existingDates = <?= json_encode($existingDates, JSON_UNESCAPED_UNICODE) ?>;

function generateCalendar() {
    const month = parseInt(document.getElementById('month').value, 10);
    const year = parseInt(document.getElementById('year').value, 10);
    const calendar = document.getElementById('calendar');

    calendar.innerHTML = '';

    const daysOfWeek = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sa', 'Du'];

    let header = '<tr>';
    for (const d of daysOfWeek) {
        header += `<th>${d}</th>`;
    }
    header += '</tr>';
    calendar.innerHTML = header;

    const jsFirstDay = new Date(year, month, 1).getDay();
    const firstDay = jsFirstDay === 0 ? 6 : jsFirstDay - 1;
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let date = 1;

    for (let week = 0; week < 6; week++) {
        let row = '<tr>';

        for (let day = 0; day < 7; day++) {
            if (week === 0 && day < firstDay) {
                row += '<td></td>';
            } else if (date > daysInMonth) {
                row += '<td></td>';
            } else {
                const dd = String(date).padStart(2, '0');
                const mm = String(month + 1).padStart(2, '0');
                const fullDate = `${dd}.${mm}.${year}`;

                let className = 'day';
                if (existingDates.includes(fullDate)) {
                    className += ' has-import';
                }

                row += `<td class="${className}" onclick="selectDate('${fullDate}', this)">${date}</td>`;
                date++;
            }
        }

        row += '</tr>';
        calendar.innerHTML += row;

        if (date > daysInMonth) break;
    }
}

function selectDate(fullDate, cell) {
    document.querySelectorAll('#calendar td.day').forEach(td => td.classList.remove('selected'));
    cell.classList.add('selected');

    document.getElementById('data_import').value = fullDate;
    document.getElementById('pdf_data_import').value = fullDate;
    document.getElementById('delete_data_import').value = fullDate;
    document.getElementById('selectedDateText').textContent = fullDate;
    document.getElementById('importBox').style.display = 'block';

    loadStats(fullDate);
}

function loadStats(fullDate) {
    fetch('psf_stats.php?data_import=' + encodeURIComponent(fullDate))
        .then(r => r.json())
        .then(data => {
            document.getElementById('stats_total').textContent = data.total || 0;
            document.getElementById('stats_cu_psf').textContent = data.cu_psf || 0;
            document.getElementById('stats_fara_psf').textContent = data.fara_psf || 0;
            document.getElementById('stats_pdf_oferta').textContent = data.pdf_oferta || '-';
            document.getElementById('stats_pdf_psf').textContent = data.pdf_psf || '-';
            document.getElementById('statsBox').style.display = 'block';
        })
        .catch(() => {
            document.getElementById('statsBox').style.display = 'none';
        });
}

function goToday() {
    const t = new Date();
    document.getElementById('month').value = t.getMonth();
    document.getElementById('year').value = t.getFullYear();
    generateCalendar();
}

window.onload = function () {
    const t = new Date();
    document.getElementById('month').value = t.getMonth();
    generateCalendar();
};
</script>

</body>
</html>
