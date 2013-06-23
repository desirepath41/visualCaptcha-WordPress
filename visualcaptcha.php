<?php
/*
Plugin Name: visualCaptcha
Plugin URI:  http://visualcaptcha.net/
Description: The easiest way to implement an unusual Captcha with images instead of text and drag & drop capabilities.
Author: emotionLoop
Version: 4.1.0  Wordpress 
Author URI: http://emotionloop.com/
License: GNU GPL v3
*/

////////////////////////////////////////////////////////////////////////////////////////////
//Wp front end Section

/////////////////////////
//set hooks 
$visualcaptcha_current_hooks = get_option( 'visualcaptcha_form_hooks' );
if ( is_array($visualcaptcha_current_hooks) && !empty($visualcaptcha_current_hooks) ) {
	foreach ( $visualcaptcha_current_hooks as $visualcaptcha_hook => $visualcaptcha_hook_data) {
		// not enabled on the admin panel -> do nothing
		if ( empty( $visualcaptcha_hook_data[ 'checked' ] ) ) {
			continue;
		}
		
		
		//add action 
		if ( !empty( $visualcaptcha_hook_data[ 'action' ] ) ) {
			add_action( $visualcaptcha_hook, 'visualcaptcha_do_action' );
			
			//add filter to a custom option
			if ( isset( $visualcaptcha_hook_data[ 'filter' ] ) && !empty( $visualcaptcha_hook_data[ 'filter' ] ) ) {
				//ignore duplicate filters
				if ( !isset( $visualcaptcha_current_hooks [ $visualcaptcha_hook_data[ 'filter' ] ] ) ) {
					add_filter( $visualcaptcha_hook_data[ 'filter' ], 'visualcaptcha_do_filter',1000,3); 
				}
			}
		}
		
		//add filter
		if ( empty( $visualcaptcha_hook_data[ 'action' ] ) ) {
			add_filter( $visualcaptcha_hook, 'visualcaptcha_do_filter',1000,3); 
		}
	}
}

/////////////////////////
//visualCaptcha Filters
function visualcaptcha_do_filter( $data_in = false, $data_in1 = false, $data_in2=false ) {
	//if no visualCaptcha posted.. ignore
	if ( !isset( $_POST[ 'captcha-value' ] ) && !isset( $_POST['visualcaptcha'] )) {
		return $data_in;
	}
	//if just the visualCaptcha input .. ignore
	if ( isset( $_POST[ 'captcha-value' ] ) && count( $_POST ) <= 1 ) {
		return $data_in;
	}
	
	
	$current_filter = current_filter();
	$test_post = validate_visualCaptcha( $_POST['visualcaptcha'] );

	
	//special consideration for comments (they have to die() )
	if ( strpos( $current_filter, 'comment' ) !== false ) {
		if ( !empty( $data_in ) ) {
			if ( is_array( $data_in ) && isset( $data_in[ 'comment_post_ID' ] ) && !empty( $data_in[ 'comment_post_ID' ] ) ) {
				if ( ! $test_post ) {
					wp_die( __('ERROR: You failed the human verification test. Please go back and try again.', 'visualcaptcha'));
				}	
			}
		}
	}
	
	//if $data_in is an error...
	if ( is_wp_error( $data_in ) || is_wp_error( $data_in1 ) || is_wp_error( $data_in2 ) ) {
		//if no visualCaptcha error
		if ( !empty( $test_post ) ) {
			return $data_in;
		//if there is a visualCaptcha error
		} else {
			$error_out = false;
			if ( is_wp_error( $data_in ) ) {
				$error_out = $data_in;
			}
			if ( is_wp_error( $data_in1 ) ) {
				$error_out = $data_in1;
			}
			if ( is_wp_error( $data_in2 ) ) {
				$error_out = $data_in2;
			}

			$error_out->add( 'visualcaptcha_error' , '<strong>'. __( 'ERROR:' , 'visualcaptcha' ) .'</strong> '.__('Invalid visualCaptcha'  , 'visualcaptcha') );
			unset( $_POST[ 'captcha-value' ], $_POST[ 'visualcaptcha' ] );
			return $error_out;
		}
		
	} 
	
	
	//if $data_in is a user
	$user_test = is_a($data_in, 'WP_User');
	if ( !empty( $user_test ) ) { 
		//if visualCaptcha data on $_POST
		if ( ( isset( $_POST[ 'captcha-value' ] ) || isset( $_POST['visualcaptcha'] ) ) && empty( $test_post ) ) {
			unset( $_POST[ 'captcha-value' ], $_POST[ 'visualcaptcha' ] );
			return new WP_Error('visualcaptcha_error', '<strong>'. __( 'ERROR:' , 'visualcaptcha' ) .'</strong> '.__('Invalid visualCaptcha'  , 'visualcaptcha') );
		}
		
	}

	//if is boolean
	if ( is_bool( $data_in ) ) {
		
		if ( !empty( $test_post ) ) {
			return $data_in;
		} else {
			unset( $_POST[ 'captcha-value' ], $_POST[ 'visualcaptcha' ] );
			return new WP_Error('visualcaptcha_error', '<strong>'. __( 'ERROR:' , 'visualcaptcha' ) .'</strong> '.__('Invalid visualCaptcha'  , 'visualcaptcha') );
		}
	}
	
	//if empty 
	if ( empty( $data_in ) ) {
		if ( !empty( $test_post ) ) {
			return $data_in;
		} else {
			unset( $_POST[ 'captcha-value' ], $_POST[ 'visualcaptcha' ] );
			return new WP_Error('visualcaptcha_error', '<strong>'. __( 'ERROR:' , 'visualcaptcha' ) .'</strong> '.__('Invalid visualCaptcha'  , 'visualcaptcha') );
		}
	}

	return $data_in;
}


