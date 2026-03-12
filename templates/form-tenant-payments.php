<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Offene Mietzahlungen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <?php if (!empty($open_rows)) : ?>
        <form method="post">
            <?php wp_nonce_field('vm_save_open_payments_table'); ?>
            <input type="hidden" name="vm_action" value="save_open_payments_table">
            <?php
            $missing_rent = false;
            foreach ($open_rows as $row) {
                if ((float)$row['total_target'] <= 0) {
                    $missing_rent = true;
                    break;
                }
            }
            ?>

            <?php if ($missing_rent) : ?>
            <div class="vm-warning">
            ⚠ Für mindestens einen Monat konnte keine Soll-Miete berechnet werden.

            Bitte prüfen Sie zuerst:

            • Kaltmiete  
            • Nebenkosten-Vorauszahlung  
            • Heizkosten-Vorauszahlung  

            für dieses Mietverhältnis.
            </div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Objekt</th>
                        <th>Wohnung</th>
                        <th>Mieter</th>
                        <th>Monat</th>
                        <th class="vm-col-usage">Nutzung</th>
                        <th class="vm-col-money">Kalt (€)</th>
                        <th class="vm-col-money">NK (€)</th>
                        <th class="vm-col-money">HK (€)</th>
                        <th class="vm-col-money vm-col-soll">Soll (€)</th>
                        <th>Zahlungseingang</th>
                        <th>Betrag (€)</th>
                        <th>Vollständig gezahlt</th>
                        <th>Notiz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($open_rows as $index => $row) : ?>
                        <?php
                        $existing = $row['existing_payment'] ?? null;
                        $default_payment_date = $existing->payment_date ?? $row['payment_month'];
                        $default_amount_paid = isset($existing->amount_paid)
                            ? (float) $existing->amount_paid
                            : (float) $row['total_target'];
                        $is_error_row = ((float) $row['total_target'] <= 0);
                        ?>
                        <tr class="<?php echo $is_error_row ? 'vm-error-row' : ''; ?>">
                            <td><?php echo esc_html($row['property_name']); ?></td>
                            <td><?php echo esc_html($row['apartment_name']); ?></td>
                            <td><?php echo esc_html($row['tenant_name']); ?></td>
                            <td>
                                <?php echo esc_html(date('d.m.Y', strtotime($row['payment_month']))); ?>
                            <?php if (!$is_error_row) : ?>
                                <input type="hidden" name="vm_rows[<?php echo esc_attr($index); ?>][apartment_tenant_id]" value="<?php echo esc_attr($row['apartment_tenant_id']); ?>">
                                <input type="hidden" name="vm_rows[<?php echo esc_attr($index); ?>][payment_month]" value="<?php echo esc_attr($row['payment_month']); ?>">
                                <input type="hidden" name="vm_rows[<?php echo esc_attr($index); ?>][default_payment_date]" value="<?php echo esc_attr($row['payment_month']); ?>">
                                <input type="hidden" name="vm_rows[<?php echo esc_attr($index); ?>][default_amount_paid]" value="<?php echo esc_attr(number_format((float) $row['total_target'], 2, '.', '')); ?>">
                            <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                echo esc_html(
                                    ((int) ($row['occupied_days'] ?? 0)) . '/' . ((int) ($row['month_days'] ?? 0)) . ' Tage'
                                );
                                ?>
                            </td>
                            <td class="vm-col-money"><?php echo esc_html(number_format((float) $row['cold_rent'], 2, ',', '.')); ?></td>
                            <td class="vm-col-money"><?php echo esc_html(number_format((float) $row['nk_advance'], 2, ',', '.')); ?></td>
                            <td class="vm-col-money"><?php echo esc_html(number_format((float) $row['hk_advance'], 2, ',', '.')); ?></td>
                            <td><strong><?php echo esc_html(number_format((float) $row['total_target'], 2, ',', '.')); ?></strong></td>
                            <td>
                                <input
                                    type="date"
                                    name="vm_rows[<?php echo esc_attr($index); ?>][payment_date]"
                                    value="<?php echo esc_attr($default_payment_date); ?>"
                                    <?php echo $is_error_row ? 'readonly' : ''; ?>
                                >
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="vm_rows[<?php echo esc_attr($index); ?>][amount_paid]"
                                    value="<?php echo esc_attr(str_replace('.', ',', number_format((float) $default_amount_paid, 2, '.', ''))); ?>"
                                    <?php disabled($is_error_row, true); ?>
                                >
                            </td>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="vm_rows[<?php echo esc_attr($index); ?>][is_paid]"
                                        value="1"
                                        <?php checked((int) ($existing->is_paid ?? 0), 1); ?>
                                        <?php disabled($is_error_row, true); ?>
                                    >
                                    ja
                                </label>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="vm_rows[<?php echo esc_attr($index); ?>][note]"
                                    value="<?php echo esc_attr($existing->note ?? ''); ?>"
                                    <?php disabled($is_error_row, true); ?>
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="submit">Offene Zahlungen speichern</button>
            </p>
        </form>
    <?php else : ?>
        <p>Keine offenen Mietzahlungen vorhanden.</p>
    <?php endif; ?>

    <h3>Bereits erfasste Zahlungen</h3>

    <?php if (!empty($payments)) : ?>
        <table>
            <thead>
                <tr>
                    <th>Wohnung</th>
                    <th>Mieter</th>
                    <th>Mietmonat</th>
                    <th>Zahlungseingang</th>
                    <th>Betrag (€)</th>
                    <th>Status</th>
                    <th>Notiz</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment) : ?>
                    <tr>
                        <td><?php echo esc_html($payment->apartment_name); ?></td>
                        <td><?php echo esc_html(trim($payment->salutation . ' ' . $payment->first_name . ' ' . $payment->last_name)); ?></td>
                        <td>
                            <?php echo esc_html(date('m-Y', strtotime($payment->payment_month))); ?>
                        </td>
                        <td>
                            <?php echo esc_html(date('d.m.Y', strtotime($payment->payment_date ?: '—'))); ?>
                        </td>
                        <td><?php echo esc_html(number_format((float) $payment->amount_paid, 2, ',', '.')); ?></td>
                        <td><?php echo (int) $payment->is_paid === 1 ? 'Bezahlt' : 'Offen / Teilzahlung'; ?></td>
                        <td><?php echo esc_html($payment->note); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Noch keine Zahlungen erfasst.</p>
    <?php endif; ?>
</div>