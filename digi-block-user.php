<?php
/*
 * Plugin Name: Digi Block User
 * Description: Block Users Accounts
 * Version: 1.0.0
 * Author: SejiL
 * Author URI: https://sejil.me
 */

defined('ABSPATH') || exit();
define('DBU_CSS_URL', plugins_url('css', __FILE__));
define('DBU_LANG_DIR', basename(dirname(__FILE__)) . '/languages/');

//Show User Status Checkbox
function dbu_block_checkbox($user) {
    wp_enqueue_style('user_status_style', DBU_CSS_URL . '/style.css');
    if (current_user_can('edit_users')):
        ?>
        <table class="form-table" id="block_user">
            <tr>
                <th>
                    <label for="user_status"><?php _e("User Account Status", 'digi-block-user'); ?></label>
                </th>
                <td>
                    <label class="tgl">  
                        <input type="checkbox" name="user_status" value="Deactive" id="user_status"  <?php checked(get_user_meta($user->ID, 'user_status', true), 'Deactive'); ?>>
                        <span data-off="<?php _e("Enabled", 'digi-block-user'); ?>" data-on="<?php _e("Disabled", 'digi-block-user'); ?>"></span>
                    </label>
                    <span class="description"><?php _e("Green: Account is Active. / Red: Account is Blocked.", 'digi-block-user'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    endif;
    return;
}

add_action('show_user_profile', 'dbu_block_checkbox');
add_action('edit_user_profile', 'dbu_block_checkbox');

//Session
function dbu_destroy_user_session($user_id) {
    $sessions = WP_Session_Tokens::get_instance($user_id);
    $sessions->destroy_all();
}

//Save User Status
function dbu_save_user_status($user_id) {
    if (current_user_can('edit_users')) {
        update_user_meta($user_id, 'user_status', $_POST['user_status']);
    }
    dbu_destroy_user_session($user_id);
    return;
}

add_action('personal_options_update', 'dbu_save_user_status');
add_action('edit_user_profile_update', 'dbu_save_user_status');

//Login Error
function dbu_login_authenticate($user, $username) {
    $userinfo = get_user_by('login', $username);
    if (!$userinfo) {
        return $user;
    } elseif (get_user_meta($userinfo->ID, 'user_status', true) == 'Deactive') {
        $error = new WP_Error();
        $error->add('account_disabled', __('Your account is disabled.', 'digi-block-user'));
        return $error;
    }
    return $user;
}

add_filter('authenticate', 'dbu_login_authenticate', 99, 2);

//Show User Status Columns
function dbu_user_status_column($column) {
    $column['user_status'] = __('User Status', 'digi-block-user');
    return $column;
}

add_filter('manage_users_columns', 'dbu_user_status_column');

function dbu_show_user_status($value, $column, $userid) {
    wp_enqueue_style('user_status_style', DBU_CSS_URL . '/style.css');
    $active = __('Active', 'digi-block-user');
    $block = __('Blocked', 'digi-block-user');
    $user_status = get_user_meta($userid, 'user_status', true);
    if ('user_status' == $column) {
        if (!empty($user_status)) {
            return "<span class='user-status-deactive'>" . $block . "</span>";
        } else {
            return "<span class='user-status-active'>" . $active . "</span>";
        }
    }
    return $value;
}

add_action('manage_users_custom_column', 'dbu_show_user_status', 10, 3);
