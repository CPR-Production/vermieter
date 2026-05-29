<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Kostenkategorie anlegen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_cost_category'); ?>
        <input type="hidden" name="vm_action" value="save_cost_category">

        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>">
                        <?php echo esc_html($property->name . ' - ' . $property->street . ' ' . $property->house_number); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_name">Name der Kategorie</label><br>
            <input type="text" name="vm_name" id="vm_name" required placeholder="z. B. Grundsteuer">
        </p>

        <p>
            <label for="vm_description">Beschreibung</label><br>
            <textarea name="vm_description" id="vm_description" rows="3"></textarea>
        </p>

        <p>
            <label for="vm_allocation_type">Verteilungsart</label><br>
            <select name="vm_allocation_type" id="vm_allocation_type" required>
                <option value="wohnflaeche">Wohnfläche</option>
                <option value="personen">Personen</option>
                <option value="distribution_key">Verteilerschlüssel</option>
            </select>
        </p>

        <p>
            <label for="vm_distribution_key_id">Konkreter Verteilerschlüssel</label><br>
            <select name="vm_distribution_key_id" id="vm_distribution_key_id">
                <option value="">Bitte wählen</option>
                <?php foreach ($distribution_keys as $key) : ?>
                    <option value="<?php echo esc_attr($key->id); ?>">
                        <?php echo esc_html($key->label . ' (' . $key->unit_code . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><small>Nur nötig, wenn Verteilungsart = Verteilerschlüssel.</small>
        </p>

        <p>
            <label>
                <input type="checkbox" name="vm_is_recurring" value="1">
                Wiederkehrende Kategorie
            </label>
        </p>

        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Kostenkategorie speichern
            </button>
        </p>
    </form>

    <?php if (!empty($categories)) : ?>
        <h3>Vorhandene Kategorien</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Objekt-ID</th>
                    <th>Verteilungsart</th>
                    <th>Schlüssel-ID</th>
                    <th>Wiederkehrend</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category) : ?>
                    <tr>
                        <td><?php echo esc_html($category->name); ?></td>
                        <td><?php echo esc_html($category->property_id); ?></td>
                        <td><?php echo esc_html($category->allocation_type); ?></td>
                        <td><?php echo esc_html($category->distribution_key_id ?: '-'); ?></td>
                        <td><?php echo (int) $category->is_recurring === 1 ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>