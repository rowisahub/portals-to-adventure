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

    add_action('wp', [$this, 'setup_submission_metadata']);
  }

  public function wldpta_render_hamburger_menu()
  {
    // echo '<style>site-header { display: none !important; }</style>';
    if (is_user_logged_in()) {
      $current_user = wp_get_current_user();
      $user_id = $current_user->ID;

      if ($this->user_func->check_user_exists($user_id) == false) {
        // user is logged in but does not exist
        // error_log('User does not exist');
        // create user
        $userPerms = $this->db_functions->format_permissions(1, 0, 0, 0);

        // get wp user data
        $user = $current_user;

        $user_id = $this->user_func->register_user(
          $user->user_email,
          $user->display_name,
          $user->first_name,
          $user->last_name,
          0,
          $user->ID,
          null,
          $userPerms,
          null
        );

        // error_log('Added user to db: ' . $user_id);
      }

      // Fetch user-specific data
      $in_progress_submissions = $this->submission_func->get_all_submissions_by_state('In Progress', 5, $user_id);
      $approved_submissions = $this->submission_func->get_all_submissions_by_state('Approved', 5, $user_id);

      //$this->logger->debug('In Progress Submissions: ' . print_r($in_progress_submissions, true));

      $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

      ob_start();
      ?>
      <!-- Sidebar Container -->
      <div id="sidebar-container" class="sidebar-container">
        <!-- Hamburger Icon -->
        <div id="hamburger-icon" class="hamburger-icon">
          <img src="<?php echo plugins_url('portals-to-adventure/assets/public/images/Hamburger_icon.png'); ?>" alt="Menu" />
        </div>

        <!-- Sidebar -->
        <div id="side-menu" class="side-menu">
          <div class="side-menu-content">
            <!-- User Info with Hover Options -->
            <div class="user-info">
              <h3>Hello, <?php echo esc_html($current_user->display_name); ?></h3>
              <div class="user-options">
                <a href="<?php echo wp_logout_url($current_url); ?>">Logout</a>
              </div>
            </div>
            <hr>
            <p><a href="/create-a-new-secret-door/">Create New Submission</a></p>
            <p><a href="/view-and-vote">View And Vote</a></p>
            <p><a href="/my-in-progress-secret-doors/">My In-Progress Secret Doors</a></p>
            <p><a href="/my-submitted-secret-doors/">My Submitted Secret Doors</a></p>
            <hr>

            <a href="">
              <h4>My In Progress Submissions:</h4>
            </a>

            <?php if (!empty($in_progress_submissions)): ?>
              <?php
              $limit = 10;
              $count = 0;
              ?>
              <ul>
                <?php foreach ($in_progress_submissions as $submission):
                  if ($count >= $limit) {
                    break;
                  }
                  $count++;
                  ?>
                  <li class="submission-item">
                    <?php echo esc_html($submission['title']); ?>
                    <div class="submission-options">

                      <a href="/my-in-progress-secret-doors/?edit_submission_id=<?php echo esc_attr($submission['id']); ?>"
                        class="edit-button">Edit</a>

                      <!-- <a href="<?php echo $this->get_submission_url($submission['id']); ?>">View</a>
                        <form method="post" action="/create-edit-submissions">
                          <input type="hidden" name="edit_submission_id" value="<?php echo esc_attr($submission['id']); ?>">
                          <a href="#" class="edit-button" onclick="this.closest('form').submit(); return false;">Edit</a>
                        </form> -->
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>

              <?php if (count($in_progress_submissions) > $limit): ?>
                <a href="/my-in-progress-secret-doors" style="color: blue;">View All In Progress</a>
              <?php endif; ?>

            <?php else: ?>
              <p>No submissions in progress.</p>

            <?php endif; ?>

            <a href="/my-submitted-secret-doors">
              <h4>My Submitted Submissions:</h4>
            </a>

            <?php if (!empty($approved_submissions)): ?>
              <ul>
                <?php foreach ($approved_submissions as $submission): ?>
                  <li class="submission-item">
                    <?php echo esc_html($submission['title']); ?>
                    <div class="submission-options">
                      <!-- <a href="/view-submission?id=<?php echo esc_attr($submission['id']); ?>">View</a> -->

                      <a href="<?php echo $this->get_submission_url($submission['id']); ?>">View</a>

                      <!-- <form method="post" action="/create-edit-submissions">
                          <input type="hidden" name="edit_submission_id" value="<?php echo esc_attr($submission['id']); ?>">
                          <a href="#" class="edit-button" onclick="this.closest('form').submit(); return false;">Edit</a>
                        </form> -->

                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p>No approved submissions.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php
      return ob_get_clean();
    } else {
      ob_start();
      ?>

      <!-- Sidebar Container -->
      <div id="sidebar-container" class="sidebar-container">
        <!-- Hamburger Icon -->
        <div id="hamburger-icon" class="hamburger-icon">
          <img src="<?php echo plugins_url('portals-to-adventure/assets/public/images/Hamburger_icon.png'); ?>" alt="Menu" />
        </div>

        <!-- Sidebar -->
        <div id="side-menu" class="side-menu">
          <div class="side-menu-content">
            <div class="user-info">
              <h3>Hello, Guest</h3>
              <p style="font-size: small;">Click here to login</p>
              <div class="user-options">
                <a id="showLogin">Login</a>
              </div>
            </div>
            <hr>
            <p><a href="/view-and-vote">View And Vote</a></p>
          </div>
        </div>
      </div>

      <div id="popup" class="popup" style="display: none;">
        <div class="popup-content">
          <span id="closePopupBtn" class="close">&times;</span>

          <!-- Login Form -->
          <div id="loginForm">
            <h2>Login</h2>
            <p>Please continue with 3rd-party</p>
            <div id="loginGoogleBtn" class="google-signin-btn"></div>
            <!-- <p>Don't have an account? <a href="#" id="showRegister">Register</a></p> -->
            <label for="promotionalEmails">
              <input type="checkbox" id="promotionalEmails" name="promotionalEmails" value="promotionalEmails" checked>
              Receive promotional emails?
            </label>
          </div>
        </div>
      </div>

      <?php
      return ob_get_clean();
    }
  }

  public function submission_add_page()
  {
    if (!is_user_logged_in()) {
      return '<p>Please log in to submit your entry.</p>';
    }


    $page_id = get_the_ID();
    $pta_submission_add_page = get_option('pta_submission_add_page');
    if (!$pta_submission_add_page) {
      add_option('pta_submission_add_page', $page_id);
    }

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
      if (isset($_GET['pta-login'])) {
        // get url of current page without query string
        $current_url = strtok($_SERVER["REQUEST_URI"], '?');

        if (is_user_logged_in()) {
          // redirect to login page
          wp_redirect($current_url);
          exit;
        }
      }

      // add cart fragments script if WooCommerce is active
      if (class_exists('WooCommerce')) {
        wp_enqueue_script('wc-cart-fragments');
      }
    }
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