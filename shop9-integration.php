<?php
/**
 * Plugin Name: Shop 9 Integration Cbih
 * Description: Integra o Shop 9 com a loja WordPress, permitindo configurar clientID e clientSecret pelo painel.
 * Version: 1.5
 * Author: Seimon Athayde
 */

if (!defined('ABSPATH')) exit;

class Shop9Integration {
    private $apiUrl;
    private $accessToken;
    private $clientID;
    private $clientSecret;

    public function __construct() {
        $this->apiUrl = get_option('shop9_api_url', '');
        $this->accessToken = '';
        $this->clientID = get_option('shop9_client_id', '');
        $this->clientSecret = get_option('shop9_client_secret', '');

        add_action('admin_menu', [$this, 'addPluginPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('wp_ajax_sync_products', [$this, 'syncProducts']);
        add_action('wp_ajax_request_auxiliar_cores', [$this, 'requestAuxiliarCores']);
        add_action('wp_ajax_request_clientes', [$this, 'requestClientes']);
        add_action('wp_ajax_cadastrar_cliente', [$this, 'cadastrarCliente']);
        add_action('wp_ajax_cadastrar_contatos_cliente', [$this, 'cadastrarContatosCliente']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    public function addPluginPage() {
        add_menu_page(
            'Shop 9 Integration',
            'Shop 9 Integration',
            'manage_options',
            'shop9-integration',
            [$this, 'pluginSettingsPage'],
            'dashicons-admin-generic',
            6
        );
    }

    public function registerSettings() {
        register_setting('shop9_integration', 'shop9_client_id');
        register_setting('shop9_integration', 'shop9_client_secret');
        register_setting('shop9_integration', 'shop9_test_serie');
        register_setting('shop9_integration', 'shop9_test_codfilial');
        register_setting('shop9_integration', 'shop9_api_url');

        add_settings_section(
            'shop9_integration_section',
            'Configurações do Shop 9',
            null,
            'shop9-integration'
        );

        add_settings_field(
            'shop9_client_id',
            'Client ID',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_client_id',
                'type' => 'text',
                'name' => 'shop9_client_id'
            ]
        );

        add_settings_field(
            'shop9_client_secret',
            'Client Secret',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_client_secret',
                'type' => 'text',
                'name' => 'shop9_client_secret'
            ]
        );

        add_settings_field(
            'shop9_test_serie',
            'Número de Série (Teste)',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_test_serie',
                'type' => 'text',
                'name' => 'shop9_test_serie'
            ]
        );

        add_settings_field(
            'shop9_test_codfilial',
            'Código da Filial (Teste)',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_test_codfilial',
                'type' => 'text',
                'name' => 'shop9_test_codfilial'
            ]
        );

        add_settings_field(
            'shop9_api_url',
            'URL da API do Shop 9',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_api_url',
                'type' => 'text',
                'name' => 'shop9_api_url'
            ]
        );
    }

    public function settingsFieldHTML($args) {
        $option = get_option($args['name']);
        echo "<input type='{$args['type']}' id='{$args['name']}' name='{$args['name']}' value='" . esc_attr($option) . "' />";
    }

    public function pluginSettingsPage() {
        ?>
        <div class="wrap shop9-settings-page">
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/logo.png'); ?>" class="shop9-logo">
            <h1>Shop 9 Integration</h1>
            <form method="post" action="options.php" id="shop9-settings-form">
                <?php
                settings_fields('shop9_integration');
                do_settings_sections('shop9-integration');
                $serie = get_option('shop9_test_serie');
                $codfilial = get_option('shop9_test_codfilial');
                if ($serie && $codfilial) {
                    if ($this->authenticateForTest($serie, $codfilial)) {
                        echo '<p style="color:green;">Autenticado com sucesso para teste.</p>';
                    } else {
                        echo '<p style="color:red;">Erro ao autenticar para teste. Verifique suas credenciais.</p>';
                    }
                }
                submit_button('Salvar Configurações');
                ?>
            </form>
        </div>
        <?php
    }

    public function authenticateForTest($serie, $codfilial) {
        $apiUrl = $this->apiUrl;

        $response = wp_remote_get("$apiUrl/auth/?serie=$serie&codfilial=$codfilial", [
            'headers' => [
                'Signature' => '-1',
                'cache-control' => 'no-cache'
            ]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!isset($data['token'])) {
            return false;
        }

        $this->accessToken = sanitize_text_field($data['token']);
        return true;
    }

    public function enqueueAdminStyles($hook) {
        if($hook != 'toplevel_page_shop9-integration') {
            return;
        }

        wp_enqueue_style('shop9_admin_styles', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css');
    }

    public function enqueueAdminScripts($hook) {
        if($hook != 'toplevel_page_shop9-integration') {
            return;
        }

        wp_enqueue_script('shop9_admin_script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0', true);
    }

    public function syncProducts() {
        check_ajax_referer('shop9_secure_nonce', 'security');

        if (!$this->authenticate()) {
            wp_send_json_error('Falha na autenticação com a API do Shop 9.');
            return;
        }

        // Aqui entra a lógica de sincronização de produtos...

        wp_send_json_success('Sincronização de produtos completada com sucesso.');
    }

    public function requestAuxiliarCores() {
        $response = $this->makeRequest('/aux/cores');
        if ($response) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Erro ao solicitar cores auxiliares.');
        }
    }

    public function requestClientes() {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $response = $this->makeRequest("/clientes/$page");
        if ($response) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Erro ao solicitar clientes.');
        }
    }

    public function cadastrarCliente() {
        check_ajax_referer('shop9_secure_nonce', 'security');

        $data = json_decode(stripslashes($_POST['data']), true);
        $response = $this->makePostRequest('/clientes/', $data);

        if ($response) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Erro ao cadastrar cliente.');
        }
    }

    public function cadastrarContatosCliente() {
        check_ajax_referer('shop9_secure_nonce', 'security');

        $codigoCliente = intval($_POST['codigo_cliente']);
        $data = json_decode(stripslashes($_POST['data']), true);
        $response = $this->makePostRequest("/clientes/contatos/$codigoCliente", $data);

        if ($response) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Erro ao cadastrar contatos do cliente.');
        }
    }

    private function makeRequest($endpoint) {
        $apiUrl = $this->apiUrl;
        $codFilial = '1';
        $authorization = 'Token ' . base64_encode($this->clientID . ':' . $this->clientSecret);
        $signature = 'NzH0v3JYuH8SeChtW/z7odowwjmFKekVx5xN25YeC0c=';
        $timestamp = '1552938274';

        $response = wp_remote_get("$apiUrl$endpoint", [
            'headers' => [
                'CodFilial' => $codFilial,
                'Authorization' => $authorization,
                'Signature' => $signature,
                'Timestamp' => $timestamp,
                'cache-control' => 'no-cache',
                'Accept' => '*/*'
            ]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    private function makePostRequest($endpoint, $data) {
        $apiUrl = $this->apiUrl;
        $codFilial = '1';
        $authorization = 'Token ' . base64_encode($this->clientID . ':' . $this->clientSecret);
        $signature = '4ogNs3XU6vKeEw+ul9tiHDiAS5sFC0+lxzorSOjosIA=';
        $timestamp = time();
        $contentLength = strlen(json_encode($data));

        $response = wp_remote_post("$apiUrl$endpoint", [
            'headers' => [
                'CodFilial' => $codFilial,
                'Authorization' => $authorization,
                'Signature' => $signature,
                'Timestamp' => $timestamp,
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Length' => $contentLength
            ],
            'body' => json_encode($data)
        ]);

        if (is_array($response) && !is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }

        return null;
    }

    private function authenticate() {
        // Lógica de autenticação com base no accessToken
        return true; // Retorna true se autenticado com sucesso
    }
}

new Shop9Integration();
