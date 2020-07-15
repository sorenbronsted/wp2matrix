<?php namespace sbronsted;

use Exception;

class Wp2Matrix {
	const slug = 'w2m';
	const settings = self::slug . '_settings';
	const url = 'host';
	const user = 'user';
	const password = 'password';
	const roomAlias = 'roomAlias';
	const accessToken = 'accessToken';
	const roomId = 'roomId';
	const userId = 'userId';

	private $matrix;

	public function __construct() {
		$this->matrix = new Matrix(new Http());
		add_action( 'admin_init', [ __CLASS__, 'onInit' ] );
		add_action( 'admin_menu', [ __CLASS__, 'onMenu' ] );
		add_filter( 'pre_update_option', [ __CLASS__, 'onPreUpdate' ], 10, 3 );
		add_action( 'publish_post', [ __CLASS__, 'onPostPublish' ], 10, 2 );
	}

	public function onPostPublish( $id, $post ) {
		if ( did_action( 'publish_post' ) > 0) {
			return;
		}
		$data = get_option( self::settings );
		try {
			$this->matrix->post( $data[ self::url ], $data[ self::accessToken ], $data[ self::roomId ], $post );
		}
		catch ( Exception $e ) {
			add_settings_error( self::slug, 'settings_update', $e->getMessage(), 'error' );
		}
	}

	public function onPreUpdate( $values, $name, $oldValues ) {
		if ( $name != self::settings ) {
			return $values;
		}
		foreach ( [ self::accessToken, self::roomId, self::userId ] as $name ) {
			if ( ! isset($oldValues[ $name ])) {
				continue;
			}
			$values[ $name ] = $oldValues[ $name ];
		}
		$changed = array_diff_assoc( $values, $oldValues );
		try {
			if ( ! isset( $oldValues[ self::accessToken ] ) ||
					 isset( $changed[ self::url ] ) || isset( $changed[ self::user ] ) || isset( $changed[ self::password ] ) ) {
				$result                      = $this->matrix->login( $values[ self::url ], $values[ self::user ], $values[ self::password ] );
				$values[ self::accessToken ] = $result->access_token;
				$values[ self::userId ]      = $result->user_id;
			}
			if ( ! isset( $oldValues[ self::roomId ] ) || isset( $changed[ self::roomAlias ] ) ) {
				$values[ self::roomId ] = $this->matrix->getRoomId( $values[ self::url ], $values[ self::userId ], $values[ self::roomAlias ] );
			}
		}
		catch ( Exception $e ) {
			add_settings_error( self::slug, 'settings_update', $e->getMessage(), 'error' );
		}

		return $values;
	}

	public function onInit() {
		register_setting( self::slug, self::settings );

		add_settings_section(
				self::slug . '_section',
				__( 'Settings for the matrix server.', self::slug ),
				[ $this, 'addSection' ],
				self::slug
		);

		$data   = get_option( self::settings );
		$labels = [
				self::url       => 'Host',
				self::user      => 'User',
				self::password  => 'Password',
				self::roomAlias => 'Room alias',
		];
		foreach ( $labels as $name => $label ) {
			add_settings_field(
					$name,
					$label,
					[ $this, 'addField' ],
					self::slug,
					self::slug . '_section',
					[ 'name' => $name, 'value' => $data[ $name ] ]
			);
		}
	}

	public function addSection( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php echo esc_html_e( __('Add the following information.'), self::slug ); ?>
		</p>
		<?php
	}

	public function addField( $args ) {
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

	public function onMenu() {
		add_options_page(
				'Wp2Matrix settings',
				'Wp2Matrix',
				'manage_options',
				self::slug,
				[ $this, 'display' ]
		);
	}
}
