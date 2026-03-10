<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Nebenkosten-/Heizkosten-Vorauszahlung erfassen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_tenancy_advance_term'); ?>
        <input type="hidden" name="vm_action" value="save_tenancy_advance_term">

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
            <label for="vm_nk_advance">Nebenkosten-Vorauszahlung</label><br>
            <input type="text" name="vm_nk_advance" id="vm_nk_advance" required placeholder="z. B. 180,00">
        </p>

        <p>
            <label for="vm_hk_advance">Heizkosten-Vorauszahlung</label><br>
            <input type="text" name="vm_hk_advance" id="vm_hk_advance" required placeholder="z. B. 90,00">
        </p>

        <p>
            <button type="submit">Vorauszahlung speichern</button>
        </p>
    </form>

    <?php if (!empty($advance_terms)) : ?>
        <h3>Vorhandene Vorauszahlungen</h3>
        <table>
            <thead>
                <tr>
                    <th>Wohnung</th>
                    <th>Mieter</th>
                    <th>Gültig ab</th>
                    <th>NK</th>
                    <th>HK</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($advance_terms as $term) : ?>
                    <tr>
                        <td><?php echo esc_html($term->apartment_name); ?></td>
                        <td><?php echo esc_html(trim($term->salutation . ' ' . $term->first_name . ' ' . $term->last_name)); ?></td>
                        <td><?php echo esc_html($term->valid_from); ?></td>
                        <td><?php echo esc_html(number_format((float) $term->nk_advance, 2, ',', '.')); ?></td>
                        <td><?php echo esc_html(number_format((float) $term->hk_advance, 2, ',', '.')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>