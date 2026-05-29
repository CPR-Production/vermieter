<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Mietkonto pro Mietverhältnis</h2>

    <?php if (!empty($message)) : ?>
        <p class="vm-message"><?php echo esc_html($message); ?></p>
    <?php endif; ?>

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

        <div class="vm-card" style="margin-top:25px;">
            <h3>Zusatz-Zahlung zur Nebenkostenabrechnung erfassen</h3>
            <p style="margin-top:0;">
                Hier kannst du Ausgleichszahlungen erfassen, die direkt mit einer Nebenkostenabrechnung verrechnet werden.
                Beispiel: Nachzahlung 120,00 € und der Mieter zahlt 120,00 € → Ergebnis wird bei erneuter Berechnung ausgeglichen.
            </p>

            <form method="post">
                <?php wp_nonce_field('vm_save_tenant_special_payment'); ?>
                <input type="hidden" name="vm_action" value="save_tenant_special_payment">
                <input type="hidden" name="vm_apartment_tenant_id" value="<?php echo esc_attr($selected_id); ?>">

                <p>
                    <label for="vm_billing_year">Abrechnungsjahr</label><br>
                    <input type="number" name="vm_billing_year" id="vm_billing_year" min="2000" max="2100" value="<?php echo esc_attr(date('Y') - 1); ?>" required>
                </p>

                <p>
                    <label for="vm_payment_date">Zahlungsdatum</label><br>
                    <input type="date" name="vm_payment_date" id="vm_payment_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </p>

                <p>
                    <label for="vm_payment_type">Art</label><br>
                    <select name="vm_payment_type" id="vm_payment_type">
                        <option value="settlement_payment">Mieter zahlt Nachzahlung</option>
                        <option value="settlement_refund">Guthaben an Mieter ausgezahlt</option>
                    </select>
                </p>

                <p>
                    <label for="vm_amount">Betrag</label><br>
                    <input type="text" name="vm_amount" id="vm_amount" placeholder="z. B. 120,00" required>
                </p>

                <p>
                    <label for="vm_note">Notiz</label><br>
                    <input type="text" name="vm_note" id="vm_note" placeholder="z. B. Ausgleich NK-Abrechnung 2025">
                </p>

                <p>
                    <button type="submit" class="vm-btn-primary">
                        <i class="fa-solid fa-save"></i> Zusatz-Zahlung speichern
                    </button>
                </p>
            </form>
        </div>

        <h3>Erfasste Zusatz-Zahlungen</h3>
        <?php if (!empty($special_payments)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Abrechnungsjahr</th>
                        <th>Zahlungsdatum</th>
                        <th>Art</th>
                        <th>Betrag (€)</th>
                        <th>Notiz</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($special_payments as $payment) : ?>
                        <tr>
                            <td><?php echo esc_html($payment->billing_year); ?></td>
                            <td><?php echo esc_html(!empty($payment->payment_date) ? date('d.m.Y', strtotime($payment->payment_date)) : '—'); ?></td>
                            <td>
                                <?php echo esc_html($payment->payment_type === 'settlement_refund' ? 'Guthaben ausgezahlt' : 'Nachzahlung erhalten'); ?>
                            </td>
                            <td><?php echo esc_html(number_format((float) $payment->amount, 2, ',', '.')); ?></td>
                            <td><?php echo esc_html($payment->note); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Diese Zusatz-Zahlung wirklich löschen?');">
                                    <?php wp_nonce_field('vm_delete_tenant_special_payment'); ?>
                                    <input type="hidden" name="vm_action" value="delete_tenant_special_payment">
                                    <input type="hidden" name="vm_apartment_tenant_id" value="<?php echo esc_attr($selected_id); ?>">
                                    <input type="hidden" name="vm_special_payment_id" value="<?php echo esc_attr($payment->id); ?>">
                                    <button type="submit" class="vm-action-button vm-action-delete" title="Löschen">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Noch keine Zusatz-Zahlungen erfasst.</p>
        <?php endif; ?>

    <?php elseif ($selected_id > 0) : ?>
        <p>Für dieses Mietverhältnis konnten keine Monatsdaten ermittelt werden.</p>
    <?php else : ?>
        <p>Bitte ein Mietverhältnis auswählen.</p>
    <?php endif; ?>
</div>