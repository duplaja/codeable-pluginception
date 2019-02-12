<?php
/*
Plugin Name: Pluginception - Codeable Spinup
Plugin URI: https://dandulaney.com
Description: A plugin to create other plugins (modified for Codeable). Pluginception.
Version: 1.0
Author: Dan Dulaney (forked from a plugin by Otto)
Author URI: https://dandulaney.com
Text Domain: pluginception_codeable
License: GPLv2 only
License URI: http://www.gnu.org/licenses/gpl-2.0.html


    Copyright 2011-2013  Samuel Wood  (email : otto@ottodestruct.com)
    Copyright 2019 by Dan Dulaney <dan.dulaney07@gmail.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define("AUTHOR_NAME", "Dan Dulaney");
define("AUTHOR_EMAIL", "dan.dulaney07@gmail.com");
define("AUTHOR_SITE", "https://codeable.io/developers/dan-dulaney/");
define("PLUGIN_SITE", "https://codeable.io/developers/dan-dulaney/");
define("PLUGIN_VERSION", "0.1");
define("PLUGIN_LICENSE", "GPLv2");
define("LICENSE_SITE", "http://www.gnu.org/licenses/gpl-2.0.html");

add_action('admin_menu', 'pluginception_codeable_admin_add_page');
function pluginception_codeable_admin_add_page() {
	add_plugins_page(
		'Create Codeable Plugin',
		'Create Codeable Plugin',
		'edit_plugins',
		'pluginception_codeable',
		'pluginception_codeable_options_page'
	);
}

function pluginception_codeable_options_page() {
	$results = pluginception_codeable_create_plugin();

	if ( $results === true ) return;
	
	echo '<div class="wrap">
		
		<h2>Create a New Codeable Plugin</h2>';
		settings_errors();
		echo '<form method="post" action="">';
		wp_nonce_field('pluginception_codeable_nonce');
		echo '<table class="form-table">';
		$opts = array(
			'name' => 'Plugin Name',
			'slug' => 'Plugin Slug (optional)',
			'uri' => 'Plugin URI (optional)',
			'description' => 'Description (optional)',
			'version' => 'Version (optional)',
			'author' => 'Author (optional)',
			'author_uri' => 'Author URI (optional)',
			'license' => 'License (optional)',
			'license_uri' => 'License URI (optional)',
			'prefix' => 'Function Prefix (optional, defaults modified plugin name if blank)'
		);

		foreach ($opts as $slug=>$title) {
			$value = '';
			if (!empty($results['pluginception_codeable_'.$slug])) $value = esc_attr($results['pluginception_codeable_'.$slug]);
			
			switch($slug) {
				case 'uri':
					if(empty($value)) $value= PLUGIN_SITE;
					break;
				case 'version':
					if(empty($value)) $value = PLUGIN_VERSION;
					break;
				case 'author':
					if(empty($value)) $value = AUTHOR_NAME;
					break;
				case 'author_uri':
					if(empty($value)) $value = AUTHOR_SITE;
					break;
				case 'license':
					if(empty($value)) $value = PLUGIN_LICENSE;
					break;
				case 'license_uri':
					if(empty($value)) $value = LICENSE_SITE;
					break;
			}
			
			
			echo "<tr valign='top'><th scope='row'>{$title}</th><td><input class='regular-text' type='text' name='" . esc_attr("pluginception_codeable_{$slug}") . "' value='{$value}'></td></tr>\n";
		}

		echo "<tr valign='top'><th scope='row'>Add CSS / JS?</th><td><select class='regular-text' name='pluginception_codeable_subdirs'>
		<option value='neither'>Neither</option>
		<option value='both'>Both</option>
		<option value='css'>Just CSS</option>
		<option value='js'>Just JS</option>
		</select></td></tr>\n";		

		echo '</table>';
		submit_button( 'Create a blank Codeable plugin and activate it!' );
		echo '</form>
	</div>';
}


function pluginception_codeable_create_plugin() {
	if ( 'POST' != $_SERVER['REQUEST_METHOD'] )
		return false;

	check_admin_referer('pluginception_codeable_nonce');

	// remove the magic quotes
	$_POST = stripslashes_deep( $_POST );

	if (empty($_POST['pluginception_codeable_name'])) {
		add_settings_error( 'pluginception_codeable', 'required_name','Plugin Name is required', 'error' );
		return $_POST;
	}

	if ( empty($_POST['pluginception_codeable_slug'] ) ) {
		$_POST['pluginception_codeable_slug'] = sanitize_title($_POST['pluginception_codeable_name']);
	} else {
		$_POST['pluginception_codeable_slug'] = sanitize_title($_POST['pluginception_codeable_slug']);
	}

	if ( file_exists(trailingslashit(WP_PLUGIN_DIR).$_POST['pluginception_codeable_slug'] ) ) {
		add_settings_error( 'pluginception_codeable', 'existing_plugin', 'That plugin appears to already exist. Use a different slug or name.', 'error' );
		return $_POST;
	}

	$form_fields = array ('pluginception_codeable_name', 'pluginception_codeable_slug', 'pluginception_codeable_uri', 'pluginception_codeable_description', 'pluginception_codeable_version',
				'pluginception_codeable_author', 'pluginception_codeable_author_uri', 'pluginception_codeable_license', 'pluginception_codeable_license_uri');
	$method = ''; // TODO TESTING

	// okay, let's see about getting credentials
	$url = wp_nonce_url('plugins.php?page=pluginception_codeable','pluginception_codeable_nonce');
	if (false === ($creds = request_filesystem_credentials($url, $method, false, false, $form_fields) ) ) {
		return true;
	}

	// now we have some credentials, try to get the wp_filesystem running
	if ( ! WP_Filesystem($creds) ) {
		// our credentials were no good, ask the user for them again
		request_filesystem_credentials($url, $method, true, false, $form_fields);
		return true;
	}

	global $wp_filesystem;

	// create the plugin directory
	$plugdir = $wp_filesystem->wp_plugins_dir() . $_POST['pluginception_codeable_slug'];

	$cssdir = $plugdir.'/css';
	$jsdir = $plugdir.'/js';

	if ( ! $wp_filesystem->mkdir($plugdir) ) {
		add_settings_error( 'pluginception_codeable', 'create_directory', 'Unable to create the plugin directory.', 'error' );
		return $_POST;
	}

	if('both' == $_POST['pluginception_codeable_subdirs'] || 'css' == $_POST['pluginception_codeable_subdirs']) {

		if ( ! $wp_filesystem->mkdir($cssdir) ) {
			add_settings_error( 'pluginception_codeable', 'create_directory', 'Unable to create the CSS subdirectory.', 'error' );
			return $_POST;
		}
	}

	if('both' == $_POST['pluginception_codeable_subdirs'] || 'js' == $_POST['pluginception_codeable_subdirs']) {
		if ( ! $wp_filesystem->mkdir($jsdir) ) {
			add_settings_error( 'pluginception_codeable', 'create_directory', 'Unable to create the JS subdirectory.', 'error' );
			return $_POST;
		}
	}

	// create the plugin main file header

	$curyear = date('Y');
	$author_name = AUTHOR_NAME;
	$author_email = AUTHOR_EMAIL;
	$plugin_version = PLUGIN_VERSION;

	if(!empty($_POST['pluginception_codeable_prefix'])) {
		$plugin_prefix = $_POST['pluginception_codeable_prefix'];
	} else {

		$plugin_prefix = str_replace(' ','_',preg_replace("/[^A-Za-z0-9 ]/", '',strtolower($_POST['pluginception_codeable_name'])));
	}


	$plugfile = trailingslashit($plugdir).$_POST['pluginception_codeable_slug'].'.php';
	$blankplugfile = trailingslashit($plugdir).'index.php';
	$cssfile = trailingslashit($plugdir).'/css/main.css';
	$jsfile = trailingslashit($plugdir).'/js/main.js';
	$blankjsindex = trailingslashit($plugdir).'/js/index.php';
	$blankcssindex = trailingslashit($plugdir).'/css/index.php';



	$header = <<<END
<?php
/*
Plugin Name: {$_POST['pluginception_codeable_name']}
Plugin URI: {$_POST['pluginception_codeable_uri']}
Description: {$_POST['pluginception_codeable_description']}
Version: {$_POST['pluginception_codeable_version']}
Author: {$_POST['pluginception_codeable_author']}
Author URI: {$_POST['pluginception_codeable_author_uri']}
License: {$_POST['pluginception_codeable_license']}
License URI: {$_POST['pluginception_codeable_license_uri']}

    Copyright {$curyear} by {$author_name} <{$author_email}>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


END;

if ('both' == $_POST['pluginception_codeable_subdirs'] || 'css' == $_POST['pluginception_codeable_subdirs']) {


	$header .= <<<END

if ( ! function_exists( '{$plugin_prefix}_enqueue_styles' ) ) {
	/**
	 * Enqueues main plugin style.
	 *
	 * @param
	 * @return
	 */
	add_action( 'wp_enqueue_scripts', '{$plugin_prefix}_enqueue_styles' );
	function {$plugin_prefix}_enqueue_styles() {
		wp_enqueue_style( '{$_POST['pluginception_codeable_slug']}-main-style', plugin_dir_url( __FILE__ ) . 'css/main.css', array(), '{$plugin_version}' ); 
		
	}
}


