# WordPress Plugin Development Tutorial
 Steps for creating plugins for WordPress. Uses the working example of a spam comment removal tool.

This article describes one way to build WordPress plugins. For demonstration we're going to create an admin tool that scans your comments table and removes spam comments. 

When comments are submitted, they're written into a table called wp_comments in the database of your web site, and if they've been identified as spam -- either by you, or by an automated system, like Akismet -- the word "spam" is written into their comment_approved field. Here's a look at the wp_comments table in phpMyAdmin:

![image](https://www.geoffstratton.com/sites/default/files/images/php_my_admin_sm.jpg)

We have some spam comments to delete, so let's get started!

### Step 1: Create the Directory Structure and Base File

On your web server, go into your mysite.<span></span>com/wp-content/plugins/ directory and create a directory called "spam-remover". In the spam-remover directory, create an empty file called wp_spam_remover.php.

Next, edit wp_spam_remover.php and add the name, authoring info, and description of the plugin:

```php
/*
Plugin Name: WordPress Comment Spam Remover
Plugin URI: https://www.geoffstratton.com/
Version: 1.0
Author: Geoff Stratton
Description: This plugin deletes all comments marked as spam from the comments table.
*/
```

Save this file as-is, then open up a web browser and go to the Plugins page (http://<span></span>mysite.<span></span>com/<span></span>wp-admin/plugins.php) in your WordPress Dashboard. You'll see that the information above is now listed as the WordPress Comment Spam Remover plugin. You can even activate it, although we haven't told it to do anything yet.

### Step 2: Add a Settings Link In the Plugins List

As you're examining your Plugins page, you'll notice some of the plugins have additional configuration links besides Activate/Deactivate and Edit, like a link to a FAQ or Settings page. Where do these come from? We have to create them.

Back in our wp_spam_remover.php file, make a Settings link:

```php
function wp_spam_remover_settings_link($links) {
    $links[] = '<a href="options-general.php?page=' . dirname(plugin_basename(__FILE__))  . 
                   '/wp_spam_remover_admin.php">Settings</a>';
    return $links;
}
 
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_spam_remover_settings_link');
 
if (is_admin()) {
    require_once('wp_spam_remover_admin.php');
}
```

Here we define the link target by calling the PHP dirname() function on the WordPress plugin_basename() function with the PHP \_\_FILE\_\_ constant. This gives you <a <span></span>href="options-general.php?page=options-general.php?page=spam-remover/wp_spam_remover_admin.php">Settings</<span></span>a>. You could also just hardcode <a <span></span>href="options-general.php?page=spam-remover/wp_spam_remover_admin.php">Settings</<span></span>a>, but then the link would break if you were to rename the parent directory.

Next we call wp_spam_remover_settings_link() from the WordPress add_filter() function. As its name suggests, add_filter() allows you to adjust or 'filter' data before it appears on your WordPress site. There are many available filters, one of which is plugin_action_links, which modifies the links that appear for each plugin on the Plugins page. It's here that you can create Settings, FAQ, or other links in addition to Activate and Edit.

Finally, we use is_admin() to ensure that our wp_spam_remover_admin.php admin page -- which we will create shortly -- is accessible to anybody who can view the Dashboard.

Now if you save your wp_spam_remover.php file and reload your Plugins page, the WordPress Comment Spam Remover plugin has a Settings link. The only problem is the link goes nowhere because the wp_spam_remover_admin.php page doesn't exist. Let's fix that.

### Step 3: Create the Settings Page

Back in the /mysite.com/wp-content/plugins/spam-remover directory, create a file called wp_spam_remover_admin.php and open it up for editing. First we define the appearance and permissions of our admin page:
	
```php
function wp_spam_remover_admin() {
    add_options_page('WP Spam Remover', 'WP Spam Remover', 'manage_options',  __FILE__, 'wp_spam_remover_page');
}
```
 
This uses the WordPress add_options_page() function. All the arguments here are important. The two 'WP Spam Remover' arguments set the titles of the admin page itself and its Dashboard menu link. 'manage_options' is the permissions level needed to access our plugin admin page, \_\_FILE\_\_ is a PHP constant we're using to set the menu slug -- the hyphenated permalink -- that refers to our admin page, and 'wp_spam_remover_page' is an optional function, which we will define below, for rendering our admin page.

Since our goal is to delete spam comments from our database, let's add some logic for that to wp_spam_remover_admin.php:
	
```php 
// Delete the spam comments from our database
function wp_spam_remover(){
    global $wpdb;
    $wsr_sql = "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'";
    $wpdb->query($wsr_sql);
}
 
// Count the spam comments for display on the Settings page
function wp_spam_remover_count(){
    global $wpdb;
    $wsr_sql = "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'";
    $count = $wpdb->get_var($wsr_sql);
    return $count;
}
 
// Display the size of the wp_comments table in kilobytes
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
 
// Provide a function to compact the wp_comments table to save disk space
function wp_spam_remover_optimize(){
    global $wpdb;
    $wsr_sql = "OPTIMIZE TABLE $wpdb->comments";
    $wpdb->query($wsr_sql);
}
``` 

These functions will all be called from the table we're about to build displaying "Delete" and "Optimize" buttons. $wpdb is a WordPress object that represents your site's database. In wp_spam_remover_table_size(), Data_length and Index_length are MySQL functions that return the table size and index in bytes, so to convert this number to a more readable form like kilobytes or megabytes you have to divide it by 1024 or 1024*1024. We then use C-like string formatting to show the result to three decimal points.

Next, let's create some PHP isset($_POST) methods that will run when our buttons are clicked:

```php
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
</div>
```

wp_spam_remover_page() is the the rendering function we defined in add_options_page() above. $wsr_message will print at the top of our admin page to let the site admin know the spam cleanup or optimization has worked. Now to build the table itself:

```html	
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
```

When the buttons are clicked, the wp_spam_remover_spam() or wp_spam_remover_optimize() functions will execute the SQL commands for deleting spam or optimizing our comments table. When our admin page loads, wp_spam_remover_count() and wp_spam_remover_table_size() will show the number of spam comments and overall size in kilobytes of our comments table.

Finally, we use the all-important WordPress add_action() function:

```php	
}
add_action('admin_menu', 'wp_spam_remover_admin');
```

add_action() tells WordPress to execute the specified activity when it's running or loading certain parts of its architecture. In this case, we're telling WordPress to call our wp_spam_remover_admin() function -- defined above -- with the admin_menu action to insert a link to our plugin's admin page in the WordPress Dashboard. The link that gets added will have the features we defined above in wp_spam_remover_admin().

And that should about do it. Our finished admin page looks like this:

![image](https://www.geoffstratton.com/sites/default/files/images/comment_spam.jpg)

If everything is in place, you'll have a 'WP Spam Remover' link under the Settings menu in your Dashboard sidebar, the Comment Spam Remover plugin appears on the Plugins page, and when you go to its admin page, the table will show you the number of spam comments and the comments table size. (It's showing 0 here because I already cleared out my spam comments.) Clicking the buttons will delete the spam comments or compact the table, and then the page will show you a "Spam comments deleted!" or "Table optimized!" message.