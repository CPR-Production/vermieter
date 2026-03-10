<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Tenant_Payments
{
    public static function add_or_update($data)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_payments';

        $user_id = get_current_user_id();
        $apartment_tenant_id = (int) ($data['apartment_tenant_id'] ?? 0);
        $payment_month = sanitize_text_field($data['payment_month'] ?? '');
        $payment_date = !empty($data['payment_date']) ? sanitize_text_field($data['payment_date']) : null;
        $amount_paid = (float) ($data['amount_paid'] ?? 0);
        $is_paid = !empty($data['is_paid']) ? 1 : 0;
        $note = sanitize_textarea_field($data['note'] ?? '');

        if ($apartment_tenant_id <= 0 || $payment_month === '') {
            return false;
        }

        // Keine komplett leeren / irrelevanten Datensätze speichern
        if (
            $is_paid !== 1 &&
            (float) $amount_paid <= 0 &&
            trim((string) $note) === ''
        ) {
            return false;
        }

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND payment_month = %s
                   AND user_id = %d
                 LIMIT 1",
                $apartment_tenant_id,
                $payment_month,
                $user_id
            )
        );

        if ($existing_id) {
            $updated = $wpdb->update(
                $table,
                [
                    'payment_date' => $payment_date,
                    'amount_paid'  => $amount_paid,
                    'is_paid'      => $is_paid,
                    'note'         => $note,
                ],
                [
                    'id'      => (int) $existing_id,
                    'user_id' => $user_id,
                ],
                ['%s', '%f', '%d', '%s'],
                ['%d', '%d']
            );

            return $updated !== false ? (int) $existing_id : false;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'             => $user_id,
                'apartment_tenant_id' => $apartment_tenant_id,
                'payment_month'       => $payment_month,
                'payment_date'        => $payment_date,
                'amount_paid'         => $amount_paid,
                'is_paid'             => $is_paid,
                'note'                => $note,
            ],
            ['%d', '%d', '%s', '%s', '%f', '%d', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get_by_apartment_tenant($apartment_tenant_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_payments';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND user_id = %d
                 ORDER BY payment_month ASC",
                (int) $apartment_tenant_id,
                get_current_user_id()
            )
        );
    }

    public static function get_by_apartment_tenant_and_month($apartment_tenant_id, $payment_month)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenant_payments';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND payment_month = %s
                   AND user_id = %d
                 LIMIT 1",
                (int) $apartment_tenant_id,
                $payment_month,
                get_current_user_id()
            )
        );
    }

    public static function get_all_by_user($user_id = 0)
    {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'vm_tenant_payments';
        $table_links = $wpdb->prefix . 'vm_apartment_tenants';
        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_tenants = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    tp.*,
                    a.name AS apartment_name,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table tp
                 LEFT JOIN $table_links at ON tp.apartment_tenant_id = at.id
                 LEFT JOIN $table_apartments a ON at.apartment_id = a.id
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE tp.user_id = %d
                 ORDER BY a.name ASC, t.last_name ASC, tp.payment_month ASC",
                $user_id
            )
        );
    }

    public static function get_monthly_target($apartment_tenant_id, $payment_month)
    {
        $cold_rent = Vermieter_Tenancy_Rent_Terms::get_valid_cold_rent_for_date($apartment_tenant_id, $payment_month);
        $advances = Vermieter_Tenancy_Advance_Terms::get_valid_advances_for_date($apartment_tenant_id, $payment_month);

        $nk = (float) ($advances['nk_advance'] ?? 0);
        $hk = (float) ($advances['hk_advance'] ?? 0);

        return [
            'cold_rent'    => $cold_rent,
            'nk_advance'   => $nk,
            'hk_advance'   => $hk,
            'total_target' => round($cold_rent + $nk + $hk, 2),
        ];
    }

    public static function get_monthly_target_prorated($apartment_tenant_row, $payment_month) {
        $payment_month = date('Y-m-01', strtotime($payment_month));

        $month_start = new DateTime($payment_month);
        $month_end = new DateTime($month_start->format('Y-m-t'));

        $occupancy_start = new DateTime(max(
            $month_start->format('Y-m-d'),
            $apartment_tenant_row->move_in_date
        ));

        $move_out = !empty($apartment_tenant_row->move_out_date)
            ? $apartment_tenant_row->move_out_date
            : $month_end->format('Y-m-d');

        $occupancy_end = new DateTime(min(
            $month_end->format('Y-m-d'),
            $move_out
        ));

        // Falls der Monat gar nicht belegt ist
        if ($occupancy_end < $occupancy_start) {
            return [
                'cold_rent'     => 0.0,
                'nk_advance'    => 0.0,
                'hk_advance'    => 0.0,
                'total_target'  => 0.0,
                'occupied_days' => 0,
                'month_days'    => (int) $month_start->diff($month_end)->days + 1,
            ];
        }

        // WICHTIG:
        // Die gültigen Konditionen müssen ab dem tatsächlichen Belegungsbeginn
        // innerhalb des Monats geholt werden, nicht ab Monatsersten.
        $effective_date = $occupancy_start->format('Y-m-d');

        $cold_rent = Vermieter_Tenancy_Rent_Terms::get_valid_cold_rent_for_date(
            $apartment_tenant_row->id,
            $effective_date
        );

        $advances = Vermieter_Tenancy_Advance_Terms::get_valid_advances_for_date(
            $apartment_tenant_row->id,
            $effective_date
        );

        $nk_advance = (float) ($advances['nk_advance'] ?? 0);
        $hk_advance = (float) ($advances['hk_advance'] ?? 0);

        $full_month_total = $cold_rent + $nk_advance + $hk_advance;

        $month_days = (int) $month_start->diff($month_end)->days + 1;
        $occupied_days = (int) $occupancy_start->diff($occupancy_end)->days + 1;
        $factor = $occupied_days / $month_days;

        return [
            'cold_rent'     => round($cold_rent * $factor, 2),
            'nk_advance'    => round($nk_advance * $factor, 2),
            'hk_advance'    => round($hk_advance * $factor, 2),
            'total_target'  => round($full_month_total * $factor, 2),
            'occupied_days' => $occupied_days,
            'month_days'    => $month_days,
        ];
    }

    public static function get_months_for_tenancy($apartment_tenant_row, $until_month = null)
    {
        $start = new DateTime(date('Y-m-01', strtotime($apartment_tenant_row->move_in_date)));

        if ($until_month) {
            $end = new DateTime($until_month);
        } elseif (!empty($apartment_tenant_row->move_out_date)) {
            $end = new DateTime(date('Y-m-01', strtotime($apartment_tenant_row->move_out_date)));
        } else {
            $end = new DateTime(date('Y-m-01'));
        }

        $months = [];

        while ($start <= $end) {
            $months[] = $start->format('Y-m-01');
            $start->modify('+1 month');
        }

        return $months;
    }

    public static function get_payment_status($target_amount, $payment)
    {
        $target_amount = round((float) $target_amount, 2);

        if (!$payment) {
            return 'Offen';
        }

        $paid = round((float) ($payment->amount_paid ?? 0), 2);
        $is_paid = (int) ($payment->is_paid ?? 0);

        if ($is_paid === 1 || $paid === $target_amount) {
            return 'Bezahlt';
        }

        if ($paid > $target_amount) {
            return 'Überzahlt';
        }

        if ($paid > 0 && $paid < $target_amount) {
            return 'Teilzahlung';
        }

        return 'Offen';
    }

    public static function get_open_payment_rows($until_month = null)
    {
        $rows = Vermieter_Apartment_Tenants::get_all_by_user();
        $result = [];

        foreach ($rows as $tenancy) {
            $months = self::get_months_for_tenancy($tenancy, $until_month);

            foreach ($months as $month) {
                $target = self::get_monthly_target_prorated($tenancy, $month);
                $payment = self::get_by_apartment_tenant_and_month($tenancy->id, $month);

                $amount_paid = (float) ($payment->amount_paid ?? 0);
                $is_paid = (int) ($payment->is_paid ?? 0);

                $is_open = !$payment || $is_paid !== 1 || round($amount_paid, 2) < round($target['total_target'], 2);

                if ($is_open) {
                    $result[] = [
                        'apartment_tenant_id' => (int) $tenancy->id,
                        'property_name'       => $tenancy->property_name ?? '',
                        'apartment_name'      => $tenancy->apartment_name ?? '',
                        'tenant_name'         => trim(($tenancy->salutation ?? '') . ' ' . ($tenancy->first_name ?? '') . ' ' . ($tenancy->last_name ?? '')),
                        'payment_month'       => $month,
                        'cold_rent'           => $target['cold_rent'],
                        'nk_advance'          => $target['nk_advance'],
                        'hk_advance'          => $target['hk_advance'],
                        'total_target'        => $target['total_target'],
                        'occupied_days'       => (int) ($target['occupied_days'] ?? 0),
                        'month_days'          => (int) ($target['month_days'] ?? 0),
                        'existing_payment'    => $payment,
                    ];
                }
            }
        }

        usort($result, function ($a, $b) {
            return strcmp($a['payment_month'], $b['payment_month']);
        });

        return $result;
    }

    public static function get_ledger_rows_by_apartment_tenant($apartment_tenant_id, $until_month = null)
    {
        global $wpdb;

        $table_links = $wpdb->prefix . 'vm_apartment_tenants';
        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_tenants = $wpdb->prefix . 'vm_tenants';
        $table_properties = $wpdb->prefix . 'vm_properties';

        $tenancy = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    at.*,
                    a.name AS apartment_name,
                    p.name AS property_name,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table_links at
                 LEFT JOIN $table_apartments a ON at.apartment_id = a.id
                 LEFT JOIN $table_properties p ON a.property_id = p.id
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE at.id = %d
                   AND at.user_id = %d
                 LIMIT 1",
                (int) $apartment_tenant_id,
                get_current_user_id()
            )
        );

        if (!$tenancy) {
            return [];
        }

        $months = self::get_months_for_tenancy($tenancy, $until_month);
        $rows = [];

        foreach ($months as $month) {
            $target = self::get_monthly_target_prorated($tenancy, $month);
            $payment = self::get_by_apartment_tenant_and_month($tenancy->id, $month);

            $ist = round((float) ($payment->amount_paid ?? 0), 2);
            $soll = round((float) ($target['total_target'] ?? 0), 2);
            $differenz = round($ist - $soll, 2);

            $rows[] = [
                'payment_month' => $month,
                'property_name' => $tenancy->property_name ?? '',
                'apartment_name' => $tenancy->apartment_name ?? '',
                'tenant_name' => trim(($tenancy->salutation ?? '') . ' ' . ($tenancy->first_name ?? '') . ' ' . ($tenancy->last_name ?? '')),
                'move_in_date' => $tenancy->move_in_date ?? '',
                'move_out_date' => $tenancy->move_out_date ?? '',
                'cold_rent' => (float) ($target['cold_rent'] ?? 0),
                'nk_advance' => (float) ($target['nk_advance'] ?? 0),
                'hk_advance' => (float) ($target['hk_advance'] ?? 0),
                'soll' => $soll,
                'ist' => $ist,
                'differenz' => $differenz,
                'status' => self::get_payment_status($soll, $payment),
                'payment_date' => $payment->payment_date ?? null,
                'note' => $payment->note ?? '',
                'occupied_days' => (int) ($target['occupied_days'] ?? 0),
                'month_days' => (int) ($target['month_days'] ?? 0),
            ];
        }

        return $rows;
    }
}