<?php
/*
Plugin Name: Security Guard
Plugin URI: http://wordpress.org/plugins/security-guard/
Description: Check your site for <strong>security vulnerabilities</strong> and get precise suggestions for corrective actions on passwords, user accounts, file permissions, database security, version hiding, plugins, themes and other security aspects.
Author: Themology
Version: 1.0.0
Author URI: http://www.themology.net/
*/


if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}


// constants
define('THEMO_SG_DIC', plugin_dir_path(__FILE__) . 'brute-force-dictionary.txt');
define('THEMO_SG_OPTIONS_KEY', 'themo_sg_results');
define('THEMO_SG_MAX_USERS_ATTACK', 3);
define('THEMO_SG_MAX_EXEC_SEC', 200);


require_once 'sg-tests.php';


class themo_sg {
  static $version = 1.0;

  // init plugin
  static function init() {
    // does the user have enough privilages to use the plugin?
    if (is_admin() && current_user_can('administrator')) {
      // this plugin requires WP v3.7
      if (!version_compare(get_bloginfo('version'), '3.7',  '>=')) {
        add_action('admin_notices', array(__CLASS__, 'min_version_error'));
        return;
      } else {
        // add menu item to tools
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));

        // aditional links in plugin description
        add_filter('plugin_action_links_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__),
                   array(__CLASS__, 'plugin_action_links'));
        add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

        // register ajax endpoints
        add_action('wp_ajax_sg_run_tests', array(__CLASS__, 'run_tests'));
        add_action('wp_ajax_sg_hide_tab', array(__CLASS__, 'hide_tab'));

        // warn if tests were not run
        add_action('admin_notices', array(__CLASS__, 'run_tests_warning'));

        // warn if Wordfence is active
        add_action('admin_notices', array(__CLASS__, 'wordfence_warning'));

        // add_markup for UI overlay
        add_action('admin_footer', array(__CLASS__, 'admin_footer'));
      } // if version
    } // if
  } // init


  // add links to plugin's description in plugins table
  static function plugin_meta_links($links, $file) {
    $documentation_link = '<a target="_blank" href="' . plugin_dir_url(__FILE__) . 'documentation/' .
                          '" title="View documentation">Documentation</a>';
    $support_link = '<a target="_blank" href="http://themology.net/support" title="Contact Web factory">Support</a>';

    if ($file == plugin_basename(__FILE__)) {
      $links[] = $documentation_link;
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links


  // add settings link to plugins page
  static function plugin_action_links($links) {
    $settings_link = '<a href="tools.php?page=themo-sg" title="Security Guard">Analyze site</a>';
    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // test if plugin's page is visible
  static function is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'tools_page_themo-sg') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page


  // hide any add-on tab
  static function hide_tab() {
    $tab = trim(@$_POST['tab']);
    $tabs = get_transient('themo_sg_hidden_tabs');
    $tabs[] = $tab;

    set_transient('themo_sg_hidden_tabs', $tabs, DAY_IN_SECONDS * 90);

    wp_send_json_success();
  } // hide_tab


  // enqueue CSS and JS scripts on plugin's pages
  static function enqueue_scripts() {
    if (self::is_plugin_page()) {
      global $wp_styles;
      $plugin_url = plugin_dir_url(__FILE__);

      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('sg-jquery-plugins', $plugin_url . 'js/themo-sg-jquery-plugins.js', array(), self::$version, true);
      wp_enqueue_script('sg-js', $plugin_url . 'js/themo-sg-common.js', array(), self::$version, true);
      wp_enqueue_style('sg-css', $plugin_url . 'css/themo-sg-style.css', array(), self::$version);

      $open_sans = str_replace('400,600', '400,600,800', $wp_styles->registered['open-sans']->src);
      wp_deregister_style('open-sans');
      wp_enqueue_style('open-sans', $open_sans);

      wp_localize_script('jquery', 'sg_plugin_url', $plugin_url);
    } // if
  } // enqueue_scripts


  // add entry to admin menu
  static function admin_menu() {
    add_management_page('Security Guard', 'Security Guard', 'manage_options', 'themo-sg', array(__CLASS__, 'options_page'));
  } // admin_menu


  // display warning if test were never run
  static function run_tests_warning() {
    $tests = get_option(THEMO_SG_OPTIONS_KEY);

    if (self::is_plugin_page() && !$tests['last_run']) {
      echo '<div class="error"><p>Security Guard <strong>tests were never run.</strong> Click "Run tests" to run them now and analyze your site for security vulnerabilities.</p></div>';
    } elseif (self::is_plugin_page() && (current_time('timestamp') - 30*24*60*60) > $tests['last_run']) {
      echo '<div class="error"><p>Security Guard <strong>tests were not run for more than 30 days.</strong> It\'s advisable to run them once in a while. Click "Run tests" to run them now and analyze your site for security vulnerabilities.</p></div>';
    }
  } // run_tests_warning


  // display warning if Wordfence plugin is active
  static function wordfence_warning() {
    if (defined('WORDFENCE_VERSION') && WORDFENCE_VERSION) {
      echo '<div class="error"><p>Please <strong>deactivate Wordfence plugin</strong> before running Security Guard tests. Some tests are detected as site attacks by Wordfence and hence can\'t be performed properly. Activate Wordfence once you\'re done testing.</p></div>';
    }
  } // wordfence_warning


  // display warning if test were never run
  static function min_version_error() {
    echo '<div class="error"><p>Security Guard <b>requires WordPress version 3.7</b> or higher to function properly. You\'re using WordPress version ' . get_bloginfo('version') . '. Please <a href="' . admin_url('update-core.php') . '" title="Update WP core">update</a>.</p></div>';
  } // min_version_error


  // add markup for UI overlay
  static function admin_footer() {
    if (self::is_plugin_page()) {
      echo '<div id="sg_overlay"><div class="sg-overlay-wrapper">';
      echo '<div class="inner">';

      // Title & sitting Guard
      echo '<div class="themo-sg-title">
             <h2>Security Guard</h2>
             <div class="sitting-guard">&nbsp;</div>
           </div>';

      // Outer
      echo '<div class="themo-sg-overlay-outer">';

      // Content
      echo '<div class="themo-sg-overlay-content">';
      echo '<div id="sg-site-scan" style="display: none;">';
      echo '<h3>Security Guard is analyzing your site.<br/>It will only take a few moments ...</h3>';
      echo '</div>';

      do_action('sg_overlay_content');

      echo '<div class="loader"><img title="Loading ..." src="' . plugins_url('images/ajax-loader.gif', __FILE__) . '" alt="Loading..." /></div>';
      echo '<p><br><br><a id="abort-scan" href="#" class="button button-secondary input-button red">Abort scanning</a></p>';

      do_action('sg_overlay_content_after');

      echo '</div>'; // themo-sg-overlay-content

      echo '</div></div></div></div>';
    } // if is_plugin_page
  } // admin_footer

  
  // whole options page
  static function options_page() {
    // does the user have enough privilages to access this page?
    if (!current_user_can('administrator'))  {
      wp_die('You do not have sufficient permissions to access this page.');
    }

    $hidden_tabs = get_transient('themo_sg_hidden_tabs');
    if (!$hidden_tabs) {
      $hidden_tabs = array();
    }
    $tabs = array();
    $tabs[] = array('id' => 'sg_tests', 'class' => '', 'label' => 'Tests', 'callback' => array('self', 'tests_table'));
    $tabs[] = array('id' => 'sg_help', 'class' => 'sg_help', 'label' => 'Tests\' details &amp; help', 'callback' => array('self', 'help_table'));
    
    $tabs = apply_filters('sg_tabs', $tabs);

    echo '<div class="wrap">' . get_screen_icon('sg-lock');
    echo '<h2>Security Guard</h2>';

    echo '<div class="themo-sg-title">
           <h2>Security Guard</h2>
           <div class="sitting-guard">&nbsp;</div>
         </div>';

    echo '<div id="tabs">';
    echo '<ul>';
    foreach ($tabs as $tab) {
      echo '<li><a href="#' . $tab['id'] . '" class="' . $tab['class'] . '">' . $tab['label'] . '</a></li>';
    }
    echo '</ul>';

    foreach ($tabs as $tab) {
      echo '<div style="display: none;" id="' . $tab['id'] . '">';
      call_user_func($tab['callback']);
      echo '</div>';
    }

    echo '</div>'; // tabs
    echo '</div>'; // wrap
  } // options_page


  // display tests help & info
  static function help_table() {
    require_once 'tests-description.php';
  } // help_table


  // display tests table
  static function tests_table() {
    // get test results from cache
    $tests = get_option(THEMO_SG_OPTIONS_KEY);

    echo '<div class="submit-test-container">
          <input type="submit" value=" Run tests " id="run-tests" class="button-primary" name="Submit" />';

    if ($tests['last_run']) {
      echo '<span class="sg-notice">Tests were last run on: ' . date(get_option('date_format') . ' ' . get_option('time_format'), $tests['last_run']) . '.</span>';
    }

    echo '<p><strong>Please read!</strong> These tests only serve as suggestions! Although they cover years of best practices getting all test <i>green</i> will not guarantee your site will not get hacked. Likewise, getting them all <i>red</i> doesn\'t mean you\'ll certainly get hacked. Please read each test\'s detailed information to see if it represents a real security issue for your site. Suggestions and test results apply to public, production sites, not local, development ones. <br /> <span class="red">If you need help getting your WordPress secured by experts please contact our <a href="http://themology.net/support" target="_blank">support</a>.</span></p><br />';

    echo '</div>';

    if ($tests['last_run']) {
      echo '<table class="wp-list-table widefat" cellspacing="0" id="security-guard">';
      echo '<thead><tr>';
      echo '<th class="sg-status">Status</th>';
      echo '<th>Test description</th>';
      echo '<th>Test results</th>';
      echo '<th>&nbsp;</th>';
      echo '</tr></thead>';
      echo '<tbody>';

      if (is_array($tests['test'])) {
        // test Results
        foreach($tests['test'] as $test_name => $details) {
          echo '<tr>
                  <td class="sg-status">' . self::status($details['status']) . '</td>
                  <td>' . $details['title'] . '</td>
                  <td>' . $details['msg'] . '</td>
                  <td class="sg-details"><a href="#' . $test_name . '" class="button action">Details, tips &amp; help</a></td>
                </tr>';
        } // foreach ($tests)
      } else { // no test results
        echo '<tr>
                <td colspan="4">No test results are available. Click "Run tests" to run tests now.</td>
              </tr>';
      } // if tests

      echo '</tbody>';
      echo '<tfoot><tr>';
      echo '<th class="sg-status">Status</th>';
      echo '<th>Test description</th>';
      echo '<th>Test results</th>';
      echo '<th>&nbsp;</th>';
      echo '</tr></tfoot>';
      echo '</table>';
    } // if $results
  } // tests_table


  // run all tests; via AJAX
  static function run_tests($return = false) {
    @set_time_limit(THEMO_SG_MAX_EXEC_SEC);
    $test_count = 0;
    $start_time = microtime(true);
    $test_description['last_run'] = current_time('timestamp');

    foreach(themo_sg_tests::$security_tests as $test_name => $test){
      if ($test_name[0] == '_') {
        continue;
      }
      $response = themo_sg_tests::$test_name();

      $test_description['test'][$test_name]['title'] = $test['title'];
      $test_description['test'][$test_name]['status'] = $response['status'];

      if (!isset($response['msg'])) {
        $response['msg'] = '';
      }

      if ($response['status'] == 10) {
        $test_description['test'][$test_name]['msg'] = sprintf($test['msg_ok'], $response['msg']);
      } elseif ($response['status'] == 0) {
        $test_description['test'][$test_name]['msg'] = sprintf($test['msg_bad'], $response['msg']);
      } else {
        $test_description['test'][$test_name]['msg'] = sprintf($test['msg_warning'], $response['msg']);
      }
      $test_count++;
    } // foreach

    do_action('security_guard_done_testing', $test_description, microtime(true) - $start_time);

    if ($return) {
      return $test_description;
    } else {
      update_option(THEMO_SG_OPTIONS_KEY, $test_description);
      die('1');
    }
  } // run_test


  // convert status integer to button
  static function status($int) {
    if ($int == 0) {
      $string = '<span class="sg-error">Critical</span>';
    } elseif ($int == 10) {
      $string = '<span class="sg-success">Good</span>';
    } else {
      $string = '<span class="sg-warning">Warning</span>';
    }

    return $string;
  } // status


  // clean-up when deactivated
  static function deactivate() {
    delete_option(THEMO_SG_OPTIONS_KEY);
    delete_transient('themo_sg_hidden_tabs');
  } // deactivate
} // themo_sg class


// hook everything up
add_action('init', array('themo_sg', 'init'));

// when deativated clean up
register_deactivation_hook( __FILE__, array('themo_sg', 'deactivate'));