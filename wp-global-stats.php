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
##################

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
    <th>WP Version</th>
    <th>Amount (%)</th>
  </tr>
  <?php foreach ($stats as $version => $amount) : ?>
    <tr>
      <?php /* If this is our version */?>
      <?php if (strpos($wp_version,$version) !== false) : ?>

        <td><b><?php echo $version; ?><b> (This is your version)</td>

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

  // Download stats
  $stats = wordpress_stats_download();

  // Show stats
  wordpress_stats_print_table($stats);
}