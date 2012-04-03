<?php
/*
Plugin Name: NabeWise
Plugin URI: http://nabewise.com/widgets
Description: NabeWise widgets on your blog!
Version: 0.1
Author: NabeWise Media
Author URI: http://nabewise.com
License: MIT
 */

/* Public: Embeds a NabeWise neighborhood widget in a Wordpress template
 *
 * Note: fails as fast as possible, because I assume some people care about db queries
 *
 * Examples
 *
 *   <?php do_action('nabewise_widget'); ?>
 *
 *   Will generate an embed code for a city/neighborhood if one is specified in 
 *   the post meta
 */
function nabewise_widget() {
  $post_id = get_the_ID();
  $api_key = get_option('nabewise_api_key'); if(!$api_key) return;
  $fha_compliant = get_option('nabewise_fha_compliant'); if(!$fha_compliant) return;
  $widget_size = get_option('nabewise_widget_size'); if(!$widget_size) return;

  $city = get_post_meta($post_id, 'nabewise_city', true); if(!$city) return;
  $neighborhood = get_post_meta($post_id, 'nabewise_neighborhood', true); if(!$neighborhood) return;

  $callback = '';
  if($widget_size == 'small') {
    $callback = 'SmallWidget';
  } else {
    $callback = 'TallWidget';
  }

?>
<div id="nabewise_widget" class="cleanslate" style="display: none !important; border-color: #cccccc !important; background-color: #ffffff !important; "width="302">
      <div class="nabewise_widget nabewise_tall_widget">
<div class="nabewise_widget_container">
<h1 class="nabewise_header"><span class="city"><?php echo $city; ?></span><a href="http://nabewise.com/widgets/nabe/<?php echo $api_key; ?>?neighborhood=<?php echo rawurlencode($neighborhood); ?>&city=<?php rawurlencode($city); ?>&v=2" target="_blank" class="nabe"><?php echo $neighborhood; ?></a> Scorecard</h1>
<div id="nabewise_widget_inner">
          </div>
        </div>
      </div>
<div class="nabewise_footer"><a href="http://nabewise.com" target="_blank">NabeWise</a></div>    </div>
    <link rel="stylesheet" type="text/css" href="http://nabewise.com/stylesheets/cleanslate.min.css" /><script src="//nabewise.com/javascripts/bundle_widgets.js"></script>    <script>
NabeWise.HOST = "http://nabewise.com"
NabeWise.EOPTS = { "color": "#333333", "link_color": "#4585A8" };
</script>
  <script src="//nabewise.com/api/v1/<?php echo $api_key; ?>/neighborhood_by_name?city=<?php echo urlencode($city); ?>&neighborhood=<?php echo urlencode($neighborhood); ?>&callback=NabeWise.<?php echo $callback; ?>('nabewise_widget',{fha:<?php echo $fha_compliant; ?>}).init"></script>
<?php
}

add_action('nabewise_widget', 'nabewise_widget');

/* Sets up options panel */
add_action('admin_menu', 'nabewise_create_menu');

function nabewise_create_menu() {
  add_menu_page('NabeWise Plugin Settings', 'NabeWise Settings', 'administrator',
    __FILE__, 'nabewise_settings_page');

  add_action('admin_init', 'nabewise_register_settings');
}

function nabewise_register_settings() {
  register_setting('nabewise', 'nabewise_api_key');
  register_setting('nabewise', 'nabewise_fha_compliant');
  register_setting('nabewise', 'nabewise_widget_size');
}

/* Sets up meta information fields on post */
add_action('load-post.php', 'nabewise_post_meta_setup');
add_action('load-post-new.php', 'nabewise_post_meta_setup');

add_action('save_post', 'nabewise_save_widget_meta', 10, 2);

function nabewise_post_meta_setup() {
  add_action('admin_enqueue_scripts', 'nabewise_admin_enqueue_scripts');
  add_action('add_meta_boxes', 'nabewise_add_post_meta_boxes');
}

