<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Nebenkostenabrechnung</h2>

    <form method="get" style="margin-bottom:20px;">
        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id">
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected((int) $selected_property_id, (int) $property->id); ?>>
                        <?php
                        echo esc_html(
                            $property->name . ' - ' .
                            $property->street . ' ' .
                            $property->house_number . ', ' .
                            $property->zip_code . ' ' .
                            $property->city
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_year">Jahr</label><br>
            <input type="number" name="vm_year" id="vm_year" value="<?php echo esc_attr($selected_year); ?>" required>
        </p>

        <p>
            <button type="submit">Abrechnung anzeigen</button>
        </p>
    </form>

    <?php if (empty($statement) || empty($statement['property'])) : ?>
        <p>Keine Abrechnungsdaten vorhanden.</p>
        <?php return; ?>
    <?php endif; ?>

    <div style="margin-bottom:20px;">
        <h3>Objekt</h3>
        <p>
            <strong><?php echo esc_html($statement['property']->name); ?></strong><br>
            <?php
            echo esc_html(
                $statement['property']->street . ' ' .
                $statement['property']->house_number . ', ' .
                $statement['property']->zip_code . ' ' .
                $statement['property']->city
            );
            ?><br>
            <strong>Abrechnungsjahr:</strong> <?php echo esc_html($statement['year']); ?>
        </p>
    </div>

    <div style="margin-bottom:25px;">
        <h3>Gesamtkosten des Objekts</h3>

        <?php if (!empty($statement['costs'])) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Rechnung / Position</th>
                        <th>Kategorie</th>
                        <th>Betrag (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statement['costs'] as $cost) : ?>
                        <tr>
                            <td><?php echo esc_html($cost->name); ?></td>
                            <td><?php echo esc_html($cost->property_cost_category_id); ?></td>
                            <td><?php echo esc_html(number_format((float) $cost->betrag, 2, ',', '.')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:10px;">
                <strong>Summe Gesamtkosten:</strong>
                <?php echo esc_html(number_format((float) $statement['cost_total'], 2, ',', '.')); ?> €
            </p>
        <?php else : ?>
            <p>Keine Kosten für dieses Jahr vorhanden.</p>
        <?php endif; ?>
    </div>

    <div>
        <h3>Abrechnung je Mieter</h3>

        <?php if (!empty($statement['grouped_tenant_statements'])) : ?>
            <?php foreach ($statement['grouped_tenant_statements'] as $tenant_statement) : ?>
                <div style="border:1px solid #ddd; padding:15px; margin-bottom:25px;">
                    <h4><?php echo esc_html($tenant_statement['tenant_name']); ?></h4>

                    <p>
                        <strong>Eingezogen am:</strong> <?php echo esc_html($tenant_statement['move_in_date'] ?: '—'); ?><br>
                        <strong>Ausgezogen am:</strong> <?php echo esc_html($tenant_statement['move_out_date'] ?: '—'); ?>
                    </p>

                    <?php if (!empty($tenant_statement['cost_items'])) : ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Kostenposition</th>
                                    <th>Einheit</th>
                                    <th>Gesamtkosten (€)</th>
                                    <th>Anteil (€)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tenant_statement['cost_items'] as $item) : ?>
                                    <tr>
                                        <td><?php echo esc_html($item['cost_name']); ?></td>
                                        <td><?php echo esc_html($item['apartment_name']); ?></td>
                                        <td><?php echo esc_html(number_format((float) $item['total_cost'], 2, ',', '.')); ?></td>
                                        <td><?php echo esc_html(number_format((float) $item['tenant_share'], 2, ',', '.')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p>Keine Kostenanteile vorhanden.</p>
                    <?php endif; ?>

                    <p style="margin-top:12px;">
                        <strong>Summe Kostenanteile:</strong>
                        <?php echo esc_html(number_format((float) $tenant_statement['cost_sum'], 2, ',', '.')); ?> €<br>

                        <strong>Vorauszahlungen NK:</strong>
                        <?php echo esc_html(number_format((float) $tenant_statement['nk_advance_sum'], 2, ',', '.')); ?> €<br>

                        <strong>Vorauszahlungen HK:</strong>
                        <?php echo esc_html(number_format((float) $tenant_statement['hk_advance_sum'], 2, ',', '.')); ?> €<br>

                        <strong>Summe Vorauszahlungen:</strong>
                        <?php echo esc_html(number_format((float) $tenant_statement['advance_sum'], 2, ',', '.')); ?> €<br>

                        <strong>Ergebnis:</strong>
                        <?php echo esc_html(number_format((float) $tenant_statement['balance'], 2, ',', '.')); ?> €
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine Mieterabrechnungen vorhanden.</p>
        <?php endif; ?>
    </div>
</div>