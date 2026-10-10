<?php

/**
 * Code snippets module.
 *
 * @package WASuit
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Stores and runs code snippets on selected front-end locations.
 */
class WA_Suit_Module_Code_Snippets extends WA_Suit_Module_Base
{
	/**
	 * Available snippet languages.
	 *
	 * @var array<string, string>
	 */
	private $languages = array(
		'html' => 'HTML',
		'css'  => 'CSS',
		'js'   => 'JavaScript',
		'php'  => 'PHP',
		'shortcode' => 'Shortcode',
	);

	/**
	 * Available front-end output locations.
	 *
	 * @var array<string, string>
	 */
	private $locations = array(
		'head'      => 'Cabeçalho — antes de fechar o head',
		'body_open' => 'Início do body — após abrir a página',
		'footer'    => 'Rodapé — antes de fechar o body',
	);

	/**
	 * Available display conditions.
	 *
	 * @var array<string, string>
	 */
	private $targets = array(
		'all'          => 'Site inteiro',
		'page'         => 'Páginas, posts ou produtos',
		'post'         => 'Posts',
		'url_contains' => 'URL contém',
	);

	/**
	 * Available device groups.
	 *
	 * @var array<string, string>
	 */
	private $devices = array(
		'all'     => 'Todos',
		'desktop' => 'Desktop',
		'tablet'  => 'Tablet',
		'mobile'  => 'Celular',
	);

