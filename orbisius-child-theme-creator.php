<?php
/*
  Plugin Name: Orbisius Child Theme Creator
  Plugin URI: http://club.orbisius.com/products/wordpress-plugins/orbisius-child-theme-creator/
  Description: This plugin allows you to quickly create child themes from any theme that you have currently installed on your site/blog.
  Version: 1.0.5
  Author: Svetoslav Marinov (Slavi)
  Author URI: http://orbisius.com
 */

/*  Copyright 2012-2050 Svetoslav Marinov (Slavi) <slavi@orbisius.com>

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Set up plugin
add_action('admin_init', 'orbisius_child_theme_creator_admin_init');
add_action('admin_menu', 'orbisius_child_theme_creator_setup_admin');
add_action('network_admin_menu', 'orbisius_child_theme_creator_setup_admin');
add_action('wp_footer', 'orbisius_child_theme_creator_add_plugin_credits', 1000); // be the last in the footer
add_action('admin_notices', 'orbisius_child_theme_creator_admin_notice_message');
add_action('network_admin_notices', 'orbisius_child_theme_creator_admin_notice_message');

add_action( 'wp_ajax_orbisius_ctc_theme_editor_ajax', 'orbisius_ctc_theme_editor_ajax');
add_action( 'wp_ajax_nopriv_orbisius_ctc_theme_editor_ajax', 'orbisius_ctc_theme_editor_ajax');

/**
 * Show a notice in the plugins area to let the user know how to work with the plugin.
 * On multisite the message is shown only on the network site.
 */
function orbisius_child_theme_creator_admin_notice_message() {
    global $pagenow;
    
    $plugin_data = orbisius_child_theme_creator_get_plugin_data();
    $name = $plugin_data['Name'];
    $show_notice = 1; // todo check cfg for dismiss

    if ($show_notice
            && ( stripos($pagenow, 'plugins.php') !== false )
            && ( !is_multisite() || ( is_multisite() && is_network_admin() ) ) ) {
        $just_link = orbisius_child_theme_creator_util::get_create_child_pages_link();
        echo "<div class='updated'><p>$name has been installed. To create a child theme go to
          <a href='$just_link'><strong>Appearance &rarr; $name</strong></a></p></div>";
    }
}

/**
 * @package Orbisius Child Theme Creator
 * @since 1.0
 *
 * Searches through posts to see if any matches the REQUEST_URI.
 * Also searches tags
 */
function orbisius_child_theme_creator_admin_init() {
    $suffix = '';
    $dev = empty($_SERVER['DEV_ENV']) ? 0 : 1;
    $suffix = $dev ? '' : '.min';

    wp_register_style('orbisius_child_theme_creator', plugins_url("/assets/main{$suffix}.css", __FILE__), false);
    wp_enqueue_style('orbisius_child_theme_creator');

    wp_enqueue_script( 'jquery' );
    wp_register_script( 'orbisius_child_theme_creator', plugins_url("/assets/main{$suffix}.js", __FILE__), array('jquery', ), '1.0', true);
    wp_enqueue_script( 'orbisius_child_theme_creator' );
}

/**
 * Set up administration
 *
 * @package Orbisius Child Theme Creator
 * @since 0.1
 */
function orbisius_child_theme_creator_setup_admin() {
    add_options_page('Orbisius Child Theme Creator', 'Orbisius Child Theme Creator', 'manage_options', 'orbisius_child_theme_creator_settings_page', 'orbisius_child_theme_creator_settings_page');
    add_theme_page('Orbisius Child Theme Creator', 'Orbisius Child Theme Creator', 'manage_options', 'orbisius_child_theme_creator_themes_action', 'orbisius_child_theme_creator_tools_action');
    add_submenu_page('tools.php', 'Orbisius Child Theme Creator', 'Orbisius Child Theme Creator', 'manage_options', 'orbisius_child_theme_creator_tools_action', 'orbisius_child_theme_creator_tools_action');

    // when plugins are show add a settings link near my plugin for a quick access to the settings page.
    add_filter('plugin_action_links', 'orbisius_child_theme_creator_add_plugin_settings_link', 10, 2);

    // Theme Editor
    add_theme_page( 'Orbisius Theme Editor', 'Orbisius Theme Editor', 'manage_options', 'orbisius_ctc_theme_editor_action', 'orbisius_ctc_theme_editor' );
    add_filter('theme_action_links', 'orbisius_child_theme_creator_add_edit_theme_link', 50, 2);
}

// Add the ? settings link in Plugins page very good
function orbisius_child_theme_creator_add_plugin_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $link = orbisius_child_theme_creator_util::get_create_child_pages_link();

        $link_html = "<a href='$link'>Create a Child Theme</a>";
        array_unshift($links, $link_html);
    }

    return $links;
}

/**
 * This adds an edit button in Apperance under each theme.
 * @param array $actions
 * @param WP_Theme/string Obj $theme
 * @return array
 */
function orbisius_child_theme_creator_add_edit_theme_link($actions, $theme) {
    $link = orbisius_child_theme_creator_util::get_theme_editor_link( array( 'theme_1' => is_scalar($theme) ? $theme : $theme->get_template()) );
    $link_html = "<a href='$link' title='Opens this theme in Orbisius Theme Editor which features double textx editor.'>Orbisius: Edit</a>";

    $actions['orb_ctc_editor'] = $link_html;

    return $actions;
}

