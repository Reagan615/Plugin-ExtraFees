<?php
/*
Plugin Name: Extra Fees
Plugin URI: #
Description: Admins can add additional fees on the final cart page according to the shopping cart total price conditions set by themselves.
Version: 1.0.0
Author: DB
Author URI: #
*/

// Add settings menu link
function extra_fees_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=extra-fees-settings">' . __('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'extra_fees_add_settings_link');


// Create settings page
function extra_fees_settings_page() {
    $conditional_rules_1 = get_option('extra_fees_conditional_rule_shipping');
    $conditional_rules_2 = get_option('extra_fees_conditional_rule_operator');
    $conditional_rules_3 = get_option('extra_fees_conditional_rule_value');
    $conditional_rules = [];
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Extra Fees Settings', 'extra-fees'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('extra_fees_settings');
            do_settings_sections('extra-fees-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Add settings page link in admin menu
function extra_fees_add_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Sorry, you are not allowed to access this page.'));
    }
    
    add_submenu_page(
        'woocommerce',
        __('Product Fees', 'extra-fees'),
        __('Product Fees', 'extra-fees'),
        'manage_options',
        'extra-fees-product-fees',
        'extra_fees_product_fees_page'
    );
    add_submenu_page(
        'extra-fees-product-fees',
        __('Add New Fee', 'extra-fees'),
        __('Add New Fee', 'extra-fees'),
        'manage_options',
        'extra-fees-add-new-fee',
        'extra_fees_add_new_fee_page'
    );
    add_submenu_page(
        'extra-fees-product-fees',
        __('Extra Fees Settings', 'extra-fees'),
        __('Extra Fees Settings', 'extra-fees'),
        'manage_options',
        'extra-fees-settings',
        'extra_fees_settings_page'
    );

    add_submenu_page(
        'extra-fees-product-fees',
        __('Edit Extra Fee', 'extra-fees'),
        __('Edit Extra Fee', 'extra-fees'),
        'manage_options',
        'extra-fees-edit-fee',
        'extra_fees_edit_fee_page'
    );
}
add_action('admin_menu', 'extra_fees_add_settings_page');

// Register plugin settings
function extra_fees_register_settings() {
    register_setting('extra_fees_settings', 'extra_fees_title');
    register_setting('extra_fees_settings', 'extra_fees_cost', 'floatval');
    register_setting('extra_fees_settings', 'extra_fees_tax_status');
    register_setting('extra_fees_settings', 'extra_fees_type');
    register_setting('extra_fees_settings', 'extra_fees_additional_title');
    register_setting('extra_fees_settings', 'extra_fees_additional_type');
    register_setting('extra_fees_settings', 'extra_fees_additional_cost', 'floatval');
    register_setting('extra_fees_settings', 'extra_fees_conditional_rule_title');
    register_setting('extra_fees_settings', 'extra_fees_conditional_rule_shipping', array(
        'type' => 'array',
        'sanitize_callback' => 'extra_fees_sanitize_conditional_rules_1',
    ));
    register_setting('extra_fees_settings', 'extra_fees_conditional_rule_operator', array(
        'type' => 'array',
        'sanitize_callback' => 'extra_fees_sanitize_conditional_rules_2',
    ));
    register_setting('extra_fees_settings', 'extra_fees_conditional_rule_value', array(
        'type' => 'array',
        'sanitize_callback' => 'extra_fees_sanitize_conditional_rules_3',
    ));
}
add_action('admin_init', 'extra_fees_register_settings');


