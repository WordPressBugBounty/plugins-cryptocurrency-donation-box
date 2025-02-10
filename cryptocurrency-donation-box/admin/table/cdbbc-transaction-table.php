<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('CDBBC_TRANSACTION_TABLE')) {
    class CDBBC_TRANSACTION_TABLE
    {
        public function __construct()
        {

        }

        //Transaction table callback

        public static function cdbbc_transaction_table()
        {
            $lists_table = new Cdbbc_donation_list();
            echo '<div class="wrap"><h2>' . esc_html__("Donation Box Wallet Transactions", "cryptocurrency-donation-box") . '</h2>';
        
            $lists_table->prepare_items();
            ?>
            <form method="post">
                <?php
                $lists_table->search_box(esc_attr__('Search', 'cryptocurrency-donation-box'), 'search_id');
                ?>
            </form>
            <?php
            $lists_table->display();
            echo '</div>';
        }
        

    }

}