// Generates Options for the plugin
function orbisius_child_theme_creator_settings_page() {
    ?>

    <div class="wrap orbisius_child_theme_creator_container">
        <h2>Orbisius Child Theme Creator</h2>

        <div class="updated"><p>
                Some untested themes and plugin may break your site. We have launched a <strong>free</strong> service
                (<a href="http://qsandbox.com/?utm_source=orbisius-child-theme-creator&utm_medium=settings_screen&utm_campaign=product"
                    target="_blank" title="[new window]">http://qsandbox.com</a>)
                that allows you to setup a test/sandbox
                WordPress site in seconds. No technical knowledge is required.
                <br/>Join today and test themes and plugins before you actually put them on your live site. For more info go to:
                <a href="http://qsandbox.com/?utm_source=orbisius-child-theme-creator&utm_medium=settings_screen&utm_campaign=product"
                   target="_blank" title="[new window]">http://qsandbox.com</a>
            </p></div>

        <div class="updated0"><p>
                This plugin doesn't currently have any configuration options. To use it go to <strong>Appearance &rarr; Orbisius Child Theme Creator</strong>
            </p></div>

        <h2>Video Demo</h2>

        <p class="orbisius_child_theme_creator_demo_video hide00">
            <iframe width="560" height="315" src="http://www.youtube.com/embed/BZUVq6ZTv-o" frameborder="0" allowfullscreen></iframe>

            <br/>Video Link: <a href="www.youtube.com/watch?v=BZUVq6ZTv-o"
                                target="_blank">www.youtube.com/watch?v=BZUVq6ZTv-o</a>
        </p>

    <?php
    $plugin_data = orbisius_child_theme_creator_get_plugin_data();

    $app_link = urlencode($plugin_data['PluginURI']);
    $app_title = urlencode($plugin_data['Name']);
    $app_descr = urlencode($plugin_data['Description']);
    ?>
        <h2>Share</h2>
        <p>
            <!-- AddThis Button BEGIN -->
        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
            <a class="addthis_button_facebook" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_twitter" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_email" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_myspace" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_google" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_digg" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_delicious" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_favorites" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_compact"></a>
        </div>
        <!-- The JS code is in the footer -->

        <script type="text/javascript">
            var addthis_config = {"data_track_clickback": true};
            var addthis_share = {
                templates: {twitter: 'Check out {{title}} #WordPress #plugin at {{lurl}} (via @orbisius)'}
            }
        </script>
        <!-- AddThis Button START part2 -->
        <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=lordspace"></script>
        <!-- AddThis Button END part2 -->
    </p>

    <h2>Troubleshooting</h2>
    If your site becomes broken due to a child theme (mis)configuration. Please check another plugin of ours:
    <a href="http://club.orbisius.com/products/wordpress-plugins/orbisius-theme-fixer/?utm_source=orbisius-child-theme-creator&utm_medium=settings_troubleshooting&utm_campaign=product" target="_blank" title="[new window]">Orbisius Theme Fixer</a>

    <h2>Support & Feature Requests</h2>
    <div class="updated"><p>
            ** NOTE: ** Support is handled on our site: <a href="http://club.orbisius.com/support/" target="_blank" title="[new window]">http://club.orbisius.com/support/</a>.
            Please do NOT use the WordPress forums or other places to seek support.
        </p></div>

    <h2>Mailing List</h2>
    <p>
        Get the latest news and updates about this and future cool
        <a href="http://profiles.wordpress.org/lordspace/"
           target="_blank" title="Opens a page with the pugins we developed. [New Window/Tab]">plugins we develop</a>.
    </p>
    <p>
        <!-- // MAILCHIMP SUBSCRIBE CODE \\ -->
        1) <a href="http://eepurl.com/guNzr" target="_blank">Subscribe to our newsletter</a>
        <!-- \\ MAILCHIMP SUBSCRIBE CODE // -->
    </p>
    <p>OR</p>
    <p>
        2) Subscribe using our QR code. [Scan it with your mobile device].<br/>
        <img src="<?php echo plugin_dir_url(__FILE__); ?>/i/guNzr.qr.2.png" alt="" />
    </p>

    <?php orbisius_child_theme_creator_generate_ext_content(); ?>
    </div>
    <?php
}

/**
 * Returns some plugin data such name and URL. This info is inserted as HTML
 * comment surrounding the embed code.
 * @return array
 */
function orbisius_child_theme_creator_get_plugin_data() {
    // pull only these vars
    $default_headers = array(
        'Name' => 'Plugin Name',
        'PluginURI' => 'Plugin URI',
        'Description' => 'Description',
    );

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];

    $data['name'] = $name;
    $data['url'] = $url;

    $data = array_merge($data, $plugin_data);

    return $data;
}

/**
 * Outputs or returns the HTML content for IFRAME promo content.
 */
function orbisius_child_theme_creator_generate_ext_content($echo = 1) {
    $plugin_slug = basename(__FILE__);
    $plugin_slug = str_replace('.php', '', $plugin_slug);
    $plugin_slug = strtolower($plugin_slug); // jic

    $domain = !empty($_SERVER['DEV_ENV']) ? 'http://orbclub.com.clients.com' : 'http://club.orbisius.com';

    $url = $domain . '/wpu/content/wp/' . $plugin_slug . '/';

    $buff = <<<BUFF_EOF
    <iframe style="width:100%;min-height:300px;height: auto;" width="100%" height="480"
            src="$url" frameborder="0" allowfullscreen></iframe>

BUFF_EOF;

    if ($echo) {
        echo $buff;
    } else {
        return $buff;
    }
}

/**
 * Upload page.
 * Ask the user to upload a file
 * Preview
 * Process
 *
 * @package Permalinks to Category/Permalinks
 * @since 1.0
 */