function extra_fees_product_fees_page() {
    $extra_fees = get_option('extra_fees', array());

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Product Fees', 'extra-fees'); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Extra Fee Title', 'extra-fees'); ?></th>
                    <th scope="col"><?php echo esc_html__('Actions', 'extra-fees'); ?></th>
                    <th scope="col"><?php echo esc_html__('Fee Id', 'extra-fees'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extra_fees as $extra_fee_id => $extra_fee_details) : ?>
                    <tr>
                        <td><?php echo esc_html($extra_fee_details['title']); ?></td>
                        <td>
                        <a href="admin.php?page=extra-fees-edit-fee&id=<?php echo esc_attr($extra_fee_id); ?>"><?php echo esc_html__('Edit', 'extra-fees'); ?></a>
                            <a href="#" class="extra-fees-delete-fee" data-fee-id="<?php echo esc_attr($extra_fee_id); ?>"><?php echo esc_html__('Delete', 'extra-fees'); ?></a>
                        </td>
                        <td><?php echo esc_html($extra_fee_id); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button class="button button-primary" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=extra-fees-add-new-fee')); ?>'"><?php echo esc_html__('Add New', 'extra-fees'); ?></button>
    </div>
    <script>
    jQuery(document).ready(function ($) {
        $('.extra-fees-delete-fee').on('click', function (e) {
            e.preventDefault();
            var feeId = $(this).data('fee-id');
            
            // Send an Ajax request to remove the extra cost
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'extra_fees_delete_fee',
                    feeId: feeId
                },
                success: function (response) {
                    if (response.success) {
                        // After successfully removing fees, reload the page to display the updated list of fees
                        location.reload();
                    } else {
                        alert('删除费用失败！');
                    }
                },
                error: function () {
                    alert('删除费用时发生错误！');
                }
            });
        });
    });
</script>
    
    <?php
}

