<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * DonationBoxPluginLicenseManager
 *
 * Show the Terms & Conditions consent after activation.
 */
final class DonationBoxPluginLicenseManager
{
	private $plugin_name;
	private $activated_clause;
	private $activation_url;
	private $activation_submenu_url;
	private $plugin_name_no_space;
	private $plugin_name_camel_case;
	private $rest_api_class;
	private $tos_url;
	private $plugin_nonce;
	private $plugin_version;
	private $plugin_url;
	private $hook_name;
	private $logo;
	/**
	 * Singleton
	 */
	private function __construct($activated_clause, $plugin_name, $rest_api_class, $tos_url, $plugin_nonce, $plugin_version, $plugin_url, $plugin_name_no_space, $plugin_name_camel_case, $activation_url, $activation_submenu_url, $logo)
	{
		$this->logo = 'cryptodonation-logo.png';
		$this->plugin_name = CDBBC_PLUGIN_NAME;
		$this->activation_submenu_url = 'cdbbc-crypto-donations';
		$this->plugin_name_no_space = 'cryptocurrency-donation-box';
		$this->plugin_name_camel_case = 'cryptocurrency-donation-box';
        $this->activated_clause = 'cdbbc_activated';
		$this->rest_api_class = 'CdbbcMetaApi';
		$this->activation_url = 'admin.php?page=cdbbc-activation';
		$this->plugin_nonce = 'cdbbc_donation_box';
		$this->plugin_version = CDBBC_VERSION;
		$this->plugin_url = CDBBC_URI;
	}

	/**
	 * Singleton
	 */
	public static function init($activated_clause, $plugin_name, $rest_api_class, $tos_url, $plugin_nonce, $plugin_version, $plugin_url, $plugin_name_no_space, $plugin_name_camel_case, $activation_url, $activation_submenu_url, $logo)
	{
		static $self = null;

		if (null === $self) {
			$self = new self($activated_clause, $plugin_name, $rest_api_class, $tos_url, $plugin_nonce, $plugin_version, $plugin_url, $plugin_name_no_space, $plugin_name_camel_case, $activation_url, $activation_submenu_url, $logo);
		}

		add_action('admin_menu', array($self, 'add_admin_menu'),50);
		add_action('admin_init', array($self, 'setup'), PHP_INT_MAX, 0);
		add_action('admin_enqueue_scripts', array($self, 'enqueue_assets'));
	}

	/**
	 * Add menu page to the admin dashboard
	 *
	 * @see https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	public function add_admin_menu($context)
	{
		$activated = get_option($this->activated_clause);

		if (!$activated) {
			$status ="";
			
			$status = $this->rest_api_class::getActivationStatus($this->plugin_name);
			
			if ('registered' === $status) {
				update_option($this->activated_clause, 1);
			}
		}

		if (!get_option($this->activated_clause)) {
			$this->hook_name = add_submenu_page(
				$this->activation_submenu_url,
				__('Plugin Activation', 'cryptocurrency-donation-box'),
				__('Plugin Activation', 'cryptocurrency-donation-box'), 
				'manage_options',
				$this->plugin_name_no_space . '-activation',
				array($this, 'render')
			);
		}
		
	}

	/**
	 * Route to this page on activation
	 *
	 * @internal Used as a callback.
	 */
	public function setup()
	{
		$run_setup = get_transient($this->plugin_name_camel_case . '_init_activation') && !get_option($this->activated_clause);

		if ($run_setup) {
			
			 if (delete_transient($this->plugin_name_camel_case . '_init_activation')) {	
				wp_safe_redirect(admin_url($this->activation_url));
				exit;
			 }
			
		}
	}

    /**
     * Render the menu page
     *
     * @internal Callback.
     */
    public function render($page_data)
    {
        $siteurl = get_site_url();
        $admin_email = get_option('admin_email');
       
        

?>
	<div class="wrap <?php echo esc_attr($this->plugin_name_no_space); ?>-activation-page">
		<div class="card-top">
			<img class="img" src="<?php echo esc_url($this->plugin_url . 'assets/images/' . $this->logo); ?>" alt="Logo">
			<p id="messager" class="description">
				<?php echo esc_html__('One more minute, please accept our Terms & Conditions!', 'cryptocurrency-donation-box'); ?>
				<br>
				<?php echo esc_html__('You will be directed to connect your wallet to activate the plugin.', 'cryptocurrency-donation-box'); ?>
			</p>

			<form method="POST" action="">
				<label>
					<input id="registration_email" type="hidden" name="registration_email" value="<?php echo esc_attr($admin_email); ?>">
				</label>
				<label>
					<input id="accept_tos" type="checkbox" name="accept_tos" value="1">
					<span>
					<?php 
						// translators: %s represents the opening and closing anchor tags for the Terms & Conditions link.
						echo sprintf(
							esc_html__('I agree to the %sTerms & Conditions%s.', 'cryptocurrency-donation-box'),
							'<a href="' . esc_url(admin_url('admin.php?page=cdbbc-crypto-donations#tab=terms-conditions')) . '" target="_blank">',
							'</a>'
						); 
					?>

					</span>

				</label>
				<div class="card-bottom">
					<button id="meta-plugin-activate-btn" class="button button-primary" type="submit" data-plugin="<?php echo esc_attr($this->plugin_name); ?>">
						<?php echo esc_html(__('Activate', 'cryptocurrency-donation-box')); ?>
					</button>
					<a class="to-dashboard" href="<?php echo esc_url(admin_url()); ?>">
						&larr; <?php echo esc_html__( 'Back to dashboard', 'cryptocurrency-donation-box'); ?>
					</a>
				</div>
				<p class="permalink">
					<?php echo __('Make sure to use <b>Settings >> Permalinks >> Post name (/%postname%/)</b> before activating this plugin.','cryptocurrency-donation-box' ); ?>
				</p>
			</form>
		</div>
	</div>

<?php


    }