function orbisius_child_theme_creator_tools_action() {
    // ACL checks *borrowed* from wp-admin/theme-install.php
    if ( ! current_user_can('install_themes') ) {
    	wp_die( __( 'You do not have sufficient permissions to install themes on this site.' ) );
    }

    $multi_site = is_multisite();

    if ( $multi_site && ! is_network_admin() ) {
        $next_url = orbisius_child_theme_creator_util::get_create_child_pages_link();

        if (headers_sent()) {
            $success = "In order to create a child theme in a multisite WordPress environment you must do it from Network Admin &gt; Apperance"
                    . "<br/><a href='$next_url' class='button button-primary'>Continue</a>";
            wp_die($success);
        } else {
            wp_redirect($next_url);
        }

        exit();
    }
    
    $msg = '';
    $errors = $success = array();
    $parent_theme_base_dirname = empty($_REQUEST['parent_theme_base_dirname']) ? '' : wp_kses($_REQUEST['parent_theme_base_dirname'], array());
    $orbisius_child_theme_creator_nonce = empty($_REQUEST['orbisius_child_theme_creator_nonce']) ? '' : $_REQUEST['orbisius_child_theme_creator_nonce'];

    $parent_theme_base_dirname = trim($parent_theme_base_dirname);
    $parent_theme_base_dirname = preg_replace('#[^\w-]#si', '-', $parent_theme_base_dirname);
    $parent_theme_base_dirname = preg_replace('#[_-]+#si', '-', $parent_theme_base_dirname);
    $parent_theme_base_dirname = trim($parent_theme_base_dirname, '-');

    if (!empty($_POST) || !empty($parent_theme_base_dirname)) {
        if (!wp_verify_nonce($orbisius_child_theme_creator_nonce, basename(__FILE__) . '-action')) {
            $errors[] = "Invalid action";
        } elseif (empty($parent_theme_base_dirname) || !preg_match('#^[\w-]+$#si', $parent_theme_base_dirname)) {
            $errors[] = "Parent theme's directory is invalid. May contain only [a-z0-9-]";
        } elseif (strlen($parent_theme_base_dirname) > 70) {
            $errors[] = "Parent theme's directory should be fewer than 70 characters long.";
        }

        if (empty($errors)) {
            try {
                $installer = new orbisius_child_theme_creator($parent_theme_base_dirname);

                // Does the user want to copy the functions.php?
                if (!empty($_REQUEST['copy_functions_php'])) {
                    $installer->add_files('functions.php');
                }

                $installer->check_permissions();
                $installer->copy_main_files();
                $installer->generate_style();

                $success[] = "The child theme has been successfully created.";
                $success[] = $installer->get_details();
                
                if (!$multi_site && !empty($_REQUEST['switch'])) {
                    $child_theme_base_dir = $installer->get_child_base_dir();
                    $theme = wp_get_theme($child_theme_base_dir);

                    if (!$theme->exists() || !$theme->is_allowed()) {
                        throw new Exception('Cannot switch to the new theme for some reason.');
                    }

                    switch_theme($theme->get_stylesheet());
                    $next_url = admin_url('themes.php?activated=true');
                    
                    if (headers_sent()) {
                        $success = "Child theme created and switched. <a href='$next_url'>Continue</a>";
                    } else {
                        wp_safe_redirect($next_url);
                        exit;
                    }
                } elseif ($multi_site && !empty($_REQUEST['orbisius_child_theme_creator_make_network_wide_available'])) {
                    // Make child theme an allowed theme (network enable theme)
                    $allowed_themes = get_site_option( 'allowedthemes' );
                    $new_theme_name = $installer->get_child_base_dir();
                    $allowed_themes[ $new_theme_name ] = true;
                    update_site_option( 'allowedthemes', $allowed_themes );
                }
            } catch (Exception $e) {
                $errors[] = "There was an error during the chat installation.";
                $errors[] = $e->getMessage();

                if (is_object($installer->result)) {
                    $errors[] = var_export($installer->result);
                }
            }
        }
    }

    if ( 0&&$multi_site ) {
        $msg .= orbisius_child_theme_creator_util::msg("You are running WordPress in MultiSite configuration. 
            Please report any glitches that you may find.", 2);
    }

    if (!empty($errors)) {
        $msg .= orbisius_child_theme_creator_util::msg($errors);
    }

    if (!empty($success)) {
        $msg .= orbisius_child_theme_creator_util::msg($success, 1);
    }
    ?>
    <div class="wrap orbisius_child_theme_creator_container">
        <h2>Orbisius Child Theme Creator

            <div class="" style="float: right;padding: 3px; _border: 1px solid #D54E21;">
                Links:
                <a href="http://qsandbox.com/?utm_source=orbisius-child-theme-creator&utm_medium=action_screen&utm_campaign=product"
                     target="_blank" title="Opens in new tab/window. qSandbox is a FREE service that allows you to setup a test/sandbox WordPress site in 2 seconds. No technical knowledge is required.
                     Test themes and plugins before you actually put them on your site">Free Test Site</a> <small>(2 sec setup)</small>

                | <a href="http://orbisius.com/page/free-quote/?utm_source=child-theme-creator&utm_medium=plugin-linksutm_campaign=plugin-update"
                     title="If you want a custom web/mobile app or a plugin developed contact us. This opens in a new window/tab">Hire Us</a>

                | <a href="#help" title="[new window]">Help</a>
            </div>
        </h2>

    <?php echo $msg; ?>
        <div class="updated">
            <p>
                Choose a parent theme from the list below and click on the <strong>Create Child Theme</strong> button.
            </p>
        </div>

    <?php
    $buff = '';
    $buff .= "<div id='availablethemes' class='theme_container'>\n";
    $nonce = wp_create_nonce(basename(__FILE__) . '-action');

    $args = array();
    $themes = wp_get_themes($args);

    // we use the same CSS as in WP's appearances but put only the buttons we want.
    foreach ($themes as $theme_basedir_name => $theme_obj) {
        // get the web uri for the current theme and go 1 level up
        $src = dirname(get_template_directory_uri()) . "/$theme_basedir_name/screenshot.png";
        $functions_file = dirname(get_template_directory()) . "/$theme_basedir_name/functions.php";
        $parent_theme_base_dirname_fmt = urlencode($theme_basedir_name);
        $create_url = $_SERVER['REQUEST_URI'];

        // cleanup old links or refreshes.
        $create_url = preg_replace('#&parent_theme_base_dirname=[\w-]+#si', '', $create_url);
        $create_url = preg_replace('#&orbisius_child_theme_creator_nonce=[\w-]+#si', '', $create_url);

        $create_url .= '&parent_theme_base_dirname=' . $parent_theme_base_dirname_fmt;
        $create_url .= '&orbisius_child_theme_creator_nonce=' . $nonce;

        /* $create_url2 = esc_url( add_query_arg(
          array( 'parent_theme_base_dirname' => $parent_theme_base_dirname_fmt,
          ), admin_url( 'themes.php' ) ) ); */

        $author_name = $theme_obj->get('Author');
        $author_name = strip_tags($author_name);
        $author_name = empty($author_name) ? 'n/a' : $author_name;

        $author_uri = $theme_obj->get('AuthorURI');
        $author_line = empty($author_uri)
                ? $author_name
                : "<a title='Visit author homepage' href='$author_uri' target='_blank'>$author_name</a>";
        
        $author_line .= " | Ver.$theme_obj->Version\n";

        $edit_theme_link = orbisius_child_theme_creator_util::get_theme_editor_link( array('theme_1' => $theme_basedir_name) );
        $author_line .= " | <a href='$edit_theme_link' title='Edit with Orbisius Theme Editor'>Edit</a>\n";

        $buff .= "<div class='available-theme'>\n";
        $buff .= "<form action='$create_url' method='post'>\n";
        $buff .= "<img class='screenshot' src='$src' alt='' />\n";
        $buff .= "<h3>$theme_obj->Name</h3>\n";
        $buff .= "<div class='theme-author'>By $author_line</div>\n";
        $buff .= "<div class='action-links'>\n";
        $buff .= "<ul>\n";

        $parent_theme = $theme_obj->get('Template');

        if (empty($parent_theme)) { // Normal themes / no child ones
            if (file_exists($functions_file)) {
                $adv_container_id = md5($src);
                
                $buff .= "
                    <li>
                        <a href='javascript:void(0)' onclick='jQuery(\"#orbisius_ctc_act_adv_$adv_container_id\").slideToggle(\"slow\");'>+ Advanced</a> (show/hide)
                        <div id='orbisius_ctc_act_adv_$adv_container_id' class='app-hide'>";

                $buff .= "<div>
                                <label>
                                    <input type='checkbox' id='orbisius_child_theme_creator_copy_functions_php' name='copy_functions_php' value='1' /> Copy functons.php
                                    (<span class='app-serious-notice'><strong>Danger</strong>: if the theme doesn't support
                                    <a href='http://wp.tutsplus.com/tutorials/creative-coding/understanding-wordpress-pluggable-functions-and-their-usage/'
                                        target='_blank'>pluggable functions</a> this <strong>will crash your site</strong>. Make a backup is highly recommended.</span>)
                                </label>
                            </div>";
                
                $buff .= "
                        </div> <!-- /orbisius_ctc_act_adv_$adv_container_id -->
                    </li>\n";
            }

            // Let's allow the user to make that theme network wide usable
            if ($multi_site) {
                $buff .= "<li>
                        <div>
                            <label>
                                <input type='checkbox' id='orbisius_child_theme_creator_make_network_wide_available'
                                name='orbisius_child_theme_creator_make_network_wide_available' value='1' /> Make the new theme network wide available
                            </label>
                        </div></li>\n";
            } else {
                $buff .= "<li><label><input type='checkbox' id='orbisius_child_theme_creator_switch' name='switch' value='1' /> "
                        . "Switch theme to the new theme after it is created</label></li>\n";
            }
            
            $buff .= "<li> <button type='submit' class='button button-primary'>Create Child Theme</button> </li>\n";
        } else {
            $buff .= "<li>[child theme]</li>\n";
        }
    
        $buff .= "</ul>\n";
        $buff .= "</div> <!-- /action-links -->\n";
        $buff .= "</form> <!-- /form -->\n";
        $buff .= "</div> <!-- /available-theme -->\n";
    }

    $buff .= "</div> <!-- /availablethemes -->\n";
    //var_dump($themes);
    echo $buff;
    ?>

        <a name="help"></a>
        <h2>Support &amp; Premium Plugins</h2>
        <div class="updated">
            <p>
                The support is handled on our Club Orbisius site: <a href="http://club.orbisius.com/" target="_blank" title="[new window]">http://club.orbisius.com/</a>.
                Please do NOT use the WordPress forums or other places to seek support.
            </p>
        </div>
        <?php if (1) : ?>
        <?php
        $plugin_data = orbisius_child_theme_creator_get_plugin_data();

        $app_link = urlencode($plugin_data['PluginURI']);
        $app_title = urlencode($plugin_data['Name']);
        $app_descr = urlencode($plugin_data['Description']);
        ?>

            <h2>Like this plugin? Share it with your friends</h2>
            <p>
                <!-- AddThis Button BEGIN -->
            <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                <a class="addthis_button_facebook" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_twitter" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_email" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_myspace" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_google" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_digg" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_delicious" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_favorites" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                <a class="addthis_button_compact"></a>
            </div>
            <!-- The JS code is in the footer -->

            <script type="text/javascript">
                var addthis_config = {"data_track_clickback": true};
                var addthis_share = {
                    templates: {twitter: 'Check out {{title}} #wordpress #plugin at {{lurl}} (via @orbisius)'}
                }
            </script>
            <!-- AddThis Button START part2 -->
            <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js"></script>
            <!-- AddThis Button END part2 -->
        </p>
    <?php endif ?>

    <h2>Want to hear about future plugins? Join our mailing List! (no spam)</h2>
    <p>
        Get the latest news and updates about this and future cool <a href="http://profiles.wordpress.org/lordspace/"
                                                                      target="_blank" title="Opens a page with the pugins we developed. [New Window/Tab]">plugins we develop</a>.
    </p>

    <p>
        <!-- // MAILCHIMP SUBSCRIBE CODE \\ -->
        1) Subscribe by going to <a href="http://eepurl.com/guNzr" target="_blank">http://eepurl.com/guNzr</a>
        <!-- \\ MAILCHIMP SUBSCRIBE CODE // -->
        OR
        2) by using our QR code. [Scan it with your mobile device].<br/>
        <img src="<?php echo plugin_dir_url(__FILE__); ?>/i/guNzr.qr.2.png" alt="" />
    </p>

    <h2>Demo</h2>
    <p>
        <iframe width="560" height="315" src="http://www.youtube.com/embed/BZUVq6ZTv-o" frameborder="0" allowfullscreen></iframe>

        <br/>Video Link: <a href="http://www.youtube.com/watch?v=BZUVq6ZTv-o&feature=youtu.be" target="_blank">http://www.youtube.com/watch?v=BZUVq6ZTv-o&feature=youtu.be</a>
    </p>
    </div>
    <?php
}

