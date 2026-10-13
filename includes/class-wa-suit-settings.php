<?php

/**
 * Global plugin settings.
 *
 * @package WASuit
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Registers and manages plugin settings.
 */
class WA_Suit_Settings
{

	/**
	 * Settings option name in wp_options.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wa_suit_settings';
	const GENERAL_OPTION_NAME = 'wa_suit_general_settings';

	/**
	 * Individual plugin-wide settings.
	 *
	 * @var array
	 */
	private $general_setting_items = array();

	/**
	 * Constructor. Registers WordPress settings hooks.
	 */
	public function __construct()
	{
		require_once WA_SUIT_PATH . 'includes/settings/duplicate-items.php';
		require_once WA_SUIT_PATH . 'includes/settings/auto-plugin-updates.php';

		$this->general_setting_items = array(
			new WA_Suit_Setting_Duplicate_Items(),
			new WA_Suit_Setting_Auto_Plugin_Updates(),
		);

		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Registers the setting with the WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings()
	{
		register_setting(
			'wa_suit',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize'),
				'default'           => array('enabled_modules' => array()),
			)
		);
	}

	/**
	 * Sanitizes submitted settings.
	 *
	 * @param mixed $input Submitted settings array.
	 * @return array
	 */
	public function sanitize($input)
	{
		if (! is_array($input)) {
			return array('enabled_modules' => array());
		}

		$enabled_modules = isset($input['enabled_modules']) && is_array($input['enabled_modules'])
			? array_map('sanitize_key', $input['enabled_modules'])
			: array();

		return array(
			'enabled_modules' => array_values(array_unique(array_filter($enabled_modules))),
		);
	}

	/**
	 * Gets all currently enabled module slugs.
	 *
	 * @return string[]
	 */
	public function get_enabled_modules()
	{
		$options = get_option(self::OPTION_NAME, array());

		if (! is_array($options) || ! isset($options['enabled_modules']) || ! is_array($options['enabled_modules'])) {
			return array();
		}

		return array_values(array_unique(array_map('sanitize_key', $options['enabled_modules'])));
	}

	/**
	 * Checks if a specific module is enabled.
	 *
	 * @param string $module_id Module slug.
	 * @return bool
	 */
	public function is_module_enabled($module_id)
	{
		$enabled = $this->get_enabled_modules();
		return in_array(sanitize_key($module_id), $enabled, true);
	}

	/**
	 * Enables a module.
	 *
	 * @param string $module_id Module slug.
	 * @return bool
	 */
	public function enable_module($module_id)
	{
		$module_id = sanitize_key($module_id);
		$enabled   = $this->get_enabled_modules();

		if (! in_array($module_id, $enabled, true)) {
			$enabled[] = $module_id;
			return update_option(self::OPTION_NAME, array('enabled_modules' => $enabled));
		}

		return true;
	}

	/**
	 * Disables a module.
	 *
	 * @param string $module_id Module slug.
	 * @return bool
	 */
	public function disable_module($module_id)
	{
		$module_id = sanitize_key($module_id);
		$enabled   = $this->get_enabled_modules();

		$key = array_search($module_id, $enabled, true);
		if (false !== $key) {
			unset($enabled[$key]);
			return update_option(self::OPTION_NAME, array('enabled_modules' => array_values($enabled)));
		}

		return true;
	}

	/**
	 * Sets the complete list of enabled modules.
	 *
	 * @param array $modules List of module slugs.
	 * @return bool
	 */
	public function set_enabled_modules(array $modules)
	{
		$sanitized = array_values(array_unique(array_filter(array_map('sanitize_key', $modules))));
		return update_option(self::OPTION_NAME, array('enabled_modules' => $sanitized));
	}

	/**
	 * Gets general plugin settings, including defaults for first-time installs.
	 *
	 * @return array
	 */
	public function get_general_settings()
	{
		$saved = get_option(self::GENERAL_OPTION_NAME, array());
		if (! is_array($saved)) {
			$saved = array();
		}

		$settings = array();
		foreach ($this->general_setting_items as $setting_item) {
			$settings = array_merge($settings, $setting_item->get_settings($saved));
		}

		return $settings;
	}

	/**
	 * Renders all individual plugin-wide settings.
	 *
	 * @return void
	 */
	public function render_general_settings()
	{
		$settings = $this->get_general_settings();

		foreach ($this->general_setting_items as $setting_item) {
			$setting_item->render_settings($settings);
		}
	}

	/**
	 * Saves validated general plugin settings.
	 *
	 * @param array $input Submitted settings.
	 * @return bool
	 */
	public function save_general_settings($input)
	{
		$input       = is_array($input) ? $input : array();
		$settings    = array();

		foreach ($this->general_setting_items as $setting_item) {
			$settings = array_merge($settings, $setting_item->sanitize_settings($input));
		}

		return update_option(self::GENERAL_OPTION_NAME, $settings);
	}
}
