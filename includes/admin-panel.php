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
				<?php do_settings_sections( 'folders' ); ?>
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
			'tfi_file_folders',
			array( $this, 'sanitize_file_folders' )
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
			'folders_option_id',
			__( 'File folders' ),
			array( $this, 'display_folders_section' ),
			'folders'
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

    /**
	 * Sanitize_shortcut.
     *
	 * @since 1.0.0
	 * @since 1.1.0		Refactoring methods with the OptionsManager class
	 * @access public
     * @param array     $input Contains shortcut set by the user
	 * @return array    $input sanitized
     */
    public function sanitize_shortcut( $input ) {
		if ( isset( $input['key'] ) ) {
			$input['key'] = ord( strtoupper( sanitize_text_field( $input['key'] ) ) );
		}

		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->verify_option( 'tfi_shortcut', $input );
	}
	
	/**
	 * Sanitize_user_page.
	 * 
	 * Sanitize the user page option
	 * It should be a page with a specific template
	 * 
	 * @since 1.0.0
	 * @since 1.1.0		Refactoring methods with the OptionsManager class
	 * @access public
     * @param array     $input Contains user page set by the user
	 * @return          $input sanitized
	 */
	public function sanitize_user_page( $input ) {
		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->verify_option( 'tfi_user_page_id', $input );
	}
	
	/**
	 * Sanitize_user_types.
	 * 
	 * Sanitize the user type array
	 * It should be an array of strings
	 * 
	 * Users are not updated when user types changed because :
	 * - It allows to redefine an unwanted deleted user_type and users won't be changed (avoid missclic, practice with a huge number of users)
	 * - The first role will be set by default on the user role select so the admin will see the user as if it has this role
	 * - The user role NEED TO BE VERIFY on the intranet page, if he doesn't exist, consider the user as the first role (as display in the admin panel)
	 * - If the admin want to delete the role and the cache, just submit the users_fields_form
	 * 
	 * @since 1.0.0
	 * @since 1.1.0		Refactoring methods with the OptionsManager class
	 * @access public
     * @param array     $input Contains an array of user type set by the user
	 * @return          $input sanitized
	 */
	public function sanitize_user_types( $input ) {
        $option = tfi_get_option( 'tfi_user_types' );
        
		if ( isset ( $input["new_types"] ) && ! empty( $input["new_types"] ) ) {
            $option = array_merge( $option, explode( '%', $input['new_types'] ) );
			unset( $input["new_types"] );
        }

		// The unchecked checkbox won't be send in post datas (and so on $input array), so just deletes every checked types  
		foreach ( $input as $id => $value ) {
			if ( isset( $option[$id] ) ) {
				unset( $option[$id] );
			}
        }
        
		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->verify_option( 'tfi_user_types', $option );
	}

	public function sanitize_file_folders( $input ) {
		/**
		 * This key is destroyed because it's only used in js
		 */
		if ( isset( $input['number_to_replace'] ) ) {
            unset( $input['number_to_replace'] );
        }
        
		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->verify_option( 'tfi_file_folders', $input );
	}
	
	/**
	 * Sanitize_fields.
	 * 
	 * Sanitize fields array
	 * 
	 * @since 1.0.0
	 * @since 1.1.0		Refactoring methods with the OptionsManager class
	 * @access public
     * @param array     $input Contains fields and their datas set by the user
	 * @return          $input sanitized
	 */
	public function sanitize_fields( $input ) {
		/**
		 * This key is destroyed because it's only used in js
		 */
		if ( isset( $input['number_to_replace'] ) ) {
            unset( $input['number_to_replace'] );
        }

        $new_fields = array();

        foreach ( $input as $key => $field ) {
            if ( isset( $field['id'] ) && ! empty( $field['id'] ) && ! array_key_exists( $field['id'], $new_fields ) ) {
                /**
                 * This is because checkbox return "value" = "on"
                 */
                if ( isset( $field['users'] ) ) {
                    $users = array();
                    foreach( $field['users'] as $user_type => $bool ) {
                        $users[] = $user_type;
                    }
                    $field['users'] = $users;
                }
                /**
                 * This is because this value should be an array
                 */
                if ( isset( $field['special_params']['mandatory_domains'] ) ) {
                    $domains = array();
                    foreach( explode( ',', $field['special_params']['mandatory_domains'] ) as $domain ) {
                        $domains[] = $domain;
                    }
                    $field['special_params']['mandatory_domains'] = $domains;
                }

                $new_fields[$field['id']] = $field;
            }
        }
        
		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->verify_option( 'tfi_fields', $new_fields );
	}
	
	/**
	 * Sanitize_users.
	 * 
	 * Sanitize users array
	 * 
	 * @since 1.0.0
	 * @since 1.1.0		Refactoring methods with the OptionsManager class
	 * @access public
     * @param array     $input Contains intranet users and their datas set by the user
	 * @return          $input sanitized
	 */
	public function sanitize_users( $input ) {
		/**
		 * This key is destroyed because it's only used in js
		 */
		if ( isset( $input['number_to_replace'] ) ) {
            unset( $input['number_to_replace'] );
        }
        
        $new_users = array();
        foreach ( $input as $id => $user ) {
            if ( isset( $user['special_fields'] ) ) {
                $user['special_fields'] = explode( ',', $user['special_fields'] );
            }

            /**
             * If the id key exists, it means that this is a new user
             */
            if ( isset( $user['id'] ) ) {
                $new_users[$user['id']] = $user;
            }
            else {
                $new_users[$id] = $user;
            }
        }
        
		require_once TFI_PATH . 'includes/options.php';

		$options_manager = new OptionsManager;
		return $options_manager->verify_option( 'tfi_users', $new_users );
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
		
		if ( ! empty( $pages ) ): ?>
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
		<i><?php printf( esc_html__( 'To be able to choose a page, please create a new page with the %s model' ), '<b>' . esc_html__( 'Intranet user page Template' ) . '</b>' ); ?></i>
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

	public function display_folders_section() {
		$folders = tfi_get_option( 'tfi_file_folders' );
		?>
		<table id="tfi-folders-table" class="tfi-options-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name' ); ?></th>
					<th><?php esc_html_e( 'Parent folder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $folders as $folder_slug => $folder ): ?>
				<tr id="tfi-field-<?php echo esc_attr( $folder_slug ); ?>">
					<td><input type="text" name="tfi_file_folders[<?php echo esc_attr( $folder_slug ); ?>][display_name]" value="<?php esc_attr_e( $folder['display_name'] ); ?>" /></td>
					<td>
						<?php if ( $folder_slug != array_key_first( $folders ) ): ?>
						<select name="tfi_file_folders[<?php echo esc_attr( $folder_slug ); ?>][parent]">
							<?php foreach ( $folders as $select_folder_slug => $select_folder ):
							if ( $select_folder_slug != $select_folder ): ?>
							<option value="<?php echo esc_attr( $select_folder_slug ); ?>" <?php echo $select_folder_slug == $folder['parent'] ? 'selected' : ''; ?>><?php esc_html_e( $select_folder['display_name'] ); ?></option>
							<?php endif;
							endforeach; ?>
						</select>
						<?php endif; ?>
					</td>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-field-<?php echo esc_attr( $folder_slug ); ?>')" class="button action"><?php esc_html_e( 'Remove folder' ); ?></button></td>
				</tr>
				<?php endforeach;
				/**
				 * This last row allows to add a new folder by pressing the Add folder button.
				 * It should be deleted from the input array before verification (in the sanitize method)
				 */
				?>
				<tr hidden>
					<td><input type="text" name="tfi_file_folders[number_to_replace][display_name]" value="" /></td>
					<td>
						<select name="tfi_file_folders[number_to_replace][parent]">
							<?php foreach ( $folders as $select_folder_slug => $select_folder ): ?>
							<option value="<?php echo esc_attr( $select_folder_slug ); ?>"><?php esc_html_e( $select_folder['display_name'] ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-folder-number_to_replace')" class="button action"><?php esc_html_e( 'Remove folder' ); ?></button></td>
				</tr>
			</tbody>
			<tr><td><button type="button" onclick="tfi_add_row('tfi-folders-table', 'tfi-folder-', 'number_to_replace')" class="button action"><?php esc_html_e( 'Add a folder' ); ?></button></td></tr>
		</table>
		<?php
	}

	public function display_fields_section() {
		$user_types = tfi_get_option( 'tfi_user_types' );
		$field_types = tfi_get_option( 'tfi_field_types' );
		$fields = tfi_get_option( 'tfi_fields' );
		$folders = tfi_get_option( 'tfi_file_folders' );
		?>
		<table id="tfi-fields-table" class="tfi-options-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Slug' ); ?></th>
					<th><?php esc_html_e( 'Name' ); ?></th>
					<th><?php esc_html_e( 'Type' ); ?></th>
					<th><?php esc_html_e( 'Default value' ); ?></th>
					<th><?php esc_html_e( 'Parameters' ); ?></th>
					<th><?php esc_html_e( 'Folder to save' ); ?></th>
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
					<td><input type="text" name="tfi_fields[<?php echo esc_attr( $id ); ?>][id]" value="<?php echo esc_attr( $id ); ?>" /></td>
					<td><input type="text" name="tfi_fields[<?php echo esc_attr( $id ); ?>][real_name]" value="<?php esc_attr_e( $datas['real_name'] ); ?>" /></td>
					<td>
						<select onchange="tfi_change_type_param(this)" class="field-type-select" name="tfi_fields[<?php echo esc_attr( $id ); ?>][type]" param-row="param-fields-<?php echo esc_attr( $id ); ?>">
							<?php foreach ( $field_types as $type_id => $param ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>" <?php echo $type_id == $datas['type'] ? 'selected' : ''; ?>><?php esc_html_e( $param['display_name'] ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td><input type="text" name="tfi_fields[<?php echo esc_attr( $id ); ?>][default]" value="<?php esc_attr_e( $datas['default'] ); ?>" /></td>
					<td class="param-fields-<?php echo esc_attr( $id ); ?> param-fields">
						<div hidden class="special-param-wrapper" field-type="image">
							<label title="<?php esc_attr_e( 'The maximum height of the image (px)' ); ?>"><?php esc_html_e( 'H:' ); ?></label>
							<input  type="number"
									name="tfi_fields[<?php echo esc_attr( $id ); ?>][special_params][height]"
									value="<?php echo isset( $datas['special_params']['height'] ) ? esc_attr( $datas['special_params']['height'] ) : 0; ?>" />
						</div>
						<div hidden class="special-param-wrapper" field-type="image">
							<label title="<?php esc_attr_e( 'The maximum width of the image (px)' ); ?>"><?php esc_html_e( 'W:' ); ?></label>
							<input  type="number"
									name="tfi_fields[<?php echo esc_attr( $id ); ?>][special_params][width]"
									value="<?php echo isset( $datas['special_params']['width'] ) ? esc_attr( $datas['special_params']['width'] ) : 0; ?>" />
						</div>
						<div hidden class="special-param-wrapper" field-type="link">
							<label title="<?php esc_attr_e( 'The required domain names separated by comma' ); ?>"><?php esc_html_e( 'D:' ); ?></label>
							<input  type="text"
									name="tfi_fields[<?php echo esc_attr( $id ); ?>][special_params][mandatory_domains]"
									value="<?php echo isset( $datas['special_params']['mandatory_domains'] ) ? esc_attr( implode( ',', $datas['special_params']['mandatory_domains'] ) ) : ''; ?>"
									placeholder="<?php esc_attr_e( 'domain.com,domain.net' ); ?>" />
						</div>
					</td>
					<td class="param-fields-<?php echo esc_attr( $id ); ?>">
						<div hidden class="special-param-wrapper" field-type="image">
							<select name="tfi_fields[<?php echo esc_attr( $id ); ?>][folder]">
								<?php foreach ( $folders as $select_folder_slug => $select_folder ): ?>
								<option value="<?php echo esc_attr( $select_folder_slug ); ?>" <?php echo isset( $datas['folder'] ) && $select_folder_slug == $datas['folder'] ? 'selected' : ''; ?>><?php esc_html_e( $select_folder['display_name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</td>
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
				 * It should be deleted from the input array before verification (in the sanitize method)
				 */
				?>
				<tr hidden>
					<td><input type="text" name="tfi_fields[number_to_replace][id]" value="<?php esc_attr_e( 'field_name' ); ?>" /></td>
					<td><input type="text" name="tfi_fields[number_to_replace][real_name]" value="<?php esc_attr_e( 'My field name' ); ?>" /></td>
					<td>
						<select onchange="tfi_change_type_param(this)" class="field-type-select" name="tfi_fields[number_to_replace][type]" param-row="param-fields-number_to_replace">
							<?php foreach ( $field_types as $type_id => $param ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>"><?php esc_html_e( $param['display_name'] ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td><input type="text" name="tfi_fields[number_to_replace][default]" value="" /></td>
					<td class="param-fields param-fields-number_to_replace" >
						<div hidden class="special-param-wrapper" field-type="image">
							<label title="<?php esc_attr_e( 'The maximum height of the image (px)' ); ?>"><?php esc_html_e( 'H:' ); ?></label>
							<input  type="number"
									name="tfi_fields[number_to_replace][special_params][height]"
									value="0" />
						</div>
						<div hidden class="special-param-wrapper" field-type="image">
							<label title="<?php esc_attr_e( 'The maximum width of the image (px)' ); ?>"><?php esc_html_e( 'W:' ); ?></label>
							<input  type="number"
									name="tfi_fields[number_to_replace][special_params][width]"
									value="0" />
						</div>
						<div hidden class="special-param-wrapper" field-type="link">
							<label title="<?php esc_attr_e( 'The required domain names separated by comma' ); ?>"><?php esc_html_e( 'D:' ); ?></label>
							<input  type="text"
									name="tfi_fields[number_to_replace][special_params][mandatory_domains]"
									value=""
									placeholder="<?php esc_attr_e( 'domain.com,domain.net' ); ?>" />
						</div>
					</td>
					<td class="param-fields-number_to_replace">
                        <div hidden class="special-param-wrapper" field-type="image">
                            <select name="tfi_fields[number_to_replace][folder]">
                                <?php foreach ( $folders as $select_folder_slug => $select_folder ): ?>
                                <option value="<?php echo esc_attr( $select_folder_slug ); ?>"><?php esc_html_e( $select_folder['display_name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
						</div>
					</td>
					<?php foreach ( $user_types as $type_id => $name ): ?>
					<td style="text-align: center;"><input type="checkbox" name="tfi_fields[number_to_replace][users][<?php echo esc_attr( $type_id ); ?>]" /></td>
					<?php endforeach; ?>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-field-number_to_replace'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Remove field' ); ?></button></td>
					<td class="change-field-row"><button type="button" onclick="tfi_move_row_to_up('tfi-field-number_to_replace')" class="button action">&#8597;</button></td>
				</tr>
			</tbody>
			<tr><td><button type="button" onclick="tfi_add_row('tfi-fields-table', 'tfi-field-', 'number_to_replace'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Add a field' ); ?></button></td></tr>
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
				 * It should be deleted from the input array before verification (in the sanitize method)
				 */
				?>
				<tr hidden>
					<td>
						<select name="tfi_users[number_to_replace][id]">
							<?php foreach ( $all_users as $user ): ?>
							<option value="<?php echo esc_attr( $user->ID ); ?>"><?php esc_html_e( $user->display_name ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td>
						<select name="tfi_users[number_to_replace][user_type]">
							<?php foreach ( $user_types as $type_id => $name ): ?>
							<option value="<?php echo esc_attr( $type_id ); ?>"><?php esc_html_e( $name ); ?></option>
							<?php endforeach; ?>
						</select>	
					</td>
					<td><input type="text" name="tfi_users[number_to_replace][special_fields]" value="" placeholder="<?php esc_attr_e( 'field_slug_1,field_slug_2' ); ?>" /></td>
					<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-user-number_to_replace')" class="button action"><?php esc_html_e( 'Remove user' ); ?></button></td>
				</tr>
			</tbody>
			<tr><td><button type="button" onclick="tfi_add_row('tfi-users-table', 'tfi-user-', 'number_to_replace')" class="button action"><?php esc_html_e( 'Add a user' ); ?></button></td></tr>
		</table>
		<?php
	}
}