/**
 * It seems WP intentionally adds slashes for consistency with php.
 * Please note: WordPress Core and most plugins will still be expecting slashes, and the above code will confuse and break them.
 * If you must unslash, consider only doing it to your own data which isn't used by others:
 * @see http://codex.wordpress.org/Function_Reference/stripslashes_deep
 */
function orbisius_child_theme_creator_get_request() {
    $req = $_REQUEST;
    $req = stripslashes_deep($req);

    return $req;
}

/**
 * adds some HTML comments in the page so people would know that this plugin powers their site.
 */
function orbisius_child_theme_creator_add_plugin_credits() {
    // pull only these vars
    $default_headers = array(
        'Name' => 'Plugin Name',
        'PluginURI' => 'Plugin URI',
    );

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];

    printf(PHP_EOL . PHP_EOL . '<!-- ' . "Powered by $name | URL: $url " . '-->' . PHP_EOL . PHP_EOL);
}

/**
 */
class orbisius_child_theme_creator {

    public $result = null;
    public $target_dir_path; // /var/www/vhosts/domain.com/www/wp-content/themes/Parent-Theme-child-01/

    /**
     * Sets up the params.
     * directories contain trailing slashes.
     * 
     * @param str $parent_theme_basedir
     */

    public function __construct($parent_theme_basedir = '') {
        $all_themes_root = get_theme_root();

        $this->parent_theme_basedir = $parent_theme_basedir;
        $this->parent_theme_dir = $all_themes_root . '/' . $parent_theme_basedir . '/';

        $i = 0;

        // Let's create multiple folders in case the script is run multiple times.
        do {
            $i++;
            $target_dir = $all_themes_root . '/' . $parent_theme_basedir . '-child-' . sprintf("%02d", $i) . '/';
        } while (is_dir($target_dir));

        $this->target_dir_path = $target_dir;
        $this->target_base_dirname = basename($target_dir);

        // this is appended to the new theme's name
        $this->target_name_suffix = 'Child ' . sprintf("%02d", $i);
    }

