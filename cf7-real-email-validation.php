<?php
/*
Plugin Name: Contact Form 7 Real E-mail Validation
Version: 1.0
Author: sahbaj
License: GPL
Author: Sahbaj
Description: An add-on for Contact Form 7 that valided an email field whether it is real or not using <a href="https://neverbounce.com/help/api/getting-started-with-the-api/" target="_blank">neverbounce API</a> 
*/

// If this file is called directly, abort.
if (!defined('WPINC')) { die; }

define( 'WPCF7CFV_VERSION', '1.0' );

define( 'WPCF7CFV_REQUIRED_WPCF7_VERSION', '4.0' );

$dir = plugin_dir_path( __FILE__ )."NeverBounceAPI-PHP-master/src/api/";
$files[] = 	$dir."NB_Curl.php";
$files[] = 	$dir."NB_Auth.php";
$files[] = 	$dir."NB_App.php";
$files[] = 	$dir."NB_Account.php";
$files[] = 	$dir."NB_Exception.php";
$files[] = 	$dir."NB_Job.php";
$files[] = 	$dir."NB_Single.php";
foreach ( $files as $file ) {
	require_once( $file);
}

new wpcf7_bg_real_email_validation();

class wpcf7_bg_real_email_validation{
	
	public function __construct() {		
		//add filter for text field validation
		add_filter('wpcf7_validate_email', array($this, 'cf7cfv_custom_form_validation'), 10, 2); // text field
		add_filter('wpcf7_validate_email*', array($this, 'cf7cfv_custom_form_validation'), 10, 2); // Req. text field
		// create custom plugin settings menu
		add_action('admin_menu', array($this, 'cf7cfv_create_menu'));
		add_action( 'admin_init', array($this,'cf7cfv_api_display_options') );
	}
	
	function cf7cfv_api_display_options() {		 
        $this->check_cf7_is_install();
		add_settings_section( 'cf7cfv_keys_section', '', array($this,'cf7cfv_api_content'), 'cf7cfv_api_options' );
		add_settings_field( 'cf7cfv_api_key', 'API KEY', array($this, 'cf7cfv_api_key_input'), 'cf7cfv_api_options', 'cf7cfv_keys_section' );
		add_settings_field( 'cf7cfv_api_secret_key', 'API SECRET KEY', array($this, 'cf7cfv_api_secret_key_input'), 'cf7cfv_api_options', 'cf7cfv_keys_section' );
		register_setting( 'cf7cfv_keys_section', 'cf7cfv_api_key' );
		register_setting( 'cf7cfv_keys_section', 'cf7cfv_api_secret_key' );
	}
	
	function cf7cfv_create_menu() {		
		add_options_page('Never Bounce API', 'Never Bounce API', 'administrator', 'cf7cfv_api_options', array($this, 'cf7cfv_settings_page'));		
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
		$api_secret_key = get_option( 'cf7cfv_api_secret_key' );		
		\NeverBounce\API\NB_Auth::auth($api_secret_key, $api_key);		
		$email = \NeverBounce\API\NB_Single::app()->verify($email);
		if( $email->is(0) )
		return true;
		else
		return false;
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
	
	function check_cf7_is_install() {
        // Check if CF7 is installed
        if (!defined('WPCF7_VERSION')) {
            // Display notice that CF7 is required
            add_action('admin_notices', array($this, 'show_cf7_required_notice'));
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
    
}
