<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Mieter anlegen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_tenant'); ?>
        <input type="hidden" name="vm_action" value="save_tenant">

        <p>
            <label for="vm_salutation">Anrede</label><br>
            <select name="vm_salutation" id="vm_salutation" required>
                <option value="">Bitte wählen</option>
                <option value="Herr">Herr</option>
                <option value="Frau">Frau</option>
                <option value="Familie">Familie</option>
                <option value="Firma">Firma</option>
            </select>
        </p>

        <p>
            <label for="vm_first_name">Vorname</label><br>
            <input type="text" name="vm_first_name" id="vm_first_name" required>
        </p>

        <p>
            <label for="vm_last_name">Nachname</label><br>
            <input type="text" name="vm_last_name" id="vm_last_name" required>
        </p>

        <p>
            <label for="vm_email">E-Mail</label><br>
            <input type="email" name="vm_email" id="vm_email">
        </p>

        <p>
            <label for="vm_phone">Telefon</label><br>
            <input type="text" name="vm_phone" id="vm_phone">
        </p>
        <p>
            <label for="vm_mailing_address">Versandadresse / neue Anschrift (optional)</label><br>
            <textarea name="vm_mailing_address" id="vm_mailing_address" rows="3" placeholder="Straße Hausnr.; PLZ Ort"></textarea><br>
            <small>Optional, z. B. für ausgezogene Mieter:innen. Mehrere Zeilen mit Semikolon trennen. Bleibt das Feld leer, verwendet das PDF automatisch die Objektadresse.</small>
        </p>

        <p>
            <label for="vm_iban">IBAN</label><br>
            <input type="text" name="vm_iban" id="vm_iban">
        </p>

        <p>
            <label for="vm_bank_name">Bank</label><br>
            <input type="text" name="vm_bank_name" id="vm_bank_name">
        </p>
        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Mieter speichern
            </button>
        </p>
    </form>

    <?php if (!empty($tenants)) : ?>
        <h3>Vorhandene Mieter</h3>
        <table>
            <thead>
                <tr>
                    <th>Anrede</th>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>E-Mail</th>
                    <th>Telefon</th>
                    <th>Versandadresse</th>
                    <th>IBAN</th>
                    <th>Bank</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $tenant) : ?>
                    <tr>
                        <td><?php echo esc_html($tenant->salutation); ?></td>
                        <td><?php echo esc_html($tenant->first_name); ?></td>
                        <td><?php echo esc_html($tenant->last_name); ?></td>
                        <td><?php echo esc_html($tenant->email); ?></td>
                        <td><?php echo esc_html($tenant->phone); ?></td>
                        <td><?php echo esc_html($tenant->mailing_address ?? ''); ?></td>
                        <td><?php echo esc_html($tenant->iban); ?></td>
                        <td><?php echo esc_html($tenant->bank_name); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>