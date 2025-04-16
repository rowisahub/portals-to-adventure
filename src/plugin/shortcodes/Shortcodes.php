<?php
namespace PTA\shortcodes;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\DB\functions\user\user_functions;
use PTA\logger\Log;
use PTA\shortcodes\Shortcodes_functions;

/**
 * Public shortcodes class for the plugin.
 */
class Shortcodes
{
  private $logger;
  private $submission_func; // $this->submission_func->
  private $image_func; // $this->image_func->
  private $user_func; // $this->user_func->
  private $handler_instance; // $this->handler_instance->
  private $db_functions; // $this->db_functions->
  private $shortcodes_functions; // $this->shortcodes_functions->


  public function __construct()
  {
    $this->logger = new Log('Shortcodes');
  }

  public function init(
    submission_functions $sub_functions = null,
    image_functions $img_functions = null,
    user_functions $user_functions = null,
    db_handler $handler_instance = null,
    db_functions $db_functions = null
  ) {
    $this->logger = $this->logger->getLogger();
    //$this->logger->debug('Initializing shortcodes.');

    // Get the handler instance and db functions instance
    $this->handler_instance = $handler_instance ?? new db_handler();
    $this->db_functions = $db_functions ?? new db_functions();

    // if handler_instance is null or db_functions is null, set them
    if ($handler_instance == null || $db_functions == null) {

      // Set the functions instance in the handler, and initialize the functions
      $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
      $this->db_functions->init(handler_instance: $this->handler_instance);

    }

    // Set the functions instances for the submission, image, and user functions
    $this->submission_func = $sub_functions ?? new submission_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    $this->image_func = $img_functions ?? new image_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    $this->user_func = $user_functions ?? new user_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);

    // Shortcode functions
    $this->shortcodes_functions = new Shortcodes_functions(
      sub_functions: $this->submission_func,
      img_functions: $this->image_func,
      user_functions: $this->user_func,
      handler_instance: $this->handler_instance,
      db_functions: $this->db_functions
    );
    $this->shortcodes_functions->register_hooks();