	/**
	 * Enqueue assets
	 *
	 * @internal Used as a callback.
	 */
	public function enqueue_assets($hook_name)
	{
		if (!isset($this->hook_name) || $hook_name !== $this->hook_name) {
			return;
		}

		wp_add_inline_style('dashicons', '@media only screen and (max-width: 782px) {
            #messager.err{color:#f22424;padding:19.5px 10px;}#messager.ok{color:#11bd40;padding:19.5px 10px;}
            .img{width:100px;height:100px;padding:10px 10px;}
            .card-top{box-shadow: 2.5px 2.5px 5px 2.5px #C0C7CA;margin-top:32px;width:300px;height:479px;}
            .card-bottom{margin-top: 15px;display:flex;flex-direction:column;align-items:center;background-color:#C0C7CA;width:100%;}
            .permalink{color:red;font-size:15px;padding:10px 10px;}
            .notice,.updated{display:none !important}
            .wp-admin{background-color:#fff !important}
            .'. $this->plugin_name.'-activation-page{margin:0 !important}
            .'. $this->plugin_name.'-activation-page h1{font-weight:600;font-size:28px}
            .'. $this->plugin_name.'-activation-page form{display:flex;flex-direction:column;align-items:center;}
            .'. $this->plugin_name.'-activation-page form label{display:block;margin-bottom:2px}
            .'. $this->plugin_name.'-activation-page form input[type="email"]{width:100%;padding:10px 10px}
            .'. $this->plugin_name.'-activation-page form .button{padding:10px 10px;text-transform:uppercase;margin-bottom:12px;margin-top:12px;}
            .'. $this->plugin_name.'-activation-page form .to-dashboard{text-decoration:none}
            .'. $this->plugin_name.'-activation-page h1,.'. $this->plugin_name.'-activation-page p{padding:10px 10px;}
            .wp-admin #wpwrap{text-align:center}
            #wpwrap #wpcontent{display:flex;flex-direction:column;align-items:center;}
            #wpwrap ##wpbody-content{padding-bottom:0;float:none}
            #adminmenumain,#wpadminbar{}
            #wpfooter{display:none !important}
        }

        @media only screen and (min-width: 782px) {
            #messager.err{color:#f22424;padding:10px 10px;}#messager.ok{color:#11bd40;padding:10px 10px;}
            .img{width:100px;height:100px;padding:10px 10px;}
            .card-top{box-shadow: 2.5px 2.5px 5px 2.5px #C0C7CA;margin-top:32px;width:500px;height:424px;}
            .card-bottom{margin-top: 15px;display:flex;flex-direction:column;align-items:center;padding:10px 10px;background-color:#C0C7CA;width:480px;}
            .permalink{color:red;font-size:15px;padding:10px 10px;}
            .notice,.updated{display:none !important}
            .wp-admin{background-color:#fff !important}
            .'. $this->plugin_name.'-activation-page{margin:0 !important}
            .'. $this->plugin_name.'-activation-page h1{font-weight:600;font-size:28px}
            .'. $this->plugin_name.'-activation-page form{display:flex;flex-direction:column;align-items:center;}
            .'. $this->plugin_name.'-activation-page form label{display:block;margin-bottom:2px}
            .'. $this->plugin_name.'-activation-page form input[type="email"]{width:100%;padding:10px 10px}
            .'. $this->plugin_name.'-activation-page form .button{padding:10px 10px;text-transform:uppercase;margin-bottom:12px;margin-top:12px; width:250px;}
            .'. $this->plugin_name.'-activation-page form .to-dashboard{text-decoration:none}
            .'. $this->plugin_name.'-activation-page h1,.'. $this->plugin_name.'-activation-page p{padding:10px 10px;}
            .wp-admin #wpwrap{text-align:center}
            #wpwrap #wpcontent{display:flex;flex-direction:column;align-items:center;}
            #wpwrap ##wpbody-content{padding-bottom:0;float:none}
            #adminmenumain,#wpadminbar{}
            #wpfooter{display:none !important}
        }');

			wp_localize_script(
				'jquery-core',
				$this->plugin_name_camel_case,
				array(
					'nonce' => wp_create_nonce($this->plugin_nonce),
					'ajaxURL' => admin_url('admin-ajax.php'),
					'adminURL' => admin_url(),
					'pluginVer' => $this->plugin_version,
					'pluginUri' => $this->plugin_url,
					'tosRequired' => __('You must accept our Terms & Conditions!', $this->plugin_name)
				)
			);

		wp_enqueue_script('admin', $this->plugin_url . 'assets/js/admin.js', [], $this->plugin_version, true);
	}
}

// Initialize the SinglgeP.
DonationBoxPluginLicenseManager::init($activated_clause, $plugin_name, $rest_api_class, $tos_url, $plugin_nonce, $plugin_version, $plugin_url, $plugin_name_no_space, $plugin_name_camel_case, $activation_url, $activation_submenu_url, $logo);
