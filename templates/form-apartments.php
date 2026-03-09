<?php if (!defined('ABSPATH')) exit; ?>
<div class="vm-wrap">
    <h3>Wohnung anlegen</h3>
    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <?php if (empty($properties)) : ?>
        <p>Bitte zuerst ein Objekt anlegen.</p>
    <?php else : ?>
        <form method="post">
            <?php wp_nonce_field('vm_save_apartment'); ?>
            <input type="hidden" name="vm_action" value="save_apartment">

            <p>
                <label>Objekt</label><br>
                <select name="vm_property_id" required>
                    <option value="">Bitte wählen</option>
                    <?php foreach ($properties as $property) : ?>
                        <option value="<?php echo esc_attr($property->id); ?>"><?php echo esc_html($property->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p><label>Wohnungsname</label><br><input type="text" name="vm_apartment_name" required></p>
            <p><label>Wohnfläche</label><br><input type="number" step="0.01" name="vm_wohnflaeche"></p>
            <p><label>Personen</label><br><input type="number" name="vm_personen" min="0"></p>
            <p><button type="submit">Wohnung speichern</button></p>
        </form>
    <?php endif; ?>

    <?php if (!empty($apartments)) : ?>
        <h4>Vorhandene Wohnungen</h4>
        <ul>
            <?php foreach ($apartments as $apartment) : ?>
                <li>
                    <?php echo esc_html($apartment->name); ?>
                    – Fläche: <?php echo esc_html(number_format((float) $apartment->wohnflaeche, 2, ',', '.')); ?> m²
                    – Personen: <?php echo esc_html((string) $apartment->personen); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
