<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Mieter einer Wohnung zuordnen</h2>

    <?php
    $selected_year = isset($selected_year) ? (int) $selected_year : (int) current_time('Y');
    $apartments = isset($apartments) && is_array($apartments) ? $apartments : [];
    $tenants = isset($tenants) && is_array($tenants) ? $tenants : [];
    ?>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="get" style="margin-bottom:20px;">
        <p>
            <label for="vm_year">Jahr für Anzeige / Leerstand</label><br>
            <input type="number" name="vm_year" id="vm_year" value="<?php echo esc_attr($selected_year); ?>" required>
            <button type="submit">Jahr anzeigen</button>
        </p>
    </form>

    <form method="post">
        <?php wp_nonce_field('vm_save_apartment_tenant'); ?>
        <input type="hidden" name="vm_action" value="save_apartment_tenant">
        <input type="hidden" name="vm_year" value="<?php echo esc_attr($selected_year); ?>">

        <p>
            <label for="vm_apartment_id">Wohnung</label><br>
            <select name="vm_apartment_id" id="vm_apartment_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($apartments as $apartment) : ?>
                    <option value="<?php echo esc_attr($apartment->id); ?>">
                        <?php echo esc_html($apartment->property_name . ' - ' . $apartment->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_tenant_id">Mieter</label><br>
            <select name="vm_tenant_id" id="vm_tenant_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($tenants as $tenant) : ?>
                    <option value="<?php echo esc_attr($tenant->id); ?>">
                        <?php echo esc_html(trim($tenant->salutation . ' ' . $tenant->first_name . ' ' . $tenant->last_name)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_move_in_date">Einzugsdatum</label><br>
            <input type="date" name="vm_move_in_date" id="vm_move_in_date" required>
        </p>

        <p>
            <label for="vm_move_out_date">Auszugsdatum</label><br>
            <input type="date" name="vm_move_out_date" id="vm_move_out_date">
        </p>

        <p>
            <button type="submit">Zuordnung speichern</button>
        </p>
    </form>

    <?php if (!empty($apartments)) : ?>
        <h3>Wohnungsnutzung ab Kaufdatum / Bestandsbeginn</h3>
        <p>Die Übersicht zeigt jetzt die vollständige Nutzungshistorie je Wohnung: <strong>Bestand ab Kaufdatum</strong>, <strong>Vermietung</strong> und <strong>Leerstand</strong>. Zusätzlich wird das ausgewählte Jahr <?php echo esc_html((string) $selected_year); ?> separat zusammengefasst.</p>

        <?php foreach ($apartments as $apartment) : ?>
            <?php
            $full_timeline = Vermieter_Apartment_Tenants::get_full_timeline_with_vacancies_by_apartment($apartment->id);
            $year_timeline = Vermieter_Apartment_Tenants::get_timeline_with_vacancies_by_apartment_and_year($apartment->id, $selected_year);
            $year_usage = Vermieter_Apartment_Tenants::get_usage_summary_by_apartment_and_year($apartment->id, $selected_year);

            $full_tenant_days = 0;
            $full_vacancy_days = 0;

            foreach ($full_timeline as $item) {
                if (($item['type'] ?? '') === 'tenant') {
                    $full_tenant_days += (int) ($item['days'] ?? 0);
                }

                if (($item['type'] ?? '') === 'vacancy') {
                    $full_vacancy_days += (int) ($item['days'] ?? 0);
                }
            }

            $has_inventory_dates = !empty($apartment->acquisition_date) || !empty($apartment->disposal_date);
            ?>

            <div style="margin:24px 0 32px; padding:16px; border:1px solid #dcdcde; background:#fff;">
                <h4 style="margin-top:0;">
                    <?php echo esc_html($apartment->property_name . ' - ' . $apartment->name); ?>
                </h4>

                <p style="margin:0 0 12px;">
                    <strong>Im Bestand seit:</strong> <?php echo esc_html(vm_format_date($apartment->acquisition_date ?? '')); ?>
                    &nbsp;|&nbsp;
                    <strong>Im Bestand bis:</strong> <?php echo esc_html(vm_format_date($apartment->disposal_date ?? '')); ?>
                    &nbsp;|&nbsp;
                    <strong>Status <?php echo esc_html((string) $selected_year); ?>:</strong> <?php echo esc_html($year_usage['status'] ?? '—'); ?>
                </p>

                <?php if (!$has_inventory_dates) : ?>
                    <p style="margin:0 0 12px; color:#8a6d3b;">
                        Hinweis: Für eine lückenlose Historie ab Kauf solltest du bei dieser Wohnung das Feld <strong>„Kaufdatum / im Bestand seit“</strong> in den Wohnungsdaten hinterlegen.
                    </p>
                <?php endif; ?>

                <table style="margin-bottom:16px;">
                    <thead>
                        <tr>
                            <th>Auswertung</th>
                            <th>Bestandstage</th>
                            <th>Vermietet</th>
                            <th>Leerstand</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Gesamtlaufzeit</strong></td>
                            <td><?php echo esc_html(!empty($full_timeline) ? (string) ($full_tenant_days + $full_vacancy_days) : '0'); ?></td>
                            <td><?php echo esc_html((string) $full_tenant_days); ?></td>
                            <td><?php echo esc_html((string) $full_vacancy_days); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo esc_html((string) $selected_year); ?></strong></td>
                            <td><?php echo esc_html((string) ($year_usage['inventory_days'] ?? 0)); ?></td>
                            <td><?php echo esc_html((string) ($year_usage['tenant_days'] ?? 0)); ?></td>
                            <td><?php echo esc_html((string) ($year_usage['vacancy_days'] ?? 0)); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h5 style="margin:16px 0 8px;">Gesamte Nutzungshistorie</h5>
                <table style="margin-bottom:16px;">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Von</th>
                            <th>Bis</th>
                            <th>Tage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($full_timeline)) : ?>
                            <?php foreach ($full_timeline as $item) : ?>
                                <?php if (($item['type'] ?? '') === 'vacancy') : ?>
                                    <tr style="background:#fff8e1;">
                                        <td><strong>Leerstand</strong></td>
                                        <td><?php echo esc_html(vm_format_date($item['start_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html(vm_format_date($item['end_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($item['days'] ?? 0)); ?></td>
                                    </tr>
                                <?php else : ?>
                                    <tr>
                                        <td><?php echo esc_html($item['label'] ?? 'Mieter'); ?></td>
                                        <td><?php echo esc_html(vm_format_date($item['start_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html(vm_format_date($item['end_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($item['days'] ?? 0)); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4">Noch keine vollständige Historie vorhanden.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h5 style="margin:16px 0 8px;">Jahresansicht <?php echo esc_html((string) $selected_year); ?></h5>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Von</th>
                            <th>Bis</th>
                            <th>Tage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($year_timeline)) : ?>
                            <?php foreach ($year_timeline as $item) : ?>
                                <?php if (($item['type'] ?? '') === 'vacancy') : ?>
                                    <tr style="background:#fff8e1;">
                                        <td><strong>Leerstand</strong></td>
                                        <td><?php echo esc_html(vm_format_date($item['start_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html(vm_format_date($item['end_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($item['days'] ?? 0)); ?></td>
                                    </tr>
                                <?php else : ?>
                                    <tr>
                                        <td><?php echo esc_html($item['label'] ?? 'Mieter'); ?></td>
                                        <td><?php echo esc_html(vm_format_date($item['start_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html(vm_format_date($item['end_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($item['days'] ?? 0)); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php elseif (!empty($year_usage['in_inventory'])) : ?>
                            <tr style="background:#fff8e1;">
                                <td><strong>Leerstand</strong></td>
                                <td><?php echo esc_html(vm_format_date($year_usage['inventory_start'] ?? '')); ?></td>
                                <td><?php echo esc_html(vm_format_date($year_usage['inventory_end'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($year_usage['inventory_days'] ?? 0)); ?></td>
                            </tr>
                        <?php else : ?>
                            <tr>
                                <td colspan="4">In diesem Jahr nicht im Bestand.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
