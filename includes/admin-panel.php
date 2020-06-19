<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manager of the plugin admin panel
 *
 * Manage all options and html page in the admin panel
 *
 * @since 1.0.0
 */
class AdminPanelManager {

	/**
	 * AdminPanelManager constructor.
	 *
	 * Initializing all actions to do for the admin panel
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'load_menu' ) );
        add_action( 'admin_init', array( $this, 'register_options' ) );
        add_action( 'admin_init', array( $this, 'verify_options' ) );
        add_action( 'admin_footer', array( $this, 'load_assets' ) );
    }

	/**
	 * Load_assets.
	 *
	 * Add js and css files needed for the admin panel form
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function load_assets() {
        wp_enqueue_style( 'options-style', TFI_URL . 'assets/css/options.css' );
        wp_enqueue_script( 'options-js', TFI_URL . 'assets/js/options.js', array(), "1.0", true );
    }

	/**
	 * Load_menu.
	 *
	 * Add the option page in the admin panel
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function load_menu() {
        add_options_page( __( 'Tempus Fugit Intranet Options' ), __( 'Tempus Fugit Intranet' ), 'manage_options', 'tfi-options', array( $this, 'display_options' ) );
    }

	/**
	 * Display_options.
	 *
	 * Html content to display in the option panel
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function display_options() {
        if ( ! current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
        <div class="wrap">
			<h1><?php esc_html_e( 'Tempus Fugit Intranet options page' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'tfi_general_options' ); ?>
				<?php do_settings_sections( 'connection-form' ); ?>
				<?php do_settings_sections( 'general-user' ); ?>
				<?php submit_button(); ?>
			</form>
			<form method="post" action="options.php">
				<?php settings_fields( 'tfi_users_fields' ); ?>
				<?php do_settings_sections( 'fields' ); ?>
				<?php do_settings_sections( 'users' ); ?>
				<?php submit_button(); ?>
			</form>
        </div>
		<?php
	}

	/**
	 * Register_options.
	 *
	 * Function calls in admin_init hook to register options and create settings form
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function register_options() {

		/**
		 * Register all options settings
		 * 
		 * @since 1.0.0
		 */
        register_setting(
			'tfi_general_options',
			'tfi_shortcut',
			array( $this, 'sanitize_shortcut' )
		);

		register_setting(
			'tfi_general_options',
			'tfi_user_page_id',
			array( $this, 'sanitize_user_page' )
		);
		
		register_setting(
			'tfi_general_options',
			'tfi_user_types',
			array( $this, 'sanitize_user_types' )
		);
		
		register_setting(
			'tfi_users_fields',
			'tfi_fields',
			array( $this, 'sanitize_fields' )
		);
		
		register_setting(
			'tfi_users_fields',
			'tfi_users',
			array( $this, 'sanitize_users' )
		);

		/**
		 * What we should do when options are updated
		 * 
		 * The tfi_users_datas need change when the tfi_users option changed
		 * (see the AdminPanelManager::update_users_datas header for more informations)
		 * 
		 * @since 1.0.0
		 */
		add_action( 'update_option_tfi_users', array( $this, 'update_users_datas' ), 10, 2 );

		/**
		 * All sections and subsections to display on the option page
		 * 
		 * @since 1.0.0
		 */
		add_settings_section(
			'connection_form_options_id',
			__( 'Connection form shortcut' ),
			array( $this, 'display_connection_form_section' ),
			'connection-form'
		);

		add_settings_field(
			'modifier_keys_id',
			__( 'Modifier keys used' ),
			array( $this, 'modifier_keys_callback' ),
			'connection-form',
			'connection_form_options_id'
		);

		add_settings_field(
			'key_id',
			__( 'The key to press' ),
			array( $this, 'key_callback' ),
			'connection-form',
			'connection_form_options_id'
		);

		add_settings_section(
			'general_user_options_id',
			__( 'General user options' ),
			array( $this, 'display_general_user_section' ),
			'general-user'
		);

		add_settings_field(
			'user_page_id',
			__( 'Intranet user page' ),
			array( $this, 'user_page_callback' ),
			'general-user',
			'general_user_options_id'
		);

