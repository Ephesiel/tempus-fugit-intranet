<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class Template
 * 
 * Everything about a template echo
 * 
 * @since 1.3.0
 */
class Template {
    public $id;
    public $name;
    public $campain;

    public function __construct( $id, $name, $campain ) {
        $this->id       = $id;
        $this->name     = $name;
        $this->campain  = $campain;
    }

    /**
     * Pretty_id.
     * 
     * This method is used to store in database and named files for the template.
     * It allows to have a real nice name and a unique identifier (which includes the id, which is already unique).
     * It's not possible to used the id directly for database because it conflicted with basic array key (0, 1, 2, 3...)
     * 
     * @since 1.3.0
     * @access public
     * 
     * @return string A unique identifier for this template
     */
    public function pretty_id() {
        $pretty_name = preg_replace( '/[^a-z0-9_-]/', '', str_replace( ' ', '_', strtolower( $this->name ) ) );
        return $pretty_name . '-' . $this->id;
    }
}