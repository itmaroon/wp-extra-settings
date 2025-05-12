<?php
/*
Plugin Name:  WP EXTRA SETTINGS
Description:  It provides a function that allows you to configure settings that are not provided in the WordPress admin screen using a GUI.
Requires at least: 6.3
Requires PHP:      8.2
Version:      0.1.0
Author:       Web Creator ITmaroon
Author URI:   https://itmaroon.net
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wp-extra-settings
Domain Path:  /languages
*/

if (! defined('ABSPATH')) exit;

require_once __DIR__ . '/vendor/itmar/loader-package/src/register_autoloader.php';

// プラグイン有効化時の処理
register_activation_hook(__FILE__, function () {

    add_option('itmar_post_label', __('Posts', 'wp-extra-settings'));
    add_option('itmar_post_archive_enabled', 0);
    add_option('itmar_post_archive_slug', 'archivepage');
    add_option('itmar_post_supports', ['title', 'editor', 'author', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'post-formats']);
});

// プラグイン無効化時の処理
register_deactivation_hook(__FILE__, function () {

    delete_option('itmar_post_label');
    delete_option('itmar_post_archive_enabled');
    delete_option('itmar_post_archive_slug');
    delete_option('itmar_post_supports');
});

//翻訳ファイルの読み込み
add_action('init', function () {
    load_plugin_textdomain('wp-extra-settings', false, basename(dirname(__FILE__)) . '/languages');
});

//独自CSSとJSをエンキュー
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'settings_page_itmar-extrasetting-settings') {
        $style_path = plugin_dir_path(__FILE__) . 'css/setting_style.css';
        $script_path = plugin_dir_path(__FILE__) . 'assets/js/tab_setting.js';
        wp_enqueue_style('itmar-admin-settings', plugins_url('css/setting_style.css', __FILE__), [], filemtime($style_path));
        wp_enqueue_script('itmar-admin-settings', plugins_url('assets/js/tab_setting.js', __FILE__), ['jquery'], filemtime($script_path), true);
    }
});


// 管理画面に設定メニューを追加
add_action('admin_menu', 'itmar_extrasetting_menu');
function itmar_extrasetting_menu()
{
    add_options_page(
        'Extra Settings', // ページタイトル
        'Extra Settings', // メニュータイトル
        'manage_options', // 必要な権限
        'itmar-extrasetting-settings', // メニューのスラッグ
        'itmar_extrasetting_settings_page' // 表示用の関数
    );
}

// 設定保存処理 (admin_post)
add_action('admin_post_itmar_save_settings', 'itmar_handle_save_settings');
function itmar_handle_save_settings()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized user', 'wp-extra-settings'));
    }
    check_admin_referer('itmar_setting_nonce');

    // 設定保存
    \Itmar\WpSettingClassPackage\ItmarModifyPost::get_instance()->save_settings();
    //\Itmar\WpSettingClassPackage\ItmarRedirectControl::get_instance()->save_settings();
    \Itmar\WpSettingClassPackage\ItmarRevisionClass::get_instance()->save_settings();
    \Itmar\WpSettingClassPackage\ItmarSEOSettings::get_instance()->save_settings();
    \Itmar\WpSettingClassPackage\ItmarSecuritySettings::get_instance()->save_settings();

    flush_rewrite_rules();


    // 正しいリダイレクト先
    $redirect_url = add_query_arg([
        'page' => 'itmar-extrasetting-settings',
        'settings-updated' => 'true'
    ], admin_url('options-general.php'));

    wp_redirect($redirect_url);
    exit;
}

function itmar_extrasetting_settings_page()
{
?>
    <div class="wrap">
        <h1>Extra Settings</h1>
        <!-- Notice 表示 -->
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Your settings saved', 'wp-extra-settings'); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('itmar_setting_nonce'); ?>
            <input type="hidden" name="action" value="itmar_save_settings" />

            <!-- タブナビ -->
            <div class="itmar-settings-tabs">
                <div class="itmar-settings-tabs__nav">
                    <button type="button" class="itmar-settings-tabs__nav-button active" data-tab="tab-general"><?php echo __("General", "wp-extra-settings") ?></button>
                    <button type="button" class="itmar-settings-tabs__nav-button" data-tab="tab-seo"><?php echo __("SEO", "wp-extra-settings") ?></button>
                    <button type="button" class="itmar-settings-tabs__nav-button" data-tab="tab-security"><?php echo __("Security", "wp-extra-settings") ?></button>
                </div>
                <div class="itmar-settings-tabs__submit">
                    <?php submit_button(__("Save Settings", "wp-extra-settings"), 'primary', 'submit', false); ?>
                </div>
            </div>

            <!-- 各タブコンテンツ -->
            <div class="itmar-settings-content">
                <div id="tab-general" class="itmar-settings-content__section active">
                    <?php
                    \Itmar\WpSettingClassPackage\ItmarRedirectControl::get_instance()->render_settings_section();
                    \Itmar\WpSettingClassPackage\ItmarModifyPost::get_instance()->render_settings_section();
                    \Itmar\WpSettingClassPackage\ItmarRevisionClass::get_instance()->render_settings_section();
                    ?>
                </div>
                <div id="tab-seo" class="itmar-settings-content__section">
                    <?php
                    \Itmar\WpSettingClassPackage\ItmarSEOSettings::get_instance()->render_settings_section();
                    ?>
                </div>
                <div id="tab-security" class="itmar-settings-content__section">
                    <?php
                    \Itmar\WpSettingClassPackage\ItmarSecuritySettings::get_instance()->render_settings_section();
                    ?>
                </div>
            </div>

            <!-- 最後に下部にも念のため -->
            <div style="margin-top:20px;">
                <?php submit_button(__("Save Settings", "wp-extra-settings")); ?>
            </div>

        </form>
    </div>
<?php
}

// 設定に基づいてコンポーネントを初期化
add_action('plugins_loaded', 'itmar_extrasetting_initialize');
function itmar_extrasetting_initialize()
{
    //リダイレクト設定
    \Itmar\WpsettingClassPackage\ItmarRedirectControl::get_instance();

    // 投稿タイプ設定
    \Itmar\WpsettingClassPackage\ItmarModifyPost::get_instance();

    //リビジョン設定
    \Itmar\WpsettingClassPackage\ItmarRevisionClass::get_instance();

    //セキュリティ設定
    \Itmar\WpsettingClassPackage\ItmarSecuritySettings::get_instance();
    //SEO関連設定
    \Itmar\WpsettingClassPackage\ItmarSEOSettings::get_instance();
}
