<?php

namespace PearPay\Payments\Izipay;

class PaymentAjaxController
{
    public function __construct()
    {
        add_action('wp_ajax_izipay_generate_token', [$this, 'izipaytoken_callback']);
        add_action('wp_ajax_nopriv_izipay_generate_token', [$this, 'izipaytoken_callback']);
        add_action('wp_ajax_pearpay_validate', [$this, 'izipayvalidate_callback']);
        add_action('wp_ajax_nopriv_pearpay_validate', [$this, 'izipayvalidate_callback']);
    }

    public function izipaytoken_callback(): void
    {
        check_ajax_referer('pearpay_nonce', 'nonce');

        // WordPress registra AJAX para varios verbos; este endpoint solo acepta POST.
        if (!$this->is_post_request()) {
            wp_send_json_error(['message' => 'Invalid request method. Only POST is allowed.'], 405);
        }

        $paydata = $this->json_post_field('peardata');

        if (!$paydata) {
            wp_send_json_error(['message' => 'Invalid payment data.'], 400);
        }

        $amount = round((float) ($paydata['totalpay'] ?? 0) * 100);
        $email = sanitize_email($paydata['email'] ?? '');

        if ($amount <= 0 || $email === '') {
            wp_send_json_error(['message' => 'Missing required parameters.'], 400);
        }

        $response = $this->create_izipay_payment([
            'amount' => $amount,
            'currency' => $this->currency(),
            'orderId' => uniqid(),
            'customer' => [
                'email' => $email,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_data(), $response->get_error_code() ?: 500);
        }

        wp_send_json_success($response);
    }

    public function izipayvalidate_callback(): void
    {
        check_ajax_referer('pearpay_nonce', 'nonce');

        if (!$this->is_post_request()) {
            wp_send_json_error(['message' => 'Invalid request method. Only POST is allowed.'], 405);
        }

        $paydata = $this->json_post_field('paydata');
        $client = $this->json_post_field('peardata') ?: [];

        if (!is_array($paydata) || !isset($paydata['hashKey'], $paydata['hash'], $paydata['clientAnswer'])) {
            wp_send_json_error(['message' => 'Datos de pago incompletos o malformateados.'], 400);
        }

        if ($paydata['hashKey'] !== 'sha256_hmac') {
            wp_send_json_error(['message' => 'Metodo de hash no soportado.'], 400);
        }

        $key = IzipayConfig::credentials()['hmac_sha256'] ?? '';

        if ($key === '') {
            wp_send_json_error(['message' => 'Clave HMAC no configurada.'], 500);
        }

        $client_answer = $paydata['clientAnswer'];
        // hash_equals evita comparaciones vulnerables por timing.
        $hash_is_valid = hash_equals((string) $paydata['hash'], $this->calculate_hash($client_answer, $key));
        $message = 'Respuesta del cliente invalida o faltante.';

        if (is_array($client_answer) && isset($client_answer['orderStatus'])) {
            if ($client_answer['orderStatus'] === 'PAID' && $hash_is_valid) {
                $order_id = $client_answer['orderDetails']['orderId'] ?? '';
                (new IzipayNotify())->notify($client, $order_id);
                $message = 'Pago realizado correctamente.';
            } else {
                $message = 'El pago no fue realizado o el hash es invalido.';
            }
        }

        wp_send_json_success([
            'message' => $message,
            'client' => $client,
            'hashIsValid' => $hash_is_valid,
            'orderStatus' => $client_answer['orderStatus'] ?? 'undefined',
        ]);
    }

    private function create_izipay_payment(array $body)
    {
        $config = IzipayConfig::credentials();
        $curl = curl_init($config['api_url']);

        // Izipay espera autenticacion basic auth y payload JSON.
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_POSTFIELDS, wp_json_encode($body));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $raw_response = curl_exec($curl);

        if ($raw_response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return new \WP_Error(500, 'cURL error.', ['message' => 'cURL error: ' . $error]);
        }

        $http_status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_status !== 200) {
            return new \WP_Error($http_status, 'API request failed.', [
                'message' => 'API request failed.',
                'status_code' => $http_status,
            ]);
        }

        $response = json_decode($raw_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(500, 'Invalid JSON response.', ['message' => 'Invalid JSON response.']);
        }

        return $response;
    }

    private function json_post_field(string $field): array
    {
        if (!isset($_POST[$field])) {
            return [];
        }

        // wp_unslash revierte el escape que WordPress aplica a $_POST.
        $value = wp_unslash($_POST[$field]);
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function calculate_hash($client_answer, string $key): string
    {
        $answer = is_array($client_answer)
            ? wp_json_encode($client_answer, JSON_UNESCAPED_SLASHES)
            : str_replace('\/', '/', (string) $client_answer);

        return hash_hmac('sha256', $answer, $key);
    }

    private function currency(): string
    {
        $currency = get_option('pearpay_currency', 'USD');

        return in_array($currency, ['USD', 'PEN'], true) ? $currency : 'USD';
    }

    private function is_post_request(): bool
    {
        return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