// Add Ajax callback function to remove extra cost
add_action('wp_ajax_extra_fees_delete_fee', 'extra_fees_delete_fee_callback');
function extra_fees_delete_fee_callback() {
    $feeId = $_POST['feeId'];

    // Perform the delete operation, modifying this part of the code as needed
    $extra_fees = get_option('extra_fees', array());
    if (isset($extra_fees[$feeId])) {
        unset($extra_fees[$feeId]);
        update_option('extra_fees', $extra_fees);
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

// Added Additional Fees Edit Page
function extra_fees_edit_fee_page() {
    $fee_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
    $extra_fees = get_option('extra_fees', array());

    if (!isset($extra_fees[$fee_id])) {
        echo 'Invalid Fee ID';
        return;
    }

    $fee = $extra_fees[$fee_id];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['additional_title'])) {
            $new_additional_titles = $_POST['additional_title'];
            $new_additional_types = $_POST['additional_type'];
            $new_additional_costs = $_POST['additional_cost'];
        
            foreach ($new_additional_titles as $fee_id => $new_additional_title) {
                // Types and Costs of Acquiring Extras
                $new_additional_type = isset($new_additional_types[$fee_id]) ? sanitize_text_field($new_additional_types[$fee_id]) : '';
                $new_additional_cost = isset($new_additional_costs[$fee_id]) ? floatval($new_additional_costs[$fee_id]) : '';
        
                // Update Additional Fee Information
                $extra_fees[$fee_id]['additional_title'] = $new_additional_title;
                $extra_fees[$fee_id]['additional_type'] = $new_additional_type;
                $extra_fees[$fee_id]['additional_cost'] = $new_additional_cost;
            }
        }

        // Handle save operations
        $new_title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $new_cost = isset($_POST['cost']) ? floatval($_POST['cost']) : '';
        $new_tax_status = isset($_POST['tax_status']) ? sanitize_text_field($_POST['tax_status']) : '';
        $new_type = isset($_POST['fee_type']) ? sanitize_text_field($_POST['fee_type']) : '';

        // Build a new array of conditional rules
        $new_conditional_rules_shipping = isset($_POST['extra_fees_conditional_rule_shipping']) ? $_POST['extra_fees_conditional_rule_shipping'] : array();
        $new_conditional_rules_operator = isset($_POST['extra_fees_conditional_rule_operator']) ? $_POST['extra_fees_conditional_rule_operator'] : array();
        $new_conditional_rules_value = isset($_POST['extra_fees_conditional_rule_value']) ? $_POST['extra_fees_conditional_rule_value'] : array();

        $new_conditional_rules = array();
        $rule_count = count($new_conditional_rules_shipping);
        for ($i = 0; $i < $rule_count; $i++) {
            if (!empty($new_conditional_rules_shipping[$i]) && !empty($new_conditional_rules_operator[$i]) && isset($new_conditional_rules_value[$i])) {
                $new_conditional_rules[] = array(
                    'shipping' => sanitize_text_field($new_conditional_rules_shipping[$i]),
                    'operator' => sanitize_text_field($new_conditional_rules_operator[$i]),
                    'value' => floatval($new_conditional_rules_value[$i])
                );
            }
        }

        // Update fee information
        $extra_fees[$fee_id]['title'] = $new_title;
        $extra_fees[$fee_id]['cost'] = $new_cost;
        $extra_fees[$fee_id]['tax_status'] = $new_tax_status;
        $extra_fees[$fee_id]['fee_type'] = $new_type;
        $extra_fees[$fee_id]['conditional_rules'] = $new_conditional_rules;

        // Save updated fee information
        update_option('extra_fees', $extra_fees);

        wp_redirect(admin_url('admin.php?page=extra-fees-product-fees'));
        exit;
    }

    // show edit form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Edit Fee', 'extra-fees'); ?></h1>
        <form method="POST" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Fees Title', 'extra-fees'); ?></th>
                    <td>
                        <input type="text" name="title" value="<?php echo esc_attr($fee['title']); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Fee Type', 'extra-fees'); ?></th>
                    <td>
                        <select name="fee_type">
                            <option value="fixed" <?php selected($fee['fee_type'], 'fixed'); ?>><?php esc_html_e('Fixed', 'extra-fees'); ?></option>
                            <option value="percentage" <?php selected($fee['fee_type'], 'percentage'); ?>><?php esc_html_e('Percentage', 'extra-fees'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Fees Cost', 'extra-fees'); ?></th>
                    <td>
                        <input type="text" name="cost" value="<?php echo esc_attr($fee['cost']); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Tax Status', 'extra-fees'); ?></th>
                    <td>
                        <select name="tax_status">
                            <option value="none" <?php selected($fee['tax_status'], 'none'); ?>><?php esc_html_e('None', 'extra-fees'); ?></option>
                            <option value="taxable" <?php selected($fee['tax_status'], 'taxable'); ?>><?php esc_html_e('Taxable', 'extra-fees'); ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Start setting up Additional Fees -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Additional Fees Title', 'extra-fees'); ?></th>
                    <td>
                    <input type="text" name="additional_title[<?php echo esc_attr($fee_id); ?>]" value="<?php echo esc_attr($fee['additional_title']); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Additional Fee Type', 'extra-fees'); ?></th>
                    <td>
                        <select name="additional_type[<?php echo esc_attr($fee_id); ?>]">
            <option value="fixed" <?php selected($fee['additional_type'], 'fixed'); ?>><?php esc_html_e('Fixed', 'extra-fees'); ?></option>
            <option value="percentage" <?php selected($fee['additional_type'], 'percentage'); ?>><?php esc_html_e('Percentage', 'extra-fees'); ?></option>
        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Additional Fees Cost', 'extra-fees'); ?></th>
                    <td>
                    <input type="text" name="additional_cost[<?php echo esc_attr($fee_id); ?>]" value="<?php echo esc_attr($fee['additional_cost']); ?>" />
                    </td>
                </tr>
                <!-- End setting Additional Fees -->

                <!-- Start setting up the Conditional Fee Rule -->
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Conditional Fee Rule', 'extra-fees'); ?></th>
                    <td>
                        <div id="conditional-rule-container">
                            <?php foreach ($fee['conditional_rules'] as $kr => $rule) : ?>
                                <div class="conditional-rule">
                                    <select name="extra_fees_conditional_rule_shipping[]">
                                        <option value="cart subtotal" <?php selected($rule['shipping'], 'cart subtotal'); ?>><?php esc_html_e('Cart Subtotal', 'extra-fees'); ?></option>
                                    </select>
                                    <select name="extra_fees_conditional_rule_operator[]">
                                        <option value="equal" <?php selected($rule['operator'], 'equal'); ?>><?php esc_html_e('Equal to', 'extra-fees'); ?></option>
                                        <option value="less_than_equal" <?php selected($rule['operator'], 'less_than_equal'); ?>><?php esc_html_e('Less than ', 'extra-fees'); ?></option>
                                        <option value="great_than_equal" <?php selected($rule['operator'], 'great_than_equal'); ?>><?php esc_html_e('Great than ', 'extra-fees'); ?></option>
                                    </select>
                                    <input type="number" name="extra_fees_conditional_rule_value[]" value="<?php echo esc_attr($rule['value']); ?>" />
                                    <button type="button" class="button button-secondary remove-rule-button"><?php esc_html_e('Remove', 'extra-fees'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button button-secondary" id="add-rule-button"><?php esc_html_e('Add Rule', 'extra-fees'); ?></button>
                    </td>
                </tr>
                <!-- Finish setting Conditional Fee Rule -->
            </table>

            <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Save', 'extra-fees'); ?>">
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Add rule button click event processing
            $('#add-rule-button').click(function() {
                var ruleHtml = '<div class="conditional-rule">' +
                    '<select name="extra_fees_conditional_rule_shipping[]">' +
                    '<option value="cart subtotal"><?php esc_html_e('Cart Subtotal', 'extra-fees'); ?></option>' +
                    '</select>' +
                    '<select name="extra_fees_conditional_rule_operator[]">' +
                    '<option value="equal"><?php esc_html_e('Equal to', 'extra-fees'); ?></option>' +
                    '<option value="less_than_equal"><?php esc_html_e('Less than ', 'extra-fees'); ?></option>' +
                    '<option value="great_than_equal"><?php esc_html_e('Great than ', 'extra-fees'); ?></option>' +
                    '</select>' +
                    '<input type="number" name="extra_fees_conditional_rule_value[]" />' +
                    '<button type="button" class="button button-secondary remove-rule-button"><?php esc_html_e('Remove', 'extra-fees'); ?></button>' +
                    '</div>';
                $('#conditional-rule-container').append(ruleHtml);
            });

            // Remove the click event processing of the rule button
            $('#conditional-rule-container').on('click', '.remove-rule-button', function() {
                $(this).closest('.conditional-rule').remove();
            });
        });
    </script>
    <?php
}