/////////////////////////
//visualCaptcha Actions
function visualcaptcha_do_action ( $data_in = false, $data_in1 = false, $data_in2=false ) {
	//get all hooks inf
	$hooks = get_option('visualcaptcha_form_hooks' );
	
	$current_hook_id = current_filter();
	$current_hook_data = ( isset( $hooks[ $current_hook_id ] ) )? $hooks[ $current_hook_id ] : false;
	
	if ( empty( $current_hook_data ) ) {
		return $data_in;
	}
	
	print_visualCaptcha('loginform', $current_hook_data['vertical_opt'],NULL,NULL);
   
	return $data_in;	
}
/////////////////////////
// Start visualCaptcha Form
function print_visualCaptcha($formId = NULL, $type = NULL, $fieldName = NULL, $accessibilityFieldName = NULL) {
	if ( session_id() == '' ) {
		session_start();
	}
	require_once('inc/visualcaptcha.class.php');
	$visualCaptcha = new \visualCaptcha\Captcha($formId,$type,$fieldName,$accessibilityFieldName);
	$visualCaptcha->show();
}

/////////////////////////
// Start visualCaptcha Validation
function validate_visualCaptcha($formId = NULL, $type = NULL, $fieldName = NULL, $accessibilityFieldName = NULL) {
	if ( session_id() == '' ) {
		session_start();
	}
	
	require_once('inc/visualcaptcha.class.php');
	$visualCaptcha = new \visualCaptcha\Captcha($formId,$type,$fieldName,$accessibilityFieldName);
	return $visualCaptcha->isValid();
}


////////////////////////////////////////////////////////////////////////////////////////////
//Wp admin Section

/////////////////////////
//Wp admin Menu
function add_visualcaptcha_admin_menu() {
	//adding emotionLoop page 
	add_menu_page( 'emotionLoop Plugins', 'emotionLoop', 'manage_options', 'emotionloop_slug', 'emotionloop_admin_menu_render' , WP_CONTENT_URL."/plugins/visualcaptcha/images/emotionloop_icon.png");
	add_submenu_page( 'emotionloop_slug' , '', '', 'manage_options', "emotionloop_slug", 'emotionloop_admin_menu_render');
	add_submenu_page( 'emotionloop_slug' , __( 'visualCaptcha', 'visualcaptcha' ), __( 'visualCaptcha', 'visualcaptcha' ), 'manage_options', "visualcaptcha.php", 'visualcaptcha_admin_settings_page');

}