    $this->register_shortcodes();
  }

  public function register_shortcodes()
  {
    add_shortcode('wldpta_hamburger_menu', [$this, 'wldpta_render_hamburger_menu']);
    add_shortcode('submission_add_page', [$this, 'submission_add_page']);
    add_shortcode('submission_edit_page', [$this, 'submission_edit_page']);
    add_shortcode('submission_view_page', [$this, 'submission_view_page']);
    add_shortcode('submission_user_view_page', [$this, 'submission_user_view_page']);
    add_shortcode('submission_view_single_page', [$this, 'submission_view_single_page']);
    add_shortcode('pta_styles', [$this, 'add_pta_styles']);

    add_action('wp', [$this, 'setup_submission_metadata']);

    add_action('login_form', [$this, 'pta_add_google_login_button']);
  }

  public function add_pta_styles(){
    ob_start();
    include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/pta_styles.html';
    return ob_get_clean();
  }

  public function wldpta_render_hamburger_menu()
  {
    ob_start();
    //include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/View_User_Submissions_Page.html';
    include PTA_PLUGIN_DIR . 'assets/public/html/sidebar.html';
    return ob_get_clean();
  }

  public function submission_add_page()
  {
    if (!is_user_logged_in()) {
      // return '<p>Please log in to submit your entry.</p>';
      wp_redirect(wp_login_url(get_permalink()));
      exit;
    }


    $page_id = get_the_ID();
    $pta_submission_add_page = get_option('pta_submission_add_page');
    if (!$pta_submission_add_page) {
      add_option('pta_submission_add_page', $page_id);
    }

    // Enqueue the necessary scripts and styles
    wp_enqueue_script('pta_submission_add_script', '/wp-content/plugins/portals-to-adventure/FrontEnd/public/JS/Add_Submission_Page.js', array('jquery'), '1.0.1', true);
    // wp_enqueue_style('pta_submission_add_style', '/wp-content/plugins/portals-to-adventure/FrontEnd/public/CSS/Add_Submission_Page.css', array(), '1.0.0');
    // wp_enqueue_style('pta_submission_add_style2', '/wp-content/plugins/portals-to-adventure/FrontEnd/public/CSS/pta-styles.css', array(), '1.0.0');
    // wp_enqueue_style('pta_submission_add_style3', '/wp-content/plugins/portals-to-adventure/FrontEnd/public/CSS/pta-submission-styles.css', array(), '1.0.0');


    ob_start();
    include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/Add_Submission_Page.html';
    return ob_get_clean();
  }

  public function submission_edit_page()
  {
    if (!is_user_logged_in()) {
      return '<p>Please log in to edit your submission.</p>';
    }

    $page_id = get_the_ID();
    $pta_submission_edit_page = get_option('pta_submission_edit_page');
    if (!$pta_submission_edit_page) {
      add_option('pta_submission_edit_page', $page_id);
    }

    ob_start();
    include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/Edit_Submissions_Page.html';
    return ob_get_clean();
  }

  public function submission_view_page()
  {

    $page_id = get_the_ID();
    $pta_submission_view_page = get_option('pta_submission_view_page');
    if (!$pta_submission_view_page) {
      add_option('pta_submission_view_page', $page_id);
    }

    ob_start();
    include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/View_Submissions_Page.html';
    return ob_get_clean();
  }

  public function submission_user_view_page()
  {
    if (!is_user_logged_in()) {
      return '<p>Please log in to view your submissions.</p>';
    }

    $page_id = get_the_ID();
    $pta_submission_user_view_page = get_option('pta_submission_user_view_page');
    if (!$pta_submission_user_view_page) {
      add_option('pta_submission_user_view_page', $page_id);
    }

    ob_start();
    include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/View_User_Submissions_Page.html';
    return ob_get_clean();
  }

  public function submission_view_single_page()
  {
    $page_id = get_the_ID();
    $pta_submission_view_single_page = get_option('pta_submission_view_single_page');
    if (!$pta_submission_view_single_page) {
      add_option('pta_submission_view_single_page', $page_id);
    }

    ob_start();
    include PTA_PLUGIN_DIR . 'FrontEnd/public/HTML/View_Single_Submission_Page.html';
    return ob_get_clean();
  }

  public function setup_submission_metadata()
  {
    $page_id = get_the_ID();
    if ($this->is_pta_submission_page($page_id)) {
      if (is_page(get_option('pta_submission_view_single_page'))) {

        $submission_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

        if ($this->db_functions->check_id_exists("submission_data", $submission_id) == true) {

          $submission = $this->submission_func->get_submission($submission_id)[0];

          if ($submission['state'] != 'Approved' && !current_user_can('administrator')) {
            wp_redirect(home_url());
            exit;
          }

          $title = $this->shorten_text('Portals to Adventure | ' . $submission['title'], 50);
          $author = $this->user_func->get_user_by_id($submission['user_owner_id'])[0]['username'];
          $description = $this->shorten_text($submission['description'], 70) . ' by ' . $author;
          $image = $this->image_func->get_image_data($submission['image_thumbnail_id'])[0]['image_reference'];
          $url = get_permalink() . '?id=' . $submission_id;
          $article_published_time = date('c', strtotime($submission['created_at']));



          // Output meta tags
          ob_start();
          ?>
          <!-- Meta Tags for SEO -->
          <title><?php echo $title; ?></title>
          <meta name="description" content="<?php echo $description; ?>">

          <!-- Open Graph Meta Tags -->
          <meta property="og:title" content="<?php echo $title; ?>">
          <meta property="og:description" content="<?php echo $description; ?>">
          <meta property="og:image" content="<?php echo esc_url($image); ?>">
          <meta property="og:url" content="<?php echo esc_url($url); ?>">
          <meta property="og:site_name" content="Portal to Adventure">
          <meta property="og:type" content="website">

          <!-- Twitter Card Meta Tags -->
          <meta name="twitter:card" content="summary_large_image">
          <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
          <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
          <meta name="twitter:image" content="<?php echo esc_url($image); ?>">

          <meta name="theme-color" content="#2fbbed">

          <?php
          echo ob_get_clean();
        }

      }
      // Redirect to same page if user is logged in
      // if (isset($_GET['pta-login'])) {
      //   // get url of current page without query string
      //   $current_url = strtok($_SERVER["REQUEST_URI"], '?');

      //   // $this->logger->debug('Current URL: ' . $current_url);

      //   if (is_user_logged_in()) {
      //     // redirect to login page
      //     wp_redirect($current_url);
      //     exit;
      //   }
      // }

      // add cart fragments script if WooCommerce is active
      if (class_exists('WooCommerce')) {
        wp_enqueue_script('wc-cart-fragments');
      }
    }
  }

  public function pta_add_google_login_button(){
    wp_enqueue_script(
      handle: 'pta-login-google',
      src: 'https://accounts.google.com/gsi/client',
      deps: [],
      ver: '1.0.0',
      args: true
    );
    $ajax_object = array(
      'ajax_url' => admin_url(path: 'admin-ajax.php'),
      'nonce' => wp_create_nonce(action: 'wldpta_ajax_nonce')
    );
    $ajax_object_json = wp_json_encode($ajax_object);
    wp_add_inline_script(
      handle: 'pta-login-google',
      data: "const ajax_object = $ajax_object_json;"
    );
    wp_enqueue_script('pta-google-login', '/wp-content/plugins/portals-to-adventure/assets/public/js/login.js', array('jquery'), '1.0.0', true);
    ob_start();
    ?>
    <label>Or continue with 3rd party</label>
    <div id="loginGoogleBtn" class="google-signin-btn">
    </div>
    <?php
  }

  private function shorten_text($text, $max_length)
  {
    // Remove any HTML tags
    $text = strip_tags($text);

    // Check if shortening is needed
    if (strlen($text) <= $max_length) {
      return $text; // No need to shorten
    }

    // Cut the text to the maximum length
    $text = substr($text, 0, $max_length);

    // Find the position of the last space within the shortened text
    if (($last_space = strrpos($text, ' ')) !== false) {
      $text = substr($text, 0, $last_space);
    }

    // Append ellipsis
    $text .= '...';

    return $text;
  }

  /**
   * Checks if the given page ID corresponds to the PTA submission page.
   *
   * @param int $page_id The ID of the page to check.
   * @return bool True if the page is the PTA submission page, false otherwise.
   */
  private function is_pta_submission_page($page_id)
  {
    $pta_submission_add_page = get_option('pta_submission_add_page');
    $pta_submission_edit_page = get_option('pta_submission_edit_page');
    $pta_submission_view_page = get_option('pta_submission_view_page');
    $pta_submission_user_view_page = get_option('pta_submission_user_view_page');
    $pta_submission_view_single_page = get_option('pta_submission_view_single_page');

    if ($page_id == $pta_submission_add_page || $page_id == $pta_submission_edit_page || $page_id == $pta_submission_view_page || $page_id == $pta_submission_user_view_page || $page_id == $pta_submission_view_single_page) {
      return true;
    } else {
      return false;
    }
  }

  private function get_submission_url($submission_id)
  {
    return add_query_arg(array('submission_id' => $submission_id), "/my-submitted-secret-doors");
  }
}