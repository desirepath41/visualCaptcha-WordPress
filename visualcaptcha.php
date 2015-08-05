<?php

/*
  Plugin Name: visualCaptcha
  Version: 5.0.6
  Plugin URI: http://visualcaptcha.net/
  Description: The best captcha alternative. Accessible, Mobile-friendly, and Retina-ready!
  Author: emotionLoop
  Author URI: http://emotionloop.com/
*/

define( "VISUALCAPTCHA_MIN_PHP_VER", '5.3.0' );

function visualcaptcha_activation() {
    if ( version_compare( phpversion(), VISUALCAPTCHA_MIN_PHP_VER, '<' ) ) {
        die( sprintf( "The minimum PHP version required for visualCaptcha is %s", VISUALCAPTCHA_MIN_PHP_VER ) );
    }
}

register_activation_hook( __FILE__, 'visualCaptcha_activation' );

function visualcaptcha_do_action( $arg1, $arg2 = null, $arg3 = null ) {
    $currentFilter = current_filter();
    $hooks = visualcaptcha_hooks();

    if ( isset( $hooks[ $currentFilter ] ) ) {
        $hook = $hooks[ $currentFilter ];

        $captcha = array(
            'namespace' => $currentFilter,
            'numberOfImages' => isset( $hook['images'] ) ? $hook[ 'images' ] : 4
        );

        // Print captcha HTML
        echo '<div data-captcha=\'', json_encode( $captcha ), '\'></div>';
    }

    return $arg1;
}

function visualcaptcha_do_filter( $arg1, $arg2 = null, $arg3 = null ) {
    if ( ! empty( $_POST['namespace'] ) ) {
        $session = new \visualCaptcha\Session( 'visualcaptcha_' . $_POST[ 'namespace' ] );
    } else {
        $session = new \visualCaptcha\Session();
    }

    $captcha = new \visualCaptcha\Captcha( $session );
    $frontendData = $captcha->getFrontendData();

    // If captcha is present, try to validate it
    if ( $frontendData ) {
        $captchaValid = false;

        // If an image field name was submitted, try to validate it
        if ( ( $imageAnswer = $_POST[ $frontendData[ 'imageFieldName' ] ] ) && !empty( $imageAnswer ) ) {
            if ( $captcha->validateImage( $imageAnswer ) ) {
                $captchaValid = true;
            }
        } else if ( ( $audioAnswer = $_POST[ $frontendData[ 'audioFieldName' ] ] ) && !empty( $audioAnswer ) ) {
            if ( $captcha->validateAudio( $audioAnswer ) ) {
                $captchaValid = true;
            }
        }

        // Clear current session after captcha has been validated
        $session->clear();

        // Handle invalid captcha
        if ( ! $captchaValid ) {
            // Special condition for comments
            if ( strpos( current_filter(), 'comment' ) !== false ) {
                if ( !empty( $arg1 ) ) {
                    if ( is_array( $arg1 ) && isset( $arg1[ 'comment_post_ID' ] ) && !empty( $arg1[ 'comment_post_ID' ] ) ) {
                        wp_die( __( 'ERROR: You failed the human verification test. Please go back and try again.', 'visualcaptcha' ) );
                    }
                }
            }

            // Check arguments for existing errors
            if ( is_wp_error( $arg1 ) ) {
                $error_out = $arg1;
            }
            else if ( is_wp_error( $arg2 ) ) {
                $error_out = $arg2;
            }
            else if ( is_wp_error( $arg3 ) ) {
                $error_out = $arg3;
            }

            // If an error was found
            if ( isset( $error_out ) ) {
                $error_out->add(
                    'visualcaptcha_error',
                    '<strong>'. __( 'ERROR:' , 'visualcaptcha' ) .'</strong> ' . __( 'Captcha was invalid', 'visualcaptcha' )
                );

                return $error_out;
            }

            $isUser = is_a( $arg1, 'WP_User' );

            if ( ! empty( $isUser ) || is_bool( $arg1 ) || empty( $arg1 ) ) {
                return new WP_Error(
                    'visualcaptcha_error',
                    '<strong>'. __( 'ERROR:' , 'visualcaptcha' ) .'</strong> ' . __( 'Captcha was invalid', 'visualcaptcha' )
                );
            }
        }
    }

    return $arg1;
}

