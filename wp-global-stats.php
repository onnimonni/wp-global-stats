<?php
/*
Plugin Name: Wp-global-stats
Version: 1.0
Description: Shortcode to show worldwide WP versions statistics
Author: Onni Hakala / Seravo Oy
Author URI: http://github.com/onnimonni
Text Domain: wp-global-stats
Domain Path: /languages
*/

##################
# Core Functions #
##################)

/**
 * Prints table out of stats
 *
 * @param Array $stats - assosiative array with stats
 */
function wordpress_stats_print_table($stats) {
  global $wp_version; // Use WordPress variable
  ?>
  <table>
  <tr>
    <th><?php _e('WP Version','wp-global-stats'); ?></th>
    <th><?php _e('Amount (%)','wp-global-stats'); ?></th>
  </tr>
  <?php foreach ($stats as $version => $amount) : ?>
    <tr>
      <?php /* If this is our version */?>
      <?php if (strpos($wp_version,$version) !== false) : ?>

        <td><b><?php echo $version; ?><b> (<?php _e('This is your version','wp-global-stats'); ?></td>

      <?php else : ?>

        <td><?php echo $version; ?></td>

      <?php endif; ?>

      <td><?php echo $amount; ?></td>
    </tr>
  <?php endforeach; ?>
  </table>
  <?php
}

/**
 * Downloads stats
 *
 * @return Array - assosiative array with stats
 */
function wordpress_stats_download() {
  return json_decode(file_get_contents('https://api.wordpress.org/stats/wordpress/1.0/'));
}

#############
# VERSION 1 #
#############

/**
 * Adds shortcode: [wordpress-stats]
 */
add_shortcode( 'wordpress-stats', 'wordpress_stats_show' );
function wordpress_stats_show() {

  // Get any existing copy of our transient data
  if ( false === ( $stats = get_transient( 'wordpress_stats_array' ) ) ) {
      // It wasn't there, so regenerate the data and save the transient
       $stats = wordpress_stats_download();
       set_transient( 'wordpress_stats_array', $stats, 12 * HOUR_IN_SECONDS );
  }

  // Show stats
  wordpress_stats_print_table($stats);
}

################################
#            Final             #
#       How to use ajax api    #
################################

/**
 * Step 1: Add callback to wordpress ajax api
 * Add callbacks to both wp-admin (wp_ajax_) and frontend (wp_ajax_nopriv_)
 */
add_action( 'wp_ajax_wordpress_global_stats', 'wordpress_stats_callback' );
add_action( 'wp_ajax_nopriv_wordpress_global_stats', 'wordpress_stats_callback' );
function wordpress_stats_callback() {
  if ( false === ( $stats = get_transient( 'wordpress_stats_array' ) ) ) {

    // It wasn't there, so regenerate the data and save the transient
    $stats = wordpress_stats_download();  
    set_transient( 'wordpress_stats_array', $stats, 24 * HOUR_IN_SECONDS );
  }
  echo json_encode($stats);

  // 
  wp_die();
}

/**
 * Step 2: Add javascript which makes the request to WP-Ajax
 */
add_action('wp_head','wordpress_stats_hook_javascript');
function wordpress_stats_hook_javascript() {
  ?>
    <script type="text/javascript">
    (function($) { // Javascript closure for avoiding variable name colliding
      $(document).ready(function() {
        var table = $("#wp-global-stats-table");
        table.each( function( key, value ) {
          $.ajax({
            url: <?php echo '"'.admin_url( 'admin-ajax.php?action=wordpress_global_stats' ).'"'; ?>,
            dataType: 'json',
            success: function(response) {

              // Generate content
              var output = "<table><tr><th>WP Version</th><th>Amount (%)</th></tr>";

              $.each(response, function( key, value ) {
                output += "<tr><td>"+key+"</td><td>"+value+"</td></tr>";
              });

              output += "</table>";

              // Remove contents
              table.empty();

              // Fill contents
              table.append(output);
            }
          });
        });
      });
    })(jQuery);
  </script>
  <?php
}

/*
 * Step 3: Add shortcode which uses ajax
 */
add_shortcode( 'wordpress-stats-ajax', 'wordpress_stats_show_ajax' );
function wordpress_stats_show_ajax() {
  ?>
  <div id="wp-global-stats-table">Loading statistics...</div>
  <?php
}



###############
# ENHANCEMENT #
# Use WP-Cron #
###############

/**
 * Uses wp-cron to download stats into cache
 */
function register_daily_revision_delete_event() {
  // Make sure this event hasn't been scheduled
  if( !wp_next_scheduled( 'populate_wp_stats_transient' ) ) {
    // Schedule the event
    wp_schedule_event( time(), 'daily', 'wordpress_stats_populate_wp_stats_transient_hook' );
  }
}

/**
 * Populates transient cache with version array
 */
add_action('wordpress_stats_populate_wp_stats_transient_hook','wordpress_stats_populate_wp_stats_transient')
function wordpress_stats_populate_wp_stats_transient() {
  $stats = wordpress_stats_download();
  set_transient('wordpress_stats_array',$stats, 25 * HOUR_IN_SECONDS);
}