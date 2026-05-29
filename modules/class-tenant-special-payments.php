<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Tenant_Special_Payments
{
    public static function add($data)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_special_payments';

        $user_id = get_current_user_id();
        $apartment_tenant_id = (int) ($data['apartment_tenant_id'] ?? 0);
        $billing_year = (int) ($data['billing_year'] ?? 0);
        $payment_date = !empty($data['payment_date']) ? sanitize_text_field($data['payment_date']) : current_time('Y-m-d');
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $payment_type = sanitize_key($data['payment_type'] ?? 'settlement_payment');
        $note = sanitize_textarea_field($data['note'] ?? '');

        if ($apartment_tenant_id <= 0 || $billing_year <= 0 || $amount == 0.0) {
            return false;
        }

        if (!in_array($payment_type, ['settlement_payment', 'settlement_refund'], true)) {
            $payment_type = 'settlement_payment';
        }

        // Technisches Vorzeichen:
        // + Betrag gleicht eine Nachzahlung aus.
        // - Betrag gleicht ein Guthaben / eine Auszahlung aus.
        if ($payment_type === 'settlement_refund' && $amount > 0) {
            $amount = -$amount;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'             => $user_id,
                'apartment_tenant_id' => $apartment_tenant_id,
                'billing_year'        => $billing_year,
                'payment_date'        => $payment_date,
                'amount'              => $amount,
                'payment_type'        => $payment_type,
                'note'                => $note,
            ],
            ['%d', '%d', '%d', '%s', '%f', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function delete($id)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_special_payments';

        return $wpdb->delete(
            $table,
            [
                'id'      => (int) $id,
                'user_id' => get_current_user_id(),
            ],
            ['%d', '%d']
        ) !== false;
    }

    public static function get_by_apartment_tenant($apartment_tenant_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_special_payments';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND user_id = %d
                 ORDER BY billing_year DESC, payment_date DESC, id DESC",
                (int) $apartment_tenant_id,
                get_current_user_id()
            )
        );
    }

    public static function get_by_apartment_tenant_and_year($apartment_tenant_id, $billing_year)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_special_payments';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND billing_year = %d
                   AND user_id = %d
                 ORDER BY payment_date ASC, id ASC",
                (int) $apartment_tenant_id,
                (int) $billing_year,
                get_current_user_id()
            )
        );
    }

    public static function get_sum_by_apartment_tenant_and_year($apartment_tenant_id, $billing_year)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_special_payments';

        $sum = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0)
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND billing_year = %d
                   AND user_id = %d",
                (int) $apartment_tenant_id,
                (int) $billing_year,
                get_current_user_id()
            )
        );

        return round((float) $sum, 2);
    }

    public static function get_sum_by_apartment_tenant_ids_and_year($apartment_tenant_ids, $billing_year)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_special_payments';

        $ids = array_values(array_filter(array_map('intval', (array) $apartment_tenant_ids)));
        if (empty($ids)) {
            return 0.0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $params = array_merge($ids, [(int) $billing_year, get_current_user_id()]);

        $sum = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0)
                 FROM $table
                 WHERE apartment_tenant_id IN ($placeholders)
                   AND billing_year = %d
                   AND user_id = %d",
                $params
            )
        );

        return round((float) $sum, 2);
    }
}
