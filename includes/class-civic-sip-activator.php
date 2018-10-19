<?php

/**
 * Fired during plugin activation
 *
 * @link       https://blockvis.com
 * @since      1.0.0
 *
 * @package    Civic_Sip
 * @subpackage Civic_Sip/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Civic_Sip
 * @subpackage Civic_Sip/includes
 * @author     Blockvis <blockvis@blockvis.com>
 */
class Civic_Sip_Activator {

	/**
	 * Activates the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		(new self)->plugin_create_db();

		// Enable WP user auth flow upon first activation.
		if ( get_option( 'civic-sip-settings' ) === false ) {
			update_option( 'civic-sip-settings', [ 'wp_user_auth_enabled' => 1, 'wp_user_registration_enabled' => 1 ] );
		}
	}

        private function plugin_create_db() {

                global $wpdb;
                $charset_collate = $wpdb->get_charset_collate();
                $table_name = $wpdb->prefix . 'civic_userdata';

                $sql = "CREATE TABLE $table_name (
                        id bigint(9) NOT NULL AUTO_INCREMENT,
                        genericid_type varchar(20) NOT NULL,
                        genericid_number varchar(30) NOT NULL,
                        genericid_name varchar(30) NOT NULL,
                        genericid_dob date,
                        genericid_issuance_date date,
                        genericid_expiry_date date,
                        genericid_image_hash varchar(40),
                        genericid_image text,
                        genericid_country varchar(30),
                        personal_email varchar(30),
                        personal_phonenumber varchar(30),
                        UNIQUE KEY id (id)
                ) $charset_collate;";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
        }

        private function plugin_create_db_new() {

                global $wpdb;
                $charset_collate = $wpdb->get_charset_collate();
                $table_name = $wpdb->prefix . 'civic_userdata';

                $sql = "CREATE TABLE $table_name (
                        id bigint(9) NOT NULL AUTO_INCREMENT,
                        civic_userid varchar(100) NOT NULL,
                        civic_data TEXT NOT NULL,
                        UNIQUE KEY id (id)
                ) $charset_collate;";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
        }
}
