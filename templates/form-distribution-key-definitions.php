<?php if (!defined('ABSPATH')) exit; ?>
<?php
$system_keys = [];
$user_keys = [];

foreach ($definitions as $definition) {
    if ((int)$definition->user_id === 0) {
        $system_keys[] = $definition;
    } else {
        $user_keys[] = $definition;
    }
}
?>

<div class="vm-wrap">
    <h2>Schlüsseldefinition anlegen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_distribution_key_definition'); ?>
        <input type="hidden" name="vm_action" value="save_distribution_key_definition">

        <p>
            <label for="vm_label">Bezeichnung</label><br>
            <input
                type="text"
                name="vm_label"
                id="vm_label"
                required
                placeholder="z. B. Stellplätze"
            >
        </p>

        <p>
            <label for="vm_unit_code">Einheit</label><br>
            <input
                type="text"
                name="vm_unit_code"
                id="vm_unit_code"
                required
                placeholder="z. B. STEL"
            >
        </p>

        <p>
            <label for="vm_total_value">Von insgesamt</label><br>
            <input
                type="text"
                name="vm_total_value"
                id="vm_total_value"
                required
                placeholder="z. B. 1000,00"
            >
        </p>

        <p>
            <button type="submit">Schlüsseldefinition speichern</button>
        </p>
    </form>

    <?php if (!empty($definitions)) : ?>
        <p>System-Schlüssel: <?php echo count($system_keys); ?></p>
        <p>Eigene Schlüssel: <?php echo count($user_keys); ?></p>

        <h3>👤 Benutzer Schlüsseldefinitionen</h3>
        <table>
            <thead>
                <tr>
                    <th>Bezeichnung</th>
                    <th>Einheit</th>
                    <th>Von insgesamt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_keys as $definition) : ?>
                    <tr>
                        <td><?php echo esc_html($definition->label); ?></td>
                        <td><?php echo esc_html($definition->unit_code); ?></td>
                        <td><?php echo esc_html(number_format((float) $definition->total_value, 2, ',', '.')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>🔒 System-Schlüssel</h3>

        <table>
        <thead>
        <tr>
            <th>Bezeichnung</th>
            <th>Einheit</th>
            <th>Von insgesamt</th>
        </tr>
        </thead>
        <tbody>

        <?php foreach ($system_keys as $definition) : ?>

        <tr style="background:#f5f5f5;">
            <td><?php echo esc_html($definition->label); ?></td>
            <td><?php echo esc_html($definition->unit_code); ?></td>
            <td><?php echo esc_html(number_format((float)$definition->total_value,2,',','.')); ?></td>
        </tr>

        <?php endforeach; ?>

        </tbody>
        </table>
    <?php endif; ?>
</div>