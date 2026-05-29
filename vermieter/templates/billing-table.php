<?php if (!defined('ABSPATH')) exit; ?>
<div class="vm-wrap">
    <h3>Nebenkostenabrechnung</h3>

    <?php if (empty($properties)) : ?>
        <p>Es sind noch keine Objekte vorhanden.</p>
    <?php else : ?>
        <form method="get">
            <p>
                <label>Objekt</label><br>
                <select name="vm_property_id">
                    <?php foreach ($properties as $property) : ?>
                        <option value="<?php echo esc_attr($property->id); ?>" <?php selected($selected_property_id, (int) $property->id); ?>>
                            <?php echo esc_html($property->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label>Jahr</label><br>
                <input type="number" name="vm_year" value="<?php echo esc_attr($selected_year); ?>">
            </p>
            <p><button type="submit">Anzeigen</button></p>
        </form>

        <?php if (empty($billing)) : ?>
            <p>Für dieses Objekt oder Jahr sind noch keine abrechenbaren Daten vorhanden.</p>
        <?php else : ?>
            <table>
                <thead>
                    <tr>
                        <th>Wohnung</th>
                        <th>Betrag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billing as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['name']); ?></td>
                            <td><?php echo esc_html(number_format((float) $row['amount'], 2, ',', '.')); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
