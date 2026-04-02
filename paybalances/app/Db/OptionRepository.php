<?php

namespace PearPay\Db;

class OptionRepository
{
    public static function all()
    {
        global $wpdb;
        $table = Database::table_name();
        return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
    }

    public static function active()
    {
        global $wpdb;
        $table = Database::table_name();
        return $wpdb->get_results("SELECT * FROM $table WHERE active = 1 ORDER BY id DESC", ARRAY_A);
    }

    public static function find($id)
    {
        global $wpdb;
        $table = Database::table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    public static function find_by_name($name)
    {
        global $wpdb;
        $table = Database::table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE name = %s", $name), ARRAY_A);
    }

    public static function create($data)
    {
        global $wpdb;
        $table = Database::table_name();

        $wpdb->insert(
            $table,
            [
                'name' => $data['name'],
                'title_en' => $data['title_en'],
                'title_es' => $data['title_es'],
                'description_en' => $data['description_en'],
                'description_es' => $data['description_es'],
                'price' => $data['price'],
                'price_type' => $data['price_type'],
                'active' => $data['active'],
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%d']
        );

        return self::find($wpdb->insert_id);
    }

    public static function delete($id)
    {
        global $wpdb;
        $table = Database::table_name();
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }

    public static function update($id, $data)
    {
        global $wpdb;
        $table = Database::table_name();

        $wpdb->update(
            $table,
            [
                'title_en' => $data['title_en'],
                'title_es' => $data['title_es'],
                'description_en' => $data['description_en'],
                'description_es' => $data['description_es'],
                'price' => $data['price'],
                'price_type' => $data['price_type'],
                'active' => $data['active'],
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%f', '%s', '%d'],
            ['%d']
        );

        return self::find($id);
    }
}