    /**
     * @param void
     * @return string returns the dirname (not abs) of the child theme
     */
    public function get_child_base_dir() {
        return $this->target_base_dirname;
    }

    /**
     * Loads files from a directory but skips . and ..
     */
    public function load_files($dir) {
        $files = orbisius_child_theme_creator_util::load_files();
        return $files;
    }

    private $info_result = 'n/a';
    private $data_file = '.ht_orbisius_child_theme_creator.json';

    /**
     * Checks for correct permissions by trying to create a file in the target dir
     * Also it checks if there are files in the target directory in case it exists.
     */
    public function check_permissions() {
        $target_dir_path = $this->target_dir_path;

        if (!is_dir($target_dir_path)) {
            if (!mkdir($target_dir_path, 0775)) {
                throw new Exception("Target child theme directory cannot be created. This is probably a permission error. Cannot continue.");
            }
        } else { // let's see if there will be files in that folder.
            $files = $this->load_files($target_dir_path);

            if (count($files) > 0) {
                throw new Exception("Target folder already exists and has file(s) in it. Cannot continue. Files: ["
                . join(',', array_slice($files, 0, 5)) . ' ... ]');
            }
        }

        // test if we can create the folder and then delete it.
        if (!touch($target_dir_path . $this->data_file)) {
            throw new Exception("Target directory is not writable.");
        }
    }

    /**
     * What files do we have to copy from the parent theme.
     * @var array
     */
    private $main_files = array('screenshot.png', 'header.php', 'footer.php', );

    /**
     * 
     */
    public function add_files($files) {
        $files = (array) $files;
        $this->main_files = array_merge($files, $this->main_files);
    }
    
