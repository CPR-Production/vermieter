<?php if (!defined('ABSPATH')) exit; ?>
<div class="vm-wrap">
    <h3>Objekt anlegen</h3>
    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_property'); ?>
        <input type="hidden" name="vm_action" value="save_property">

        <p><label>Objektname</label><br><input type="text" name="vm_property_name" required></p>
        <p><label>Straße</label><br><input type="text" name="vm_property_street"></p>
        <p><label>Hausnummer</label><br><input type="text" name="vm_property_house_number"></p>
        <p><label>PLZ</label><br><input type="text" name="vm_property_zip_code"></p>
        <p><label>Ort</label><br><input type="text" name="vm_property_city"></p>
        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Objekt speichern
            </button>
        </p>

    </form>

    <?php if (!empty($properties)) : ?>
        <h4>Vorhandene Objekte</h4>
        <ul>
            <?php foreach ($properties as $property) : ?>
                <li>
                    <?php echo esc_html($property->name); ?>
                    <?php if (!empty($property->city)) : ?>
                        – <?php echo esc_html(trim($property->street . ' ' . $property->house_number . ', ' . $property->zip_code . ' ' . $property->city)); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