function nabewise_admin_enqueue_scripts() {
  wp_enqueue_script(
    'nabewise_wordpress_admin',
    'https://nabewise.com/javascripts/bundle_wordpress_admin.js',
    array('jquery'));
}

function nabewise_add_post_meta_boxes() {
  add_meta_box(
    'nabewise-widget',
    'NabeWise Widget',
    'nabewise_widget_meta_box',
    'post',
    'normal',
    'default');
}

function nabewise_save_widget_meta($post_id, $post) {
  if(!isset($_POST['nabewise_widget_nonce']) ||
    !wp_verify_nonce($_POST['nabewise_widget_nonce'], basename(__FILE__))) {
    return $post_id;
  }

  $post_type = get_post_type_object($post->post_type);
  
  if(!current_user_can($post_type->cap->edit_post, $post_id)) {
    return $post_id;
  }

  $city = (isset($_POST['nabewise_city']))? $_POST['nabewise_city'] : '';
  $neighborhood = (isset($_POST['nabewise_neighborhood']))? $_POST['nabewise_neighborhood'] : '';
  
  $old_city = get_post_meta($post_id, 'nabewise_city', true);
  $old_neighborhood = get_post_meta($post_id, 'nabewise_neighborhood', true);

  if($city && $neighborhood) {
    update_post_meta($post_id, 'nabewise_city', $city);
    update_post_meta($post_id, 'nabewise_neighborhood', $neighborhood);
  } elseif ($old_city || $old_neighborhood) {
    delete_post_meta($post_id, 'nabewise_city');
    delete_post_meta($post_id, 'nabewise_neighborhood');
  }
}




/* HTML output */

function nabewise_widget_meta_box($object, $box) { ?>
  <?php wp_nonce_field(basename(__FILE__), 'nabewise_widget_nonce'); ?>
  <table>
    <tr>
      <td>
      <select class="nabewise-city" name="nabewise_city" data-key="<?php echo '279e52178d63'; ?>" data-placeholder="Choose a City">
          <option value></option>
          <?php if(($val = get_post_meta($object->ID, 'nabewise_city', true)) != '') { ?>
            <option value="<?php echo esc_attr($val); ?>" selected><?php echo $val; ?></option>
          <?php } ?>
        </select>
      </td>
      <td>
        <select class="nabewise-neighborhood" name="nabewise_neighborhood" data-placeholder="Choose a Nabe">
          <option value="0"></option>
          <?php if(($val = get_post_meta($object->ID, 'nabewise_neighborhood', true)) != '') { ?>
            <option value="<?php echo esc_attr($val); ?>" selected><?php echo $val; ?></option>
          <?php } ?>
        </select>
      </td>
    </tr>
  </table>
<?php
}
  

function nabewise_settings_page() {
?>
<div class="wrap">
  <h2>NabeWise</h2>
  
  <form method="post" action="options.php">
    <?php settings_fields('nabewise'); ?>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">API Key</th>
        <td>
          <input type="text" name="nabewise_api_key" value="<?php echo get_option('nabewise_api_key'); ?>" />
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Widget version</th>
        <td>
          <label for="fha_compliant_false"><input type="radio" id="fha_compliant_false" name="nabewise_fha_compliant" value="false" <?php if(get_option('nabewise_fha_compliant') == 'false') { echo "checked"; } ?> /> Regular version</label>
          <label for="fha_compliant_true"><input type="radio" id="fha_compliant_true" name="nabewise_fha_compliant" value="true" <?php if(get_option('nabewise_fha_compliant') == 'true') { echo "checked"; } ?> /> Real estate version</label>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Widget type</th>
        <td>
          <label for="widget_size_small"><input type="radio" id="widget_size_small" name="nabewise_widget_size" value="small" <?php if(get_option('nabewise_widget_size') == 'small') { echo "checked"; } ?> /> Short</label>
          <label for="widget_size_tall"><input type="radio" id="widget_size_tall" name="nabewise_widget_size" value="tall" <?php if(get_option('nabewise_widget_size') == 'tall') { echo "checked"; } ?> /> Tall</label>
        </td>
      </tr>
    </table>
    
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
  </form>
</div>
<?php
}
?>
