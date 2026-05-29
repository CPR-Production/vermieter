<?php if (!defined('ABSPATH')) exit; ?>

<?php
$system_definitions = [];
$user_definitions = [];

foreach ($definitions as $definition) {
    if ((int) ($definition->user_id ?? 0) === 0) {
        $system_definitions[] = $definition;
    } else {
        $user_definitions[] = $definition;
    }
}
?>

<div class="vm-wrap">
    <h2>Kategoriedefinition anlegen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_cost_category_definition'); ?>
        <input type="hidden" name="vm_action" value="save_cost_category_definition">
        <input type="hidden" name="vm_record_id" value="0">

        <p>
            <label for="vm_name">Bezeichnung</label><br>
            <input
                type="text"
                name="vm_name"
                id="vm_name"
                required
                value=""
                placeholder="z. B. Allgemeinstrom"
            >
        </p>

        <p>
            <label for="vm_description">Beschreibung</label><br>
            <textarea name="vm_description" id="vm_description" rows="3"></textarea>
        </p>

        <p>
            <label for="vm_default_allocation_type">Standard-Verteilungsart</label><br>
            <select name="vm_default_allocation_type" id="vm_default_allocation_type">
                <?php foreach ($apportionment_types as $type_key => $type_label) : ?>
                    <option value="<?php echo esc_attr($type_key); ?>">
                        <?php echo esc_html($type_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label>
                <input type="checkbox" name="vm_default_is_recurring" value="1">
                Standardmäßig wiederkehrend
            </label>
        </p>

        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Kategoriedefinition speichern
            </button>
        </p>
    </form>

    <?php if (!empty($user_definitions)) : ?>
        <h3>Eigene Kategoriedefinitionen</h3>

        <table id="vm-cost-category-definitions-table">
            <thead>
                <tr>
                    <th>Bezeichnung</th>
                    <th>Beschreibung</th>
                    <th>Standard-Verteilungsart</th>
                    <th>Wiederkehrend</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_definitions as $definition) : ?>
                    <?php $is_editing = !empty($edit_item) && (int) $edit_item->id === (int) $definition->id; ?>

                    <?php if ($is_editing) : ?>
                        <tr>
                            <form method="post">
                                <?php wp_nonce_field('vm_save_cost_category_definition'); ?>
                                <input type="hidden" name="vm_action" value="save_cost_category_definition">
                                <input type="hidden" name="vm_record_id" value="<?php echo esc_attr((int) $definition->id); ?>">

                                <td>
                                    <input
                                        type="text"
                                        name="vm_name"
                                        value="<?php echo esc_attr($definition->name); ?>"
                                        required
                                        style="width:100%;"
                                    >
                                </td>
                                <td>
                                    <textarea
                                        name="vm_description"
                                        rows="2"
                                        style="width:100%;"
                                    ><?php echo esc_textarea($definition->description); ?></textarea>
                                </td>
                                <td>
                                    <select name="vm_default_allocation_type" style="width:100%;">
                                        <?php foreach ($apportionment_types as $type_key => $type_label) : ?>
                                            <option
                                                value="<?php echo esc_attr($type_key); ?>"
                                                <?php selected($definition->default_allocation_type, $type_key); ?>
                                            >
                                                <?php echo esc_html($type_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <label>
                                        <input
                                            type="checkbox"
                                            name="vm_default_is_recurring"
                                            value="1"
                                            <?php checked((int) ($definition->default_is_recurring ?? 0), 1); ?>
                                        >
                                        Ja
                                    </label>
                                </td>
                                <td>
                                    <button type="submit" class="button button-primary">
                                        <i class="fa-solid fa-save"></i>
                                    </button>

                                    <a
                                        href="<?php echo esc_url(remove_query_arg('edit_id')); ?>"
                                        class="button"
                                        aria-label="Abbrechen"
                                    >
                                        Abbrechen
                                    </a>
                                </td>
                            </form>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <td><?php echo esc_html($definition->name); ?></td>
                            <td><?php echo esc_html($definition->description ?: '—'); ?></td>
                            <td><?php echo esc_html($apportionment_types[$definition->default_allocation_type] ?? $definition->default_allocation_type); ?></td>
                            <td><?php echo !empty($definition->default_is_recurring) ? 'Ja' : 'Nein'; ?></td>
                            <td>
                                <a
                                    href="<?php echo esc_url(add_query_arg(['edit_id' => (int) $definition->id])); ?>"
                                    class="button"
                                    aria-label="Bearbeiten"
                                >
                                    <i class="fa-solid fa-ellipsis"></i>
                                </a>

                                <form
                                    method="post"
                                    style="display:inline-block; margin-left:6px;"
                                    onsubmit="return confirm('Diese Kategoriedefinition wirklich löschen?');"
                                >
                                    <?php wp_nonce_field('vm_delete_cost_category_definition'); ?>
                                    <input type="hidden" name="vm_action" value="delete_cost_category_definition">
                                    <input type="hidden" name="vm_record_id" value="<?php echo esc_attr((int) $definition->id); ?>">

                                    <button
                                        type="submit"
                                        class="button button-link-delete"
                                        aria-label="Löschen"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<br>
    <?php if (!empty($system_definitions)) : ?>
        <h3>System-Kategoriedefinitionen</h3>

        <table>
            <thead>
                <tr>
                    <th>Bezeichnung</th>
                    <th>Beschreibung</th>
                    <th>Standard-Verteilungsart</th>
                    <th>Wiederkehrend</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($system_definitions as $definition) : ?>
                    <tr style="background:#f5f5f5;">
                        <td><?php echo esc_html($definition->name); ?></td>
                        <td><?php echo esc_html($definition->description ?: '—'); ?></td>
                        <td><?php echo esc_html($apportionment_types[$definition->default_allocation_type] ?? $definition->default_allocation_type); ?></td>
                        <td><?php echo !empty($definition->default_is_recurring) ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>