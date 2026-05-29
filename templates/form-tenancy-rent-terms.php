<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Kaltmiete / Staffelmiete erfassen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_tenancy_rent_term'); ?>
        <input type="hidden" name="vm_action" value="save_tenancy_rent_term">

        <p>
            <label for="vm_apartment_tenant_id">Mietverhältnis</label><br>
            <select name="vm_apartment_tenant_id" id="vm_apartment_tenant_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($apartment_tenants as $row) : ?>
                    <option value="<?php echo esc_attr($row->id); ?>">
                        <?php echo esc_html(($row->property_name ?? '') . ' - ' . ($row->apartment_name ?? '') . ' | ' . trim(($row->salutation ?? '') . ' ' . ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_valid_from">Gültig ab</label><br>
            <input type="date" name="vm_valid_from" id="vm_valid_from" required>
        </p>

        <p>
            <label for="vm_cold_rent">Kaltmiete</label><br>
            <input type="text" name="vm_cold_rent" id="vm_cold_rent" required placeholder="z. B. 850,00">
        </p>

        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Kaltmiete speichern
            </button>
        </p>
    </form>

    <?php if (!empty($rent_terms)) : ?>
        <h3>Vorhandene Kaltmieten</h3>
        <table>
            <thead>
                <tr>
                    <th><i class="fa-solid fa-building"></i> Wohnung</th>
                    <th><i class="fa-solid fa-user"></i> Mieter</th>
                    <th><i class="fa-solid fa-calendar"></i> Gültig ab</th>
                    <th><i class="fa-solid fa-euro-sign"></i> Kaltmiete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rent_terms as $term) : ?>
                    <tr>
                        <td><?php echo esc_html($term->apartment_name); ?></td>
                        <td><?php echo esc_html(trim($term->salutation . ' ' . $term->first_name . ' ' . $term->last_name)); ?></td>
                        <td><?php echo esc_html($term->valid_from); ?></td>
                        <td><?php echo esc_html(number_format((float) $term->cold_rent, 2, ',', '.')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>