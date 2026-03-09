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
        <h3>Vorhandene Zuordnungen über die gesamte Laufzeit</h3>
        <?php foreach ($apartments as $apartment) : ?>
            <?php
            $timeline = Vermieter_Apartment_Tenants::get_full_timeline_with_vacancies_by_apartment($apartment->id);
            ?>

            <h4>
                <?php echo esc_html($apartment->property_name . ' - ' . $apartment->name); ?>
                <?php if ($vacancy_days > 0) : ?>
                    <small>(Leerstand: <?php echo esc_html($vacancy_days); ?> Tage)</small>
                <?php endif; ?>
            </h4>

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
                    <?php if (!empty($timeline)) : ?>
                        <?php foreach ($timeline as $item) : ?>
                            <?php if ($item['type'] === 'vacancy') : ?>
                                <tr style="background:#fff8e1;">
                                    <td><strong>Leerstand</strong></td>
                                    <td><?php echo esc_html($item['start_date']); ?></td>
                                    <td><?php echo esc_html($item['end_date']); ?></td>
                                    <td><?php echo esc_html($item['days']); ?></td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <td><?php echo esc_html($item['label']); ?></td>
                                    <td><?php echo esc_html($item['start_date']); ?></td>
                                    <td><?php echo esc_html($item['end_date']); ?></td>
                                    <td><?php echo esc_html($item['days']); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr style="background:#fff8e1;">
                            <td><strong>Leerstand</strong></td>
                            <td><?php echo esc_html($selected_year . '-01-01'); ?></td>
                            <td><?php echo esc_html($selected_year . '-12-31'); ?></td>
                            <td>
                                <?php
                                $start = new DateTime($selected_year . '-01-01');
                                $end   = new DateTime($selected_year . '-12-31');
                                echo esc_html($start->diff($end)->days + 1);
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</div>