END;
} 
if ('both' == $_POST['pluginception_codeable_subdirs'] || 'js' == $_POST['pluginception_codeable_subdirs']) {

	$header .= <<<END

	
if ( ! function_exists( '{$plugin_prefix}_enqueue_scripts' ) ) {
	/**
	 * Enqueues main plugin script.
	 *
	 * @param
	 * @return
	 */
	add_action( 'wp_enqueue_scripts', '{$plugin_prefix}_enqueue_scripts' );
	function {$plugin_prefix}_enqueue_scripts() {
		wp_enqueue_script( '{$_POST['pluginception_codeable_slug']}-main-script', plugin_dir_url( __FILE__ ) . 'js/main.js', array(), '{$plugin_version}' ); 
		
	}
}


END;

}

	$blank_header = <<<END
<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

END;

	$css_file_header = <<<END
/* {$_POST['pluginception_codeable_name']} - CSS */	
END;

	$js_file_header = <<<END
/* {$_POST['pluginception_codeable_name']} - JS */	
END;



	if ( ! $wp_filesystem->put_contents( $plugfile, $header, FS_CHMOD_FILE) ) {
		add_settings_error( 'pluginception_codeable', 'create_file', 'Unable to create the plugin file.', 'error' );
	}

	if ( ! $wp_filesystem->put_contents( $blankplugfile, $blank_header, FS_CHMOD_FILE) ) {
		add_settings_error( 'pluginception_codeable', 'create_file', 'Unable to create the plugin blank index file.', 'error' );
	}

	if('both' == $_POST['pluginception_codeable_subdirs'] || 'css' == $_POST['pluginception_codeable_subdirs']) {

		if ( ! $wp_filesystem->put_contents( $cssfile, $css_file_header, FS_CHMOD_FILE) ) {
			add_settings_error( 'pluginception_codeable', 'create_file', 'Unable to create the plugin blank index file.', 'error' );
		}
		
		if ( ! $wp_filesystem->put_contents( $blankcssindex, $blank_header, FS_CHMOD_FILE) ) {
			add_settings_error( 'pluginception_codeable', 'create_file', 'Unable to create the plugin blank index file in css subfolder.', 'error' );
		}

	}
	if('both' == $_POST['pluginception_codeable_subdirs'] || 'js' == $_POST['pluginception_codeable_subdirs']) {

		if ( ! $wp_filesystem->put_contents( $jsfile, $js_file_header, FS_CHMOD_FILE) ) {
			add_settings_error( 'pluginception_codeable', 'create_file', 'Unable to create the plugin blank index file.', 'error' );
		}
		if ( ! $wp_filesystem->put_contents( $blankjsindex, $blank_header, FS_CHMOD_FILE) ) {
			add_settings_error( 'pluginception_codeable', 'create_file', 'Unable to create the plugin blank index file in js subfolder.', 'error' );
		}
	}

	$plugslug = $_POST['pluginception_codeable_slug'].'/'.$_POST['pluginception_codeable_slug'].'.php';
	$plugeditor = admin_url('plugin-editor.php?file='.$_POST['pluginception_codeable_slug'].'%2F'.$_POST['pluginception_codeable_slug'].'.php');

	if ( null !== activate_plugin( $plugslug, '', false, true ) ) {
		add_settings_error( 'pluginception_codeable', 'activate_plugin', 'Unable to activate the new plugin.', 'error' );
	}

	// plugin created and activated, redirect to the plugin editor
	?>
	<script type="text/javascript">
	<!--
	window.location = "<?php echo esc_url_raw( $plugeditor ); ?>"
	//-->
	</script>
	<?php

	/* translators: inline link to plugin editor */
	$message = '<a href="'.$plugeditor.'">The new plugin has been created and activated. You can go to the editor if your browser does not redirect you.</a>';

	add_settings_error('pluginception_codeable', 'plugin_active', $message, 'pluginception_codeable', 'updated');

	return true;
}