function visualcaptcha_plugin_init() {

    $visualcaptcha_current_hooks = visualcaptcha_hooks();

    if ( is_array($visualcaptcha_current_hooks) && ! empty($visualcaptcha_current_hooks) ) {
        foreach ( $visualcaptcha_current_hooks as $visualcaptcha_hook => $visualcaptcha_hook_data) {
            // not enabled on the admin panel -> do nothing
            if ( empty( $visualcaptcha_hook_data[ 'checked' ] ) ) {
                continue;
            }


            // add action
            if ( ! empty( $visualcaptcha_hook_data[ 'action' ] ) ) {
                add_action( $visualcaptcha_hook, 'visualcaptcha_do_action' );

                // add filter to a custom option
                if ( isset( $visualcaptcha_hook_data[ 'filter' ] ) && ! empty( $visualcaptcha_hook_data[ 'filter' ] ) ) {
                    // ignore duplicate filters
                    if ( ! isset( $visualcaptcha_current_hooks [ $visualcaptcha_hook_data[ 'filter' ] ] ) ) {
                        add_filter( $visualcaptcha_hook_data[ 'filter' ], 'visualcaptcha_do_filter', 1000, 3 );
                    }
                }
            }

            // add filter
            if ( empty( $visualcaptcha_hook_data[ 'action' ] ) ) {
                add_filter( $visualcaptcha_hook, 'visualcaptcha_do_filter', 1000, 3 );
            }
        }
    }
}

function visualcaptcha_scripts() {
    wp_enqueue_style( 'visualcaptcha', plugins_url( 'public/visualcaptcha.css', __FILE__, '1.0.0' ) );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'visualcaptcha.jquery', plugins_url( 'public/visualcaptcha.jquery.js', __FILE__ ), array( 'jquery' ), '1.0.5', true );
    wp_enqueue_script( 'visualcaptcha.bootstrap', plugins_url( 'public/visualcaptcha.bootstrap.js', __FILE__ ), array( 'visualcaptcha.jquery' ), '1.0.1', true );

    wp_localize_script(
        'visualcaptcha.bootstrap',
        'captchaParams',
        array(
            'imgPath' => plugins_url( 'public/img/', __FILE__ ),
            'url' => plugins_url( 'app.php', __FILE__ ),
            'language' => array(
                'accessibilityAlt' => __( 'Sound icon', 'visualcaptcha' ),
                'accessibilityTitle' => __( 'Accessibility option: listen to a question and answer it!', 'visualcaptcha' ),
                'accessibilityDescription' => __( 'Type below the <strong>answer</strong> to what you hear. Numbers or words:', 'visualcaptcha' ),
                'explanation' => __( 'Click or touch the <strong>ANSWER</strong>', 'visualcaptcha' ),
                'refreshAlt' => __( 'Refresh/reload icon', 'visualcaptcha' ),
                'refreshTitle' => __( 'Refresh/reload: get new images and accessibility option!', 'visualcaptcha' )
            )
        )
    );
}

