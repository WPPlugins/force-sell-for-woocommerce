<div class="wrap">
<?php 
$dplugin_name = 'WooCommerce Force Sell';
$dplugin_link = 'http://berocket.com/product/woocommerce-force-sell';
$dplugin_price = 18;
$dplugin_desc = '';
@ include 'settings_head.php';
@ include 'discount.php';
?>
<div class="wrap br_settings br_force_sell_settings show_premium">
    <div id="icon-themes" class="icon32"></div>
    <h2>Force Sell Settings</h2>
    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active general-tab" data-block="general"><?php _e('General', 'BeRocket_force_sell_domain') ?></a>
        <a href="#css" class="nav-tab css-tab" data-block="css"><?php _e('CSS', 'BeRocket_force_sell_domain') ?></a>
    </h2>

    <form class="force_sell_submit_form" method="post" action="options.php">
        <?php 
        $options = BeRocket_force_sell::get_option(); ?>
        <div class="nav-block general-block nav-block-active">
            <table class="form-table license">
                <tr>
                    <th scope="row"><?php _e('Display linked products on single product page', 'BeRocket_force_sell_domain') ?></th>
                    <td>
                        <div><label>
                            <input type="checkbox" value="1" name="br-force_sell-options[display_force_sell]"<?php echo ( @ $options['display_force_sell'] ? ' checked' : ''); ?>>
                            <?php _e('Linked Products, that can be removed', 'BeRocket_force_sell_domain') ?>
                        </label></div>
                        <div><label>
                            <input type="checkbox" value="1" name="br-force_sell-options[display_force_sell_linked]"<?php echo ( @ $options['display_force_sell_linked'] ? ' checked' : ''); ?>>
                            <?php _e('Linked Products, that can be removed only with this product', 'BeRocket_force_sell_domain') ?>
                        </label></div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="nav-block css-block">
            <table class="form-table license">
                <tr>
                    <th scope="row"><?php _e('Custom CSS', 'BeRocket_force_sell_domain') ?></th>
                    <td>
                        <textarea name="br-force_sell-options[custom_css]"><?php echo $options['custom_css']?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'BeRocket_force_sell_domain') ?>" />
        <div class="br_save_error"></div>
    </form>
</div>
<?php
$feature_list = array(
    'Link products to all products',
    'Link products to product categories',
    'Link products to list of products',
    'Linked products as link to product page',
    'Widget to display linked products',
    'Shortcode to display linked products',
);
@ include 'settings_footer.php';
?>
</div>
