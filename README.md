#  Módulo Magento para Wide Pay
Módulo desenvolvido para integração entre o sistema Magento e Wide Pay. Com o módulo é possível gerar cobrança para pagamento e liquidação automática pelo Wide Pay após o recebimento.

* **Versão atual:** 1.0.0
* **Versão Magento Testada:** 2.4
* **Acesso Wide Pay**: [Abrir Link](https://www.widepay.com/acessar)
* **API Wide Pay**: [Abrir Link](https://widepay.github.io/api/index.html)
* **Módulos Wide Pay**: [Abrir Link](https://widepay.github.io/api/modulos.html)

# Instalação Plugin

1. Para segurança do seu e-commerce, faça um backup de todos os arquivos e banco de dados.
2. Faça o download pelo link: https://github.com/widepay/mod-magento
2. Após o download concluído, será preciso mesclar as pastas do módulo com a pasta app do Magento.
3. Mescladas as pastas execute os comandos para habilitar e compilar o plugin.
 - php bin/magento module:enable Magento_WidePay
 - php bin/magento setup:upgrade
 - php bin/magento setup:di:compile

# Configuração do Plugin
Lembre-se que para esta etapa, o plugin deve estar instalado e ativado no Magento conforme as orientações descritas acima.

A configuração do Plugin Wide Pay pode ser encontrada no menu: Magento -> Lojas -> Configurações -> Vendas -> Métodos de Pagamento -> Wide Pay.


|Campo|Obrigatório|Descrição|
|--- |--- |--- |
|Habilitado |**Sim** |Marque sim para o módulo ser exibido para o seu cliente na página de checkout|
|Titulo|**Sim**|Nome que será exibido na tela de pagamento|]
|Status do pagamento pendente|**Sim**|Este será o primeiro status de um novo pedido|]
|Descrição |**Sim**|Breve descrição sobre o meio de pagamento|]
|ID da Carteira Wide Pay |**Sim** |Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras|
|Token da Carteira Wide Pay|**Sim**|Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token|
|Tipo da Taxa de Variação|Não|Modifique o valor final do recebimento. Configure aqui um desconto ou acrescimo na venda.|
|Taxa de Variação|Não|O campo acima "Tipo de Taxa de Variação" será aplicado de acordo com este campo. Será adicionado um novo item na cobrança do Wide Pay. Esse item será possível verificar apenas na tela de pagamento do Wide Pay.|
|Acréscimo de Dias no Vencimento|Não|Número em dias para o vencimento do Boleto.|
|Configuração de Multa|Não|Configuração de multa após o vencimento. Valor em porcentagem|
|Configuração de Juros|Não|Configuração de juros após o vencimento. Valor em porcentagem|
|Forma de Recebimento|Não|Selecione entre Boleto, Cartão|