/////////////////////////
//visualCaptcha page
function visualcaptcha_admin_settings_page () {
	$visualcaptcha_current_hooks = get_option( 'visualcaptcha_form_hooks' );
	$updated_vars = false;
	$new_hook = array('name' =>'','action' =>'','filter' =>'');
	$new_hook_error = array();
	
	//updates
	if ( isset( $_POST['visualcaptcha_nonce']) && check_admin_referer( 'visualcaptcha_update', 'visualcaptcha_nonce' ) )  {
		//update the current hooks
		if ( isset( $_POST['visualcaptcha_form_hooks'] ) && is_array( $_POST['visualcaptcha_form_hooks'] ) ) {
			
			$posted_hooks = $_POST['visualcaptcha_form_hooks'];
			
			foreach ( $visualcaptcha_current_hooks as $hook => &$data ) {
				//ignore filters changes
				if ( empty( $data['action'] ) ) {
					continue;
				}
				$data[ 'checked' ] =  ( in_array( $hook, $posted_hooks ) ) ? true : false;
				$data[ 'vertical_opt' ] =  ( isset( $_POST[ $hook.'_orientation' ] ) && !empty( $_POST[ $hook.'_orientation'] ) )? true : false ;
			}
			
			//update 
			update_option( 'visualcaptcha_form_hooks' , $visualcaptcha_current_hooks );
			$updated_vars = true;
		}
	}

	//add new options
	if ( isset( $_POST['visualcaptcha_nonce_opt']) && check_admin_referer( 'visualcaptcha_addpot', 'visualcaptcha_nonce_opt' ) )  {
		$hook_name = $_POST['visualcaptcha_add_opt_name'];
		$hook_action = $_POST['visualcaptcha_add_opt_action_hook'];
		$hook_filter = $_POST['visualcaptcha_add_opt_filter_hook'];
		$terms = $_POST['visualcaptcha_terms'];
		//check for empty post data
		if ( empty( $hook_name ) ||  empty( $hook_action ) ||  empty( $hook_filter ) ) {
			if ( empty( $hook_name ) ) { $new_hook_error['visualcaptcha_add_opt_name'] = 'error'; }
			if ( empty( $hook_action ) ) { $new_hook_error['visualcaptcha_add_opt_action'] = 'error'; }
			if ( empty( $hook_filter ) ) { $new_hook_error['visualcaptcha_add_opt_filter'] = 'error'; }
		}
		//terms error
		if ( empty( $terms ) ) {
			$new_hook_error['visualcaptcha_terms'] = 'error';
		}
		
		//action error
		if ( isset( $visualcaptcha_current_hooks[ $hook_action ] ) && empty( $new_hook_error )) {
			$new_hook_error['action'] = 'error';
		}
		//filter alert
		if ( isset( $visualcaptcha_current_hooks[ $hook_filter ] ) && empty( $visualcaptcha_current_hooks[ $hook_filter ][ 'action' ] ) && empty( $new_hook_error )) {
			$new_hook_error['filter'] = 'alert';
		}
		
		//if no error or just filter alert
		if ( empty( $new_hook_error ) || 
			(count($new_hook_error) == 1 && isset( $new_hook_error['filter'] )  )
			) {
			$visualcaptcha_current_hooks[ $hook_action ] = array( 
															'name' => $hook_name, 
															'filter' => $hook_filter,
															'checked' => true, 
															'action' => true, 
															'vertical_opt' => false, );


			update_option( 'visualcaptcha_form_hooks' , $visualcaptcha_current_hooks );
			$updated_vars = true;
		} else {
			$new_hook['name'] = $hook_name;
			$new_hook['action'] = $hook_action;
			$new_hook['filter'] = $hook_filter;		
		}
		
	}
	
	/////////
	if ( !empty( $updated_vars ) ) {
		$visualcaptcha_current_hooks = get_option( 'visualcaptcha_form_hooks' );
	}
	
	
	if ( !empty( $updated_vars ) && isset( $_POST['visualcaptcha_nonce']) ) {
?>
<div class="updated"><p><strong><?php _e('visualCaptcha Updated.', 'visualcaptcha' ); ?></strong></p></div>
<?php
	}
	
	if( empty( $new_hook_error ) && isset( $_POST[ 'visualcaptcha_nonce_opt' ] ) ) { ?>
<div class="updated"><p><strong><?php _e('visualCaptcha option added.', 'visualcaptcha' ); ?></strong></p></div>
        <?php 
	}
	
	if( !empty( $new_hook_error ) && isset( $_POST[ 'visualcaptcha_nonce_opt' ] ) ) {
		//filter warning
		if ( isset( $new_hook_error[ 'filter' ] ) ) {
			//do nothing.. the user doesn't need to know about this
		} else if( isset( $new_hook_error[ 'action' ] ) ) {
						?>
<div class="error"><p><strong><?php _e('This action is already being used by visualCaptcha.', 'visualcaptcha' ); ?></strong></p></div>
                        <?php
		} else {
			//normal error
						?>
<div class="error"><p><strong><?php _e('Please fill in correctly all the fields before adding an option to visualCaptcha.', 'visualcaptcha' ); ?></strong></p></div>
                        <?php
		}
	}
?>

<style>
	.visualcaptcha_promo_img{ float:left; margin-right: 5px;}
	.clear { clear:both;}
	.visualcaptcha_settings_title { margin-top:20px;}
	.visualcaptcha_form_container { background-color:#E3E9E3; margin-bottom:20px; padding-top:10px ; padding-left:10px;}
	.visualcatpcha_form_text_input { min-width:200px;}
	.visualcaptcha_error{ color:#F00 }
</style>
<div class="wrap">
	<div>
    	<h2><?php _e('visualCaptcha by emotionLoop', 'visualcaptcha' ); ?></h2>
    </div>
    <div>
    	<div class="visualcaptcha_promo_img"><img src="<?php echo WP_CONTENT_URL."/plugins/visualcaptcha/images/visualcaptcha-screenshot.png" ?>" alt="emotionLoop" /></div>
    	<p><strong>visualCaptcha</strong> is an easy to implement secure Captcha <br /><strong>with images</strong> instead of text <strong>and drag & drop</strong> capabilities (and mobile-friendly).</p>
        <p>&#10004; Accessible<br />&#10004; Retina-ready<br />&#10004; Easy to install<br />&#10004; Easy to use<br />&#10004; Secure<br />&#10004; Customizable</p>
        <div class="clear"></div>
    </div>
	<div class="visualcaptcha_settings_title">
    	<h3><?php _e('visualCaptcha Settings', 'visualcaptcha' ); ?> <small><a href="http://emotionloop.com/donate?app=visualCaptcha<?php echo visualcaptcha_donate() ?>&tkc=wpvctl" target="_new">Please DONATE</a></small></h3>
    </div>
    <div class="visualcaptcha_form_container">
        <form method="post" action="admin.php?page=<?php echo $_GET['page'] ?>">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Add visualCaptcha to:', 'visualcaptcha' ); ?> </th>
                    <td>
                        
            <?php 
            foreach ( $visualcaptcha_current_hooks as $hook => $hook_data ) { 
                if ( empty( $hook_data[ 'action' ] ) && !isset( $hook_data[ 'user_opt' ] ) ) {
                    continue;
                }
				$opt_name = $hook_data[ 'name' ];
				if ( isset( $hook_data[ 'filter' ] ) ) {
					$opt_name = $hook_data[ 'name' ].' (<strong>'.__('show', 'visualcaptcha').':</strong> '.$hook.' <strong>'.__('validate', 'visualcaptcha').':</strong> '.$hook_data[ 'filter' ].')' ;
				}
            ?>
                            <label>
                                <input type="checkbox" name="visualcaptcha_form_hooks[]" value="<?php echo $hook; ?>" <?php echo ( isset( $hook_data[ 'checked' ] ) && !empty($hook_data[ 'checked' ]  ) ) ? 'checked="checked"' : ''; ?> /> 
                                <?php echo $opt_name ?>
                            </label> - 
                            <select name="<?php echo $hook; ?>_orientation" id="<?php echo $hook; ?>_orientation">
                                <option value="1" <?php echo ( isset( $hook_data[ 'vertical_opt' ] ) &&  !empty( $hook_data[ 'vertical_opt' ] ) )? 'selected="selected"' : '' ?> ><?php _e( 'vertical captcha', 'visualcaptcha' ) ?></option>
                                <option value="0" <?php echo ( isset( $hook_data[ 'vertical_opt' ] ) &&  empty( $hook_data[ 'vertical_opt' ] ) )? 'selected="selected"' : '' ?> ><?php _e( 'horizontal captcha', 'visualcaptcha' ) ?>  </option>
                            </select><br />
            <?php 
            }
            ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th></th>
                    <td>
                    	<?php wp_nonce_field( 'visualcaptcha_update', 'visualcaptcha_nonce' ); ?>
                    	<?php submit_button( __('Update visualCaptcha', 'visualcaptcha' ), 'submit', 'submit' )  ?>
					</td>
                </tr>
            </table>
        </form>
        
        <form method="post" action="admin.php?page=<?php echo $_GET['page'] ?>">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Add custom options', 'visualcaptcha' ); ?> </th>
                    <td>
                    	<label class="<?php echo ( isset($new_hook_error['visualcaptcha_add_opt_name'] ) )? 'visualcaptcha_error' : '' ; ?>"><?php _e('Option Name', 'visualcaptcha' ) ?> *</label><br />
                        <input name="visualcaptcha_add_opt_name" class="visualcatpcha_form_text_input" value="<?php echo $new_hook['name'] ?>"/> <small>(<?php _e('Name for the "Add visualCaptcha to:" List', 'visualcaptcha' ) ?>)</small><br />
                    	<label class="<?php echo ( isset($new_hook_error['visualcaptcha_add_opt_action'] ) )? 'visualcaptcha_error' : '' ; ?>"><?php _e('Display Hook', 'visualcaptcha' ) ?> *</label><br />
                        <input name="visualcaptcha_add_opt_action_hook" class="visualcatpcha_form_text_input"  value="<?php echo $new_hook['action'] ?>"/> <small>(<?php _e('Action Hook used to show visualCaptcha - i.e. "login_form"', 'visualcaptcha' ) ?>)</small><br />
                    	<label class="<?php echo ( isset($new_hook_error['visualcaptcha_add_opt_filter'] ) )? 'visualcaptcha_error' : '' ; ?>"><?php _e('Validation Hook', 'visualcaptcha' ) ?> *</label><br />
                        <input name="visualcaptcha_add_opt_filter_hook" class="visualcatpcha_form_text_input"  value="<?php echo $new_hook['filter'] ?>"/> <small>(<?php _e('Filter Hook used to validate a visualCaptcha submission - i.e. "authenticate"', 'visualcaptcha' ) ?>)</small><br />
                        <small><a href="http://adambrown.info/p/wp_hooks/version/" target="_new"><?php _e('DEFAULT HOOKS REFERENCE', 'visualcaptcha' ) ?></a></small><br /><br />
                        <label class="<?php echo ( isset($new_hook_error['visualcaptcha_terms'] ) )? 'visualcaptcha_error' : '' ; ?>"><input type="checkbox" name="visualcaptcha_terms" value="1" /> <?php _e('By using this option you confirm that you have the proper knowledge to use Wordpress Actions and Filters. If you don\'t, please don\'t use it') ?> *</label><br />
                    	<?php wp_nonce_field( 'visualcaptcha_addpot', 'visualcaptcha_nonce_opt' ); ?>
                    	<a href="http://emotionloop.com/donate?app=visualCaptcha<?php echo visualcaptcha_donate() ?>&tkc=wpvcbt" target="_new">Please DONATE</a><?php submit_button( __('Add visualCaptcha Option', 'visualcaptcha' ), 'submit', 'submit' )  ?>
                        *<small> - <?php _e('Required fields', 'visualcaptcha' ) ?></small>
                    </td>
                </tr>
            </table>
        </form>
	</div>
    <div>
    	<p><strong><?php _e('TRANSLATIONS:', 'visualcaptcha' ) ?></strong> <?php _e('this module can be translated! For more information please follow this link:', 'visualcaptcha' )?> <a href="http://codex.wordpress.org/Translating_WordPress#Localization_Technology"><?php _e('Translating WordPress', 'visualcaptcha' ) ?></a>.</p>
    	<p>If you need help, you can reach us on <a href="https://twitter.com/emotionLoop" target="_new">Twitter</a> or by email at <a href="mailto:hello@emotionloop.com">hello@emotionloop.com</a>.</p>
    </div>
</div>
    <?php
}
/////////////////////////
//visualCaptcha register settings
function visualcaptcha_admin_register_settings () {
	//register hook options
	$all_hooks = get_option( 'visualcaptcha_form_hooks' );
	

	//install
	//default hooks
	if ( is_bool( $all_hooks ) && empty( $all_hooks ) ) {
		$default_hooks = array(
			'login_form' => array( 'name' => __( 'Login Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'vertical_opt' => true ),
			'authenticate' => array( 'name' => __( 'Authenticate Filter' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'vertical_opt' => false ),
//			'login_errors' => array( 'name' => __( 'Login Error Form' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'vertical_opt' => true ),
			'login_redirect' => array( 'name' => __( 'Login Redirect Form' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'vertical_opt' => false ),
			
			'register_form' => array( 'name' => __( 'Register Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'vertical_opt' => false ),
			'register_post' => array( 'name' => __( 'Register Post' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'vertical_opt' => false ),
			'signup_extra_fields' => array( 'name' => __( 'Signup Extra Fields' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'vertical_opt' => false ),
			
			'lostpassword_form' => array( 'name' => __( 'Lost password Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'vertical_opt' => false ),
			'allow_password_reset' => array( 'name' => __( 'Lost password Post' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'vertical_opt' => false ),
			
			'comment_form_after_fields' => array( 'name' => __( 'Comment Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'vertical_opt' => false ),
			'comment_form_logged_in_after' => array( 'name' => __( 'Comment Form ( logged in user )' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'vertical_opt' => false ),
			'preprocess_comment' => array( 'name' => __( 'Pre-Process Comment' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'vertical_opt' => false ),			
		);
		
		add_option( 'visualcaptcha_form_hooks' , $default_hooks , '', 'yes' );
		
	}
	
}
/////////////////////////
//visualCaptcha deactivate function
function visualcaptcha_admin_remove_settings() {
	delete_option( 'visualcaptcha_form_hooks');
}

/////////////////////////
//Emotionloop page
if( !function_exists( 'emotionloop_admin_menu_render' ) ) { 
	function emotionloop_admin_menu_render() {

		$active_plugins = get_option('active_plugins');
		$all_plugins = get_plugins();
		
		//get all emotionloop plugins 
		$emotionloop_plugins = array();
		
		foreach ( $all_plugins as $plg_path => $data ) {
			if ( !is_array( $data ) || ( is_array( $data ) && !isset( $data[ 'Author' ] ) ) ) { 
				continue;
			}
			//normalize author name
			$norm_author = strtolower( $data[ 'Author' ] );
			//remove emptyspaces
			$norm_author = str_replace( ' ', '' , $norm_author );
			
			if ( $norm_author == 'emotionloop' ) {
				$emotionloop_plugins[ $plg_path ] = $data;
				//check if the plugin is active or not
				$plg_active = ( in_array( $plg_path , $active_plugins ) )? true : false;
				$emotionloop_plugins[ $plg_path ]['active'] = $plg_active;
				 
			}
		}
?>
<div class="wrap">
	<div><img src="<?php echo WP_CONTENT_URL."/plugins/visualcaptcha/images/logo-header.png" ?>" alt="emotionLoop" /></div>
    <div>
        <h3><?php _e( 'emotionLoop plugins', 'emotionloop' ); ?></h3>
        <hr />
        <?php 
			if ( !empty ( $emotionloop_plugins ) ) {
				//loop plugins
				foreach ( $emotionloop_plugins as $plugin_path => $plugin_info ) {
					
					list($folder,$file) = explode( '/', $plugin_path );
					//if active
					if ( isset ( $plugin_info['active'] ) && !empty ( $plugin_info['active'] ) ) {
					?>
        <b><?php echo $plugin_info['Name']; ?></b> <p><?php echo $plugin_info['Description'] ?> <br /><a href="admin.php?page=<?php echo $file; ?>"><?php _e( "Settings", 'emotionloop'); ?></a> | <a href="<?php echo $plugin_info['PluginURI']; ?>" target="_blank"><?php _e( "Read more", 'emotionloop'); ?></a> | <a href="http://emotionloop.com/donate?app=<?php echo $plugin_info['Name'] ?><?php echo visualcaptcha_donate() ?>&tkc=wpemlpg" target="_new">Please DONATE</a></p>
                    <?php
					} else {
					// if not active
						?>
        <b><?php echo $plugin_info['Name']; ?></b> <p><?php echo $plugin_info['Description'] ?> <br /><a href="plugins.php?s=<?php echo $plugin_info['Name']; ?>"><?php _e( "Activate", 'emotionloop'); ?></a> | <a href="http://emotionloop.com/donate?app=<?php echo $plugin_info['Name'] ?><?php echo visualcaptcha_donate() ?>&tkc=wpemlpg" target="_new">Please DONATE</a></p>

						<?php
					}
				}
			}
		?>
        <p></p>
        <p>
        	<small><?php _e( "for more information about emotionLoop, please visit our site:", 'emotionloop'); ?> <strong><a href="http://emotionloop.com/?wp_plugins">emotionLoop</a></strong></small>
        </p>
    </div>
</div>
<?php		
	}
	
}
/////////////////////////
//Emotionloop page
function add_visualcaptcha_admin_plugins_list_config_option( $data_in = false ) {
	$is_visualcaptcha_active = is_plugin_active('visualcaptcha/visualcaptcha.php');
	$is_admin = is_admin();
?>
<script>
if ( typeof jQuery != 'undefined' ) {
	(function($){
		$(document).ready(function() {
			$('#visualcaptcha').find('div.row-actions-visible').append('<span> |  <a href="admin.php?page=visualcaptcha.php"><?php _e( 'Configuration', 'visualcaptcha' ) ?></a></span>')
			$('#visualcaptcha').find('div.row-actions-visible').append('<br/><span><b><a href="http://emotionloop.com/donate?app=visualCaptcha<?php echo visualcaptcha_donate() ?>&tkc=wpplg" target="_new"><?php _e( 'Please Donate', 'visualcaptcha' ) ?></a></b></span>')
		})
	})(jQuery)
}
</script>
<?php
	return $data_in;
}

function visualcaptcha_donate() {
	$donate = array(
				'&amount=10',
				'&amount=5',
				''	
	);
	$key = array_rand ( $donate, 1 );
	return $donate[ $key ];
}

//call register settings function
register_activation_hook(__FILE__,'visualcaptcha_admin_register_settings');
//call deactivate
register_deactivation_hook( __FILE__, 'visualcaptcha_admin_remove_settings');

//add configure option to plugins page
add_action( 'pre_current_active_plugins', 'add_visualcaptcha_admin_plugins_list_config_option' );

//adding visualCaptcha to admin
add_action( 'admin_menu', 'add_visualcaptcha_admin_menu' );

//adding jQuery and jQuery IU to template
if ( ! is_admin() ) { 
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js', array('jquery'), '1.9.1' );
	wp_enqueue_style( 'visualcaptcha', plugins_url('inc/visualcaptcha.css', __FILE__) );
	wp_enqueue_script( 'visualcaptcha', plugins_url('inc/visualcaptcha.js', __FILE__), array('jquery', 'jquery-ui'), '4.1.0', true );
}
?>