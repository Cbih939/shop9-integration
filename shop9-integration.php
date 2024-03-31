<?php
/**
 * Plugin Name: Shop 9 Integration
 * Description: Integra o Shop 9 com a loja WordPress, permitindo configurar clientID e clientSecret pelo painel.
 * Version: 1.9
 * Author: Seimon Athayde
 * Tested up to: 5.9
 * Requires at least: 5.6
 * Requires PHP: 7.0
 */

// Adiciona o menu de configuração e teste no painel administrativo
function meu_plugin_shop_control9_menu() {
    add_menu_page(
        'Configurações Shop Control 9',
        'Shop Control 9',
        'manage_options',
        'meu-plugin-shop-control9',
        'meu_plugin_shop_control9_pagina_configuracao',
        'dashicons-admin-settings', // Ícone da página
        20 // Prioridade do menu
    );

    add_submenu_page(
        'meu-plugin-shop-control9',
        'Teste de Integração',
        'Teste de Integração',
        'manage_options',
        'meu-plugin-shop-control9-teste',
        'meu_plugin_shop_control9_pagina_teste'
    );
}
add_action('admin_menu', 'meu_plugin_shop_control9_menu');

// Função para exibir a página de teste de integração
function meu_plugin_shop_control9_pagina_teste() {
    ?>
    <div class="wrap">
        <h2>Teste de Integração com Shop Control 9</h2>
        <p>Aqui você pode executar testes para verificar a integração com o Shop Control 9.</p>
        <p>Insira os parâmetros necessários e clique em "Executar Teste".</p>
        <form method="post">
            <input type="hidden" name="meu_plugin_shop_control9_teste_nonce" value="<?php echo wp_create_nonce('meu_plugin_shop_control9_teste_nonce'); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="url_servico">URL do Serviço</label></th>
                    <td><input type="text" id="url_servico" name="url_servico"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="porta">Porta</label></th>
                    <td><input type="text" id="porta" name="porta"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="senha">Senha</label></th>
                    <td><input type="text" id="senha" name="senha"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="numero_serie_teste">Número de Série para Homologação</label></th>
                    <td><input type="text" id="numero_serie_teste" name="numero_serie_teste"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="codigo_filial_teste">Código da Filial para Homologação</label></th>
                    <td><input type="text" id="codigo_filial_teste" name="codigo_filial_teste"></td>
                </tr>
            </table>
            <?php submit_button('Executar Teste', 'primary', 'executar_teste', true); ?>
        </form>
        <?php
        if (isset($_POST['executar_teste'])) {
            // Verifica o nonce
            if (!isset($_POST['meu_plugin_shop_control9_teste_nonce']) || !wp_verify_nonce($_POST['meu_plugin_shop_control9_teste_nonce'], 'meu_plugin_shop_control9_teste_nonce')) {
                die('Erro de segurança. Por favor, tente novamente.');
            }

            // Verifica se todos os parâmetros estão presentes
            $parametros_necessarios = array(
                'url_servico',
                'porta',
                'senha',
                'numero_serie_teste',
                'codigo_filial_teste'
            );
            foreach ($parametros_necessarios as $parametro) {
                if (empty($_POST[$parametro])) {
                    echo '<p>O parâmetro "' . $parametro . '" é obrigatório.</p>';
                    return;
                }
            }

            // Conecta-se ao banco de dados do Shop Control 9
            $conectado = conectar_shop_control9($_POST['url_servico'], $_POST['porta'], $_POST['senha'], $_POST['numero_serie_teste'], $_POST['codigo_filial_teste']);

            if ($conectado) {
                // Execute os testes aqui
                echo '<p>Teste executado com sucesso!</p>';
            } else {
                echo '<p>Falha ao conectar ao banco de dados do Shop Control 9. Verifique suas configurações.</p>';
            }
        }
        ?>
    </div>
    <?php
}

// Função para conectar ao banco de dados do Shop Control 9
function conectar_shop_control9($url_servico, $porta, $senha, $numero_serie_teste, $codigo_filial_teste) {
// Adiciona o endpoint personalizado para receber as informações do Shop Control 9 via JSON
add_action('rest_api_init', function () {
    register_rest_route('meu-plugin-shop-control9/v1', '/dados-shop-control9', array(
        'methods' => 'POST',
        'callback' => 'meu_plugin_shop_control9_processar_dados',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ));
});
}

