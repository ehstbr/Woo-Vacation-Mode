# Woo Vacation Mode Basic

![Woo Vacation Mode Basic](assets/banner-pt-BR.png)

[English](README.md)

Um plugin leve e de código aberto para colocar lojas WooCommerce em modo férias. Ele permite interromper novos pedidos sem tirar a loja do ar, mantendo os clientes informados de forma clara.

## Recursos

- Ativação e desativação do modo férias pelo painel do WordPress.
- Barra de aviso em largura total no topo ou rodapé do site.
- Editor WYSIWYG para o aviso principal, com alinhamento e tamanho de fonte.
- Cores personalizáveis para fundo, texto e botão de fechar.
- Botão de fechar opcional com tempo configurável para reexibir o aviso.
- Exibição do aviso em todo o site ou apenas nas páginas do WooCommerce.
- Opção para ocultar os botões padrão de compra.
- Mensagem personalizada no lugar dos controles de compra.
- Mensagem independente para carrinho e checkout.
- Suporte a HTML simples e seguro nas mensagens de carrinho/checkout e substituição do botão.
- Bloqueio de novas compras na validação do WooCommerce.
- Pronto para tradução, com arquivo `.pot`.
- Tradução para Português do Brasil (`pt_BR`) incluída.
- Interface administrativa nativa do WordPress, sem framework visual externo.

## Instalação

1. Baixe o ZIP da release.
2. No WordPress, acesse **Plugins > Adicionar novo > Enviar plugin**.
3. Envie o ZIP e ative o **Woo Vacation Mode Basic**.
4. Acesse **WooCommerce > Modo férias**.
5. Configure o aviso, cores, mensagens e comportamento das compras.

## Mensagens exibidas ao cliente

O plugin trabalha com três mensagens independentes:

1. **Mensagem da barra de aviso** — editada no WYSIWYG do WordPress.
2. **Mensagem do carrinho e checkout** — aviso específico do WooCommerce com suporte a HTML simples e seguro.
3. **Texto substituto para os botões de compra** — exibido na área onde normalmente ficam os controles de compra, também com HTML simples.

Entre as tags simples aceitas estão `<b>`, `<strong>`, `<i>`, `<em>`, `<u>`, `<br>`, `<p>` e `<center>`.

## Traduções

O idioma-base do código é inglês. A tradução `pt_BR` já está incluída. Os arquivos são codificados em UTF-8:

- `languages/woo-vacation-mode-basic.pot`
- `languages/woo-vacation-mode-basic-pt_BR.po`
- `languages/woo-vacation-mode-basic-pt_BR.mo`

## Compatibilidade

O plugin usa hooks padrão do WooCommerce e fallbacks em CSS. Temas ou construtores de páginas que substituam profundamente a marcação padrão de compra podem precisar de ajustes específicos de compatibilidade.

## Autor

Desenvolvido por **Eduardo Henrique Teixeira**, profissional de e-commerce e marketplaces e entusiasta de software de código aberto.

- GitHub do projeto: [https://github.com/ehstbr/Woo-Vacation-Mode](https://github.com/ehstbr/Woo-Vacation-Mode)
- Perfil: [https://github.com/ehstbr](https://github.com/ehstbr)

Issues, pull requests e melhorias de compatibilidade são bem-vindos.

## Licença

Licenciado sob a **GNU General Public License v3.0 ou posterior (GPL-3.0-or-later)**. Consulte [LICENSE](LICENSE).
