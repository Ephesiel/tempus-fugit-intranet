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
		 * The tfi_users_datas need change when the tfi_users or tfi_fields options changed
		 * (see the AdminPanelManager::update_users_datas header for more informations)
		 * 
		 * @since 1.0.0
		 * @since 1.2.2		Add the hook when tfi_fields are changed because admin needed to reupdate users
		 */
		add_action( 'update_option_tfi_users', array( $this, 'update_users_datas' ), 10, 0 );
		add_action( 'update_option_tfi_fields', array( $this, 'update_users_datas' ), 10, 0 );

		/**
		 * When tfi_fields change, the file folder can hav been changed
		 * If this is the case, we move existing files to the new folder.
		 * 
		 * @since 1.2.0
		 */
		add_action( 'update_option_tfi_fields', array( $this, 'update_file_folders' ), 10, 2 );

		/**
		 * Verify values for each users for changed fields
		 * 
		 * Before implementation of multiple field, all values were strings.
		 * Since 1.2.2, values can be array or string according to if the field is multiple or not.
		 * If the type of a field change, the value inside database need to change too, else, it can produce errors when datas are used
		 * 
		 * @since 1.2.3
		 */
		add_action( 'update_option_tfi_fields', array( $this, 'update_users_datas_type' ), 10, 2 );

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
                /**
                 * This is the same value but inside a multiple fields
                 */
                if ( isset( $field['special_params']['multiple_field_special_params']['mandatory_domains'] ) ) {
                    $domains = array();
                    foreach( explode( ',', $field['special_params']['multiple_field_special_params']['mandatory_domains'] ) as $domain ) {
                        $domains[] = $domain;
                    }
                    $field['special_params']['multiple_field_special_params']['mandatory_domains'] = $domains;
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
	 * This method is called when the tfi_users or tfi_fields options have been updated
	 * It will add all new users in the tfi_datas table.
	 * It will add all new fields allowed for each user with their default value.
	 * 
	 * This table will never delete any row except if this is asked by the admin (not implemented yet)
	 * It allows to keep a cache of all datas if users and fields are deleted and then add again. 
	 * 
	 * @since 1.0.0
	 * @since 1.2.2		Remove args and call this method when both options are changed
	 * @access public
	 * 
     * @global wpdb $wpdb	The database object to update users
	 */
	public function update_users_datas() {
		global $wpdb;

		$users_datas 	= $wpdb->get_results( "SELECT user_id, datas FROM " . $wpdb->prefix . TFI_TABLE, ARRAY_A );
		$updated_datas	= array();
		$users			= tfi_get_option( 'tfi_users' );

		if ( ! empty( $users ) ) {
			require_once TFI_PATH . 'includes/user.php';
		}

		foreach ( $users as $user_id => $user_datas ) {
			$user = new User( $user_id );
			if ( ! $user->is_ok() )
				continue;

			$user_datas = array();
			$new_user 	= true;

			/**
			 * Get datas for this user
			 */
			foreach ( $users_datas as $value ) {
				if ( $value['user_id'] == $user_id ) {
					$user_datas = maybe_unserialize( $value['datas'] );
					$new_user = false;
					break;
				}
			}

			$changed = false;

			/**
			 * If a new field has been allowed for this user, add it with the default value on the database
			 */
			foreach ( $user->allowed_fields() as $field ) {
				if ( ! array_key_exists( $field->name, $user_datas ) ) {
					$user_datas[$field->name] = $field->default_value();
					$changed = true;
				}
			}

			/**
			 * Even without any datas a new user need to be inserted
			 */
			if ( $new_user || $changed ) {
				$updated_datas[] = '(' . $user_id . ', \'' . maybe_serialize( $user_datas ) . '\')';
			}
		}
		
		if ( ! empty( $updated_datas ) ) {
			$wpdb->query( "INSERT INTO " . $wpdb->prefix . TFI_TABLE . " (user_id, datas) VALUES " . implode( ', ', $updated_datas ) . " ON DUPLICATE KEY UPDATE datas = VALUES(datas);" );
		}
	}

	/**
	 * Update_file_folders.
	 * 
	 * This method is called when the tfi_fields option has been updated
	 * It will move existing files to new set folders
	 * 
	 * You need to know some rules to this :
	 * - You should avoid changing folder path of fields where a lot of files or big ones have been set
	 * - Because this plugin keep a cache. If you change the type of a field and a user modify it, the file wont' be deleted, ever !
	 * - If you changed the field type and changing it again, the file will be deleted when the user will modify it. Until that, the path is kept.
	 * - Remind that folder are never destroyed, even if you remove them form the list. So you can have empty folders in your uploads dir. 
	 * 
	 * @since 1.2.0
	 * @access public
	 * 
	 * @param array $old_fields contains The old values of tfi_fields option
	 * @param array $new_fields contains The new values of tfi_fields option
	 */
	public function update_file_folders( $old_fields, $new_fields ) {
		if ( ! defined( 'TFI_UPLOAD_FOLDER_DIR' ) ) {
			return false;
		}

		/**
		 * The first array keep User object in mind
		 * The second keep fields to change for each user
		 */
		$changed_users = array();
		$changed_fields = array();

		foreach ( $new_fields as $field_slug => $field_datas ) {
			/**
			 * The path is changed only if the last AND new datas are files.
			 * In other cases, the path will be changed when users will update their datas 
			 */
			if ( array_key_exists( $field_slug, $old_fields )
				&& $field_datas['type'] === 'image' && $old_fields[$field_slug]['type'] === 'image'
			    && $field_datas['special_params']['folder'] !== $old_fields[$field_slug]['special_params']['folder'] ) {
				foreach ( tfi_get_users_which_have_field( $field_slug ) as $wp_user ) {
					$user;
					if ( array_key_exists( $wp_user->ID, $changed_users ) ) {
						$user = $changed_users[$wp_user->ID];
					}
					else {
						$user = new User( $wp_user->ID );
					}

					$old_path 	= $user->get_value_for_field( $field_slug, 'absolute_path' );
					if ( ! file_exists( $old_path ) ) {
						continue;
					}

					$filename 	= basename( $old_path );
					/**
					 * The upload dir is the directory where values are saved in database, it means the directory inside the TFI_UPLOAD_FOLDER_DIR
					 * The local dir is the absolute path, we use it to rename the file later
					 */
					$upload_dir	= tfi_get_user_file_folder_path( $wp_user->ID, $field_datas['special_params']['folder'], false );
					$local_dir  = TFI_UPLOAD_FOLDER_DIR . '/' . $upload_dir;
					$new_value 	= $upload_dir . '/' . $filename;
					$new_path	= $local_dir . '/' . $filename;

					if ( ! file_exists( $local_dir ) ) {
						wp_mkdir_p( $local_dir );
					}

					$changed_users[$user->id] = $user;
					$changed_fields[$user->id][$field_slug] = array(
						'old' => $old_path,
						'new' => $new_path,
						'new_value' => $new_value
					);
				}
			}
		}

		foreach ( $changed_users as $user ) {
			$changed_datas = array();
			foreach ( $changed_fields[$user->id] as $field_slug => $paths ) {
				$changed_datas[$field_slug] = $paths['new_value'];
			}

			/**
			 * Rename the files only if the query succeed
			 */
			if ( $user->set_values_for_fields( $changed_datas ) ) {
				foreach ( $changed_fields[$user->id] as $field_slug => $paths ) {
					rename( $paths['old'], $paths['new'] );
				}
			}
		}
	}

	/**
	 * Update_users_datas_type.
	 * 
	 * This method is called when tfi_fields option has been updated
	 * It will change users database values for fields which changed from multiple to simple or the opposite.
	 * 
	 * @since 1.2.3
	 * @access public
	 * 
	 * @param array $old_fields contains The old values of tfi_fields option
	 * @param array $new_fields contains The new values of tfi_fields option
	 */
	public function update_users_datas_type( $old_fields, $new_fields ) {
		$new_simple_fields = array();
		$new_multiple_fields = array();

		foreach( $new_fields as $new_field_slug => $new_field_datas ) {
			/**
			 * If the key already exists in the old key, it's easy to verify if the value need change.
			 */
			if ( array_key_exists( $new_field_slug, $old_fields ) ) {
				$old_field_datas = $old_fields[$new_field_slug];

				if ( $old_field_datas['type'] === 'multiple' && $new_field_datas['type'] !== 'multiple' ) {
					$new_simple_fields[] = $new_field_slug;
				}
				else if ( $old_field_datas['type'] !== 'multiple' && $new_field_datas['type'] === 'multiple' ) {
					$new_multiple_fields[] = $new_field_slug;
				}
			}
			/**
			 * If the key is a new one, we need to verify it for each user to be sure that values have the good type.
			 */
			else {
				if ( $new_field_datas['type'] !== 'multiple' ) {
					$new_simple_fields[] = $new_field_slug;
				}
				else if ( $new_field_datas['type'] === 'multiple' ) {
					$new_multiple_fields[] = $new_field_slug;
				}
			}
		}

		foreach ( tfi_get_users() as $wp_user ) {
			$user = new User( $wp_user->ID );
			$changes = array();

			/**
			 * We get the value in database directly because even if the user don't have the right to access it anymore the value is kept and eventually needs to be change !
			 */
			foreach ( $user->user_db_datas() as $field_slug => $field_value ) {
				/**
				 * When the value was an array for a single field
				 * Place the first value of the old array as new value
				 */
				if ( in_array( $field_slug, $new_simple_fields ) && ! is_string( $field_value ) ) {
					$value = array_shift( $field_value );
					$changes[$field_slug] = $value !== null ? $value : '';
				}
				/**
				 * When the value was a string for a multiple field
				 * Replace the value by an array with the first value equal to the old value
				 */
				else if ( in_array( $field_slug, $new_multiple_fields ) && ! is_array( $field_value ) ) {
					$changes[$field_slug] = array( $field_value );
				}
			}

			if ( ! empty( $changes ) ) {
				$user->set_values_for_fields( $changes );
			}
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
		require_once TFI_PATH . 'includes/options.php';

		$folders = tfi_get_option( 'tfi_file_folders' );
		$default_folder_slug = OptionsManager::get_parent_file_folder_slug();
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
						<?php if ( $folder_slug != $default_folder_slug ): ?>
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
				<tr id="tfi-folder-row-to-clone" hidden>
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
			<tr><td><button type="button" onclick="tfi_add_row('tfi-folder-row-to-clone', 'tfi-folder-', 'number_to_replace')" class="button action"><?php esc_html_e( 'Add a folder' ); ?></button></td></tr>
		</table>
		<?php
	}

	public function display_fields_section() {
		$user_types = tfi_get_option( 'tfi_user_types' );
		$fields = tfi_get_option( 'tfi_fields' );
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
				<?php
				foreach ( $fields as $id => $datas ) {
				?><tr id="tfi-field-<?php echo esc_attr( $id ); ?>"><?php
					$this->display_field_row( $id, $datas );
				?></tr><?php
				}
				/**
				 * This last row allows to add a new field by pressing the Add Field button.
				 * It should be deleted from the input array before verification (in the sanitize method)
				 */
				?><tr hidden id="tfi-field-row-to-clone"><?php
					$this->display_field_row( 'number_to_replace', array() );
				?></tr><?php
				?>
			</tbody>
			<tr><td><button type="button" onclick="tfi_add_row('tfi-field-row-to-clone', 'tfi-field-', 'number_to_replace'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Add a field' ); ?></button></td></tr>
		</table>
		<?php
	}

	private function display_field_row( $id, $datas ) {
		$user_types = tfi_get_option( 'tfi_user_types' );
		$field_types = tfi_get_option( 'tfi_field_types' );
		$folders = tfi_get_option( 'tfi_file_folders' );

		$default_special_params = array(
			'height' => 0,
			'width' => 0,
			'min' => 0,
			'max' => 0,
			'mandatory_domains' => array(),
			'folder' => OptionsManager::get_parent_file_folder_slug()
		);

		$default_single_params = array(
			'min_length' => 0,
			'max_length' => 0,
			'type' => 'text'
		);

		$default_multiple_fields = array(
			'multiple_field_special_params' => $default_special_params
		);

		$default_datas = array(
			'real_name' => __( 'New field' ),
			'type' => 'text',
			'default' => '',
			'special_params' => array_merge( $default_special_params, $default_single_params, $default_multiple_fields ),
			'users' => array()
		);

		$datas = tfi_array_merge_recursive_ex( $default_datas, $datas );
		$name = 'tfi_fields[' . $id . ']';
		$param_class = 'param-fields-' . $id;
		$param_multiple_class = 'param-fields-multiple-' . $id;
		?>
		<td><input type="text" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $id ); ?>" /></td>
		<td><input type="text" name="<?php echo esc_attr( $name ); ?>[real_name]" value="<?php esc_attr_e( $datas['real_name'] ); ?>" /></td>
		<td>
			<div class="tfi-form-col">
				<div>
					<select onchange="tfi_change_type_param(this)" class="field-type-select" name="<?php echo esc_attr( $name ); ?>[type]" param-row="<?php echo esc_attr( $param_class ); ?>">
						<?php foreach ( $field_types as $type_id => $display_name ): ?>
						<option value="<?php echo esc_attr( $type_id ); ?>" <?php echo $type_id == $datas['type'] ? 'selected' : ''; ?>><?php esc_html_e( $display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="<?php echo esc_attr( $param_class ); ?>" field-type="multiple" >
					<select onchange="tfi_change_type_param(this)" class="field-type-select" name="<?php echo esc_attr( $name ); ?>[special_params][type]" param-row="<?php echo esc_attr( $param_multiple_class ); ?>">
						<?php foreach ( $field_types as $type_id => $display_name ):
						if ( $type_id != 'multiple' ): ?>
						<option value="<?php echo esc_attr( $type_id ); ?>" <?php echo $type_id == $datas['special_params']['type'] ? 'selected' : ''; ?>><?php esc_html_e( $display_name ); ?></option>
						<?php endif;
						endforeach; ?>
					</select>
				</div>
			</div>
		</td>
		<td><input type="text" name="<?php echo esc_attr( $name ); ?>[default]" value="<?php esc_attr_e( $datas['default'] ); ?>" /></td>
		<td>
			<div class="tfi-form-col">
				<div class="param-fields">
					<?php $this->display_special_params( $id, $datas['special_params'], false ); ?>
				</div>
				<div class="param-fields <?php echo esc_attr( $param_class ); ?>" field-type="multiple">
					<?php $this->display_special_params( $id, $datas['special_params']['multiple_field_special_params'], true ); ?>
				</div>
			</div>
		</td>
		<td class="tfi-folder-row">
			<div class="<?php echo esc_attr( $param_class ); ?>" field-type="image">
				<select name="<?php echo esc_attr( $name ); ?>[special_params][folder]">
					<?php foreach ( $folders as $select_folder_slug => $select_folder ): ?>
					<option value="<?php echo esc_attr( $select_folder_slug ); ?>" <?php echo $select_folder_slug == $datas['special_params']['folder'] ? 'selected' : ''; ?>><?php esc_html_e( $select_folder['display_name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="<?php echo esc_attr( $param_class ); ?>" field-type="multiple">
				<div class="<?php echo esc_attr( $param_multiple_class ); ?>" field-type="image">
					<select name="<?php echo esc_attr( $name ); ?>[special_params][multiple_field][special_params][folder]">
						<?php foreach ( $folders as $select_folder_slug => $select_folder ): ?>
						<option value="<?php echo esc_attr( $select_folder_slug ); ?>" <?php echo $select_folder_slug == $datas['special_params']['multiple_field_special_params']['folder'] ? 'selected' : ''; ?>><?php esc_html_e( $select_folder['display_name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</td>
		<?php foreach ( $user_types as $type_id => $display_name ): ?>
		<td style="text-align: center;"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[users][<?php echo esc_attr( $type_id ); ?>]" <?php echo in_array( $type_id, $datas['users'] ) ? 'checked ' : ''; ?>/></td>
		<?php endforeach; ?>
		<td class="delete-button-row"><button type="button" onclick="tfi_remove_row('tfi-field-<?php echo esc_attr( $id ); ?>'); tfi_hide_first_row_button()" class="button action"><?php esc_html_e( 'Remove field' ); ?></button></td>
		<td class="change-field-row"><button type="button" onclick="tfi_move_row_to_up('tfi-field-<?php echo esc_attr( $id ); ?>')" class="button action">&#8597;</button></td>
		<?php
	}

	private function display_special_params( $id, $special_params, $multiple ) {
		$name = 'tfi_fields[' . $id . '][special_params]';
		$class = 'param-fields-' . $id;
		if ( $multiple ) {
			$name .= '[multiple_field_special_params]';
			$class = 'param-fields-multiple-' . $id;
		}
		?>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="image">
			<label title="<?php esc_attr_e( 'The mandatory height of the image (px)' ); ?>"><?php esc_html_e( 'H:' ); ?></label>
			<input  type="number"
					min="0"
					name="<?php echo esc_attr( $name ); ?>[height]"
					value="<?php echo esc_attr( $special_params['height'] ); ?>" />
		</div>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="image">
			<label title="<?php esc_attr_e( 'The mandatory width of the image (px)' ); ?>"><?php esc_html_e( 'W:' ); ?></label>
			<input  type="number"
					min="0"
					name="<?php echo esc_attr( $name ); ?>[width]"
					value="<?php echo esc_attr( $special_params['width'] ); ?>" />
		</div>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="link">
			<label title="<?php esc_attr_e( 'The required domain names separated by comma' ); ?>"><?php esc_html_e( 'D:' ); ?></label>
			<input  type="text"
					name="<?php echo esc_attr( $name ); ?>[mandatory_domains]"
					value="<?php echo esc_attr( implode( ',', $special_params['mandatory_domains'] ) ); ?>"
					placeholder="<?php esc_attr_e( 'domain.com,domain.net' ); ?>" />
		</div>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="number">
			<label title="<?php esc_attr_e( 'The minimum number possible to choose (if min > max there is no min value)' ); ?>"><?php esc_html_e( 'm:' ); ?></label>
			<input  type="number"
					name="<?php echo esc_attr( $name ); ?>[min]"
					value="<?php echo esc_attr( $special_params['min'] ); ?>" />
		</div>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="number">
			<label title="<?php esc_attr_e( 'The maximum number possible to choose (if max < min there is no max value)' ); ?>"><?php esc_html_e( 'M:' ); ?></label>
			<input  type="number"
					name="<?php echo esc_attr( $name ); ?>[max]"
					value="<?php echo esc_attr( $special_params['max'] ); ?>" />
		</div>
		<?php if ( ! $multiple ): ?>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="multiple">
			<label title="<?php esc_attr_e( 'The minimum length (should be >= 0)' ); ?>"><?php esc_html_e( 'm:' ); ?></label>
			<input  type="number"
					min="0"
					name="<?php echo esc_attr( $name ); ?>[min_length]"
					value="<?php echo esc_attr( $special_params['min_length'] ); ?>" />
		</div>
		<div class="<?php echo esc_attr( $class ); ?>" field-type="multiple">
			<label title="<?php esc_attr_e( 'The maximum length (no max if set to 0)' ); ?>"><?php esc_html_e( 'M:' ); ?></label>
			<input  type="number"
					min="0"
					name="<?php echo esc_attr( $name ); ?>[max_length]"
					value="<?php echo esc_attr( $special_params['max_length'] ); ?>" />
		</div>
		<?php endif; ?>
		<?php
	}

	public function display_users_section() {
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
				<tr id="tfi-user-row-to-clone" hidden>
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
			<tr><td><button type="button" onclick="tfi_add_row('tfi-user-row-to-clone', 'tfi-user-', 'number_to_replace')" class="button action"><?php esc_html_e( 'Add a user' ); ?></button></td></tr>
		</table>
		<?php
	}
}