<?php
/**
 * Plugin Name: Shop 9 Integration Cbih
 * Description: Integra o Shop 9 com a loja WordPress, permitindo configurar clientID e clientSecret pelo painel.
 * Version: 1.8
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
        register_setting('shop9_integration', 'shop9_test_service_url');
        register_setting('shop9_integration', 'shop9_test_service_port');
        register_setting('shop9_integration', 'shop9_test_service_password');

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

        add_settings_field(
            'shop9_test_service_url',
            'URL do Serviço',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_test_service_url',
                'type' => 'text',
                'name' => 'shop9_test_service_url'
            ]
        );

        add_settings_field(
            'shop9_test_service_port',
            'Porta',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_test_service_port',
                'type' => 'text',
                'name' => 'shop9_test_service_port'
            ]
        );

        add_settings_field(
            'shop9_test_service_password',
            'Senha',
            [$this, 'settingsFieldHTML'],
            'shop9-integration',
            'shop9_integration_section',
            [
                'label_for' => 'shop9_test_service_password',
                'type' => 'password',
                'name' => 'shop9_test_service_password'
            ]
        );
    }

    public function settingsFieldHTML($args) {
        $option = get_option($args['name']);
        echo "<input type='{$args['type']}' id='{$args['name']}' name='{$args['name']}' value='" . esc_attr($option) . "' />";
    }

    public function pluginSettingsPage() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'activate_plugin';
        ?>
        <div class="wrap">
            <h2>Shop 9 Integration</h2>
            <h2 class="nav-tab-wrapper">
                <a href="?page=shop9-integration&tab=activate_plugin" class="nav-tab <?php echo $active_tab == 'activate_plugin' ? 'nav-tab-active' : ''; ?>">Ativar o Plugin</a>
                <a href="?page=shop9-integration&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Configurações</a>
            </h2>
    
            <?php
            if ($active_tab == 'activate_plugin') {
                // Conteúdo da aba Ativar o Plugin aqui
                $this->activatePluginTabContent();
            } else {
                // Conteúdo da aba Configurações aqui
                $this->settingsTabContent();
            }
            ?>
        </div>
        <?php
    }

    private function activatePluginTabContent() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('shop9_integration_activate');
            do_settings_sections('shop9_integration_activate');
            submit_button('Ativar Plugin');
            ?>
        </form>
        <?php
    }
    
    private function settingsTabContent() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('shop9_integration_settings');
            do_settings_sections('shop9_integration_settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function authenticateForTest($serie, $codfilial) {
        $apiUrl = $this->apiUrl;
        $serviceUrl = get_option('shop9_test_service_url');
        $servicePort = get_option('shop9_test_service_port');
        $servicePassword = get_option('shop9_test_service_password');

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
    
        // Obtém os produtos da loja WordPress
        $products = $this->getProductsFromWordPress();
    
        // Verifica se existem produtos para sincronizar
        if (!empty($products)) {
            // Loop através dos produtos e sincronize com o Shop 9
            foreach ($products as $product) {
                $sync_result = $this->syncProductWithShop9($product);
                if (!$sync_result) {
                    // Se houver erro ao sincronizar um produto, envie uma mensagem de erro e pare a sincronização
                    wp_send_json_error('Erro ao sincronizar produtos com o Shop 9.');
                    return;
                }
            }
        }
    
        // Envie uma mensagem de sucesso se a sincronização for concluída sem erros
        wp_send_json_success('Sincronização de produtos completada com sucesso.');
    }
    
    private function syncProductWithShop9($product) {
        // Verifica se o produto tem todas as informações necessárias
        if (empty($product['id']) || empty($product['name']) || empty($product['price'])) {
            // Se faltar alguma informação essencial do produto, retorne falso
            return false;
        }
    
        // Detalhes do produto para sincronização
        $product_id = $product['id'];
        $product_name = $product['name'];
        $product_price = $product['price'];
    
        // Endpoint da API do Shop 9 para sincronização de produto
        $endpoint = '/products/sync';
    
        // Dados do produto para enviar para o Shop 9
        $data = array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'product_price' => $product_price,
            // Adicione outros detalhes do produto, conforme necessário
        );
    
        // Faz a solicitação POST para sincronizar o produto com o Shop 9
        $response = $this->makePostRequest($endpoint, $data);
    
        // Verifica se a solicitação foi bem-sucedida
        if ($response && isset($response['success']) && $response['success'] === true) {
            // Se a sincronização for bem-sucedida, retorne verdadeiro
            return true;
        } else {
            // Se houver um erro na sincronização, retorne falso
            return false;
        }
    }
    
        // Exemplo simplificado de como sincronizar um produto com o Shop 9
        $product_id = $product['id'];
        $product_name = $product['name'];
        $product_price = $product['price'];
    
        // Aqui você enviará os detalhes do produto para o Shop 9
        // Exemplo fictício:
        $response = $this->makePostRequest('/sync/product', array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'product_price' => $product_price,
            // Adicione outros detalhes do produto, conforme necessário
        ));
    
        // Verifica se a solicitação foi bem-sucedida
        if ($response) {
            // Se a sincronização for bem-sucedida, retorne verdadeiro
            return true;
        } else {
            // Se houver um erro na sincronização, retorne falso
            return false;
        }
    }

private function getProductsFromWordPress() {
    $args = array(
        'post_type' => 'product', // Tipo de postagem para produtos
        'post_status' => 'publish', // Apenas produtos publicados
        'posts_per_page' => -1, // Todos os produtos
    );

    // Consulta para obter os produtos
    $products_query = new WP_Query($args);

    // Inicializa um array para armazenar os produtos
    $products = array();

    // Verifica se existem produtos
    if ($products_query->have_posts()) {
        // Loop através dos resultados da consulta
        while ($products_query->have_posts()) {
            $products_query->the_post();
            
            // Obtém os detalhes do produto
            $product_id = get_the_ID();
            $product_name = get_the_title();
            $product_price = get_post_meta($product_id, '_price', true); // Preço do produto (exemplo)

            // Crie um array com os detalhes do produto e adicione à lista de produtos
            $product_data = array(
                'id' => $product_id,
                'name' => $product_name,
                'price' => $product_price,
                // Adicionar outros detalhes do produto, se necessário
            );
            $products[] = $product_data;
        }

        // Restaura os dados originais do post
        wp_reset_postdata();
    }

    // Retorna a lista de produtos
    return $products;
}

    // Exemplo básico:
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $products = get_posts($args);
    
    // Retorna os produtos obtidos
    return $products;

private function syncProductsWithShop9($products) {
    // Implementar a função para sincronizar os produtos com o Shop 9
    
    // Exemplo básico:
    foreach ($products as $product) {
        // Obtém os dados do produto
        $product_id = $product->ID;
        $product_name = $product->post_title;
        $product_price = get_post_meta($product_id, '_price', true); // Exemplo de obtenção do preço
        
        // Função para sincronizar o produto com o Shop 9
        // Por exemplo, enviar uma solicitação POST para criar ou atualizar o produto no Shop 9
        
        // Se ocorrer um erro durante a sincronização, retorne false
        // Se todos os produtos forem sincronizados com sucesso, retorne true
    }
    
    // Retorna true se a sincronização for bem-sucedida, caso contrário, retorna false
    return true;
}

        wp_send_json_success('Sincronização de produtos completada com sucesso.');

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
        // Verifica se o accessToken está presente e não está vazio
        if (!empty($this->accessToken)) {
            // Consulta a API do Shop 9 para validar o token de acesso
            $validation_response = $this->validateAccessToken();
    
            if ($validation_response && isset($validation_response['valid']) && $validation_response['valid'] === true) {
                // O token de acesso ainda é válido
                return true;
            } else {
                // O token de acesso expirou ou é inválido
                // Futuramente será implementado uma função para renovar o token
                // Por enquanto, retornamos falso
                return false;
            }
        } else {
            // Se o accessToken estiver ausente ou vazio, retorne false
            return false;
        }
    }
    
    private function validateAccessToken() {
        // Faz uma solicitação à API do Shop 9 para validar o token de acesso
        $apiUrl = $this->apiUrl;
    
        $response = wp_remote_get("$apiUrl/validate_token", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'cache-control' => 'no-cache'
            ]
        ]);
    
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        } else {
            // Se houver erro na solicitação, ou o código de resposta não for 200,
            // assumimos que o token de acesso é inválido
            return false;
        }
    }

new Shop9Integration();
