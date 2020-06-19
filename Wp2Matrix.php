<?php namespace sbronsted;

use Exception;

require plugin_dir_path(__FILE__).'functions.php';

/**
 * Plugin Name: Wp2Matrix
 */

class Wp2Matrix {
	const slug = 'w2m';
	const settings = self::slug . '_settings';
	const host = 'host';
	const user = 'user';
	const password = 'password';
	const roomAlias = 'roomAlias';
	const accessToken = 'accessToken';
	const roomId = 'roomId';
	const userId = 'userId';

	public function __construct() {
		add_action('admin_init', [$this, 'init']);
		add_action('admin_menu', [$this, 'menu']);
		add_filter('pre_update_option', [$this, 'validate'], 10 , 3);
		add_action('publish_post', [$this, 'onPostPublish'], 10, 2 ); //https://codex.wordpress.org/Post_Status_Transitions
	}

	public function onPostPublish($id, $post) {
		if (did_action('publish_post') !== 1) {
			return;
		}
		$data = get_option(self::settings);
		try {
			post($data[self::host], $data[self::accessToken], $data[self::roomId], $post);
		}
		catch (Exception $e) {
			wp_die($e->getMessage(), 'w2m post publish failed');
		}
	}

	public function validate($values, $name, $oldValues) {
		if ($name != self::settings) {
			return $values;
		}
		foreach ([self::accessToken, self::roomId, self::userId] as $name) {
			$values[$name] = $oldValues[$name];
		}
		$changed = array_diff_assoc($values, $oldValues);
		try {
			if (!isset($oldValues[self::accessToken]) || isset($changed[self::host]) || isset($changed[self::user]) || isset($changed[self::password])) {
				$result = login($values[self::host], $values[self::user], $values[self::password]);
				$values[self::accessToken] = $result->access_token;
				$values[self::userId] = $result->user_id;
			}
			if (!isset($oldValues[self::roomId]) || isset($changed[self::roomAlias])) {
				$values[self::roomId] = getRoomId($values[self::host], $values[self::userId], $values[self::roomAlias]);
			}
		}
		catch (Exception $e) {
			add_settings_error(self::slug, 'settings_update', $e->getMessage(), 'error');
		}
		return $values;
	}

	public function init() {
		register_setting(self::slug, self::settings);

		add_settings_section(
			self::slug.'_section',
			__('Settings for the matrix server.', self::slug),
			[$this, 'addSection'],
			self::slug
		);

		$data = get_option(self::settings);
		$labels = [
			self::host => 'Host',
			self::user => 'User',
			self::password => 'Password',
			self::roomAlias => 'Room alias',
		];
		foreach ($labels as $name => $label) {
			add_settings_field(
				$name,
				$label,
				[$this, 'addField'],
				self::slug,
				self::slug.'_section',
				['name' => $name, 'value' => $data[$name]]
			);
		}
	}

	public function addSection($args) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Add the following information.', self::slug ); ?></p>
		<?php
	}

	public function addField($args) {
		?>
		<input type="text" name="<?php echo self::settings; ?>[<?php echo $args['name']; ?>]"
					 value="<?php echo isset( $args['value'] ) ? esc_attr( $args['value'] ) : ''; ?>"
					 required>
		<?php
	}

	public function display() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "wporg_options"
				settings_fields( self::slug );
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				do_settings_sections( self::slug );
				// output save settings button
				submit_button( __( 'Save Settings', 'textdomain' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function menu() {
		add_options_page(
			'Wp2Matrix settings',
			'Wp2Matrix',
			'manage_options',
			self::slug,
			[$this, 'display']
		);
	}
}
$wp2Matrix = new Wp2Matrix();
