<?php

namespace PearPay\Database;

class OptionRepository
{
    /**
     * Lista todas las opciones de pago para el administrador.
     */
    public static function all(): array
    {
        global $wpdb;

        $table = Database::table_name();

        // ARRAY_A hace que WordPress devuelva cada fila como array asociativo.
        return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
    }

    /**
     * Lista solo las opciones activas visibles en el formulario publico.
     */
    public static function active(): array
    {
        global $wpdb;

        $table = Database::table_name();

        return $wpdb->get_results("SELECT * FROM $table WHERE active = 1 ORDER BY id DESC", ARRAY_A);
    }

    public static function find(int $id): ?array
    {
        global $wpdb;

        $table = Database::table_name();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    public static function find_by_name(string $name): ?array
    {
        global $wpdb;

        $table = Database::table_name();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE name = %s", $name), ARRAY_A);
    }

    /**
     * Inserta una opcion y devuelve la fila creada.
     */
    public static function create(array $data): ?array
    {
        global $wpdb;

        $wpdb->insert(
            Database::table_name(),
            self::option_fields($data),
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%d']
        );

        return self::find((int) $wpdb->insert_id);
    }

    public static function update(int $id, array $data): ?array
    {
        global $wpdb;

        $wpdb->update(
            Database::table_name(),
            self::option_fields($data, false),
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%f', '%s', '%d'],
            ['%d']
        );

        return self::find($id);
    }

    public static function delete(int $id)
    {
        global $wpdb;

        return $wpdb->delete(Database::table_name(), ['id' => $id], ['%d']);
    }

    /**
     * Mantiene en un solo lugar los campos que se guardan en la tabla.
     */
    private static function option_fields(array $data, bool $include_name = true): array
    {
        $fields = [
            'title_en' => $data['title_en'],
            'title_es' => $data['title_es'],
            'description_en' => $data['description_en'],
            'description_es' => $data['description_es'],
            'price' => $data['price'],
            'price_type' => $data['price_type'],
            'active' => $data['active'],
        ];

        if ($include_name) {
            $fields = ['name' => $data['name']] + $fields;
        }

        return $fields;
    }
}
