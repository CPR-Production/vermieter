<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Rechnung / Kosten erfassen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <?php if ($selected_property_id > 0) : ?>
        <p style="color:#666; margin-top:-8px;">
            Vorausgewählt aus dem Dashboard.
        </p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_cost'); ?>
        <input type="hidden" name="vm_action" value="save_cost">

        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected((int) $selected_property_id, (int) $property->id); ?>>
                        <?php echo esc_html($property->name . ' - ' . $property->street . ' ' . $property->house_number); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_property_cost_category_id">Kategorie</label><br>
            <select name="vm_property_cost_category_id" id="vm_property_cost_category_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($property_categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->id); ?>">
                        <?php
                        echo esc_html(
                            ($category->property_name ?: 'Objekt') . ' - ' .
                            $category->category_name . ' (' . $category->allocation_type . ')'
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php if (empty($property_categories)) : ?>
            <p style="color:#856404;">Für das gewählte Objekt sind noch keine Kostenkategorien zugeordnet.</p>
        <?php endif; ?>

        <p>
            <label for="vm_name">Rechnungsname</label><br>
            <input type="text" name="vm_name" id="vm_name" required>
        </p>

        <p>
            <label for="vm_betrag">Betrag</label><br>
            <input type="text" name="vm_betrag" id="vm_betrag" required placeholder="z. B. 1250,50">
        </p>

        <p>
            <label for="vm_invoice_date">Rechnungsdatum</label><br>
            <input type="date" name="vm_invoice_date" id="vm_invoice_date" required>
        </p>

        <p>
            <label for="vm_period_start">Abrechnungsbeginn</label><br>
            <input type="date" name="vm_period_start" id="vm_period_start" value="<?php echo esc_attr($period['start']); ?>" required>
        </p>

        <p>
            <label for="vm_period_end">Abrechnungsende</label><br>
            <input type="date" name="vm_period_end" id="vm_period_end" value="<?php echo esc_attr($period['end']); ?>" required>
        </p>

        <p>
            <label for="vm_period_year">Abrechnungsjahr</label><br>
            <input type="number" name="vm_period_year" id="vm_period_year" value="<?php echo esc_attr($period['year']); ?>" required>
        </p>

        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Kosten speichern
            </button>
        </p>
    </form>

    <?php if (!empty($costs)) : ?>
        <h3>Vorhandene Rechnungen</h3>
        <table>
            <thead>
                <tr>
                    <th><i class="fa-solid fa-building"></i> Objekt</th>
                    <th><i class="fa-solid fa-layer-group"></i> Kategorie</th>
                    <th><i class="fa-solid fa-euro-sign"></i> Betrag</th>
                    <th><i class="fa-solid fa-calendar"></i> Datum</th>
                    <th><i class="fa-solid fa-calendar-day"></i> Jahr</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($costs as $cost) : ?>
                    <tr>
                        <td><?php echo esc_html($cost->name); ?></td>
                        <td><?php echo esc_html($cost->property_name ?: $cost->property_id); ?></td>
                        <td><?php echo esc_html($cost->category_name ?: '—'); ?></td>
                        <td><?php echo esc_html(number_format((float) $cost->betrag, 2, ',', '.')); ?></td>
                        <td><?php echo esc_html($cost->invoice_date); ?></td>
                        <td><?php echo esc_html($cost->period_year); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>