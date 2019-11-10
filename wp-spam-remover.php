<?php
 
/*
Plugin Name: WordPress Comment Spam Remover
Plugin URI: https://www.geoffstratton.com/
Version: 1.0
Author: Geoff Stratton
Description: This plugin deletes all comments marked as spam from the comments table.
*/
 
// Add a Settings link on the Plugins page
function wp_spam_remover_settings_link($links) {
     $links[] = '<a href="options-general.php?page=' . dirname(plugin_basename(__FILE__)) .
                    '/wp_spam_remover_admin.php">Settings</a>';
     return $links;
}
 
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_spam_remover_settings_link');
 
if(is_admin()){
  require_once('wp_spam_remover_admin.php');
}
 
?>