		add_settings_field(
			'user_types_id',
			__( 'User types' ),
			array( $this, 'user_types_callback' ),
			'general-user',
			'general_user_options_id'
		);

		add_settings_section(
			'fields_options_id',
			__( 'Fields' ),
			array( $this, 'display_fields_section' ),
			'fields'
		);

		add_settings_section(
			'users_options_id',
			__( 'Users' ),
			array( $this, 'display_users_section' ),
			'users'
		);
	}

	public function verify_options() {
		//$fields = $this->$sanitize_fields( get_option( 'tfi_fields' ) );
	}

    /**
	 * Sanitize_shortcut.
     *
	 * @since 1.0.0
	 * @since 1.0.1		Refactoring methods with the OptionsManager class
	 * @access public
     * @param array $input Contains shortcut set by the user
	 * @return array $input sanitized
     */
    public function sanitize_shortcut( $input ) {
		if ( isset( $input['key'] ) ) {
			$input['key'] = ord( strtoupper( sanitize_text_field( $input['key'] ) ) );
		}

		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->sanitize_option( 'tfi_shortcut', $input );
	}
	
	/**
	 * Sanitize_user_page.
	 * 
	 * Sanitize the user page option
	 * It should be a page with a specific template
	 * 
	 * @since 1.0.0
	 * @access public
     * @param array $input Contains user page set by the user
	 * @return $input sanitized
	 */
	public function sanitize_user_page( $input ) {
		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->sanitize_option( 'tfi_user_page_id', $input );
	}
	
	/**
	 * Sanitize_user_types.
	 * 
	 * Sanitize the user type array
	 * It should be an array of strings
	 * 
	 * This array can't be empty and should at least have one key.
	 * Users are not updated when user types changed because :
	 * - It allows to redefine an unwanted deleted user_type and users won't be changed (avoid missclic, practice with a huge number of users)
	 * - The first role will be set by default on the user role select so the admin will see the user as if it has this role
	 * - The user role NEED TO BE VERIFY on the intranet page, if he doesn't exist, consider the user as the first role (as display in the admin panel)
	 * - If the admin want to delete the role and the cache, just submit the users_fields_form
	 * 
	 * @since 1.0.0
	 * @access public
     * @param array $input Contains an array of user type set by the user
	 * @return $input sanitized
	 */
	public function sanitize_user_types( $input ) {
		$new_input = tfi_get_option( 'tfi_user_types' );
		if ( isset ( $input["new_types"] ) ) {
			if ( ! empty( $input["new_types"] ) ) {
				foreach ( explode( '%', $input["new_types"] ) as $new_type) {
					$new_type = filter_var( $new_type, FILTER_SANITIZE_STRING );
					$new_type_id = $this->sanitize_db_slug( $new_type );
					if ( ! array_key_exists( $new_type_id, $new_input ) && ! empty( $new_type_id ) ) {
						$new_input[$new_type_id] = $new_type;
					}
				}
			}

			unset( $input["new_types"] );
		}

		// The unchecked checbox won't be send in post datas (and so on $input array), so just deletes every checked types  
		foreach ( $input as $id => $value ) {
			if ( isset( $new_input[$id] ) ) {
				unset( $new_input[$id] );
			}
		}

		// It needs to have at least one type to avoid problem
		if ( empty( $new_input ) ) {
			$new_input["default_type"] = 'Default type';
		}

		return $new_input;
	}
	
	/**
	 * Sanitize_fields.
	 * 
	 * Sanitize fields array
	 * 
	 * @since 1.0.0
	 * @access public
     * @param array $input Contains fields and their datas set by the user
	 * @return $input sanitized
	 */
	public function sanitize_fields( $input ) {
		$new_input = tfi_get_option( 'tfi_fields' );
		$scores = array();

		// This score is used when ther is no field_score key to a field
		// We assume that it will never have 10000 fields anyway
		$max_score = 10000;

		/**
		 * First, we delete every field which is not set
		 * We did that first because an id of existing fields can be set to the deleted one
		 * If we delete after, the field won't be add because it will be considered like it already exists
		 */
		foreach ( $new_input as $id => $datas ) {
			if ( ! array_key_exists( $id, $input ) ) {
				unset( $new_input[$id] );
			}
		}

		/**
		 * Then change every existing fields 
		 */
		foreach ( $input as $id => $datas ) {
			if ( array_key_exists( $id, $new_input ) ) {
				$field = $this->sanitize_field( $new_input[$id], $datas );

				if ( isset( $datas['id'] ) && $datas['id'] != $id ) {
					$new_field_id = $this->sanitize_db_slug( $datas['id'] );;
					if ( ! in_array( $new_field_id, $new_input ) ) {
						$new_input[$new_field_id] = $field;
						unset( $new_input[$id] );

						// Changes the id value to the new one, to store the score
						$id = $new_field_id;
					}
				}
				else {
					$new_input[$id] = $field;
				}

				// Add the field according to it's score
				if ( isset( $datas['field_score'] ) ) {
					$scores[abs( $datas['field_score'] )] = $id;
				}
				else {
					$scores[$max_score] = $id;
					$max_score++;
				}
			}
		} 

		/**
		 * Finally add all new wanted fields
		 */
		if ( isset( $input['new_fields'] ) ) {
			foreach ( $input['new_fields'] as $id => $datas ) {
				if ( $id != 'number_to_replace' ) {
					$new_field = $this->sanitize_field( array(
						'real_name' => 'PLACEHOLDER',
						'type'		=> array_key_first( tfi_get_option( 'tfi_field_types' ) ),
						'default'	=> '',
						'users' 	=> array()
					), $datas );

					if ( isset( $datas['id'] ) ) {
						// Set the field only if the slug doesn't exist
						$new_field_id = $this->sanitize_db_slug( $datas['id'] );
						if ( ! array_key_exists( $new_field_id, $new_input ) ) {
							$new_input[$new_field_id] = $new_field;
						}

						// Add the field according to it's score
						if ( isset( $datas['field_score'] ) ) {
							$scores[abs( $datas['field_score'] )] = $new_field_id;
						}
						else {
							$scores[$max_score] = $new_field_id;
							$max_score--;
						}
					}
				}
			}
		}

		/**
		 * Sorts the final array according to the scores
		 */
		ksort( $scores );
		$to_return = array();

		foreach ( $scores as $field_name ) {
			if ( array_key_exists( $field_name, $new_input ) ) {
				$to_return[$field_name] = $new_input[$field_name];
			}
		}

		return $to_return;
	}
	
	/**
	 * Sanitize_users.
	 * 
	 * Sanitize users array
	 * 
	 * @since 1.0.0
	 * @access public
     * @param array $input Contains intranet users and their datas set by the user
	 * @return $input sanitized
	 */
	public function sanitize_users( $input ) {
		$new_input = tfi_get_option( 'tfi_users' );

		/**
		 * First, we delete every user which is not set
		 * We did that first because the user can have been delete and then add again
		 * We want to keep the new one 
		 */
		foreach ( $new_input as $id => $datas ) {
			if ( ! array_key_exists( $id, $input ) ) {
				unset( $new_input[$id] );
			}
		}

		/**
		 * Then change every existing users 
		 */
		foreach ( $input as $id => $datas ) {
			if ( array_key_exists( $id, $new_input ) ) {
				$field = $this->sanitize_user( $new_input[$id], $datas );
				$new_input[$id] = $field;
			}
		} 

		/**
		 * Finally add all new wanted users
		 */
		if ( isset( $input['new_users'] ) ) {
			foreach ( $input['new_users'] as $id => $datas ) {
				if ( $id != 'number_to_replace' ) {
					$new_field = $this->sanitize_user( array(
						'user_type' => array_key_first( tfi_get_option( 'tfi_user_types' ) ),
						'special_fields' => array()
					), $datas );

					if ( isset( $datas['id'] ) ) {
						// Only set the user if it's not already set
						if ( get_user_by( 'id', $datas['id'] ) != false && ! array_key_exists( $datas['id'], $new_input ) ) {
							$new_input[$datas['id']] = $new_field;
						}
					}
				}
			}
		}

		return $new_input;
	}

	/**
	 * Update_users_datas.
	 * 
	 * This method is called when the tfi_users option has been updated
	 * It will add all new users in the tfi_datas table.
	 * 
	 * This table will never delete any row except if this is asked by the admin (not implemented yet)
	 * It allows to keep a cache of all datas if users and fields are deleted and then add again. 
	 * 
	 * @since 1.0.0
	 * @access public
	 * @param array $old_users contains The old values of tfi_users option
	 * @param array $new_users contains The new values of tfi_users option
     * @global wpdb $wpdb           	The database object to drop the table
	 */
	public function update_users_datas( $old_users, $new_users ) {
		global $wpdb;

		$users_datas 	= $wpdb->get_results( "SELECT user_id, datas FROM " . $wpdb->prefix . TFI_TABLE, ARRAY_A );
		$updated_datas	= array();

		if ( ! empty( $new_users ) ) {
			require_once TFI_PATH . 'includes/user.php';
		}

		foreach ( $new_users as $user_id => $user_datas ) {
			$user = new User( $user_id );
			if ( ! $user->is_ok() )
				continue;

			$user_datas = array();

			foreach ( $users_datas as $value ) {
				if ( $value['user_id'] == $user_id ) {
					$user_datas = maybe_unserialize( $value['datas'] );
					break;
				}
			}

			$changed = false;

			foreach ( $user->allowed_fields() as $field ) {
				if ( ! array_key_exists( $field->name, $user_datas ) ) {
					$user_datas[$field->name] = $field->default_value;
					$changed = true;
				}
			}

			if ( $changed ) {
				$updated_datas[] = '(' . $user_id . ', \'' . maybe_serialize( $user_datas ) . '\')';
			}
		}
		
		if ( ! empty( $updated_datas ) ) {
			$wpdb->query( "INSERT INTO " . $wpdb->prefix . TFI_TABLE . " (user_id, datas) VALUES " . implode( ', ', $updated_datas ) . " ON DUPLICATE KEY UPDATE datas = VALUES(datas);" );
		}
	}

	/**
	 * Sanitize_field.
	 * 
	 * Sanitize a specific field, this is a private function to factorize code
	 * 
	 * @since 1.0.0
	 * @access private
     * @param array $field array where datas will be change and return
	 * @param array $datas array of datas to change in $field
	 * @return array The field sanitized
	 */
	private function sanitize_field( $field, $datas ) {
		if ( isset( $datas['real_name'] ) ) {
			$field['real_name'] = filter_var( $datas['real_name'], FILTER_SANITIZE_STRING );
		}
		if ( isset( $datas['type'] ) ) {
			if ( array_key_exists( $datas['type'], tfi_get_option( 'tfi_field_types' ) ) ) {
				$field['type'] = $this->sanitize_db_slug( $datas['type'] );
			}
		}
		if ( isset( $datas['default'] ) ) {
			$field['default'] = filter_var( $datas['default'], FILTER_SANITIZE_STRING );
		}
		
		// Reset the users array if it exists
		$field['users'] = array();

		if ( isset( $datas['users'] ) && is_array( $datas['users'] ) ) {
			foreach ( $datas['users'] as $user_type => $bool ) {
				$field['users'][] = $user_type;
			}
		}

		return $field;
	}

	/**
	 * Sanitize_db_slug.
	 * 
	 * Sanitize a string to be set as a slug
	 * 
	 * @since 1.0.0
	 * @access private
     * @param string $string The string to saintize
	 * @return string The field sanitized
	 */
	private function sanitize_db_slug( $string ) {
		return preg_replace( '/[^a-z0-9_]/', '_', strtolower( $string ) );
	}

	/**
	 * Sanitize_user.
	 * 
	 * Sanitize a specific user, this is a private function to factorize code
	 * 
     * @param array $user array where datas will be change and return
	 * @param array $datas array of datas to change in $user
	 * @return array The user sanitized
	 * @since 1.0.0
	 * @access private
	 */
	private function sanitize_user( $user, $datas ) {
		if ( isset( $datas['user_type'] ) && array_key_exists( $datas['user_type'], tfi_get_option( 'tfi_user_types' ) ) ) {
			$user['user_type'] = $datas['user_type'];
		}
		else {
			$user['user_type'] = array_key_first( tfi_get_option( 'tfi_user_types' ) );
		}

		// Reset the special fields array if it exists
		$user['special_fields'] = array();

		if ( isset( $datas['special_fields'] ) ) {
			if ( is_string( $datas['special_fields'] ) ) {
				$datas['special_fields'] = explode( ',', $datas['special_fields'] );
			}
			foreach ( $datas['special_fields'] as $special_field ) {
				if ( array_key_exists( $special_field, tfi_get_option( 'tfi_fields' ) ) ) {
					$user['special_fields'][] = $special_field;
				}
			}
		}

		return $user;
	}

    public function display_connection_form_section() {
		?>
		<p><?php esc_html_e( '' ); ?></p>
		<?php
    }

    public function modifier_keys_callback() {
		$shortcut = tfi_get_option( 'tfi_shortcut' )
		?>
		<label for="ctrl_key_used"><?php esc_html_e( 'Ctrl' ); ?></label>
		<input type="checkbox" id="ctrl_key_used" name="tfi_shortcut[ctrl_key_used]" <?php echo $shortcut['ctrl_key_used'] ? 'checked ' : ''; ?>/>
		<label for="alt_key_used"><?php esc_html_e( 'Alt' ); ?></label>
		<input type="checkbox" id="alt_key_used" name="tfi_shortcut[alt_key_used]" <?php echo $shortcut['alt_key_used'] ? 'checked ' : ''; ?>/>
		<label for="shift_key_used"><?php esc_html_e( 'Shift' ); ?></label>
		<input type="checkbox" id="shift_key_used" name="tfi_shortcut[shift_key_used]" <?php echo $shortcut['shift_key_used'] ? 'checked ' : ''; ?>/>
        <?php
    }

    public function key_callback() {
		$shortcut = tfi_get_option( 'tfi_shortcut' )
		?>
		<input type="text" id="key" name="tfi_shortcut[key]" value="<?php echo esc_attr( chr ( $shortcut['key'] ) ); ?>" />
        <?php
	}

	public function display_general_user_section() {
		?>
		<p><?php esc_html_e( '' ); ?></p>
		<?php
	}

	public function user_page_callback() {
		$pages = get_pages( array(
			'meta_key' => '_wp_page_template',
			'meta_value' => TFI_TEMPLATE_PAGE
		) );
		$actual_page_id = tfi_get_option( 'tfi_user_page_id' );
		
		if ( !empty( $pages ) ): ?>
		<select name="tfi_user_page_id">
			<?php
			foreach( $pages as $page ) {
				?>
				<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $page->ID, $actual_page_id ); ?>><?php esc_html_e( $page->post_title ); ?></option>
				<?php
			}
			?>
		</select>
		<i><?php printf( esc_html__( 'The page need to have the model %s to be set here' ), '<b>' . esc_html__( 'Intranet user page Template') . '</b>' ); ?></i>
		<?php if ($actual_page_id == -1 ):?>
		<p><i class="tfi-option-warning"><?php esc_html_e( 'This page isn\'t set yet, you need to submit the form before' ); ?></i></p>
		<?php endif; ?>
		<?php else: ?>
		<i><?php printf( esc_html_e( 'To be able to choose a page, please create a new page with the %s model' ), '<b>' . esc_html__( 'Intranet user page Template' ) . '</b>' ); ?></i>
		<?php endif; ?>
		<?php
	}

	public function user_types_callback() {
		?>
		<i><?php esc_html_e( 'Checked types will be deleted' ); ?></i>
		<ul role="list" class="tfi-user-list">
			<?php foreach ( tfi_get_option( 'tfi_user_types' ) as $id => $name ): ?>
			<li>
				<input type="checkbox" id="tfi-user-type-<?php echo esc_attr( $id ); ?>" name="tfi_user_types[<?php echo esc_attr( $id ); ?>]" />
				<label for="tfi-user-type-<?php echo esc_attr( $id ); ?>"><?php esc_html_e( $name ); ?></label><p><i><?php printf( esc_html__( 'slug: %s' ), $id ); ?></i></p>
			</li>
			<?php endforeach; ?>
		</ul>
		<p><i><?php esc_html_e( 'Add new types here (separate by % if you want multiple types).' ); ?></i></p>
		<input name="tfi_user_types[new_types]" type="text" placeholder="<?php esc_attr_e( 'First type%Second type' ); ?>" />
		<?php
	}

	public function display_fields_section() {
		$user_types = tfi_get_option( 'tfi_user_types' );
		$field_types = tfi_get_option( 'tfi_field_types' );
		$fields = tfi_get_option( 'tfi_fields' );
		?>
		<table id="tfi-fields-table" class="tfi-options-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Slug' ); ?></th>
					<th><?php esc_html_e( 'Name' ); ?></th>
					<th><?php esc_html_e( 'Type' ); ?></th>
					<th><?php esc_html_e( 'Parameters' ); ?></th>
					<th><?php esc_html_e( 'Default value' ); ?></th>
					<?php foreach ( $user_types as $id => $name ): ?>
					<th><?php esc_html_e( $name ); ?></th>
					<?php endforeach; ?>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php $count = 0;
				foreach ( $fields as $id => $datas ): ?>
				<tr id="tfi-field-<?php echo esc_attr( $id ); ?>">
					<input class="field-score" type="hidden" name="tfi_fields[<?php echo esc_attr( $id ); ?>][field_score]" value="<?php echo esc_attr( $count ); ?>" />
					<td><input type="text" name="tfi_fields[<?php echo esc_attr( $id ); ?>][id]" value="<?php echo esc_attr( $id ); ?>" /></td>
					<td><input type="text" name="tfi_fields[<?php echo esc_attr( $id ); ?>][real_name]" value="<?php esc_attr_e( $datas['real_name'] ); ?>" /></td>
					<td>
						<select name="tfi_fields[<?php echo esc_attr( $id ); ?>][type]">
							<?php foreach ( $field_types as $type_id => $param ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>" <?php echo $type_id == $datas['type'] ? 'selected' : ''; ?>><?php esc_html_e( $param['display_name'] ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td class="param-fields" id="param-fields-<?php echo esc_attr( $id ); ?>">
						<?php if ( $datas['type'] === 'image' ): ?>
						<label><?php esc_html_e( 'Height:'); ?></label>
						<input type="number" name="tfi_fields[<?php echo esc_attr( $id ); ?>][special_params][height]" value="<?php echo esc_attr( $datas['special_params']['height'] ); ?>" />
						<label><?php esc_html_e( 'Width:'); ?></label>
						<input type="number" name="tfi_fields[<?php echo esc_attr( $id ); ?>][special_params][width]" value="<?php echo esc_attr( $datas['special_params']['width'] ); ?>" />
						<?php endif; ?>
					</td>
					<td><input type="text" name="tfi_fields[<?php echo esc_attr( $id ); ?>][default]" value="<?php esc_attr_e( $datas['default'] ); ?>" /></td>
					<?php foreach ( $user_types as $type_id => $name ): ?>
					<td style="text-align: center;"><input type="checkbox" name="tfi_fields[<?php echo esc_attr( $id ); ?>][users][<?php echo esc_attr( $type_id ); ?>]" <?php echo in_array( $type_id, $datas['users'] ) ? 'checked ' : ''; ?>/></td>
					<?php endforeach; ?>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-field-<?php echo esc_attr( $id ); ?>'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Remove field' ); ?></button></td>
					<td class="change-field-row"><button type="button" onclick="tfi_move_row_to_up('tfi-field-<?php echo esc_attr( $id ); ?>')" class="button action">&#8597;</button></td>
				</tr>
				<?php $count++;
				endforeach; 
				/**
				 * This last row allows to add a new field by pressing the Add Field button.
				 * It should be deleted from the input array before sending to the database (in the sanitize method)
				 */
				?>
				<tr id="tfi-field-new" hidden>
					<input class="field-score" type="hidden" name="tfi_fields[new_fields][number_to_replace][field_score]" value="<?php echo esc_attr( $count ); ?>" />
					<td><input type="text" name="tfi_fields[new_fields][number_to_replace][id]" value="<?php esc_attr_e( 'field_name' ); ?>" /></td>
					<td><input type="text" name="tfi_fields[new_fields][number_to_replace][real_name]" value="<?php esc_attr_e( 'My field name' ); ?>" /></td>
					<td>
						<select name="tfi_fields[new_fields][number_to_replace][type]">
							<?php foreach ( $field_types as $type_id => $param ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>"><?php esc_html_e( $param['display_name'] ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<?php foreach ( $user_types as $type_id => $name ): ?>
					<td style="text-align: center;"><input type="checkbox" name="tfi_fields[new_fields][number_to_replace][users][<?php echo esc_attr( $type_id ); ?>]" /></td>
					<?php endforeach; ?>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-field-number_to_replace'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Remove field' ); ?></button></td>
					<td class="change-field-row"><button type="button" onclick="tfi_move_row_to_up('tfi-field-number_to_replace')" class="button action">&#8597;</button></td>
				</tr>
			</tbody>
			<tr><td><button type="button" onclick="tfi_increase_field_score(); tfi_add_row('tfi-fields-table', 'tfi-field-', 'number_to_replace'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Add a field' ); ?></button></td></tr>
		</table>
		<?php
	}

	function display_users_section() {
		$user_types = tfi_get_option( 'tfi_user_types' );
		$all_users = array();
		foreach ( get_users() as $user ) {
			if ( array_key_exists( 'access_intranet', $user->allcaps ) ) {
				$all_users[] = $user;
			}
		}
		?>
		<table id="tfi-users-table" class="tfi-options-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name' ); ?></th>
					<th><?php esc_html_e( 'Type' ); ?></th>
					<th><?php esc_html_e( 'Special fields' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( tfi_get_option( 'tfi_users' ) as $user_id => $user_datas ):
				$user = get_user_by( 'id', $user_id );
				if ( $user != false ): ?>
				<tr id="tfi-user-<?php echo esc_attr( $user_id ); ?>">
					<td><input type="text" value="<?php esc_attr_e( $user->display_name ); ?>" readonly /></td>
					<td>
						<select name="tfi_users[<?php echo esc_attr( $user_id ); ?>][user_type]">
							<?php foreach ( $user_types as $type_id => $name ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>" <?php echo $type_id == $user_datas['user_type'] ? 'selected' : ''; ?>><?php esc_html_e( $name ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td><input type="text" name="tfi_users[<?php echo esc_attr( $user_id ); ?>][special_fields]" value="<?php echo esc_attr( implode( ',', $user_datas['special_fields'] ) ); ?>" placeholder="<?php esc_attr_e( 'field_slug_1,field_slug_2' ); ?>" /></td>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-user-<?php echo esc_attr( $user_id ); ?>')" class="button action"><?php esc_html_e( 'Remove user' ); ?></button></td>
				</tr>
				<?php endif;
				endforeach;
				/**
				 * This last row allows to add a new user by pressing the Add User button.
				 * It should be deleted from the input array before sending to the database (in the sanitize method)
				 */
				?>
				<tr hidden>
					<td>
						<select name="tfi_users[new_users][number_to_replace][id]">
							<?php foreach ( $all_users as $user ): ?>
							<option value="<?php echo esc_attr( $user->ID ); ?>"><?php esc_html_e( $user->display_name ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td>
						<select name="tfi_users[new_users][number_to_replace][user_type]">
							<?php foreach ( $user_types as $type_id => $name ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>"><?php esc_html_e( $name ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td><input type="text" name="tfi_users[new_users][number_to_replace][special_fields]" value="" placeholder="<?php esc_attr_e( 'field_slug_1,field_slug_2' ); ?>" /></td>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-user-number_to_replace')" class="button action"><?php esc_html_e( 'Remove user' ); ?></button></td>
				</tr>
			</tbody>
			<tr><td><button type="button" onclick="tfi_add_row('tfi-users-table', 'tfi-user-', 'number_to_replace')" class="button action"><?php esc_html_e( 'Add a user' ); ?></button></td></tr>
		</table>
		<?php
	}
}