    /**
     * Copy some files from the parent theme.
     * @return bool success
     */
    public function copy_main_files() {
        $stats = 0;

        $main_files = $this->main_files;

        foreach ($main_files as $file) {
            if (!file_exists($this->parent_theme_dir . $file)) {
                continue;
            }

            $stat = copy($this->parent_theme_dir . $file, $this->target_dir_path . $file);
            $stat = intval($stat);
            $stats += $stat;
        }

        // Some themes have admin files for control panel stuff. So Let's copy it as well.
        if (is_dir($this->parent_theme_dir . 'admin/')) {
            orbisius_child_theme_creator_util::copy($this->parent_theme_dir . 'admin/', $this->target_dir_path . 'admin/');
        }
    }

    /**
     *
     * @return bool success
     * @see http://codex.wordpress.org/Child_Themes
     */
    public function generate_style() {
        global $wp_version;

        $plugin_data = get_plugin_data(__FILE__);
        $app_link = $plugin_data['PluginURI'];
        $app_title = $plugin_data['Name'];

        $parent_theme_data = version_compare($wp_version, '3.4', '>=') ? wp_get_theme($this->parent_theme_basedir) : (object) get_theme_data($this->target_dir_path . 'style.css');

        $buff = '';
        $buff .= "/*\n";
        $buff .= "Theme Name: $parent_theme_data->Name $this->target_name_suffix\n";
        $buff .= "Theme URI: $parent_theme_data->ThemeURI\n";
        $buff .= "Description: $this->target_name_suffix theme for the $parent_theme_data->Name theme\n";
        $buff .= "Author: $parent_theme_data->Author\n";
        $buff .= "Author URI: $parent_theme_data->AuthorURI\n";
        $buff .= "Template: $this->parent_theme_basedir\n";
        $buff .= "Version: $parent_theme_data->Version\n";
        $buff .= "*/\n";

        $buff .= "\n/* Generated by $app_title ($app_link) on " . date('r') . " */ \n\n";

        $buff .= "@import url('../$this->parent_theme_basedir/style.css');\n";

        file_put_contents($this->target_dir_path . 'style.css', $buff);

        // RTL langs; make rtl.css to point to the parent file as well
        if (file_exists($this->parent_theme_dir . 'rtl.css')) {
            $rtl_buff = '';
            $rtl_buff .= "/*\n";
            $rtl_buff .= "Theme Name: $parent_theme_data->Name $this->target_name_suffix\n";
            $rtl_buff .= "Template: $this->parent_theme_basedir\n";
            $rtl_buff .= "*/\n";

            $rtl_buff .= "\n/* Generated by $app_title ($app_link) on " . date('r') . " */ \n\n";

            $rtl_buff .= "@import url('../$this->parent_theme_basedir/rtl.css');\n";

            file_put_contents($this->target_dir_path . 'rtl.css', $rtl_buff);
        }

        $this->info_result = "$parent_theme_data->Name " . $this->target_name_suffix . ' has been created in ' . $this->target_dir_path
                . ' based on ' . $parent_theme_data->Name . ' theme.'
                . "\n<br/>Next Go to Appearance > Themes and Activate the new theme.";
    }

    /**
     *
     * @return string
     */
    public function get_details() {
        return $this->info_result;
    }

    /**
     *
     * @param type $filename
     */
    function log($msg) {
        error_log($msg . "\n", 3, ini_get('error_log'));
    }

}

/**
 * Util funcs
 */
class orbisius_child_theme_creator_util {
    /**
     * Returns a link to appearance. Taking into account multisite.
     * 
     * @param array $params
     * @return string
     */
    static public function get_create_child_pages_link($params = array()) {
        $rel_path = 'themes.php?page=orbisius_child_theme_creator_themes_action';

        if (!empty($params)) {
            $rel_path = orbisius_child_theme_creator_html::add_url_params($rel_path, $params);
        }

        $create_child_themes_page_link = is_multisite()
                    ? network_admin_url($rel_path)
                    : admin_url($rel_path);

        return $create_child_themes_page_link;
    }

    /**
     * Returns the link to the Theme Editor e.g. when a theme_1 or theme_2 is supplied.
     * @param type $params
     * @return string
     */
    static public function get_theme_editor_link($params = array()) {
        $rel_path = 'themes.php?page=orbisius_ctc_theme_editor_action';

        if (!empty($params)) {
            $rel_path = orbisius_child_theme_creator_html::add_url_params($rel_path, $params);
        }

        $link = is_multisite()
                    ? network_admin_url($rel_path)
                    : admin_url($rel_path);

        return $link;
    }

    /**
     * Recursive function to copy (all subdirectories and contents).
     * It doesn't create folder in the target folder.
     * Note: this may be slow if there are a lot of files.
     * The native call might be quicker.
     *
     * Example: src: folder/1/ target: folder/2/
     * @see http://stackoverflow.com/questions/5707806/recursive-copy-of-directory
     */
    static public function copy($src, $dest, $perm = 0775) {
        if (!is_dir($dest)) {
            mkdir($dest, $perm, 1);
        }

        if (is_dir($src)) {
            $dir = opendir($src);

            while ( false !== ( $file = readdir($dir) ) ) {
                if ( $file == '.' || $file == '..' || $file == '.git'  || $file == '.svn' ) {
                    continue;
                }

                $new_src = rtrim($src, '/') . '/' . $file;
                $new_dest = rtrim($dest, '/') . '/' . $file;

                if ( is_dir( $new_src ) ) {
                    self::copy( $new_src, $new_dest );
                } else {
                    copy( $new_src, $new_dest );
                }
            }

            closedir($dir);
        } else { // can also handle simple copy commands
            copy($src, $dest);
        }
    }

