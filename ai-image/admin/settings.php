<?php
class BDTHEMES_OPENAI_ADMIN_SETTINGS_PAGE {
    public function __construct() {
        add_action('admin_init', [$this, 'admin_settings_page_init']);
        add_action('admin_menu', [$this, 'add_openai_menu_page']);

        add_filter( 'plugin_action_links_' . plugin_basename( BDT_AI_IMAGE__FILE__ ), [$this, 'plugin_action_links'] );
    }
    public function add_openai_menu_page() {
        add_options_page('AI Image', esc_html__('AI Image', 'ai-image'), 'manage_options', 'bdthemes-ai-image-options', [$this, 'create_admin_page']);
    }
    public function create_admin_page() {
?>
        <div class="wrap">
            <h1><?php esc_attr_e('AI Image', 'ai-image'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bdthemes-ai-image-options');
                do_settings_sections('bdthemes-ai-image-options');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    public function admin_settings_page_init() {
        add_settings_section('bdthemes_openai_section', '<span class="ai-title-settings">Openai Setings</span>', [$this, 'openai_settings_section'], 'bdthemes-ai-image-options');
        add_settings_field('bdthemes_openai_api_key', __('API Key', 'ai-image'), [$this, 'openai_content_generator_callback'], 'bdthemes-ai-image-options', 'bdthemes_openai_section');
        register_setting('bdthemes-ai-image-options', 'bdthemes_openai_api_key', ['sanitize_callback' => 'esc_attr']);
    }
    public function openai_settings_section() {
    ?>
        <p>
            <?php 
            printf(
                esc_html__('Please enter your Openai API key. You can get your API key from %s .', 'ai-image'),
                '<a href="https://platform.openai.com/api-keys" target="_blank">here</a>'
            );
            ?>
            <br>
            <?php 
            printf(
                esc_html__('Learn more about %s', 'ai-image'),
                '<a href="https://platform.openai.com/" target="_blank">how to use OpenAI API</a>'
            );
            ?>
        </p>
<?php
    }
    public function openai_content_generator_callback() {
        $api_key = get_option('bdthemes_openai_api_key');
        printf(
            '<input type="text" name="bdthemes_openai_api_key" value="%s" class="large-text" placeholder="sk-..." />',
            isset($api_key) ? esc_attr($api_key) : ''
        );
    }

    /**
	 * Plugin action links
	 * @access public
	 * @return array
	 */

	 public function plugin_action_links( $plugin_meta ) {

        $row_meta = [
            'settings' => '<a href="'.admin_url( 'upload.php?page=bdt-ai-media-tab' ) .'" aria-label="' . esc_attr(__('Go to Image Generator', 'ai-image')) . '" >' . __('Image Generator', 'ai-image') . '</b></a>',
        ];

        $plugin_meta = array_merge($plugin_meta, $row_meta);

        return $plugin_meta;
    }
}

new BDTHEMES_OPENAI_ADMIN_SETTINGS_PAGE();
