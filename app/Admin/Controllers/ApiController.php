<?php

namespace PearPay\Admin\Controllers;

use PearPay\Database\OptionRepository;

class ApiController
{
    private const NAMESPACE = 'pearpay/v1';

    private const SETTINGS_DEFAULTS = [
        'pearpay_mail' => '',
        'pearpay_commission_type' => 'percent',
        'pearpay_commission_value' => 0,
        'pearpay_show_payment_options' => true,
        'pearpay_currency' => 'USD',
        'pearpay_balance_title_en' => 'Pay your balance or make a deposit',
        'pearpay_balance_title_es' => 'Pague su saldo o realice un deposito',
        'pearpay_balance_desc_en' => 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.',
        'pearpay_balance_desc_es' => 'Pague cualquier saldo restante o realice un deposito personalizado. Ingrese el monto que desea pagar.',
        'pearpay_balance_label_en' => 'Enter the payment amount',
        'pearpay_balance_label_es' => 'Introduzca el importe del pago',
        'pearpay_theme_color' => '#1d2327',
        'pearpay_theme_border' => 3,
        'pearpay_confirm_message' => 'Payment successful!',
    ];

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        // REST endpoints consumidos por el panel Vue del administrador.
        $this->register_route('/settings', 'GET', 'get_settings');
        $this->register_route('/settings', 'POST', 'save_settings');
        $this->register_route('/options', 'GET', 'list_options');
        $this->register_route('/options', 'POST', 'create_option');
        $this->register_route('/options/(?P<id>\d+)', 'PUT', 'update_option');
        $this->register_route('/options/(?P<id>\d+)', 'DELETE', 'delete_option');
    }

    public function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public function get_settings(): array
    {
        $settings = [];

        // Usar defaults centralizados evita repetir valores en varios metodos.
        foreach (self::SETTINGS_DEFAULTS as $key => $default) {
            $settings[$key] = get_option($key, $default);
        }

        $settings['pearpay_commission_value'] = (float) $settings['pearpay_commission_value'];
        $settings['pearpay_show_payment_options'] = (bool) $settings['pearpay_show_payment_options'];
        $settings['pearpay_theme_border'] = absint($settings['pearpay_theme_border']);

        return $settings;
    }

    public function save_settings($request): array
    {
        $params = $request->get_json_params();
        $params = is_array($params) ? $params : [];

        // Sanitiza antes de guardar para no persistir datos sin validar.
        $settings = [
            'pearpay_mail' => isset($params['pearpay_mail']) ? sanitize_text_field($params['pearpay_mail']) : self::SETTINGS_DEFAULTS['pearpay_mail'],
            'pearpay_commission_type' => $this->sanitize_commission_type($params['pearpay_commission_type'] ?? ''),
            'pearpay_commission_value' => isset($params['pearpay_commission_value']) ? (float) $params['pearpay_commission_value'] : self::SETTINGS_DEFAULTS['pearpay_commission_value'],
            'pearpay_show_payment_options' => !empty($params['pearpay_show_payment_options']) ? 1 : 0,
            'pearpay_currency' => $this->sanitize_currency($params['pearpay_currency'] ?? ''),
            'pearpay_balance_title_en' => $this->sanitize_text_setting($params, 'pearpay_balance_title_en'),
            'pearpay_balance_title_es' => $this->sanitize_text_setting($params, 'pearpay_balance_title_es'),
            'pearpay_balance_desc_en' => $this->sanitize_text_setting($params, 'pearpay_balance_desc_en'),
            'pearpay_balance_desc_es' => $this->sanitize_text_setting($params, 'pearpay_balance_desc_es'),
            'pearpay_balance_label_en' => $this->sanitize_text_setting($params, 'pearpay_balance_label_en'),
            'pearpay_balance_label_es' => $this->sanitize_text_setting($params, 'pearpay_balance_label_es'),
            'pearpay_theme_color' => $this->sanitize_theme_color($params['pearpay_theme_color'] ?? ''),
            'pearpay_theme_border' => isset($params['pearpay_theme_border']) ? absint($params['pearpay_theme_border']) : self::SETTINGS_DEFAULTS['pearpay_theme_border'],
            'pearpay_confirm_message' => $this->sanitize_text_setting($params, 'pearpay_confirm_message'),
        ];

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        $settings['pearpay_show_payment_options'] = (bool) $settings['pearpay_show_payment_options'];

        return $settings;
    }

    public function list_options(): array
    {
        return [
            'items' => OptionRepository::all(),
        ];
    }

    public function create_option($request)
    {
        $params = $request->get_json_params();
        $params = is_array($params) ? $params : [];
        $data = $this->sanitize_option_data($params);

        if ($this->has_missing_option_fields($data)) {
            return new \WP_Error('pearpay_invalid', 'Missing required fields.', ['status' => 400]);
        }

        $data['name'] = $this->unique_option_name($data['title_en']);

        return [
            'item' => OptionRepository::create($data),
        ];
    }

    public function update_option($request)
    {
        $params = $request->get_json_params();
        $params = is_array($params) ? $params : [];
        $data = $this->sanitize_option_data($params);

        if ($this->has_missing_option_fields($data)) {
            return new \WP_Error('pearpay_invalid', 'Missing required fields.', ['status' => 400]);
        }

        return [
            'item' => OptionRepository::update(absint($request['id']), $data),
        ];
    }

    public function delete_option($request): array
    {
        OptionRepository::delete(absint($request['id']));

        return ['deleted' => true];
    }

    private function register_route(string $route, string $method, string $callback): void
    {
        // Todos los endpoints comparten capability y namespace.
        register_rest_route(self::NAMESPACE, $route, [
            'methods' => $method,
            'callback' => [$this, $callback],
            'permission_callback' => [$this, 'can_manage'],
        ]);
    }

    private function sanitize_commission_type(string $value): string
    {
        return $value === 'fixed' ? 'fixed' : 'percent';
    }

    private function sanitize_currency(string $value): string
    {
        return in_array($value, ['USD', 'PEN'], true) ? $value : 'USD';
    }

    private function sanitize_theme_color(string $value): string
    {
        $color = sanitize_hex_color($value);

        return $color ?: self::SETTINGS_DEFAULTS['pearpay_theme_color'];
    }

    private function sanitize_text_setting(array $params, string $key): string
    {
        return isset($params[$key])
            ? sanitize_text_field($params[$key])
            : self::SETTINGS_DEFAULTS[$key];
    }

    private function sanitize_option_data(array $params): array
    {
        return [
            'title_en' => isset($params['title_en']) ? sanitize_text_field($params['title_en']) : '',
            'title_es' => isset($params['title_es']) ? sanitize_text_field($params['title_es']) : '',
            'description_en' => isset($params['description_en']) ? sanitize_text_field($params['description_en']) : '',
            'description_es' => isset($params['description_es']) ? sanitize_text_field($params['description_es']) : '',
            'price' => isset($params['price']) ? (float) $params['price'] : 0,
            'price_type' => isset($params['price_type']) && $params['price_type'] === 'group' ? 'group' : 'person',
            'active' => !empty($params['active']) ? 1 : 0,
        ];
    }

    private function has_missing_option_fields(array $data): bool
    {
        return $data['title_en'] === ''
            || $data['title_es'] === ''
            || $data['description_en'] === ''
            || $data['description_es'] === '';
    }

    private function unique_option_name(string $title): string
    {
        $name = sanitize_title($title);
        if ($name === '') {
            $name = 'option-' . time();
        }
        if (OptionRepository::find_by_name($name)) {
            $name .= '-' . time();
        }
        return $name;
    }
}
