=== WA Suit - WA Web Design ===
Contributors: wawebdesign
Tags: administration, modules, links, elementor, shortcodes
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.0.0
License: MIT

Plugin oficial da WA Web Design com design nível Apple, controle modular de funções e gerenciador centralizado de links dinâmicos para botões do Elementor.

== Description ==

O **WA Suit** é o plugin oficial da **WA Web Design** desenvolvido para agilizar a criação e manutenção de sites em WordPress e Elementor.

### Principais Características:
* **Design Nível Apple**: Interface minimalista em Branco e Preto, detalhes em Rosa e degradê sutil Roxo/Amarelo como micro-detalhe. Sem poluição visual e direto ao ponto.
* **Ordem Superior no Painel**: Posicionado logo abaixo do "Painel" do WordPress (ordem mais alta).
* **Arquitetura 100% Modular**: Cada função do plugin vive em seu próprio arquivo dentro da pasta `modules/`. O painel descobre arquivos automaticamente.
* **Abas Laterais Dinâmicas**: Cada função possui uma chave liga/desliga no Painel Geral. Ao ligar a função, sua aba aparece imediatamente na lateral esquerda para configuração.

### Primeira Função: Links Dinâmicos1
Centralize links em shortcodes para usar em botões do Elementor.
1. Cadastre links (ex: WhatsApp, Checkout, Agendamento).
2. O plugin gera o shortcode `[wa_link id="slug"]`.
3. Cole o shortcode nos botões do Elementor.
4. Quando precisar trocar o número do WhatsApp ou o link do checkout, mude apenas no painel e **todos os botões do site mudam de uma vez só!**

### Códigos Personalizados
Crie vários snippets em HTML, CSS, JavaScript, PHP ou Shortcode e escolha posição, escopo e dispositivos (desktop, tablet ou celular). O módulo aceita várias páginas e artigos específicos, além de vários textos de URL (um por linha). O status de cada snippet pode ser alternado diretamente no cartão, sem abrir o editor, e o editor de código exibe numeração de linhas.

Snippets mais específicos são impressos depois dos globais; entre regras de URL, a correspondência mais longa vem depois. Shortcodes são executados no body ou footer, não no head. PHP executa no servidor e não aceita filtro por dispositivo. HTML com filtro de dispositivo deve ser colocado no body ou footer.

== Installation ==

1. Envie a pasta `Plugin-WA-Suit` para o diretório `/wp-content/plugins/` da sua instalação WordPress.
2. Ative o plugin no menu **Plugins** do WordPress.
3. Acesse o menu **WA Web Design** (logo abaixo do Painel) para gerenciar as funções e links.

== Como Criar Uma Nova Função ==

Para adicionar uma nova função no futuro, basta criar um novo arquivo PHP na pasta `modules/` (ex: `modules/nova-funcao.php`):

```php
<?php
class WA_Suit_Module_Nova_Funcao extends WA_Suit_Module_Base {
    public function get_id()          { return 'nova-funcao'; }
    public function get_title()       { return 'Minha Nova Função'; }
    public function get_description() { return 'Descrição clara e objetiva.'; }
    public function get_icon()        { return 'dashicons-star-filled'; }
    public function get_category()    { return 'Ferramentas'; }

    public function init() {
        // Código executado quando a função for ativada
    }

    public function render_tab() {
        // HTML da aba lateral
    }
}
wa_suit_register_module( new WA_Suit_Module_Nova_Funcao() );
```

== Changelog ==

= 1.0.0 =
* Lançamento com design Apple e módulo de Links Dinâmicos para Elementor com auto-substituição de shortcodes em botões.