// Register Additional Fees Edit Page
function extra_fees_register_edit_fee_page() {
    add_submenu_page(
        'admin.php?page=extra-fees-product-fees',
        __('Edit Fee', 'extra-fees'),
        __('Edit Fee', 'extra-fees'),
        'manage_options',
        'extra-fees-edit-fee',
        'extra_fees_edit_fee_page'
    );
}
add_action('admin_menu', 'extra_fees_register_edit_fee_page');


function extra_fees_add_new_fee_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create a new fee entry
        $new_fee = array(
            'title' => sanitize_text_field($_POST['extra_fees_title']),
            'cost' => floatval($_POST['extra_fees_cost']),
            'tax_status' => $_POST['extra_fees_tax_status'],
            'type' => $_POST['extra_fees_type'],
            'additional_title' => sanitize_text_field($_POST['extra_fees_additional_title']),
            'additional_type' => $_POST['extra_fees_additional_type'],
            'additional_cost' => floatval($_POST['extra_fees_additional_cost']),
            'conditional_rules' => array(),
        );

        // Get the existing fees
        $extra_fees = get_option('extra_fees', array());

        // Generate a unique ID for the new fee
        $new_fee_id = uniqid();

        // Add the new fee to the fees array
        $extra_fees[$new_fee_id] = $new_fee;

        // Update the fees option
        update_option('extra_fees', $extra_fees);

        // Redirect to the fees page
        wp_redirect(admin_url('admin.php?page=extra-fees-product-fees'));
        exit;
    }

    // Display the new fee form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Add New Fee', 'extra-fees'); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Fees Title', 'extra-fees'); ?></th>
                    <td>
                        <input type="text" name="extra_fees_title" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Fee Type', 'extra-fees'); ?></th>
                    <td>
                        <select name="extra_fees_type">
                            <option value="fixed"><?php esc_html_e('Fixed', 'extra-fees'); ?></option>
                            <option value="percentage"><?php esc_html_e('Percentage', 'extra-fees'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Fees Cost', 'extra-fees'); ?></th>
                    <td>
                        <input type="text" name="extra_fees_cost" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Tax Status', 'extra-fees'); ?></th>
                    <td>
                        <select name="extra_fees_tax_status">
                            <option value="none"><?php esc_html_e('None', 'extra-fees'); ?></option>
                            <option value="taxable"><?php esc_html_e('Taxable', 'extra-fees'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Additional Fees Title', 'extra-fees'); ?></th>
                    <td>
                        <input type="text" name="extra_fees_additional_title" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Additional Fee Type', 'extra-fees'); ?></th>
                    <td>
                        <select name="extra_fees_additional_type">
                            <option value="fixed"><?php esc_html_e('Fixed', 'extra-fees'); ?></option>
                            <option value="percentage"><?php esc_html_e('Percentage', 'extra-fees'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Additional Fees Cost', 'extra-fees'); ?></th>
                    <td>
                        <input type="text" name="extra_fees_additional_cost" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Conditional Fee Rule', 'extra-fees'); ?></th>
                    <td>
                        <div id="conditional-rule-container">
                            <div class="conditional-rule">
                                <select name="extra_fees_conditional_rule_shipping[]">
                                    <option value="cart subtotal"><?php esc_html_e('Cart Subtotal', 'extra-fees'); ?></option>
                                </select>
                                <select name="extra_fees_conditional_rule_operator[]">
                                    <option value="equal"><?php esc_html_e('Equal to', 'extra-fees'); ?></option>
                                    <option value="less_than_equal"><?php esc_html_e('Less than', 'extra-fees'); ?></option>
                                    <option value="great_than_equal" <?php selected($rule['operator'], 'great_than_equal'); ?>><?php esc_html_e('Great than', 'extra-fees'); ?></option>
                                </select>
                                <input type="text" name="extra_fees_conditional_rule_value[]" placeholder="<?php esc_attr_e('Value', 'extra-fees'); ?>" />
                                <button type="button" class="button button-secondary remove-rule-button"><?php esc_html_e('Remove', 'extra-fees'); ?></button>
                            </div>
                        </div>
                        <button type="button" class="button button-secondary" id="add-rule-button"><?php esc_html_e('Add Rule', 'extra-fees'); ?></button>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
        jQuery(document).ready(function($) {
            // Add rule button click event handler
            $('#add-rule-button').click(function() {
                var ruleHtml = '<div class="conditional-rule">' +
                    '<select name="extra_fees_conditional_rule_shipping[]">' +
                    '<option value="cart subtotal"><?php esc_html_e('Cart Subtotal', 'extra-fees'); ?></option>' +
                    '</select>' +
                    '<select name="extra_fees_conditional_rule_operator[]">' +
                    '<option value="equal"><?php esc_html_e('Equal to', 'extra-fees'); ?></option>' +
                    '<option value="less_than_equal"><?php esc_html_e('Less than ', 'extra-fees'); ?></option>' +
                    '<option value="great_than_equal"><?php esc_html_e('Great than ', 'extra-fees'); ?></option>' +
                    '</select>' +
                    '<input type="text" name="extra_fees_conditional_rule_value[]" placeholder="<?php esc_attr_e('Value', 'extra-fees'); ?>" />' +
                    '<button type="button" class="button button-secondary remove-rule-button"><?php esc_html_e('Remove', 'extra-fees'); ?></button>' +
                    '</div>';
                $('#conditional-rule-container').append(ruleHtml);
            });

            // Remove rule button click event handler
            $('#conditional-rule-container').on('click', '.remove-rule-button', function() {
                $(this).closest('.conditional-rule').remove();
            });
        });
    </script>
    <?php
}


// Show Fees Settings
function extra_fees_render_fees_settings() {
    $fees_title = get_option('extra_fees_title', '');
    $fees_cost = get_option('extra_fees_cost', '');
    $tax_status = get_option('extra_fees_tax_status', 'none');
    $fee_type = get_option('extra_fees_type', 'fixed');
    $additional_title = get_option('extra_fees_additional_title', ''); 
    $additional_type = get_option('extra_fees_additional_type', 'fixed'); 
    $additional_cost = get_option('extra_fees_additional_cost', ''); 
    $conditional_rule_title = get_option('extra_fees_conditional_rule_title', '');
    $conditional_rules = get_option('extra_fees_conditional_rules', array());
    $conditional_rules_1 = get_option('extra_fees_conditional_rule_shipping');
    $conditional_rules_2 = get_option('extra_fees_conditional_rule_operator');
    $conditional_rules_3 = get_option('extra_fees_conditional_rule_value');
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Fees Title', 'extra-fees'); ?></th>
            <td>
                <input type="text" name="extra_fees_title" value="<?php echo esc_attr($fees_title); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Fee Type', 'extra-fees'); ?></th>
            <td>
                <select name="extra_fees_type">
                    <option value="fixed" <?php selected($fee_type, 'fixed'); ?>><?php esc_html_e('Fixed', 'extra-fees'); ?></option>
                    <option value="percentage" <?php selected($fee_type, 'percentage'); ?>><?php esc_html_e('Percentage', 'extra-fees'); ?></option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Fees Cost', 'extra-fees'); ?></th>
            <td>
                <input type="text" name="extra_fees_cost" value="<?php echo esc_attr($fees_cost); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Tax Status', 'extra-fees'); ?></th>
            <td>
                <select name="extra_fees_tax_status">
                    <option value="none" <?php selected($tax_status, 'none'); ?>><?php esc_html_e('None', 'extra-fees'); ?></option>
                    <option value="taxable" <?php selected($tax_status, 'taxable'); ?>><?php esc_html_e('Taxable', 'extra-fees'); ?></option>
                </select>
            </td>
        </tr>

        <!-- Start setting up Additional Fees -->
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Additional Fees Title', 'extra-fees'); ?></th>
            <td>
                <input type="text" name="extra_fees_additional_title" value="<?php echo esc_attr($additional_title); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Additional Fee Type', 'extra-fees'); ?></th>
            <td>
                <select name="extra_fees_additional_type">
                    <option value="fixed" <?php selected($additional_type, 'fixed'); ?>><?php esc_html_e('Fixed', 'extra-fees'); ?></option>
                    <option value="percentage" <?php selected($additional_type, 'percentage'); ?>><?php esc_html_e('Percentage', 'extra-fees'); ?></option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Additional Fees Cost', 'extra-fees'); ?></th>
            <td>
                <input type="text" name="extra_fees_additional_cost" value="<?php echo esc_attr($additional_cost); ?>" />
            </td>
        </tr>
        <!-- End setting Additional Fees -->

        <!-- Start setting up Conditional Fee Rule -->
        <tr valign="top">
            <th scope="row"><?php esc_html_e('Conditional Fee Rule', 'extra-fees'); ?></th>
            <td>
                <div id="conditional-rule-container">
                    <?php foreach ($conditional_rules_1 as $kr => $rule) : ?>
                        <div class="conditional-rule">
                            <select name="extra_fees_conditional_rule_shipping[]">
                                <option value="cart subtotal" <?php selected($rule, 'cart subtotal'); ?>><?php esc_html_e('cart subtotal', 'extra-fees'); ?></option>
                            </select>
                            <select name="extra_fees_conditional_rule_operator[]">
                                <option value="equal" <?php selected($conditional_rules_2[$kr], 'equal'); ?>><?php esc_html_e('Equal to', 'extra-fees'); ?></option>
                                <option value="less_than_equal" <?php selected($conditional_rules_2[$kr], 'less_than_equal'); ?>><?php esc_html_e('Less than ', 'extra-fees'); ?></option>
                                <option value="great_than_equal" <?php selected($rule['operator'], 'great_than_equal'); ?>><?php esc_html_e('Great than ', 'extra-fees'); ?></option>
                            </select>
                            <input type="number" name="extra_fees_conditional_rule_value[]" value="<?php echo esc_attr($conditional_rules_3[$kr]); ?>" />
                            <button type="button" class="button button-secondary remove-rule-button"><?php esc_html_e('Remove', 'extra-fees'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="add-rule-button"><?php esc_html_e('Add Rule', 'extra-fees'); ?></button>
            </td>
        </tr>
        <!-- End setting Conditional Fee Rule -->
    </table>

    <script>
        jQuery(document).ready(function($) {
            $('#add-rule-button').click(function() {
                var ruleHtml = '<div class="conditional-rule">' +
                    '<select name="extra_fees_conditional_rule_shipping[]">' +
                    '<option value="cart subtotal"><?php esc_html_e('cart subtotal', 'extra-fees'); ?></option>' +
                    '</select>' +
                    '<select name="extra_fees_conditional_rule_operator[]">' +
                    '<option value="equal"><?php esc_html_e('Equal to', 'extra-fees'); ?></option>' +
                    '<option value="less_than_equal"><?php esc_html_e('Less than ', 'extra-fees'); ?></option>' +
                    '<option value="great_than_equal"><?php esc_html_e('Great than ', 'extra-fees'); ?></option>' +
                    '</select>' +
                    '<input type="number" name="extra_fees_conditional_rule_value[]" />' +
                    '<button type="button" class="button button-secondary remove-rule-button"><?php esc_html_e('Remove', 'extra-fees'); ?></button>' +
                    '</div>';
                $('#conditional-rule-container').append(ruleHtml);
            });

            $('#conditional-rule-container').on('click', '.remove-rule-button', function() {
                $(this).closest('.conditional-rule').remove();
            });
        });
    </script>
    <?php
}

// Calculate and apply additional charges
function extra_fees_calculate_fees($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $extra_fees = get_option('extra_fees', array());

    $cart->fees = array();

    foreach ($extra_fees as $extra_fee_id => $extra_fee_details) {
        $title = $extra_fee_details['title'];
        $cost = $extra_fee_details['cost'];

        // Calculate Fees Based on Fee Type
        if ('fixed' === $extra_fee_details['fee_type']) {
            $fee = $cost;
        } elseif ('percentage' === $extra_fee_details['fee_type']) {
            $cart_total = $cart->subtotal;
            $fee = $cart_total * ($cost / 100);
        }

        $additional_title = $extra_fee_details['additional_title'];
        $additional_cost = $extra_fee_details['additional_cost'];

        // Calculate additional charges based on the additional charge type
        if ('fixed' === $extra_fee_details['additional_type']) {
            $additional_fee = $additional_cost;
        } elseif ('percentage' === $extra_fee_details['additional_type']) {
            $other_fees_total = 0;

        // Get Shipping Total
        $shipping_total = WC()->cart->get_shipping_total();

        // Add the shipping total to the other charges
        $other_fees_total += $shipping_total;

        // Calculate the sum of other fees that need to be paid (excluding the additional fee itself)
        foreach ($extra_fees as $other_fee_id => $other_fee_details) {
        if ($other_fee_id !== $extra_fee_id) {
            $other_fees_total += $other_fee_details['cost'];
        }

         // 加上cart subtotal并乘以百分比得到最终的additional fee
        $additional_fee = ($other_fees_total + $cart->subtotal) * ($additional_cost / 100);
        }

        }

        $conditional_rules = $extra_fee_details['conditional_rules'];
        $is_true = true;

        foreach ($conditional_rules as $key => $rule) {
            $rule_shipping = $rule['shipping'];
            $rule_operator = $rule['operator'];
            $rule_value = $rule['value'];

            switch ($rule_shipping) {
                case 'shipping':
                case 'pickup':
                case 'credit card':
                case 'apply pay':
                    if ($rule_value != $rule_shipping) {
                        $is_true = false;
                    }
                    break;
                case 'cart subtotal':
                    if ($rule_operator === 'less_than_equal') {
                        if ($cart->subtotal > $rule_value) {
                            $is_true = false;
                        } 
                    } else if($rule_operator === 'great_than_equal') {
                        if($cart->subtotal < $rule_value) {
                            $is_true = false;
                        }
                    } else if ($rule_operator === 'equal') {
                        if($cart->subtotal !== $rule_value) {
                            $is_true = false;
                        }
                    }
                    break;
            }
        }

        if ($is_true) {
            $cart->add_fee($title, $fee + $additional_fee, false, '');
        }
    }
}


// Save premium settings
function extra_fees_save_settings() {
    if (isset($_POST['extra_fees'])) {
        $extra_fees = $_POST['extra_fees'];

        // Extra charge for clearing empty
        $extra_fees = array_filter($extra_fees, function ($fee) {
            return !empty($fee['title']) && !empty($fee['cost']);
        });

        // Update conditional fee rules for each additional fee
        foreach ($extra_fees as &$fee) {
            $fee['conditional_rules'] = array_filter($fee['conditional_rules'], function ($rule) {
                return !empty($rule['shipping']) && !empty($rule['operator']) && !empty($rule['value']);
            });
        }

        // Save premium settings
        update_option('extra_fees', $extra_fees);
    }
}

add_action('woocommerce_cart_calculate_fees', 'extra_fees_calculate_fees', 10, 1);
add_action('woocommerce_update_options', 'extra_fees_save_settings');

// order processing code
function extra_fees_apply_fees($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;


    $conditional_rules_1 = get_option('extra_fees_conditional_rule_shipping');
    $conditional_rules_2 = get_option('extra_fees_conditional_rule_operator');
    $conditional_rules_3 = get_option('extra_fees_conditional_rule_value');
    $is_true = 1;

    foreach ($conditional_rules_1 as $key => $v) {
        if ($v) {
            switch ($v) {
                case 'shipping':
                    $val = $conditional_rules_3[$key];
                    break;
                case 'pickup':
                    $val = $conditional_rules_3[$key];
                    break;
                case 'credit card':
                    $val = $conditional_rules_3[$key];
                    break;
                case 'apply pay':
                    $val = $conditional_rules_3[$key];
                    break;
                case 'cart subtotal':
                    if ($conditional_rules_2[$key] == 'less_than_equal') {
                        if ($conditional_rules_3[$key] < $cart->cart_contents_total) {
                            $is_true = 0;
                        }
                    } else if($rule_operator === 'great_than_equal') {
                        if($cart->subtotal < $rule_value) {
                            $is_true = false;
                        }
                    } else if ($rule_operator === 'equal') {
                        if($cart->subtotal !== $rule_value) {
                            $is_true = false;
                        }
                    }
                    break;
            }
        }
    }
    if (!$is_true) {
        return;
    }

    $fee_title = get_option('extra_fees_title');
    $fee_type = get_option('extra_fees_type');
    $fee_cost = get_option('extra_fees_cost');
    $additional_fee_title = get_option('extra_fees_additional_title'); 
    $additional_fee_type = get_option('extra_fees_additional_type'); 
    $additional_fee_cost = get_option('extra_fees_additional_cost'); 

    if ($fee_type == 'percentage') {
        $fee_cost = ($cart->cart_contents_total * $fee_cost) / 100;
    }

    $cart->add_fee($fee_title, $fee_cost);

    // Determine whether additional fees are required
    if (!empty($additional_fee_title) && !empty($additional_fee_cost)) {
        if ($additional_fee_type == 'percentage') {
            $additional_fee_cost = ($cart->cart_contents_total * $additional_fee_cost) / 100;
        }

        $cart->add_fee($additional_fee_title, $additional_fee_cost);
    }

}
// add_action('woocommerce_cart_calculate_fees', 'extra_fees_apply_fees');

// Set whether the added fee is taxable
function extra_fees_set_fees_tax($cart_object) {
    $tax_status = get_option('extra_fees_tax_status');
    $fees = $cart_object->get_fees();
    foreach ($fees as $fee_key => $fee) {
        $fee->taxable = ($tax_status == 'taxable');
    }
}
add_action('woocommerce_cart_calculate_fees', 'extra_fees_set_fees_tax', 20);

// Array of normalized conditional rules
function extra_fees_sanitize_conditional_rules_1($input) {
    $sanitized_rules = array();
    if (is_array($input)) {
        foreach ($input as $rule) {
            $sanitized_rules[] = $rule;
        }
    }
    return $sanitized_rules;
}
function extra_fees_sanitize_conditional_rules_2($input) {
    $sanitized_rules = array();
    if (is_array($input)) {
        foreach ($input as $rule) {
            $sanitized_rules[] = $rule;
        }
    }
    return $sanitized_rules;
}
function extra_fees_sanitize_conditional_rules_3($input) {
    $sanitized_rules = array();
    if (is_array($input)) {
        foreach ($input as $rule) {
            $sanitized_rules[] = $rule;
        }
    }
    return $sanitized_rules;
}
?>
