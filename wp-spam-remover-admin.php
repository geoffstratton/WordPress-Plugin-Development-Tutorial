<?php
 
function wp_spam_remover_admin() {
    add_options_page('WP Spam Remover', 'WP Spam Remover','manage_options', __FILE__, 'wp_spam_remover_page');
}
 
function wp_spam_remover(){
    global $wpdb;
    $wsr_sql = "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'";
    $wpdb->query($wsr_sql);
}
 
function wp_spam_remover_count(){
    global $wpdb;
    $wsr_sql = "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'";
    $count = $wpdb->get_var($wsr_sql);
    return $count;
}
 
function wp_spam_remover_table_size() {
    global $wpdb;
    $wsr_sql = "SHOW TABLE STATUS LIKE 'wp_comments'";
    $result = $wpdb->get_results($wsr_sql);
    foreach($result as $row) {
        $table_size = $row->Data_length + $row->Index_length;
        $table_size = $table_size / 1024;
        $table_size = sprintf("%0.3f", $table_size);
    }
    return $table_size . " KB";
}
 
function wp_spam_remover_optimize(){
    global $wpdb;
    $wsr_sql = "OPTIMIZE TABLE $wpdb->comments";
    $wpdb->query($wsr_sql);
}
 
function wp_spam_remover_page() {
 
?>
 
<div class="wrap">
 
<h2>WordPress Spam Remover</h2>
 
<?php
 
$wsr_message = '';
 
if(isset($_POST['wp_spam_remover_spam'])){
   wp_spam_remover();
   $wsr_message = "All spam comments deleted!";
}
 
if(isset($_POST['wp_spam_remover_optimize'])){
   wp_spam_remover_optimize();
   $wsr_message = "Database Optimized!";
}
 
if($wsr_message != ''){
   echo '<div id="message" class="updated fade"><p><strong>' . $wsr_message . '</strong></p></div>';
}
 
?>
 
<p>
<table class="widefat" style="width:600px;">
  <thead>
    <tr>
        <th scope="col">Spam Comments</th>
        <th scope="col">Action</th>
        <th scope="col">Table Size</th>
        <th scope="col">Action</th>
    </tr>
  </thead>
  <tbody id="the-list">
    <tr>
        <td>
          <?php echo wp_spam_remover_count(); ?>
        </td>
        <td>
          <form action="" method="post">
            <input type="hidden" name="wp_spam_remover_spam" value="spam" />
            <input type="submit" class="button-primary" value="Delete" />
          </form>
        </td>
        <td>
           <?php echo wp_spam_remover_table_size(); ?>
        </td>
        <td>
          <form action="" method="post">
            <input type="hidden" name="wp_spam_remover_optimize" value="optimize" />
            <input type="submit" class="button-primary" value="Optimize" />
          </form>
        </td>
    </tr>
  </tbody>
</table>
 
<?php
}
add_action('admin_menu', 'wp_spam_remover_admin');
?>