<?php
namespace PearPay\Admin\Izipay;

use PearPay\Admin\Izipay\Izipaynotify;
use PearPay\Admin\Izipay\IzipayConfig;
class Pay
{
    public function __construct()
    {
        //  pay izipay generate token
        add_action('wp_ajax_izipay_generate_token', [$this, 'izipaytoken_callback']);
        add_action('wp_ajax_nopriv_izipay_generate_token', [$this, 'izipaytoken_callback']);

        // izipay validate
        add_action('wp_ajax_pearpay_validate', [$this, 'izipayvalidate_callback']);
        add_action('wp_ajax_nopriv_pearpay_validate', [$this, 'izipayvalidate_callback']);
    }

    public function izipaytoken_callback()
    {
        // Verificar nonce para seguridad
        check_ajax_referer('pearpay_nonce', 'nonce');

        // Verificar que sea una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'Invalid request method. Only POST is allowed.'], 405);
            wp_die();
        }
        $paydata = stripslashes($_POST['peardata']);
        $paydata = json_decode($paydata, true);
        $paydata = (object) $paydata;

        $config = IzipayConfig::credentials();
        // Validar y sanitizar los datos de entrada
        // $paydata->totalpay
        $amount = $paydata->totalpay * 100;
        $orderId = uniqid();

        $email = $paydata->email;

        if (!$amount || !$orderId || !$email) {
            wp_send_json_error(['message' => 'Missing required parameters.'], 400);
            wp_die();
        }

        // Construir el cuerpo de la solicitud
        $currency = get_option('pearpay_currency', 'USD');
        if (!in_array($currency, ['USD', 'PEN'], true)) {
            $currency = 'USD';
        }

        $body = [
            "amount" => $amount,
            "currency" => $currency,
            "orderId" => $orderId,
            "customer" => [
                "email" => $email,
            ],
        ];

        // Configuración de cURL
        $url = $config['api_url'];
        $auth = $config['username'] . ":" . $config['password'];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-type: application/json"]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_USERPWD, $auth);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        // Ejecutar cURL y manejar errores
        $raw_response = curl_exec($curl);

        if ($raw_response === false) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            wp_send_json_error(['message' => 'cURL error: ' . $error_msg], 500);
            wp_die();
        }

        // Validar código de estado HTTP
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_status !== 200) {
            wp_send_json_error(['message' => 'API request failed.', 'status_code' => $http_status], $http_status);
            wp_die();
        }

        // Decodificar y validar respuesta JSON
        $response = json_decode($raw_response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON response.'], 500);
            wp_die();
        }

        // Enviar respuesta JSON
        wp_send_json_success($response);
        wp_die();
    }

    public function izipayvalidate_callback()
    {
        check_ajax_referer('pearpay_nonce', 'nonce');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'Invalid request method. Only POST is allowed.'], 405);
            wp_die();
        }
        $config = IzipayConfig::credentials();
        // Recibir y decodificar los datos del pago
        $paydata = isset($_POST['paydata']) ? stripslashes($_POST['paydata']) : '';
        $paydata = json_decode($paydata, true);

        // Recibir y decodificar los datos del cliente
        $client = isset($_POST['peardata']) ? stripslashes($_POST['peardata']) : '';
        $client = json_decode($client, true);

        if (!is_array($paydata) || !isset($paydata['hashKey'], $paydata['hash'], $paydata['clientAnswer'])) {
            wp_send_json_error(['message' => 'Datos de pago incompletos o malformateados.']);
            wp_die();
        }

        // Determinar la clave HMAC según el tipo de hashKey
        if ($paydata['hashKey'] === "sha256_hmac") {
            $key = $config['hmac_sha256'] ?? '';
            if ($key === '') {
                wp_send_json_error(['message' => 'Clave HMAC no configurada.']);
                wp_die();
            }
        } else {
            wp_send_json_error(['message' => 'Método de hash no soportado.']);
            wp_die();
        }

        // Convertir clientAnswer en JSON para calcular el hash
        // Si clientAnswer es un array, conviértelo a JSON
        if (is_array($paydata["clientAnswer"])) {
            $krAnswer = json_encode($paydata["clientAnswer"], JSON_UNESCAPED_SLASHES);
        } else {
            // Si es string, asegúrate de que las barras no estén escapadas
            $krAnswer = str_replace('\/', '/', $paydata["clientAnswer"]);
        }
        $calculatedHash = hash_hmac("sha256", $krAnswer, $key);

        // Verificar si el hash calculado coincide con el recibido
        $hashIsValid = ($calculatedHash === $paydata["hash"]);

        // Inicializar mensaje de respuesta
        $message = '';

        // Decodificar clientAnswer para obtener información sobre el estado del pedido
        $clientAnswer = $paydata["clientAnswer"];

        if (is_array($clientAnswer) && isset($clientAnswer['orderStatus'])) {
            if ($clientAnswer['orderStatus'] === "PAID" && $hashIsValid) {
                // Si el pago fue realizado y el hash es válido, enviar notificación
                $orderID = $clientAnswer['orderDetails']['orderId'];
                (new Izipaynotify())->notify($client, $orderID);
                $message = 'Pago realizado correctamente.';
            } else {
                $message = 'El pago no fue realizado o el hash es inválido.';
            }
        } else {
            $message = 'Respuesta del cliente inválida o faltante.';
        }

        wp_send_json_success([
            'message' => $message,
            'client' => $client,
            'hashIsValid' => $hashIsValid,
            'orderStatus' => $clientAnswer['orderStatus'] ?? 'undefined'
        ]);

        wp_die();
    }

}

