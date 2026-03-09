<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Activator {

    public static function activate() {

        // Tabellen erstellen
        Vermieter_DB::install();

        // Default-Daten
        //Vermieter_Apportionment_Types::install_defaults();

        // DB-Version speichern
        //update_option('vermieter_db_version', VERMIETER_DB_VERSION);

    }

}