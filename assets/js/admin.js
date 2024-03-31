jQuery(document).ready(function($) {
    // Função para processar a requisição de teste de integração
    $('#executar_teste').on('click', function(e) {
        e.preventDefault();
        
        // Obtenha os valores dos campos
        var url_servico = $('#url_servico').val();
        var porta = $('#porta').val();
        var senha = $('#senha').val();
        var numero_serie_teste = $('#numero_serie_teste').val();
        var codigo_filial_teste = $('#codigo_filial_teste').val();
        
        // Verifique se todos os campos estão preenchidos
        if (url_servico && porta && senha && numero_serie_teste && codigo_filial_teste) {
            // Faça a requisição AJAX para processar o teste de integração
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'executar_teste_integracao',
                    url_servico: url_servico,
                    porta: porta,
                    senha: senha,
                    numero_serie_teste: numero_serie_teste,
                    codigo_filial_teste: codigo_filial_teste,
                    security: ajax_nonce // Certifique-se de incluir o nonce de segurança
                },
                success: function(response) {
                    // Exiba a mensagem de sucesso ou erro
                    alert(response.message);
                },
                error: function(xhr, status, error) {
                    // Exiba a mensagem de erro
                    alert('Erro: ' + error);
                }
            });
        } else {
            // Exiba uma mensagem de erro se algum campo estiver vazio
            alert('Por favor, preencha todos os campos.');
        }
    });
    
    // Função para processar a requisição de salvamento de configurações
    $('#salvar_configuracoes').on('click', function(e) {
        e.preventDefault();
        
        // Obtenha os valores dos campos
        var client_id = $('#client_id').val();
        var client_secret = $('#client_secret').val();
        var numero_serie_teste = $('#numero_serie_teste').val();
        var codigo_filial_teste = $('#codigo_filial_teste').val();
        var url_api = $('#url_api').val();
        var porta = $('#porta').val();
        var senha = $('#senha').val();
        
        // Verifique se todos os campos estão preenchidos
        if (client_id && client_secret && numero_serie_teste && codigo_filial_teste && url_api && porta && senha) {
            // Faça a requisição AJAX para salvar as configurações
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'salvar_configuracoes_shop_control9',
                    client_id: client_id,
                    client_secret: client_secret,
                    numero_serie_teste: numero_serie_teste,
                    codigo_filial_teste: codigo_filial_teste,
                    url_api: url_api,
                    porta: porta,
                    senha: senha,
                    security: ajax_nonce // Certifique-se de incluir o nonce de segurança
                },
                success: function(response) {
                    // Exiba a mensagem de sucesso ou erro
                    alert(response.message);
                },
                error: function(xhr, status, error) {
                    // Exiba a mensagem de erro
                    alert('Erro: ' + error);
                }
            });
        } else {
            // Exiba uma mensagem de erro se algum campo estiver vazio
            alert('Por favor, preencha todos os campos.');
        }
    });
});