	/**
	 * Registers settings handlers even when front-end snippet execution is disabled.
	 */
	public function __construct()
	{
		add_action('admin_post_wa_suit_save_code_snippets', array($this, 'save_snippets'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
		add_action('wp_ajax_wa_suit_toggle_code_snippet', array($this, 'ajax_toggle_snippet'));
	}

	public function get_id()
	{
		return 'code-snippets';
	}

	public function get_title()
	{
		return __('Códigos Personalizados', 'wa-suit');
	}

	public function get_description()
	{
		return __('Adicione HTML, CSS, JavaScript, PHP ou shortcodes e escolha onde e em quais páginas executar.', 'wa-suit');
	}

	public function get_icon()
	{
		return 'dashicons-editor-code';
	}

	public function get_category()
	{
		return __('Desenvolvimento', 'wa-suit');
	}

	public function get_order()
	{
		return 20;
	}

	/**
	 * Registers snippet management and front-end output hooks.
	 *
	 * @return void
	 */
	public function init()
	{
		add_action('wp_head', array($this, 'render_head_snippets'), 99);
		add_action('wp_body_open', array($this, 'render_body_snippets'), 1);
		add_action('wp_footer', array($this, 'render_footer_snippets'), 99);
	}

	/**
	 * Enqueues module controls on its settings tab.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets($hook_suffix)
	{
		if (
			! isset($_GET['page'], $_GET['tab']) ||
			! is_string($_GET['page']) ||
			! is_string($_GET['tab']) ||
			'wa-suit' !== sanitize_key(wp_unslash($_GET['page'])) ||
			$this->get_id() !== sanitize_key(wp_unslash($_GET['tab']))
		) {
			return;
		}

		wp_enqueue_script(
			'wa-suit-code-snippets-admin',
			WA_SUIT_URL . 'assets/js/code-snippets-admin.js',
			array(),
			filemtime(WA_SUIT_PATH . 'assets/js/code-snippets-admin.js'),
			true
		);
		wp_enqueue_style(
			'wa-suit-code-snippets-admin',
			WA_SUIT_URL . 'assets/css/code-snippets-admin.css',
			array(),
			filemtime(WA_SUIT_PATH . 'assets/css/code-snippets-admin.css')
		);
		wp_localize_script(
			'wa-suit-code-snippets-admin',
			'waSuitCodeSnippets',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => WA_Suit_Security::create_nonce(),
				'strings' => array(
					'saving'         => __('Salvando...', 'wa-suit'),
					'error'          => __('Não foi possível atualizar o código.', 'wa-suit'),
					'urlPlaceholder' => __('Ex.: campanha ou /promocao/', 'wa-suit'),
					'urlMatch'       => __('Texto que a URL deve conter', 'wa-suit'),
					'removeUrl'      => __('Remover condição de URL', 'wa-suit'),
					'addUrl'         => __('Adicionar condição', 'wa-suit'),
					'urlRequired'    => __('Adicione pelo menos um trecho de URL para usar esta regra.', 'wa-suit'),
					'posts'          => __('Posts', 'wa-suit'),
					'products'       => __('Produtos', 'wa-suit'),
					'content'        => class_exists('WooCommerce') && post_type_exists('product')
						? __('Páginas, posts ou produtos', 'wa-suit')
						: __('Páginas ou posts', 'wa-suit'),
					'requiredFields' => __('Preencha o nome e o código antes de salvar.', 'wa-suit'),
				),
			)
		);
	}

	/**
	 * Toggles one snippet without requiring the editor form to be opened or saved.
	 *
	 * @return void
	 */
	public function ajax_toggle_snippet()
	{
		if (! WA_Suit_Security::can_manage_settings()) {
			wp_send_json_error(array('message' => __('Você não tem permissão para alterar este código.', 'wa-suit')), 403);
		}

		$nonce = isset($_POST['nonce']) && is_string($_POST['nonce'])
			? sanitize_text_field(wp_unslash($_POST['nonce']))
			: '';
		if (! WA_Suit_Security::verify_nonce($nonce)) {
			wp_send_json_error(array('message' => __('Sessão expirada. Atualize a página e tente novamente.', 'wa-suit')), 403);
		}

		$id = isset($_POST['snippet_id']) && is_string($_POST['snippet_id'])
			? sanitize_text_field(wp_unslash($_POST['snippet_id']))
			: '';
		$enabled = isset($_POST['enabled']) && is_string($_POST['enabled']) && '1' === wp_unslash($_POST['enabled']);
		$settings = $this->get_settings();
		$snippets = isset($settings['snippets']) && is_array($settings['snippets']) ? $settings['snippets'] : array();

		if ('' === $id || ! isset($snippets[$id]) || ! is_array($snippets[$id])) {
			wp_send_json_error(array('message' => __('Código não encontrado.', 'wa-suit')), 404);
		}

		$snippets[$id]['enabled'] = $enabled ? '1' : '0';
		$this->update_settings(array('snippets' => $snippets));

		wp_send_json_success(
			array(
				'enabled' => $enabled,
				'message' => $enabled ? __('Código ativado.', 'wa-suit') : __('Código desativado.', 'wa-suit'),
			)
		);
	}

	/**
	 * Saves all submitted snippets after checking authorization and nonce.
	 *
	 * @return void
	 */
	public function save_snippets()
	{
		if (! WA_Suit_Security::can_manage_settings()) {
			wp_die(esc_html__('Você não tem permissão para editar estes códigos.', 'wa-suit'));
		}

		check_admin_referer('wa_suit_save_code_snippets');

		$submitted = isset($_POST['snippets']) && is_array($_POST['snippets'])
			? wp_unslash($_POST['snippets'])
			: array();
		$snippets  = array();

		foreach ($submitted as $key => $input) {
			if (! is_array($input)) {
				continue;
			}

			if (empty($input['title']) && empty($input['code'])) {
				continue;
			}

			$snippet = $this->sanitize_snippet($input);
			if (null === $snippet) {
				wp_die(esc_html__('Um ou mais códigos estão incompletos ou têm opções inválidas. Revise nome, código, linguagem e regra de exibição.', 'wa-suit'));
			}

			$id                = sanitize_key((string) $key);
			$snippets[$id ? $id : wp_generate_uuid4()] = $snippet;
		}

		$this->update_settings(array('snippets' => $snippets));

		$redirect_url = add_query_arg(
			array(
				'page'           => 'wa-suit',
				'tab'            => $this->get_id(),
				'snippets-saved' => '1',
			),
			admin_url('admin.php')
		);

		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * Renders the snippets editor.
	 *
	 * @return void
	 */
	public function render_tab()
	{
		$snippets = $this->get_setting('snippets', array());
		if (! is_array($snippets)) {
			$snippets = array();
		}

		$selectable_post_types = array('page', 'post');
		if (class_exists('WooCommerce') && post_type_exists('product')) {
			$selectable_post_types[] = 'product';
		}

		$selectable_posts = get_posts(
			array(
				'post_type'      => $selectable_post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$post_type_labels = array(
			'post'    => __('Post', 'wa-suit'),
			'page'    => __('Página', 'wa-suit'),
			'product' => __('Produto', 'wa-suit'),
		);
		$targets = $this->get_available_targets();
?>
		<div class="wa-code-snippets">
			<div class="wa-code-snippets__header">
				<div>
					<h2><?php echo esc_html__('Códigos Personalizados', 'wa-suit'); ?></h2>
					<p><?php echo esc_html__('Crie códigos separados e escolha onde cada um será carregado no site.', 'wa-suit'); ?></p>
				</div>
				<button type="button" class="wa-admin-btn wa-admin-btn--primary" data-wa-snippet-add>
					<?php echo esc_html__('Adicionar código', 'wa-suit'); ?>
				</button>
			</div>

			<?php if (isset($_GET['snippets-saved']) && is_string($_GET['snippets-saved']) && '1' === sanitize_text_field(wp_unslash($_GET['snippets-saved']))) : ?>
				<div class="notice notice-success inline">
					<p><?php echo esc_html__('Códigos salvos.', 'wa-suit'); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-wa-snippet-form>
				<input type="hidden" name="action" value="wa_suit_save_code_snippets">
				<?php wp_nonce_field('wa_suit_save_code_snippets'); ?>

				<div class="wa-code-snippets__list" data-wa-snippet-list>
					<?php foreach ($snippets as $id => $snippet) : ?>
						<?php
						if (is_array($snippet) && is_scalar($id)) {
							$this->render_snippet_fields(sanitize_key((string) $id), $snippet, $selectable_posts);
						}
						?>
					<?php endforeach; ?>
				</div>

				<template data-wa-snippet-template>
					<?php $this->render_snippet_fields('__SNIPPET_KEY__', array(), $selectable_posts); ?>
				</template>

				<div class="wa-code-snippets__actions">
					<button type="submit" class="wa-admin-btn wa-admin-btn--primary">
						<?php echo esc_html__('Salvar códigos', 'wa-suit'); ?>
					</button>
				</div>
			</form>

			<div class="notice notice-warning inline">
				<p><?php echo esc_html__('HTML, CSS, JavaScript e o resultado de shortcodes são enviados ao navegador. PHP executa no servidor para todos os visitantes que correspondam à regra escolhida, com acesso às funções do WordPress. PHP não pode ser limitado por dispositivo. Use apenas código confiável e mantenha um backup.', 'wa-suit'); ?></p>
			</div>

			<div class="notice notice-info inline">
				<p><?php echo esc_html__('Você pode selecionar várias páginas/artigos ou informar vários textos de URL (um por linha). Quando mais de um código corresponder, o site inteiro é carregado primeiro, depois as regras de URL (as correspondências mais longas por último) e, por fim, páginas e artigos específicos.', 'wa-suit'); ?></p>
			</div>

		</div>
	<?php
	}

	/**
	 * Outputs snippets registered for the document head.
	 *
	 * @return void
	 */
	public function render_head_snippets()
	{
		$this->render_location('head');
	}

	/**
	 * Outputs snippets registered for the beginning of the document body.
	 *
	 * @return void
	 */
	public function render_body_snippets()
	{
		$this->render_location('body_open');
	}

	/**
	 * Outputs snippets registered for the end of the document body.
	 *
	 * @return void
	 */
	public function render_footer_snippets()
	{
		$this->render_location('footer');
	}

	/**
	 * Renders snippets at a location when their display condition matches.
	 *
	 * @param string $location Current document location.
	 * @return void
	 */
	private function render_location($location)
	{
		if (is_admin()) {
			return;
		}

		$snippets = $this->get_setting('snippets', array());
		if (! is_array($snippets)) {
			return;
		}

		$matched_snippets = array();
		$sequence         = 0;

		foreach ($snippets as $id => $snippet) {
			if (! is_array($snippet) || empty($snippet['enabled']) || ! isset($snippet['location'], $snippet['language'], $snippet['target']) || $location !== $snippet['location'] || ! isset($this->languages[$snippet['language']]) || ! $this->matches_target($snippet)) {
				continue;
			}

			$target_rank = 'all' === $snippet['target'] ? 0 : ('url_contains' === $snippet['target'] ? 1 : 2);
			$url_length  = 'url_contains' === $snippet['target'] ? $this->get_matching_url_specificity($snippet) : 0;
			$devices     = $this->get_snippet_devices($snippet);
			$device_rank = in_array('all', $devices, true) ? 0 : 1;

			$matched_snippets[] = array(
				'id'          => $id,
				'snippet'     => $snippet,
				'target_rank' => $target_rank,
				'url_length'  => $url_length,
				'device_rank' => $device_rank,
				'sequence'    => $sequence++,
			);
		}

		usort(
			$matched_snippets,
			function ($first, $second) {
				if ($first['target_rank'] !== $second['target_rank']) {
					return $first['target_rank'] < $second['target_rank'] ? -1 : 1;
				}

				if ($first['url_length'] !== $second['url_length']) {
					return $first['url_length'] < $second['url_length'] ? -1 : 1;
				}

				if ($first['device_rank'] !== $second['device_rank']) {
					return $first['device_rank'] < $second['device_rank'] ? -1 : 1;
				}

				return $first['sequence'] < $second['sequence'] ? -1 : 1;
			}
		);

		foreach ($matched_snippets as $entry) {
			$this->output_snippet($entry['id'], $entry['snippet']);
		}
	}

	/**
	 * Evaluates PHP in an isolated method scope.
	 *
	 * @param string $code PHP statements.
	 * @return void
	 */
	private function run_php_code($code)
	{
		eval($code); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- Executing admin-authored PHP is an explicit module feature.
	}

	/**
	 * Prints the snippet in its selected language.
	 *
	 * @param string $id      Snippet identifier.
	 * @param array  $snippet Snippet settings.
	 * @return void
	 */
	private function output_snippet($id, $snippet)
	{
		$code = isset($snippet['code']) ? $snippet['code'] : '';
		if ('' === $code) {
			return;
		}

		$devices = $this->get_snippet_devices($snippet);

		switch ($snippet['language']) {
			case 'html':
				$this->output_html_for_devices($code, $id, $devices);
				break;
			case 'css':
				if (array('all') !== $devices) {
					$queries = $this->get_device_queries($devices);
					$code    = '@media screen and ' . implode(', screen and ', $queries) . " {\n" . $code . "\n}";
				}
				echo "\n<style>\n" . $code . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-authored CSS is the feature's purpose.
				break;
			case 'js':
				if (array('all') !== $devices) {
					$checks = array();
					foreach ($this->get_device_queries($devices) as $query) {
						$checks[] = 'window.matchMedia(' . wp_json_encode($query) . ').matches';
					}
					$code = 'if (' . implode(' || ', $checks) . ") {\n" . $code . "\n}";
				}
				echo "\n<script>\n" . $code . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-authored JavaScript is the feature's purpose.
				break;
			case 'php':
				$this->execute_php_snippet($id, $code);
				break;
			case 'shortcode':
				$this->output_html_for_devices(do_shortcode($code), $id, $devices);
				break;
		}
	}

	/**
	 * Outputs HTML immediately or only at matching viewport widths.
	 *
	 * @param string   $html    Rendered HTML.
	 * @param string   $id      Snippet identifier.
	 * @param string[] $devices Selected device groups.
	 * @return void
	 */
	private function output_html_for_devices($html, $id, $devices)
	{
		if (array('all') === $devices) {
			echo "\n" . $html . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-authored HTML/shortcode output is the feature's purpose.
			return;
		}

		$queries = wp_json_encode($this->get_device_queries($devices));
		echo "\n<template data-wa-suit-device-html>" . $html . "</template>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-authored HTML/shortcode output is the feature's purpose.
		echo '<script>(function(t,q){if(q.some(function(m){return window.matchMedia(m).matches;})){var f=t.content.cloneNode(true);f.querySelectorAll("script").forEach(function(o){var s=document.createElement("script");Array.from(o.attributes).forEach(function(a){s.setAttribute(a.name,a.value);});s.textContent=o.textContent;o.parentNode.replaceChild(s,o);});t.parentNode.insertBefore(f,t);}t.remove();})(document.currentScript.previousElementSibling,' . $queries . ");</script>\n";
	}

	/**
	 * Converts selected device names into viewport media queries.
	 *
	 * @param string[] $devices Selected device names.
	 * @return string[]
	 */
	private function get_device_queries($devices)
	{
		$queries = array();
		$map     = array(
			'mobile'  => '(max-width: 767px)',
			'tablet'  => '(min-width: 768px) and (max-width: 1024px)',
			'desktop' => '(min-width: 1025px)',
		);

		foreach ($devices as $device) {
			if (isset($map[$device])) {
				$queries[] = $map[$device];
			}
		}

		return $queries;
	}

	/**
	 * Returns validated device values for a saved snippet.
	 *
	 * @param array $snippet Snippet settings.
	 * @return string[]
	 */
	private function get_snippet_devices($snippet)
	{
		$devices = isset($snippet['devices']) && is_array($snippet['devices'])
			? array_values(array_intersect($snippet['devices'], array_keys($this->devices)))
			: array('all');

		if (empty($devices) || in_array('all', $devices, true)) {
			return array('all');
		}

		return $devices;
	}

	/**
	 * Returns all URL patterns, including the legacy single-pattern setting.
	 *
	 * @param array $snippet Snippet settings.
	 * @return string[]
	 */
	private function get_url_matches($snippet)
	{
		if (isset($snippet['url_matches']) && is_array($snippet['url_matches']) && ! empty($snippet['url_matches'])) {
			$patterns = $snippet['url_matches'];
		} elseif (isset($snippet['url_match']) && is_string($snippet['url_match'])) {
			$patterns = array($snippet['url_match']);
		} elseif (isset($snippet['url_matches']) && is_array($snippet['url_matches'])) {
			$patterns = $snippet['url_matches'];
		} else {
			$patterns = array();
		}

		return array_values(
			array_filter(
				array_map(
					'trim',
					array_filter(
						$patterns,
						function ($pattern) {
							return is_string($pattern);
						}
					)
				),
				function ($pattern) {
					return '' !== $pattern;
				}
			)
		);
	}

	/**
	 * Returns the longest URL pattern matching the current request.
	 *
	 * @param array $snippet Snippet settings.
	 * @return int
	 */
	private function get_matching_url_specificity($snippet)
	{
		$request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
			? wp_unslash($_SERVER['REQUEST_URI'])
			: '';
		$specificity = 0;

		foreach ($this->get_url_matches($snippet) as $pattern) {
			if (false !== stripos($request_uri, $pattern)) {
				$specificity = max($specificity, strlen($pattern));
			}
		}

		return $specificity;
	}

	/**
	 * Executes a PHP snippet and logs runtime errors without exposing details to visitors.
	 *
	 * PHP snippets must contain statements without opening or closing PHP tags.
	 *
	 * @param string $id   Snippet identifier.
	 * @param string $code PHP statements.
	 * @return void
	 */
	private function execute_php_snippet($id, $code)
	{
		$buffer_level = ob_get_level();
		ob_start();

		try {
			$this->run_php_code($code);
			$output = ob_get_clean();
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PHP snippets intentionally generate front-end output.
		} catch (Throwable $error) {
			while (ob_get_level() > $buffer_level) {
				ob_end_clean();
			}

			error_log(
				sprintf(
					'WA Suit PHP snippet "%s" failed: %s',
					sanitize_key($id),
					$error->getMessage()
				)
			);
		}
	}

	/**
	 * Checks whether a snippet's page or URL rule matches the current request.
	 *
	 * @param array $snippet Snippet settings.
	 * @return bool
	 */
	private function matches_target($snippet)
	{
		if (! isset($snippet['target'])) {
			return false;
		}

		if (in_array($snippet['target'], array('post', 'product'), true)) {
			$post_ids = isset($snippet['post_ids']) && is_array($snippet['post_ids']) ? array_map('absint', $snippet['post_ids']) : array();
			$post_id  = get_queried_object_id();
			$post_type = 'product' === $snippet['target'] ? 'product' : 'post';

			return $post_id && in_array($post_id, $post_ids, true) && is_singular($post_type);
		}

		if ('all' === $snippet['target']) {
			return true;
		}

		if ('page' === $snippet['target']) {
			$post_ids = isset($snippet['post_ids']) && is_array($snippet['post_ids'])
				? array_map('absint', $snippet['post_ids'])
				: (isset($snippet['page_id']) ? array(absint($snippet['page_id'])) : array());

			foreach ($post_ids as $post_id) {
				if (is_page($post_id) || is_single($post_id)) {
					return true;
				}
			}

			return false;
		}

		if ('url_contains' === $snippet['target']) {
			$request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
			$patterns    = $this->get_url_matches($snippet);

			foreach ($patterns as $pattern) {
				if (is_string($pattern) && '' !== $pattern && false !== stripos($request_uri, $pattern)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Renders the controls for one snippet.
	 *
	 * @param string $key     Snippet identifier.
	 * @param array  $snippet Snippet values.
	 * @param array  $selectable_posts Published pages and posts.
	 * @return void
	 */
	private function render_snippet_fields($key, $snippet, $selectable_posts)
	{
		$targets = $this->get_available_targets();
		$post_type_labels = array(
			'post'    => __('Post', 'wa-suit'),
			'page'    => __('Página', 'wa-suit'),
			'product' => __('Produto', 'wa-suit'),
		);
		$snippet = wp_parse_args(
			$snippet,
			array(
				'title'     => '',
				'enabled'   => '1',
				'language'  => 'html',
				'location'  => 'footer',
				'target'    => 'all',
				'page_id'   => '',
				'url_match' => '',
				'post_ids'  => array(),
				'url_matches' => array(),
				'devices'   => array('all'),
				'code'      => '',
			)
		);
		if (empty($snippet['post_ids']) && ! empty($snippet['page_id'])) {
			$snippet['post_ids'] = array(absint($snippet['page_id']));
		}
		$snippet['url_matches'] = $this->get_url_matches($snippet);
		if (! is_array($snippet['post_ids'])) {
			$snippet['post_ids'] = array();
		}
		if (! is_array($snippet['url_matches'])) {
			$snippet['url_matches'] = array();
		}
		if (! is_array($snippet['devices']) || empty($snippet['devices'])) {
			$snippet['devices'] = array('all');
		}
		$prefix  = 'snippets[' . $key . ']';
	?>
		<details class="wa-code-snippet" data-wa-snippet>
			<summary class="wa-code-snippet__summary">
				<strong data-wa-snippet-title><?php echo esc_html('' !== $snippet['title'] ? $snippet['title'] : __('Novo código', 'wa-suit')); ?></strong>
				<span data-wa-snippet-summary><?php echo esc_html($this->snippet_summary($snippet)); ?></span>
				<span class="wa-code-snippet__switch-wrap" data-wa-snippet-switch-wrap>
					<input type="hidden" name="<?php echo esc_attr($prefix); ?>[enabled]" value="0">
					<input class="wa-code-snippet__switch" type="checkbox" name="<?php echo esc_attr($prefix); ?>[enabled]" value="1" data-wa-snippet-enabled data-snippet-id="<?php echo esc_attr($key); ?>" data-snippet-persisted="<?php echo esc_attr('__SNIPPET_KEY__' === $key ? '0' : '1'); ?>" aria-label="<?php echo esc_attr(sprintf(__('Ativar %s', 'wa-suit'), $snippet['title'])); ?>" <?php checked('1', (string) $snippet['enabled']); ?>>
					<span class="wa-code-snippet__switch-ui" aria-hidden="true"></span>
					<span class="wa-code-snippet__switch-status" data-wa-snippet-enabled-label><?php echo '1' === (string) $snippet['enabled'] ? esc_html__('Ativo', 'wa-suit') : esc_html__('Inativo', 'wa-suit'); ?></span>
				</span>
			</summary>
			<div class="wa-code-snippet__content">
				<div class="wa-code-snippet__top">
					<button type="button" class="wa-code-snippet__remove" data-wa-snippet-remove aria-label="<?php echo esc_attr__('Remover código', 'wa-suit'); ?>" title="<?php echo esc_attr__('Remover código', 'wa-suit'); ?>">
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php echo esc_html__('Remover código', 'wa-suit'); ?></span>
					</button>
				</div>

				<div class="wa-code-snippet__grid">
					<label>
						<span><?php echo esc_html__('Nome', 'wa-suit'); ?></span>
						<input type="text" class="regular-text" name="<?php echo esc_attr($prefix); ?>[title]" value="<?php echo esc_attr($snippet['title']); ?>" data-wa-snippet-title-input placeholder="<?php echo esc_attr__('Ex.: Pixel da landing page', 'wa-suit'); ?>">
					</label>
					<label>
						<span><?php echo esc_html__('Linguagem', 'wa-suit'); ?></span>
						<select name="<?php echo esc_attr($prefix); ?>[language]" data-wa-snippet-language>
							<?php foreach ($this->languages as $language => $label) : ?>
								<option value="<?php echo esc_attr($language); ?>" <?php selected($snippet['language'], $language); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span><?php echo esc_html__('Posição no site', 'wa-suit'); ?></span>
						<select name="<?php echo esc_attr($prefix); ?>[location]" data-wa-snippet-location>
							<?php foreach ($this->locations as $location => $label) : ?>
								<option value="<?php echo esc_attr($location); ?>" <?php selected($snippet['location'], $location); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span><?php echo esc_html__('Exibir em', 'wa-suit'); ?></span>
						<select name="<?php echo esc_attr($prefix); ?>[target]" data-wa-snippet-target>
							<?php foreach ($targets as $target => $label) : ?>
								<option value="<?php echo esc_attr($target); ?>" <?php selected($snippet['target'], $target); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="wa-code-snippet__field" data-wa-snippet-page <?php echo in_array($snippet['target'], array('page', 'post', 'product'), true) ? '' : 'hidden'; ?>>
						<label class="wa-code-snippet__field-label" for="<?php echo esc_attr('wa-content-search-' . sanitize_key((string) $key)); ?>" data-wa-snippet-post-label><?php echo esc_html($targets['page']); ?></label>
						<input id="<?php echo esc_attr('wa-content-search-' . sanitize_key((string) $key)); ?>" class="wa-code-snippet__search" type="search" data-wa-post-search placeholder="<?php echo esc_attr__('Buscar por título...', 'wa-suit'); ?>" aria-label="<?php echo esc_attr__('Buscar conteúdo pelo título', 'wa-suit'); ?>">
						<label class="screen-reader-text" for="<?php echo esc_attr('wa-content-select-' . sanitize_key((string) $key)); ?>"><?php echo esc_html__('Conteúdos disponíveis', 'wa-suit'); ?></label>
						<select id="<?php echo esc_attr('wa-content-select-' . sanitize_key((string) $key)); ?>" name="<?php echo esc_attr($prefix); ?>[post_ids][]" multiple size="7" data-wa-snippet-posts>
							<?php foreach ($selectable_posts as $selectable_post) : ?>
								<option value="<?php echo esc_attr($selectable_post->ID); ?>" data-wa-post-type="<?php echo esc_attr($selectable_post->post_type); ?>" <?php selected(in_array((int) $selectable_post->ID, array_map('absint', $snippet['post_ids']), true)); ?>>
									<?php
									$post_type_label  = isset($post_type_labels[$selectable_post->post_type]) ? $post_type_labels[$selectable_post->post_type] : $selectable_post->post_type;
									?>
									<?php echo esc_html('[' . $post_type_label . '] '); ?><?php echo esc_html($selectable_post->post_title); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php echo esc_html__('Selecione um ou mais itens. As seleções permanecem marcadas mesmo enquanto você pesquisa.', 'wa-suit'); ?></small>
					</div>
					<div class="wa-code-snippet__field" data-wa-snippet-url <?php echo 'url_contains' === $snippet['target'] ? '' : 'hidden'; ?>>
						<span class="wa-code-snippet__field-label"><?php echo esc_html__('URL contém', 'wa-suit'); ?></span>
						<div class="wa-code-snippet__url-fields" data-wa-url-fields data-wa-url-name="<?php echo esc_attr($prefix . '[url_matches][]'); ?>">
							<?php if (empty($snippet['url_matches'])) : ?>
								<div class="wa-code-snippet__url-row" data-wa-url-row>
									<input type="text" name="<?php echo esc_attr($prefix); ?>[url_matches][]" value="" data-wa-url-value placeholder="<?php echo esc_attr__('Ex.: campanha ou /promocao/', 'wa-suit'); ?>" aria-label="<?php echo esc_attr__('Texto que a URL deve conter', 'wa-suit'); ?>">
									<button type="button" class="wa-code-snippet__url-remove" data-wa-url-remove aria-label="<?php echo esc_attr__('Remover condição de URL', 'wa-suit'); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
								</div>
							<?php else : ?>
								<?php foreach ($snippet['url_matches'] as $url_match) : ?>
									<div class="wa-code-snippet__url-row" data-wa-url-row>
										<input type="text" name="<?php echo esc_attr($prefix); ?>[url_matches][]" value="<?php echo esc_attr($url_match); ?>" data-wa-url-value placeholder="<?php echo esc_attr__('Ex.: campanha ou /promocao/', 'wa-suit'); ?>" aria-label="<?php echo esc_attr__('Texto que a URL deve conter', 'wa-suit'); ?>">
										<button type="button" class="wa-code-snippet__url-remove" data-wa-url-remove aria-label="<?php echo esc_attr__('Remover condição de URL', 'wa-suit'); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<button type="button" class="wa-code-snippet__url-add" data-wa-url-add>
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php echo esc_html__('Adicionar condição', 'wa-suit'); ?>
						</button>
						<small><?php echo esc_html__('O código será carregado quando a URL contiver qualquer um destes textos. Adicione uma condição para cada caminho ou palavra.', 'wa-suit'); ?></small>
					</div>
					<fieldset class="wa-code-snippet__devices" data-wa-snippet-devices>
						<input type="hidden" name="<?php echo esc_attr($prefix); ?>[devices_submitted]" value="1">
						<legend><?php echo esc_html__('Executar em dispositivos', 'wa-suit'); ?></legend>
						<div class="wa-code-snippet__device-options">
							<?php foreach ($this->devices as $device => $label) : ?>
								<label class="wa-code-snippet__device-option">
									<input type="checkbox" name="<?php echo esc_attr($prefix); ?>[devices][]" value="<?php echo esc_attr($device); ?>" data-wa-snippet-device <?php checked(in_array($device, $snippet['devices'], true)); ?>>
									<span><?php echo esc_html($label); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<small data-wa-snippet-php-device-note hidden><?php echo esc_html__('PHP roda no servidor e não pode ser limitado por dispositivo. Para PHP, mantenha “Todos” selecionado.', 'wa-suit'); ?></small>
						<small><?php echo esc_html__('A seleção considera a largura da tela: celular até 767 px, tablet de 768 a 1024 px e desktop a partir de 1025 px.', 'wa-suit'); ?></small>
					</fieldset>
					<div class="wa-code-snippet__code">
						<label class="wa-code-snippet__field-label" for="<?php echo esc_attr('wa-code-' . sanitize_key((string) $key)); ?>"><?php echo esc_html__('Código', 'wa-suit'); ?></label>
						<span class="wa-code-editor-shell" data-wa-code-shell data-theme="light">
							<span class="wa-code-editor-toolbar">
								<span><?php echo esc_html__('Tema do editor', 'wa-suit'); ?></span>
								<span class="wa-code-editor-themes" role="group" aria-label="<?php echo esc_attr__('Tema do editor de código', 'wa-suit'); ?>">
									<button type="button" data-wa-code-theme="light" aria-pressed="true"><?php echo esc_html__('Claro', 'wa-suit'); ?></button>
									<button type="button" data-wa-code-theme="dark" aria-pressed="false"><?php echo esc_html__('Escuro', 'wa-suit'); ?></button>
								</span>
							</span>
							<span class="wa-code-editor" data-wa-code-editor>
								<pre class="wa-code-editor__lines" aria-hidden="true" data-wa-code-lines>1</pre>
								<textarea id="<?php echo esc_attr('wa-code-' . sanitize_key((string) $key)); ?>" name="<?php echo esc_attr($prefix); ?>[code]" rows="8" spellcheck="false" data-wa-code-input placeholder="<?php echo esc_attr__('Cole seu código aqui...', 'wa-suit'); ?>"><?php echo esc_textarea($snippet['code']); ?></textarea>
							</span>
						</span>
						<small data-wa-snippet-php-note hidden><?php echo esc_html__('PHP: informe apenas as instruções, sem as tags de abertura ou fechamento do PHP.', 'wa-suit'); ?></small>
						<small data-wa-snippet-shortcode-note hidden><?php echo esc_html__('Cole o shortcode completo, por exemplo: [wa_link id="whatsapp"]. O resultado será inserido nesta posição.', 'wa-suit'); ?></small>
					</div>
				</div>
			</div>
		</details>
<?php
	}

	/**
	 * Validates and normalizes a submitted snippet.
	 *
	 * @param array $input Submitted snippet.
	 * @return array|null
	 */
	private function sanitize_snippet($input)
	{
		$language = isset($input['language']) && is_string($input['language']) ? sanitize_key($input['language']) : '';
		$location = isset($input['location']) && is_string($input['location']) ? sanitize_key($input['location']) : '';
		$target   = isset($input['target']) && is_string($input['target']) ? sanitize_key($input['target']) : '';
		$title    = isset($input['title']) && is_string($input['title']) ? sanitize_text_field($input['title']) : '';
		$code     = isset($input['code']) && is_string($input['code']) ? $input['code'] : '';
		$uses_content_ids = in_array($target, array('page', 'post', 'product'), true);
		$raw_post_ids = $uses_content_ids && isset($input['post_ids']) && is_array($input['post_ids'])
			? $input['post_ids']
			: ($uses_content_ids && isset($input['page_id']) ? array($input['page_id']) : array());
		$post_ids = array();
		foreach ($raw_post_ids as $post_id) {
			if (! is_scalar($post_id) || ! absint($post_id)) {
				return null;
			}
			$post = get_post(absint($post_id));
			$allowed_post_types = 'post' === $target ? array('post') : array('page', 'post');
			if ('product' === $target && class_exists('WooCommerce') && post_type_exists('product')) {
				$allowed_post_types = array('product');
			} elseif ('page' === $target && class_exists('WooCommerce') && post_type_exists('product')) {
				$allowed_post_types[] = 'product';
			}
			$target_post_types = $allowed_post_types;
			if (! $post || ! in_array($post->post_type, $target_post_types, true) || 'publish' !== get_post_status($post)) {
				return null;
			}
			$post_ids[] = absint($post_id);
		}
		$post_ids = array_values(array_unique($post_ids));
		$raw_url_matches = isset($input['url_matches']) && is_array($input['url_matches'])
			? $input['url_matches']
			: (isset($input['url_matches']) && is_string($input['url_matches'])
				? preg_split('/\r\n|\r|\n/', $input['url_matches'])
				: (isset($input['url_match']) && is_string($input['url_match']) ? array($input['url_match']) : array()));
		$url_matches = array_values(
			array_unique(
				array_filter(
					array_map(
						function ($pattern) {
							return is_string($pattern) ? trim(sanitize_text_field($pattern)) : '';
						},
						$raw_url_matches
					),
					function ($pattern) {
						return '' !== $pattern;
					}
				)
			)
		);
		$raw_devices = isset($input['devices']) && is_array($input['devices']) ? $input['devices'] : array();
		$devices     = array();
		foreach ($raw_devices as $device) {
			if (! is_string($device)) {
				return null;
			}
			$devices[] = sanitize_key($device);
		}
		$devices = array_values(array_unique($devices));
		if (! isset($input['devices_submitted'])) {
			$devices = array('all');
		}

		$targets = $this->get_available_targets();
		if (! isset($this->languages[$language], $this->locations[$location], $targets[$target]) || '' === trim($title) || '' === trim($code) || empty($devices)) {
			return null;
		}

		$devices = array_values(array_intersect($devices, array_keys($this->devices)));
		if (empty($devices)) {
			return null;
		}

		if (in_array('all', $devices, true)) {
			$devices = array('all');
		}

		if ('php' === $language && array('all') !== $devices) {
			return null;
		}

		if ('html' === $language && array('all') !== $devices && 'head' === $location) {
			return null;
		}

		if ('shortcode' === $language && 'head' === $location) {
			return null;
		}

		if ($uses_content_ids) {
			if (empty($post_ids)) {
				return null;
			}
		}

		if ('url_contains' === $target && empty($url_matches)) {
			return null;
		}

		return array(
			'title'       => $title,
			'enabled'     => isset($input['enabled']) && is_scalar($input['enabled']) && '1' === (string) $input['enabled'] ? '1' : '0',
			'language'    => $language,
			'location'    => $location,
			'target'      => $target,
			'post_ids'    => $post_ids,
			'url_matches' => $url_matches,
			'devices'     => $devices,
			'code'        => $code,
		);
	}

	/**
	 * Returns a compact display summary for the editor card.
	 *
	 * @param array $snippet Snippet settings.
	 * @return string
	 */
	private function snippet_summary($snippet)
	{
		$target = isset($snippet['target']) ? $snippet['target'] : 'all';
		$targets = $this->get_available_targets();
		$label   = isset($targets[$target]) ? $targets[$target] : $targets['all'];

		if (in_array($target, array('page', 'post', 'product'), true)) {
			$post_ids = isset($snippet['post_ids']) && is_array($snippet['post_ids'])
				? array_map('absint', $snippet['post_ids'])
				: (! empty($snippet['page_id']) ? array(absint($snippet['page_id'])) : array());
			if (! empty($post_ids)) {
				$label = sprintf(
					/* translators: 1: selected content count, 2: content type. */
					_n('%1$d item selecionado em %2$s', '%1$d itens selecionados em %2$s', count($post_ids), 'wa-suit'),
					count($post_ids),
					isset($targets[$target]) ? strtolower($targets[$target]) : __('conteúdo', 'wa-suit')
				);
			}
		} elseif ('url_contains' === $target) {
			$patterns = $this->get_url_matches($snippet);
			if (! empty($patterns)) {
				$label .= ': ' . implode(', ', $patterns);
			}
		}

		$devices = $this->get_snippet_devices($snippet);
		if (array('all') !== $devices) {
			$labels = array_intersect_key($this->devices, array_flip($devices));
			$label .= ' · ' . implode(', ', $labels);
		}

		return $label;
	}

	/**
	 * Returns display conditions supported by this site.
	 *
	 * @return array<string, string>
	 */
	private function get_available_targets()
	{
		$targets = $this->targets;
		if (class_exists('WooCommerce') && post_type_exists('product')) {
			$targets['product'] = __('Produtos', 'wa-suit');
		} else {
			$targets['page'] = __('Páginas ou posts', 'wa-suit');
		}

		return $targets;
	}
}

wa_suit_register_module(new WA_Suit_Module_Code_Snippets());