    /**
     * Loads files from a directory but skips . and ..
     */
    public static function load_files($dir) {
        $files = array();
        $all_files = scandir($dir);

        foreach ($all_files as $file) {
            if ($file == '.' || $file == '..' || substr($file, 0, 1) == '.') { // skip hidden files
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }

    /**
     * Outputs a message (adds some paragraphs).
     */
    static public function msg($msg, $status = 0) {
        $msg = join("<br/>\n", (array) $msg);

        if (empty($status)) {
            $cls = 'app-alert-error';
        } elseif ($status == 1) {
            $cls = 'app-alert-success';
        } else {
            $cls = 'app-alert-notice';
        }

        $str = "<div class='$cls'><p>$msg</p></div>";

        return $str;
    }

}

/**
 * HTML related methods
 */
class orbisius_child_theme_creator_html {

    /**
     *
     * Appends a parameter to an url; uses '?' or '&'. It's the reverse of parse_str().
     * If no URL is supplied no prefix is added (? or &)
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function add_url_params($url, $params = array()) {
        $str = $query_start = '';

        $params = (array) $params;

        if (empty($params)) {
            return $url;
        }

        if (!empty($url)) {
            $query_start = (strpos($url, '?') === false) ? '?' : '&';
        }

        $str = $url . $query_start . http_build_query($params);

        return $str;
    }

    // generates HTML select
    public static function html_select($name = '', $sel = null, $options = array(), $attr = '') {
        $name = trim($name);
        $elem_name = $name;
        $elem_name = strtolower($elem_name);
        $elem_name = preg_replace('#[^\w]#si', '_', $elem_name);
        $elem_name = trim($elem_name, '_');

        $html = "\n" . '<select id="' . esc_attr($elem_name) . '" name="' . esc_attr($name) . '" ' . $attr . '>' . "\n";

        foreach ($options as $key => $label) {
            $selected = $sel == $key ? ' selected="selected"' : '';

            // if the key contains underscores that means these are labels
            // and should be readonly
            if (strpos($key, '__') !== false) {
                $selected .= ' disabled="disabled" ';
            }

            // This makes certain options to have certain CSS class
            // which can be used to highlight the row
            // the key must start with __sys_CLASS_NAME
            if (preg_match('#__sys_([\w-]+)#si', $label, $matches)) {
                $label = str_replace($matches[0], '', $label);
                $selected .= " class='$matches[1]' ";
            }

            $html .= "\t<option value='$key' $selected>$label</option>\n";
        }

        $html .= '</select>';
        $html .= "\n";

        return $html;
    }
}

/**
 * This method creates 2 panes that the user is able to use to edit theme files.
 * Everythin happens with AJAX
 */
function orbisius_ctc_theme_editor() {
    if ( is_multisite() && ! is_network_admin() ) {
        $next_url = orbisius_child_theme_creator_util::get_create_child_pages_link();

        if (headers_sent()) {
            $success = "In order to edit a theme in a multisite WordPress environment you must do it from Network Admin &gt; Apperance"
                    . "<br/><a href='$next_url' class='button button-primary'>Continue</a>";
            wp_die($success);
        } else {
            wp_redirect($next_url);
        }

        exit();
    }

    $msg = 'Pick any two themes and copy snippets from one to the other.';

    $plugin_data = orbisius_child_theme_creator_get_plugin_data();
    
    ?>
    <div class="wrap orbisius_child_theme_creator_container orbisius_ctc_theme_editor_container">
        <h2>Orbisius Theme Editor <small>(Part of <a href='<?php echo $plugin_data['url'];?>' target="_blank">Orbisius Child Theme Creator</a>)</small></h2>

        <div class="updated"><p><?php echo $msg; ?></p></div>

        <?php
            $buff = $theme_1_file = $theme_2_file = '';
            $req = orbisius_ctc_theme_editor_get_request();

            $html_dropdown_themes = array('' => '== SELECT THEME ==');

            $theme_1 = empty($req['theme_1']) ? '' : $req['theme_1'];
            $theme_2 = empty($req['theme_2']) ? '' : $req['theme_2'];

            $theme_load_args = array();
            $themes = wp_get_themes( $theme_load_args );

            $current_theme = wp_get_theme();

            // we use the same CSS as in WP's appearances but put only the buttons we want.
            foreach ($themes as $theme_basedir_name => $theme_obj) {
                $theme_name = $theme_obj->Name;

                $theme_dir = $theme_basedir_name;

                $parent_theme = $theme_obj->get('Template');

                // Is this a child theme?
                if ( !empty($parent_theme) ) {
                    $theme_name .= " (child of $parent_theme)";
                }

                // Is this the current theme?
                if ($theme_basedir_name == $current_theme->get_stylesheet()) {
                    $theme_name .= ' (site theme) __sys_highlight';
                }

                $html_dropdown_themes[$theme_dir] = $theme_name;
            }

            $html_dropdown_theme_1_files = array(
                '' => '<== SELECT THEME ==',
            );

        ?>

        <table class="widefat">
            <tr>
                <td width="50%">
                    <form id="orbisius_ctc_theme_editor_theme_1_form" class="orbisius_ctc_theme_editor_theme_1_form">
                        <strong>Theme #1:</strong>
                        <?php echo orbisius_child_theme_creator_html::html_select('theme_1', $theme_1, $html_dropdown_themes); ?>

                        <span class="theme_1_file_container">
                            | <strong>File:</strong>
                            <?php echo orbisius_child_theme_creator_html::html_select('theme_1_file', $theme_1_file, $html_dropdown_theme_1_files); ?>
                        </span>

                        <textarea id="theme_1_file_contents" name="theme_1_file_contents" rows="30" class="widefat"></textarea>
                        <div>
                            <button type='submit' class='button button-primary' id="theme_1_submit" name="theme_1_submit">Update</button>
                            <span class="status"></span>
                        </div>
                    </form>
                </td>
                <td width="50%">
                    <form id="orbisius_ctc_theme_editor_theme_2_form" class="orbisius_ctc_theme_editor_theme_2_form">
                        <strong>Theme #2:</strong>
                        <?php echo orbisius_child_theme_creator_html::html_select('theme_2', $theme_2, $html_dropdown_themes); ?>

                        <span class="theme_2_file_container">
                            | <strong>File:</strong>
                            <?php echo orbisius_child_theme_creator_html::html_select('theme_2_file', $theme_2_file, $html_dropdown_theme_1_files); ?>
                        </span>

                        <textarea id="theme_2_file_contents" name="theme_2_file_contents" rows="30" class="widefat"></textarea>
                        <div>
                            <button type='submit' class='button button-primary' id="theme_2_submit" name="theme_2_submit">Update</button>
                            <span class="status"></span>
                        </div>
                    </form>
                </td>
            </tr>
        </table>
    <?php
}

/**
 * This is called via ajax. Depending on the sub_cmd param a different method will be called.
 *
 */
function orbisius_ctc_theme_editor_ajax() {
    $buff = 'INVALID AJAX SUB_CMD';

    $req = orbisius_ctc_theme_editor_get_request();
    $sub_cmd = empty($req['sub_cmd']) ? '' : $req['sub_cmd'];

    switch ($sub_cmd) {
        case 'generate_dropdown':
            $buff = orbisius_ctc_theme_editor_generate_dropdown();

            break;

        case 'load_file':
            $buff = orbisius_ctc_theme_editor_manage_file(1);
            break;

        case 'save_file':
            $buff = orbisius_ctc_theme_editor_manage_file(2);

            break;

        default:
            break;
    }


    die($buff);
}

/**
 * It seems WP intentionally adds slashes for consistency with php.
 * Please note: WordPress Core and most plugins will still be expecting slashes, and the above code will confuse and break them.
 * If you must unslash, consider only doing it to your own data which isn't used by others:
 * @see http://codex.wordpress.org/Function_Reference/stripslashes_deep
 */
function orbisius_ctc_theme_editor_get_request() {
    $req = $_REQUEST;
    $req = stripslashes_deep( $req );

    return $req;
}

/**
 * This returns an HTML select with the selected theme's files.
 * the name/id of that select must be either theme_1_file or theme_2_file
 * @return string
 */
function orbisius_ctc_theme_editor_generate_dropdown() {
    $theme_base_dir = $theme_1_file = '';
    $req = orbisius_ctc_theme_editor_get_request();

    $select_name = 'theme_1_file';

    if (!empty($req['theme_1'])) {
        $theme_base_dir = empty($req['theme_1']) ? '' : preg_replace('#[^\w-]#si', '', $req['theme_1']);
        $theme_1_file = empty($req['theme_1_file']) ? 'style.css' : $req['theme_1_file'];
    } elseif (!empty($req['theme_2'])) {
        $theme_base_dir = empty($req['theme_2']) ? '' : preg_replace('#[^\w-]#si', '', $req['theme_2']);
        $theme_1_file = empty($req['theme_2_file']) ? 'style.css' : $req['theme_2_file'];
        $select_name = 'theme_2_file';
    } else {
        return 'Invalid params.';
    }

    $theme_dir = get_theme_root() . "/$theme_base_dir/";

    if (empty($theme_base_dir) || !is_dir($theme_dir)) {
        return 'Selected theme is invalid.';
    }

    $files = array();
    $all_files = orbisius_child_theme_creator_util::load_files($theme_dir);

    foreach ($all_files as $file) {
        if (preg_match('#\.(php|css|js|txt)$#si', $file)) {
            $files[] = $file;
        }
    }

    // we're going to make values to be keys as well.
    $html_dropdown_theme_1_files = array_combine($files, $files);
    $buff = orbisius_child_theme_creator_html::html_select($select_name, $theme_1_file, $html_dropdown_theme_1_files);

    return $buff;
}

/**
 * Reads or writes contents to a file
 * @param type $read
 * @return string
 */
function orbisius_ctc_theme_editor_manage_file($read = 1) {
    $buff = $theme_base_dir = $theme_file = '';

    $req = orbisius_ctc_theme_editor_get_request();

    if (!empty($req['theme_1']) && !empty($req['theme_1_file'])) {
        $theme_base_dir = empty($req['theme_1']) ? '' : preg_replace('#[^\w-]#si', '', $req['theme_1']);
        $theme_file = empty($req['theme_1_file']) ? 'style.css' : sanitize_file_name($req['theme_1_file']);
        $theme_file_contents = empty($req['theme_1_file_contents']) ? '' : $req['theme_1_file_contents'];
    } elseif (!empty($req['theme_2']) && !empty($req['theme_2_file'])) {
        $theme_base_dir = empty($req['theme_2']) ? '' : preg_replace('#[^\w-]#si', '', $req['theme_2']);
        $theme_file = empty($req['theme_2_file']) ? 'style.css' : sanitize_file_name($req['theme_2_file']);
        $theme_file_contents = empty($req['theme_2_file_contents']) ? '' : $req['theme_2_file_contents'];
    } else {
        return 'Missing data!';
    }

    $theme_dir = get_theme_root() . "/$theme_base_dir/";

    if (empty($theme_base_dir) || !is_dir($theme_dir)) {
        return 'Selected theme is invalid.';
    } elseif (!file_exists($theme_dir . $theme_file)) {
        return 'Selected file is invalid.';
    }

    $theme_file = $theme_dir . $theme_file;

    if ($read == 1) {
        $buff = file_get_contents($theme_file);
    } elseif ($read == 2) {
        file_put_contents($theme_file, $theme_file_contents);
        $buff = $theme_file_contents;
    }

    return $buff;
}
