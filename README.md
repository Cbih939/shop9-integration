# Shop 9 Integration

O plugin Shop 9 Integration permite integrar o Shop 9 com a sua loja WordPress, facilitando a sincronização de produtos e clientes entre as duas plataformas. Este plugin permite configurar o clientID e clientSecret pelo painel administrativo do WordPress, proporcionando uma integração rápida e segura. 
Com este plugin, é possível configurar detalhes de conexão com a API do Shop 9, ativar o plugin com credenciais específicas e ajustar configurações para o ambiente de teste.

## Features

- **Ativação do Plugin**: Permite a ativação do plugin usando um número de série e código da filial, garantindo uma integração segura e personalizada para cada instalação.
- **Configurações Avançadas**: Configurações detalhadas disponíveis para ajustar a integração em um ambiente de teste, incluindo URL do serviço, porta e senha.
- **Sistema de Abas no Painel Administrativo**: Facilita a navegação entre a ativação do plugin e configurações avançadas, proporcionando uma experiência de usuário aprimorada.
  
## Instalação

1. Faça o download do plugin ZIP através do [repositório do GitHub](https://github.com/Cbih939/shop9-integration/releases/latest).
2. Acesse o painel administrativo do seu site WordPress.
3. Vá para "Plugins" > "Adicionar novo".
4. Clique em "Fazer upload do plugin" e selecione o arquivo ZIP que você baixou.
5. Clique em "Instalar agora" e, em seguida, em "Ativar plugin".

### Configurações

Na aba "Configurações", você pode configurar detalhes para o ambiente de teste, incluindo:

- **URL do Serviço**: URL para conexão com a API do Shop 9.
- **Porta**: Porta utilizada pela API.
- **Senha**: Senha para autenticação com a API.
- **Número de Série para Homologação**: Número de série utilizado para testes.
- **Código da Filial para Homologação**: Código da filial utilizado para testes.
Essas configurações permitem testar a integração em um ambiente controlado antes de colocá-la em produção.

## Funcionalidades

### Sincronização de Produtos

A função de sincronização de produtos permite transferir os produtos da sua loja WordPress para o Shop 9. Para sincronizar os produtos, vá para "Shop 9 Integration" no painel administrativo e clique em "Sincronizar Produtos".

### Requisições de Dados Auxiliares

O plugin permite fazer requisições para obter dados auxiliares do Shop 9, como cores cadastradas. Para isso, vá para "Shop 9 Integration" no painel administrativo e clique em "Requisitar Cores Auxiliares".

### Consulta de Clientes

É possível consultar os clientes cadastrados no Shop 9 diretamente do painel administrativo do WordPress. Para isso, vá para "Shop 9 Integration" no painel administrativo e clique em "Consultar Clientes".

### Cadastrando um Cliente

O serviço também permite o cadastro de novos clientes. Ao fazer a requisição com os dados preenchidos com sucesso é retornado o código cadastrado no Shop Control 9. O código utilizado é sempre o próximo código disponível.

### Cadastrando Contatos para um Cliente

Também há a possibilidade de cadastrar contatos para um cliente. Vamos pegar o cliente que cadastramos agora e cadastrar dois contatos, passando o código como parâmetro na URL.

## Ambiente de Teste

O plugin oferece suporte para configurar e testar a integração com o Shop 9 em um ambiente de teste. Para configurar o ambiente de teste, siga estas etapas:

1. No painel administrativo do WordPress, vá para "Shop 9 Integration".
2. Preencha os campos "Número de Série (Teste)" e "Código da Filial (Teste)" com as credenciais de teste fornecidas pela IdealSoft.
3. Preencha o campo "URL da API do Shop 9" com a URL da API de teste fornecida pela IdealSoft.
4. Clique em "Salvar Configurações" para aplicar as alterações.

## Suporte

Se você encontrar problemas ou tiver dúvidas sobre o plugin, entre em contato conosco através do [repositório do GitHub](https://github.com/Cbih939/shop9-integration/issues). Faremos o possível para ajudar e resolver quaisquer problemas encontrados.

## Contribuições

Contribuições são bem-vindas! Se você quiser melhorar este plugin ou adicionar novos recursos, sinta-se à vontade para enviar um pull request para o [repositório do GitHub](https://github.com/Cbih939/shop9-integration).

## Licença

Este plugin é licenciado sob a Licença MIT. Consulte o arquivo [LICENSE](LICENSE) para obter mais detalhes.