function visualcaptcha_hooks() {
    $hooks = get_option( 'visualcaptcha_form_hooks' );

    // install
    // default hooks
    if ( is_bool( $hooks ) && empty( $hooks ) ) {
        $default_hooks = array(
            'login_form' => array( 'name' => __( 'Login Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'images' => 6 ),
            'authenticate' => array( 'name' => __( 'Authenticate Filter' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'images' => 6 ),
            'login_redirect' => array( 'name' => __( 'Login Redirect Form' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'images' => 6 ),

            'register_form' => array( 'name' => __( 'Register Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'images' => 6 ),
            'register_post' => array( 'name' => __( 'Register Post' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'images' => 6 ),
            'signup_extra_fields' => array( 'name' => __( 'Signup Extra Fields' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'images' => 6 ),

            'lostpassword_form' => array( 'name' => __( 'Lost password Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'images' => 6 ),
            'allow_password_reset' => array( 'name' => __( 'Lost password Post' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'images' => 6 ),

            'comment_form_after_fields' => array( 'name' => __( 'Comment Form' , 'visualcaptcha' ), 'checked' => true, 'action' => true, 'images' => 6 ),
            'comment_form_logged_in_after' => array( 'name' => __( 'Comment Form ( logged in user )' , 'visualcaptcha' ), 'checked' => true, 'action' => true ),
            'preprocess_comment' => array( 'name' => __( 'Pre-Process Comment' , 'visualcaptcha' ), 'checked' => true, 'action' => false, 'images' => 6 )
        );

        add_option( 'visualcaptcha_form_hooks' , $default_hooks , '', 'yes' );
    }

    return $hooks;
}

function visualcaptcha_menu() {
    add_options_page( 'visualCaptcha', 'visualCaptcha', 'manage_options', 'visualcaptcha', 'visualcaptcha_options' );
}

function visualcaptcha_options() {
  	if ( ! current_user_can( 'manage_options' ) )  {
  		  wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  	}

    $reset = ( isset( $_POST[ 'reset' ] ) && $_POST[ 'reset' ] === 'Y' );

    if ( $reset ) {
        delete_option( 'visualcaptcha_form_hooks' );
        visualcaptcha_hooks();
    }

    $visualcaptcha_current_hooks = visualcaptcha_hooks();

    if ( $reset ) {
        echo '<div class="error"><p><strong>', __( 'Reset settings.', 'visualcaptcha' ), '</strong></p></div>';
    } else {
        // Prevent errors
        $new_hook = array(
          'name' => '',
          'action' => '',
          'filter' => ''
        );

        // Update actions on post
        if ( isset( $_POST[ 'visualcaptcha_form_hooks' ] ) && is_array( $_POST[ 'visualcaptcha_form_hooks' ] ) ) {
            $postHooks = $_POST[ 'visualcaptcha_form_hooks' ];

            foreach ( $postHooks as $name => $data ) {
                if (isset( $visualcaptcha_current_hooks[ $name ] ) && is_array( $visualcaptcha_current_hooks[ $name ] )) {
                    $visualcaptcha_current_hooks[ $name ][ 'checked' ] = ( intval( $data[ 'checked' ] ) >= 1 );
                    $visualcaptcha_current_hooks[ $name ][ 'images' ] = intval( $data[ 'images' ] );
                }
            }
            update_option( 'visualcaptcha_form_hooks', $visualcaptcha_current_hooks );
            $updated = true;
        }

        if ( isset( $updated ) ) {
            echo '<div class="updated"><p><strong>' , __( 'Settings saved.', 'visualcaptcha' ) , '</strong></p></div>';
        }

        // add new options
        if ( isset( $_POST['visualcaptcha_nonce_opt']) && check_admin_referer( 'visualcaptcha_addpot', 'visualcaptcha_nonce_opt' ) )  {
            $hook_name = $_POST['visualcaptcha_add_opt_name'];
            $hook_action = $_POST['visualcaptcha_add_opt_action_hook'];
            $hook_filter = $_POST['visualcaptcha_add_opt_filter_hook'];
            $terms = $_POST['visualcaptcha_terms'];

            // check for empty post data
            if ( empty( $hook_name ) ||  empty( $hook_action ) ||  empty( $hook_filter ) ) {
                if ( empty( $hook_name ) ) { $new_hook_error['visualcaptcha_add_opt_name'] = 'error'; }
                if ( empty( $hook_action ) ) { $new_hook_error['visualcaptcha_add_opt_action'] = 'error'; }
                if ( empty( $hook_filter ) ) { $new_hook_error['visualcaptcha_add_opt_filter'] = 'error'; }
            }

            // terms error
            if ( empty( $terms ) ) {
                $new_hook_error['visualcaptcha_terms'] = 'error';
            }

            // action error
            if ( isset( $visualcaptcha_current_hooks[ $hook_action ] ) && empty( $new_hook_error )) {
                $new_hook_error['action'] = 'error';
            }
            // filter alert
            if ( isset( $visualcaptcha_current_hooks[ $hook_filter ] ) && empty( $visualcaptcha_current_hooks[ $hook_filter ][ 'action' ] ) && empty( $new_hook_error )) {
                $new_hook_error['filter'] = 'alert';
            }

            // if no error or just filter alert
            if ( empty( $new_hook_error ) ||
                ( count( $new_hook_error ) == 1 && isset( $new_hook_error['filter'] )  )
            ) {
                $visualcaptcha_current_hooks[ $hook_action ] = array(
                    'name' => $hook_name,
                    'filter' => $hook_filter,
                    'checked' => true,
                    'action' => true,
                    'images' => 6 );

                update_option( 'visualcaptcha_form_hooks' , $visualcaptcha_current_hooks );
                $updated_vars = true;
            } else {
                $new_hook['name'] = $hook_name;
                $new_hook['action'] = $hook_action;
                $new_hook['filter'] = $hook_filter;
            }

            if ( isset( $updated_vars ) ) {
                echo '<div class="updated"><p><strong>', __( 'Custom action added.', 'visualcaptcha' ), '</strong></p></div>';
            }

        }
    }

    ?>
    <style>
        .visualcaptcha_promo_img { float:left; margin-right: 10px; margin-bottom: 5px; }
        .visualcaptcha_promo_img img { width: 200px; }
        .clear { clear:both; }
        .visualcaptcha_settings_title { margin-top:20px; }
        .visualcaptcha_form_container { background-color:#E3E9E3; margin-bottom:20px; padding-top:10px ; padding-left:10px; }
        .visualcatpcha_form_text_input { min-width:200px; }
        .visualcaptcha_error { color:#F00; }
    </style>
    <div class="wrap">
        <div>
            <h1><?php _e('visualCaptcha by emotionLoop', 'visualcaptcha' ); ?></h1>
        </div>
        <div>
            <div class="visualcaptcha_promo_img"><a href="http://visualcaptcha.net" target="_blank"><img src="<?php echo WP_CONTENT_URL , '/plugins/visualcaptcha/application.png'; ?>" alt="visualCaptcha on multiple devices" /></a></div>
            <p><strong>visualCaptcha</strong> is a configurable captcha solution, focusing on <strong>accessibility &amp; simplicity</strong> whilst maintaining <strong>security</strong>.</p>
            <p>More information over at <a href="http://visualcaptcha.net" target="_blank">visualcaptcha.net</a>.</p>
            <div class="clear"></div>
        </div>
        <div class="visualcaptcha_settings_title">
            <h2><?php _e('visualCaptcha Settings', 'visualcaptcha' ); ?></h2>
        </div>
        <form name="form" method="post" action="">
            <h3><small><?php _e('Enable visualCaptcha in the following places:', 'visualcaptcha' ); ?></small></h3>
            <table class="form-table">
            <?php foreach ( $visualcaptcha_current_hooks as $name => $hook ) {
                if ( empty( $hook[ 'action' ] ) && !isset( $hook[ 'user_opt' ] ) ) {
                    continue;
                }
                $opt_name = $hook[ 'name' ];
                if ( isset( $hook[ 'filter' ] ) ) {
                    $opt_name = $hook[ 'name' ].' (<strong>'.__('show', 'visualcaptcha').':</strong> '.$name.' <strong>'.__('validate', 'visualcaptcha').':</strong> '.$hook[ 'filter' ].')' ;
                }
            ?>
                <tr valign="top">
                    <th scope="row" style="width: 150px">
                        <?php echo $opt_name ?>
                    </th>
                    <td>
                        <input name="visualcaptcha_form_hooks[<?php echo $name ?>][checked]" value="0" type="hidden" />
                        <label style="margin-right: 5px">
                            <input name="visualcaptcha_form_hooks[<?php echo $name ?>][checked]" value="1" type="checkbox" <?php if ( $hook[ 'checked' ] ) echo 'checked="checked"' ?> />
                            Enabled
                        </label>
                        with
                        <select name="visualcaptcha_form_hooks[<?php echo $name ?>][images]">
                            <option value="4" <?php echo ( isset( $hook[ 'images' ] ) &&  !empty( $hook[ 'images' ] )  && $hook[ 'images' ] == 4 )? 'selected="selected"' : '' ?> >4</option>
                            <option value="5" <?php echo ( isset( $hook[ 'images' ] ) &&  !empty( $hook[ 'images' ] ) && $hook[ 'images' ] == 5 )? 'selected="selected"' : '' ?> >5</option>
                            <option value="6" <?php echo ( isset( $hook[ 'images' ] ) &&  !empty( $hook[ 'images' ] ) && $hook[ 'images' ] == 6 )? 'selected="selected"' : '' ?> >6</option>
                        </select>
                        images
                    </td>
                </tr>
            <?php
                }
            ?>
            </table>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>" />
                <button class="button-secondary" name="reset" value="Y">Reset All</button>
            </p>
        </form>

        <h3 class="title">Add custom options</h3>
        <form method="post" action="admin.php?page=<?php echo $_GET['page'] ?>">
            <table class="form-table">
                <tr valign="top">
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
                        <?php submit_button( __('Add visualCaptcha Option', 'visualcaptcha' ), 'submit', 'submit' )  ?>
                        *<small> - <?php _e('Required fields', 'visualcaptcha' ) ?></small>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <?php
}

if ( version_compare( phpversion(), VISUALCAPTCHA_MIN_PHP_VER, '>=' ) ) {
    @include( __DIR__ . '/vendor/autoload.php' );

    // Initialize Session
    session_cache_limiter( false );

    if ( session_id() == '' ) {
        session_start();
    }

    add_action( 'init', 'visualcaptcha_plugin_init' );
    add_action( 'wp_enqueue_scripts', 'visualcaptcha_scripts' );
    add_action( 'login_enqueue_scripts', 'visualcaptcha_scripts' );
    add_action( 'admin_menu', 'visualcaptcha_menu' );
}

?>