<?php
/*
Plugin Name: Contact Form 7 Real E-mail Validation using Neverbounce
Version: 3.0
Author: garubi
License: GPL
Plugin URI: https://github.com/garubi/cf7-real-email-validation
Description: An add-on for Contact Form 7 that validates the email fields on any CF7 form using the online service at <a href="https://neverbounce.com/" target="_blank">Neverbounce</a>. Please note that a valid account and some credits at Neverbounce are rquired.
GitHub Plugin URI: https://github.com/garubi/cf7-real-email-validation
GitHub Branch: master
*/

// If this file is called directly, abort.
if (!defined('WPINC')) { die; }

define( 'WPCF7CFV_VERSION', '1.0' );

define( 'WPCF7CFV_REQUIRED_WPCF7_VERSION', '4.1' );

new wpcf7_bg_real_email_validation();

class wpcf7_bg_real_email_validation{

	public function __construct() {
		//check if Contact Form 7 is active and if its version is adeguate
		add_action('admin_init', array($this, 'check_cf7_is_install'));
		add_action('admin_init', array($this, 'check_API_key_is_saved'));
		//add filter for text field validation
		add_filter('wpcf7_validate_email', array($this, 'cf7cfv_custom_form_validation'), 10, 2); // text field
		add_filter('wpcf7_validate_email*', array($this, 'cf7cfv_custom_form_validation'), 10, 2); // Req. text field
		// create custom plugin settings menu
		add_action('admin_menu', array($this, 'cf7cfv_create_menu'));
		add_action( 'admin_init', array($this,'cf7cfv_api_display_options') );
	}

	function cf7cfv_api_display_options() {
		add_settings_section( 'cf7cfv_keys_section', '', array($this,'cf7cfv_api_content'), 'cf7cfv_api_options' );
		add_settings_field( 'cf7cfv_api_key', 'API KEY', array($this, 'cf7cfv_api_key_input'), 'cf7cfv_api_options', 'cf7cfv_keys_section' );
		add_settings_field( 'cf7cfv_catchall_as_valid', 'Consider CatchAll emails as valid emails', array($this, 'cf7cfv_catchall_as_valid_input'), 'cf7cfv_api_options', 'cf7cfv_keys_section' );
		register_setting( 'cf7cfv_keys_section', 'cf7cfv_api_key' );
		register_setting( 'cf7cfv_keys_section', 'cf7cfv_catchall_as_valid' );
	}

	function cf7cfv_create_menu() {
		add_options_page('NeverBounce integration', 'Never Bounce API', 'administrator', 'cf7cfv_api_options', array($this, 'cf7cfv_settings_page'));
	}

	function cf7cfv_settings_page(){
		require_once('cf7cfv_settings.php');
	}

	function cf7cfv_custom_form_validation($result,$tag) {
		$tag = new WPCF7_Shortcode( $tag );
		$name = $tag->name;
		//$_POST[$name] = 'sahabj';
		$value = isset( $_POST[$name] )
			? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
			: '';
		if ( 'email' == $tag->basetype ) {
			if ( $tag->is_required() && '' == $value ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			} elseif ( '' != $value && ! wpcf7_is_email( $value ) ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_email' ) );
			}
			elseif ( '' != $value && ! $this->bg_check_is_real_email( $value ) ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_email' ) );
			}
		}
		return $result;
	}

	function bg_check_is_real_email($email){
		$api_key = get_option( 'cf7cfv_api_key' );

		$catchall_is_valid = get_option( 'cf7cfv_catchall_as_valid' );

		if ( strlen( $api_key ) > 0 ){

  			// write_log($api_key);
  			$response = wp_remote_post('https://api.neverbounce.com/v4/single/check', array('body'=>array('key' => $api_key, 'email' => $email)) );
			$http_code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			write_log($body);
			// write_log($response);
			// write_log($http_code);

			if( $http_code != 200 ) return true; // if Neverbounce can't answer we keep collecting this email


			$body = json_decode( $body, $assoc_array = false );
			if( 'success' != $body->status ) return true; // if we have problems with Neverbounce we keep collecting this email

			$result = $body->result;

			if( 'valid' == $result || 'unknown' == $result ){
				return true;
			}
			elseif( 'catchall' == $result && 1 == $catchall_is_valid  ){
				return true;
			}
			else {
				return false;
			}

		}

		return true;
	}

	function cf7cfv_api_content() {
		echo '<p>Please find your credetials from <strong><a href="https://app.neverbounce.com/settings/api">here</a></strong>.</p>';
	}

	function cf7cfv_api_key_input() {
		echo '<input type="text" name="cf7cfv_api_key" id="captcha_site_key" placeholder="Your API username" value="'. get_option( 'cf7cfv_api_key' ) . '" />';
	}

	function cf7cfv_api_secret_key_input() {
		echo '<input type="text" name="cf7cfv_api_secret_key" id="captcha_secret_key" placeholder="Your API secret key" value="' . get_option( 'cf7cfv_api_secret_key' ) . '" />';
	}

	function cf7cfv_catchall_as_valid_input() {
		echo '<input type="checkbox" name="cf7cfv_catchall_as_valid" id="captcha_catchall_valid" value="1" '. checked( get_option( 'cf7cfv_catchall_as_valid' ), 1, false )  .'/>';
	}

	function check_cf7_is_install() {
        // Check if CF7 is installed
        if (!defined('WPCF7_VERSION') || version_compare( WPCF7_VERSION, WPCF7CFV_REQUIRED_WPCF7_VERSION, '<' )) {
            // Display notice that CF7 is required
            add_action('admin_notices', array($this, 'show_cf7_required_notice'));
            return;
		}
	}

	function check_API_key_is_saved() {
		$api_key = get_option( 'cf7cfv_api_key' );
		// $api_secret_key = get_option( 'cf7cfv_api_secret_key' );
		// if ( strlen( $api_key ) == 0 || strlen( $api_secret_key ) == 0 ){
		if ( strlen( $api_key ) == 0 ){
            add_action('admin_notices', array($this, 'show_save_apikey_notice'));
            return;
		}
	}

	function show_cf7_required_notice() {
		$plugin_data = get_plugin_data(__FILE__);
            echo '
        <div class="notice notice-error is-dismissible">
          <p>' . sprintf(__('<strong>%s</strong> requires <strong><a href="'.admin_url('plugin-install.php?tab=search&s=contact+form+7').'" target="_blank">Contact Form 7</a></strong> plugin to be installed and activated on your site.', 'vc_extend'), $plugin_data['Name']) . '</p>
        </div>';
    }

	function show_save_apikey_notice() {
		$plugin_data = get_plugin_data(__FILE__);
            echo '
        <div class="notice notice-error is-dismissible">
          <p>' . sprintf(__('<strong>%s</strong>: Please save your Neverbounce API keys in order to enable real email verification in your forms.', 'vc_extend'), $plugin_data['Name']) . '</p>
        </div>';
    }

}
if (!function_exists('write_log')) {
    function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
			error_log(var_export($log,true));
        }
    }
}