// Função para processar os dados recebidos do Shop Control 9
function meu_plugin_shop_control9_processar_dados(WP_REST_Request $request) {
    // Obter os parâmetros da solicitação
    $dados = $request->get_json_params();

    // Concatenar os campos Método HTTP, Timestamp e Conteúdo BODY (se houver)
    $metodo_http = strtolower($request->get_method());
    $timestamp = time();
    $conteudo_body = isset($dados['body']) ? $dados['body'] : '';
    $conteudo_concatenado = $metodo_http . $timestamp . $conteudo_body;

    // Encriptar os dados concatenados com o algoritmo HMAC SHA256 utilizando a senha informada nas configurações do módulo
    $senha = get_option('meu_plugin_shop_control9_senha'); // Obtenha a senha das configurações do plugin
    $hash_hmac = hash_hmac('sha256', $conteudo_concatenado, $senha);

    // Passar os dados encriptados para Base64
    $conteudo_base64 = base64_encode($hash_hmac);

    // Obter outros dados necessários
    $cod_filial = $dados['cod_filial'];
    $token_autenticacao = get_option('meu_plugin_shop_control9_token'); // Obtenha o token de autenticação das configurações do plugin

    // Montar o cabeçalho da requisição
    $cabecalho = array(
        'CodFilial' => $cod_filial,
        'Authorization' => 'Token ' . $token_autenticacao,
        'Timestamp' => $timestamp
    );

    // Processar os dados recebidos e retornar a resposta no formato JSON
    $resposta = array(
        'sucesso' => true,
        'mensagem' => 'Dados processados com sucesso.',
        'tipo' => 'informacao',
        'complementoTipo' => '',
        'statusCode' => 200,
        'dados' => $cabecalho // Retorne os dados processados ou quaisquer outros dados que deseja fornecer ao WooCommerce
    );

    return new WP_REST_Response($resposta, 200);
}

    // Função para exibir a página de configuração do plugin
function meu_plugin_shop_control9_pagina_configuracao() {
    // Verifica se o usuário tem permissão para acessar a página
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }

    // Verifica se o formulário foi enviado
    if (isset($_POST['submit'])) {
        // Salvar configurações
        update_option('shop_control9_client_id', $_POST['client_id']);
        update_option('shop_control9_client_secret', $_POST['client_secret']);
        update_option('shop_control9_numero_serie_teste', $_POST['numero_serie_teste']);
        update_option('shop_control9_codigo_filial_teste', $_POST['codigo_filial_teste']);
        update_option('shop_control9_url_api', $_POST['url_api']);
        update_option('shop_control9_porta', $_POST['porta']);
        update_option('shop_control9_senha', $_POST['senha']);
    }

    // Recupera as configurações salvas
    $client_id = get_option('shop_control9_client_id');
    $client_secret = get_option('shop_control9_client_secret');
    $numero_serie_teste = get_option('shop_control9_numero_serie_teste');
    $codigo_filial_teste = get_option('shop_control9_codigo_filial_teste');
    $url_api = get_option('shop_control9_url_api');
    $porta = get_option('shop_control9_porta');
    $senha = get_option('shop_control9_senha');
    ?>
    <div class="wrap">
        <div class="meu-plugin-shop-control9-logo">
            <img src="<?php echo plugins_url('logo.png', __FILE__); ?>" alt="Logo do plugin">
        </div>
        <h2>Configurações Shop Control 9</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="client_id">Cliente ID</label></th>
                    <td><input type="text" id="client_id" name="client_id" value="<?php echo esc_attr($client_id); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="client_secret">Cliente Secret</label></th>
                    <td><input type="text" id="client_secret" name="client_secret" value="<?php echo esc_attr($client_secret); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="numero_serie_teste">Número de Série Teste</label></th>
                    <td><input type="text" id="numero_serie_teste" name="numero_serie_teste" value="<?php echo esc_attr($numero_serie_teste); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="codigo_filial_teste">Código da Filial Teste</label></th>
                    <td><input type="text" id="codigo_filial_teste" name="codigo_filial_teste" value="<?php echo esc_attr($codigo_filial_teste); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="url_api">URL da API</label></th>
                    <td><input type="text" id="url_api" name="url_api" value="<?php echo esc_attr($url_api); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="porta">Porta</label></th>
                    <td><input type="text" id="porta" name="porta" value="<?php echo esc_attr($porta); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="senha">Senha</label></th>
                    <td><input type="password" id="senha" name="senha" value="<?php echo esc_attr($senha); ?>"></td>
                </tr>
            </table>
            <?php submit_button('Salvar', 'primary', 'submit', true); ?>
        </form>
    </div>
    <?php
}

// Adiciona o endpoint personalizado para receber as informações do Shop Control 9 via JSON
add_action('rest_api_init', function () {
    register_rest_route('meu-plugin-shop-control9/v1', '/dados-shop-control9', array(
        'methods' => 'POST',
        'callback' => 'meu_plugin_shop_control9_processar_dados',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ));
});