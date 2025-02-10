<?php
if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('CDBBC_REVIEW_NOTICE')) {
    class CDBBC_REVIEW_NOTICE {
        /**
         * The Constructor
         */
        public function __construct() {
            if (is_admin()) {
                add_action('admin_notices', array($this, 'atlt_admin_notice_for_reviews'));
                add_action('wp_ajax_cdbbc_dismiss_notice', array($this, 'atlt_dismiss_review_notice'));
                add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            }
        }

        // Enqueue admin CSS and JS
        public function enqueue_admin_assets() {
            $plugin_url = plugin_dir_url(__FILE__);
            wp_register_style('cdbbc_admin_style', $plugin_url . 'assets/css/admin-style.css', array(), '1.0.0');
            wp_register_script('cdbbc_admin_script', $plugin_url . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('cdbbc_admin_style');
            wp_enqueue_script('cdbbc_admin_script');
            wp_localize_script('cdbbc_admin_script', 'CDBBC_Ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_callback' => 'cdbbc_dismiss_notice'
            ));
        }

        // Ajax callback for dismissing review notice
        public function atlt_dismiss_review_notice() {
            update_option('cdbbc-alreadyRated', 'yes');
            wp_send_json_success(array("message" => "Notice dismissed."));
        }

        // Admin notice
        public function atlt_admin_notice_for_reviews() {
            if (!current_user_can('update_plugins')) {
                return;
            }

            $installation_date = get_option('cdbbc_activation_time');
            if (is_numeric($installation_date)) {
                $installation_date = gmdate('Y-m-d h:i:s', $installation_date);
            }

            $alreadyRated = get_option('cdbbc-alreadyRated') ? get_option('cdbbc-alreadyRated') : 'no';

            if (null != get_option('cdbbc_spare_me')) {
                $spare_me = get_option('cdbbc_spare_me');
                if ($spare_me == true) {
                    $alreadyRated = 'yes';
                }
            }

            if ($alreadyRated == 'yes') {
                return;
            }

            $display_date = gmdate('Y-m-d h:i:s');
            $install_date = new DateTime($installation_date);
            $current_date = new DateTime($display_date);
            $difference = $install_date->diff($current_date);
            $diff_days = $difference->days;

            if (isset($diff_days) && $diff_days >= 3) {
                echo wp_kses_post($this->atlt_create_notice_content());
            }
            
        }

        // Generate review notice HTML
        public function atlt_create_notice_content() {
            $img_path = plugin_dir_url(__FILE__) . 'assets/images/cryptodonation-logo.png';
            $p_name = 'Cryptocurrency Donation Box';
            $like_it_text = esc_html__('Rate Now! ★★★★★', 'cryptocurrency-donation-box');
            $already_rated_text = esc_html__('I already rated it', 'cryptocurrency-donation-box');
            $not_like_it_text = esc_html__('Not Interested', 'cryptocurrency-donation-box');
            $p_link = esc_url('https://wordpress.org/support/plugin/cryptocurrency-donation-box/reviews/#new-post');

            $message = "Thanks for using <b>$p_name</b> - WordPress plugin.
            We hope you liked it! <br/>Please give us a quick rating, it works as a boost for us to keep working on more!<br/>";

            $html = '<div class="cdbbc-feedback-notice-wrapper notice notice-info is-dismissible">
                <div class="logo_container"><a href="' . $p_link . '"><img src="' . $img_path . '" alt="' . esc_attr($p_name) . '" style="max-width:80px;"></a></div>
                <div class="message_container">' . $message . '
                    <div class="callto_action">
                        <ul>
                            <li class="love_it"><a href="' . $p_link . '" class="like_it_btn button button-primary" target="_blank" title="' . $like_it_text . '">' . $like_it_text . '</a></li>
                            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button cdbbc_dismiss_notice" title="' . $already_rated_text . '">' . $already_rated_text . '</a></li>
                            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button cdbbc_dismiss_notice" title="' . $not_like_it_text . '">' . $not_like_it_text . '</a></li>
                        </ul>
                        <div class="clrfix"></div>
                    </div>
                </div>
            </div>';

            return $html;
        }
    }

    new CDBBC_REVIEW_NOTICE();
}
