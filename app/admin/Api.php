<?php

namespace PearPay\Admin;

use PearPay\Db\OptionRepository;

class Api
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('pearpay/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'can_manage'],
        ]);

        register_rest_route('pearpay/v1', '/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'save_settings'],
            'permission_callback' => [$this, 'can_manage'],
        ]);

        register_rest_route('pearpay/v1', '/options', [
            'methods' => 'GET',
            'callback' => [$this, 'list_options'],
            'permission_callback' => [$this, 'can_manage'],
        ]);

        register_rest_route('pearpay/v1', '/options', [
            'methods' => 'POST',
            'callback' => [$this, 'create_option'],
            'permission_callback' => [$this, 'can_manage'],
        ]);

        register_rest_route('pearpay/v1', '/options/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_option'],
            'permission_callback' => [$this, 'can_manage'],
        ]);

        register_rest_route('pearpay/v1', '/options/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_option'],
            'permission_callback' => [$this, 'can_manage'],
        ]);
    }

    public function can_manage()
    {
        return current_user_can('manage_options');
    }

    public function get_settings()
    {
        return [
            'pearpay_mail' => get_option('pearpay_mail', ''),
            'pearpay_commission_type' => get_option('pearpay_commission_type', 'percent'),
            'pearpay_commission_value' => floatval(get_option('pearpay_commission_value', 0)),
            'pearpay_show_payment_options' => (bool) get_option('pearpay_show_payment_options', true),
            'pearpay_currency' => get_option('pearpay_currency', 'USD'),
            'pearpay_balance_title_en' => get_option('pearpay_balance_title_en', 'Pay your balance or make a deposit'),
            'pearpay_balance_title_es' => get_option('pearpay_balance_title_es', 'Pague su saldo o realice un depósito'),
            'pearpay_balance_desc_en' => get_option('pearpay_balance_desc_en', 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.'),
            'pearpay_balance_desc_es' => get_option('pearpay_balance_desc_es', 'Pague cualquier saldo restante o realice un depósito personalizado. Ingrese el monto que desea pagar.'),
            'pearpay_balance_label_en' => get_option('pearpay_balance_label_en', 'Enter the payment amount'),
            'pearpay_balance_label_es' => get_option('pearpay_balance_label_es', 'Introduzca el importe del pago'),
            'pearpay_theme_color' => get_option('pearpay_theme_color', '#1d2327'),
             'pearpay_theme_border'=>get_option('pearpay_theme_border',3),
            'pearpay_confirm_message' => get_option('pearpay_confirm_message', 'Payment successful!'),
        ];
    }

    public function save_settings($request)
    {
        $params = $request->get_json_params();
        $mail = isset($params['pearpay_mail']) ? ($params['pearpay_mail']) : '';
        $commission_type = isset($params['pearpay_commission_type']) && $params['pearpay_commission_type'] === 'fixed'
            ? 'fixed'
            : 'percent';
        $commission_value = isset($params['pearpay_commission_value']) ? floatval($params['pearpay_commission_value']) : 0;
        $show_payment_options = !empty($params['pearpay_show_payment_options']) ? 1 : 0;
        $currency = isset($params['pearpay_currency']) && in_array($params['pearpay_currency'], ['USD', 'PEN'], true)
            ? $params['pearpay_currency']
            : 'USD';
        $balance_title_en = isset($params['pearpay_balance_title_en']) ? sanitize_text_field($params['pearpay_balance_title_en']) : 'Pay your balance or make a deposit';
        $balance_title_es = isset($params['pearpay_balance_title_es']) ? sanitize_text_field($params['pearpay_balance_title_es']) : 'Pague su saldo o realice un depósito';
        $balance_desc_en = isset($params['pearpay_balance_desc_en']) ? sanitize_text_field($params['pearpay_balance_desc_en']) : 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.';
        $balance_desc_es = isset($params['pearpay_balance_desc_es']) ? sanitize_text_field($params['pearpay_balance_desc_es']) : 'Pague cualquier saldo restante o realice un depósito personalizado. Ingrese el monto que desea pagar.';
        $balance_label_en = isset($params['pearpay_balance_label_en']) ? sanitize_text_field($params['pearpay_balance_label_en']) : 'Enter the payment amount';
        $balance_label_es = isset($params['pearpay_balance_label_es']) ? sanitize_text_field($params['pearpay_balance_label_es']) : 'Introduzca el importe del pago';
        $theme_color = isset($params['pearpay_theme_color']) ? sanitize_hex_color($params['pearpay_theme_color']) : '#1d2327';
        if (!$theme_color) {
            $theme_color = '#1d2327';
        }
        $theme_border=$params['pearpay_theme_border'];
        $confirm_message = isset($params['pearpay_confirm_message']) ? sanitize_text_field($params['pearpay_confirm_message']) : 'Payment successful!';

        update_option('pearpay_mail', $mail);
        update_option('pearpay_commission_type', $commission_type);
        update_option('pearpay_commission_value', $commission_value);
        update_option('pearpay_show_payment_options', $show_payment_options);
        update_option('pearpay_currency', $currency);
        update_option('pearpay_balance_title_en', $balance_title_en);
        update_option('pearpay_balance_title_es', $balance_title_es);
        update_option('pearpay_balance_desc_en', $balance_desc_en);
        update_option('pearpay_balance_desc_es', $balance_desc_es);
        update_option('pearpay_balance_label_en', $balance_label_en);
        update_option('pearpay_balance_label_es', $balance_label_es);
        update_option('pearpay_theme_color', $theme_color);
        update_option('pearpay_theme_border',$theme_border);
        update_option('pearpay_confirm_message', $confirm_message);

        return [
            'pearpay_mail' => $mail,
            'pearpay_commission_type' => $commission_type,
            'pearpay_commission_value' => $commission_value,
            'pearpay_show_payment_options' => (bool) $show_payment_options,
            'pearpay_currency' => $currency,
            'pearpay_balance_title_en' => $balance_title_en,
            'pearpay_balance_title_es' => $balance_title_es,
            'pearpay_balance_desc_en' => $balance_desc_en,
            'pearpay_balance_desc_es' => $balance_desc_es,
            'pearpay_balance_label_en' => $balance_label_en,
            'pearpay_balance_label_es' => $balance_label_es,
            'pearpay_theme_color' => $theme_color,
              'pearpay_theme_border'=>$theme_border,
            'pearpay_confirm_message' => $confirm_message,
        ];
    }

    public function list_options()
    {
        return [
            'items' => OptionRepository::all(),
        ];
    }

    public function create_option($request)
    {
        $params = $request->get_json_params();

        $title_en = isset($params['title_en']) ? sanitize_text_field($params['title_en']) : '';
        $title_es = isset($params['title_es']) ? sanitize_text_field($params['title_es']) : '';
        $description_en = isset($params['description_en']) ? sanitize_text_field($params['description_en']) : '';
        $description_es = isset($params['description_es']) ? sanitize_text_field($params['description_es']) : '';
        $price = isset($params['price']) ? floatval($params['price']) : 0;
        $price_type = isset($params['price_type']) && $params['price_type'] === 'group' ? 'group' : 'person';
        $active = !empty($params['active']) ? 1 : 0;

        if ($title_en === '' || $title_es === '' || $description_en === '' || $description_es === '') {
            return new \WP_Error('pearpay_invalid', 'Missing required fields.', ['status' => 400]);
        }

        $name = sanitize_title($title_en);
        if ($name === '') {
            $name = 'option-' . time();
        }
        if (OptionRepository::find_by_name($name)) {
            $name = $name . '-' . time();
        }

        $item = OptionRepository::create([
            'name' => $name,
            'title_en' => $title_en,
            'title_es' => $title_es,
            'description_en' => $description_en,
            'description_es' => $description_es,
            'price' => $price,
            'price_type' => $price_type,
            'active' => $active,
        ]);

        return [
            'item' => $item,
        ];
    }

    public function delete_option($request)
    {
        $id = intval($request['id']);
        OptionRepository::delete($id);
        return ['deleted' => true];
    }

    public function update_option($request)
    {
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $title_en = isset($params['title_en']) ? sanitize_text_field($params['title_en']) : '';
        $title_es = isset($params['title_es']) ? sanitize_text_field($params['title_es']) : '';
        $description_en = isset($params['description_en']) ? sanitize_text_field($params['description_en']) : '';
        $description_es = isset($params['description_es']) ? sanitize_text_field($params['description_es']) : '';
        $price = isset($params['price']) ? floatval($params['price']) : 0;
        $price_type = isset($params['price_type']) && $params['price_type'] === 'group' ? 'group' : 'person';
        $active = !empty($params['active']) ? 1 : 0;

        if ($title_en === '' || $title_es === '' || $description_en === '' || $description_es === '') {
            return new \WP_Error('pearpay_invalid', 'Missing required fields.', ['status' => 400]);
        }

        $item = OptionRepository::update($id, [
            'title_en' => $title_en,
            'title_es' => $title_es,
            'description_en' => $description_en,
            'description_es' => $description_es,
            'price' => $price,
            'price_type' => $price_type,
            'active' => $active,
        ]);

        return ['item' => $item];
    }
}
