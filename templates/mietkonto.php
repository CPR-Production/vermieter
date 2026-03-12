<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Mietkonto pro Mietverhältnis</h2>

    <form method="get" style="margin-bottom:20px;">
        <p>
            <label for="vm_apartment_tenant_id">Mietverhältnis wählen</label><br>
            <select name="vm_apartment_tenant_id" id="vm_apartment_tenant_id" onchange="this.form.submit()">
                <option value="">Bitte wählen</option>
                <?php foreach ($apartment_tenants as $row) : ?>
                    <option value="<?php echo esc_attr($row->id); ?>" <?php selected((int) $selected_id, (int) $row->id); ?>>
                        <?php
                        echo esc_html(
                            ($row->property_name ?? '') . ' - ' .
                            ($row->apartment_name ?? '') . ' | ' .
                            trim(($row->salutation ?? '') . ' ' . ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''))
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
    </form>

    <?php if (!empty($ledger_rows)) : ?>
        <?php
        $first = $ledger_rows[0];
        ?>
        <p>
            <strong>Objekt:</strong> <?php echo esc_html($first['property_name']); ?><br>
            <strong>Wohnung:</strong> <?php echo esc_html($first['apartment_name']); ?><br>
            <strong>Mieter:</strong> <?php echo esc_html($first['tenant_name']); ?><br>
            <strong>Eingezogen am:</strong> <?php echo esc_html($first['move_in_date'] ?: '—'); ?><br>
            <strong>Ausgezogen am:</strong> <?php echo esc_html($first['move_out_date'] ?: '—'); ?>
        </p>
        <table>
            <thead>
                <tr>
                    <th>Monat</th>
                    <th>Soll (€)</th>
                    <th>Ist (€)</th>
                    <th>Differenz (€)</th>
                    <th>Status</th>
                    <th>Zahlungseingang</th>
                    <th>Notiz</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ledger_rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html(date('m-Y', strtotime($row['payment_month']))); ?></td>
                        <td><?php echo esc_html(number_format((float) $row['soll'], 2, ',', '.')); ?></td>
                        <td><?php echo esc_html(number_format((float) $row['ist'], 2, ',', '.')); ?></td>
                        <td><?php echo esc_html(number_format((float) $row['differenz'], 2, ',', '.')); ?></td>
                        <td><?php echo esc_html($row['status']); ?></td>
                        <td><?php echo esc_html($row['payment_date'] ?: '—'); ?></td>
                        <td><?php echo esc_html($row['note']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $sum_soll = 0;
        $sum_ist = 0;
        $sum_diff = 0;

        foreach ($ledger_rows as $row) {
            $sum_soll += (float) $row['soll'];
            $sum_ist += (float) $row['ist'];
            $sum_diff += (float) $row['differenz'];
        }
        ?>

        <p style="margin-top:15px;">
            <strong>Summe Soll:</strong> <?php echo esc_html(number_format($sum_soll, 2, ',', '.')); ?> €<br>
            <strong>Summe Ist:</strong> <?php echo esc_html(number_format($sum_ist, 2, ',', '.')); ?> €<br>
            <strong>Saldo:</strong> <?php echo esc_html(number_format($sum_diff, 2, ',', '.')); ?> €
        </p>
    <?php elseif ($selected_id > 0) : ?>
        <p>Für dieses Mietverhältnis konnten keine Monatsdaten ermittelt werden.</p>
    <?php else : ?>
        <p>Bitte ein Mietverhältnis auswählen.</p>
    <?php endif; ?>
</div>