<?php

namespace PearPay\Db;

class Database
{
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'pearpay_options';
    }

    public static function install()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            title_en VARCHAR(191) NOT NULL,
            title_es VARCHAR(191) NOT NULL,
            description_en VARCHAR(255) NOT NULL,
            description_es VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            price_type VARCHAR(20) NOT NULL DEFAULT 'person',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset;";

        dbDelta($sql);
    }
}
