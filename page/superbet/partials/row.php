<tr>
    <td><?= e($row['id']) ?></td>

    <td>
        <?= textInput('match_id', $row['id'], $row['match_id']) ?>
    </td>

    <td>
        <?= textInput('id_off', $row['id'], $row['id_off']) ?>
    </td>

    <td>
        <?= textInput('ora', $row['id'], $row['ora']) ?>
    </td>

    <td>
        <?= textInput('gazda', $row['id'], $row['gazda']) ?>
    </td>

    <td>
        <?= numberInput('superbet_1', $row['id'], $row['superbet_1']) ?>
    </td>

    <td>
        <?= numberInput('superbet_x', $row['id'], $row['superbet_x']) ?>
    </td>

    <td>
        <?= numberInput('superbet_2', $row['id'], $row['superbet_2']) ?>
    </td>

    <td>
        <?= numberInput('ofline1', $row['id'], $row['ofline1']) ?>
    </td>

    <td>
        <?= numberInput('oflinex', $row['id'], $row['oflinex']) ?>
    </td>

    <td>
        <?= numberInput('ofline2', $row['id'], $row['ofline2']) ?>
    </td>

    <td>
        <?= textInput('oaspeti', $row['id'], $row['oaspeti']) ?>
    </td>
</tr>
