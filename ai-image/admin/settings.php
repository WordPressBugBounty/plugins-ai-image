<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class BDTHEMES_OPENAI_ADMIN_SETTINGS_PAGE {
    public function __construct() {
        add_action('admin_init', [$this, 'admin_settings_page_init']);
        add_filter( 'plugin_action_links_' . plugin_basename( BDT_AI_IMAGE__FILE__ ), [$this, 'plugin_action_links'] );
    }

    public function admin_settings_page_init() {
        add_settings_section('bdthemes_openai_section', '<span class="ai-title-settings">OpenAI Settings</span>', [$this, 'openai_settings_section'], 'bdthemes-ai-image-options');
        add_settings_field('bdthemes_openai_api_key', __('OpenAI API Key', 'ai-image'), [$this, 'openai_content_generator_callback'], 'bdthemes-ai-image-options', 'bdthemes_openai_section');
        register_setting('bdthemes-ai-image-options', 'bdthemes_openai_api_key', ['sanitize_callback' => 'sanitize_text_field']);

        add_settings_section('bdthemes_gemini_section', '<span class="ai-title-settings">Google Gemini Settings</span>', [$this, 'gemini_settings_section'], 'bdthemes-ai-image-options');
        add_settings_field('bdthemes_gemini_api_key', __('Gemini API Key', 'ai-image'), [$this, 'gemini_api_key_callback'], 'bdthemes-ai-image-options', 'bdthemes_gemini_section');
        register_setting('bdthemes-ai-image-options', 'bdthemes_gemini_api_key', ['sanitize_callback' => 'sanitize_text_field']);

        add_settings_section('bdthemes_unsplash_section', '<span class="ai-title-settings">Unsplash Settings</span>', [$this, 'unsplash_settings_section'], 'bdthemes-ai-image-options');
        add_settings_field('bdthemes_unsplash_access_key', __('Unsplash Access Key', 'ai-image'), [$this, 'unsplash_api_key_callback'], 'bdthemes-ai-image-options', 'bdthemes_unsplash_section');
        register_setting('bdthemes-ai-image-options', 'bdthemes_unsplash_access_key', ['sanitize_callback' => function( $v ) { return sanitize_text_field( is_string( $v ) ? trim( $v ) : $v ); }]);

        add_settings_section('bdthemes_giphy_section', '<span class="ai-title-settings">Giphy Settings</span>', [$this, 'giphy_settings_section'], 'bdthemes-ai-image-options');
        add_settings_field('bdthemes_giphy_api_key', __('Giphy API Key', 'ai-image'), [$this, 'giphy_api_key_callback'], 'bdthemes-ai-image-options', 'bdthemes_giphy_section');
        register_setting('bdthemes-ai-image-options', 'bdthemes_giphy_api_key', ['sanitize_callback' => function( $v ) { return sanitize_text_field( is_string( $v ) ? trim( $v ) : $v ); }]);

        $provider_ids = array( 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy', 'openai', 'gemini' );
        $sanitize_provider = function( $v ) {
            if ( $v === true || $v === '1' || $v === 1 ) return '1';
            return '0';
        };
        foreach ( $provider_ids as $id ) {
            register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_provider_' . $id, array(
                'sanitize_callback' => $sanitize_provider,
            ) );
        }

        register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_max_upload_width', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_max_upload_height', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_default_provider', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_attribution', array( 'sanitize_callback' => $sanitize_provider ) );
        register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_hide_media_modal_tab', array( 'sanitize_callback' => $sanitize_provider ) );
        register_setting( 'bdthemes-ai-image-options', 'bdthemes_ai_image_custom_sizes', array( 'sanitize_callback' => array( $this, 'sanitize_custom_sizes' ) ) );
    }

    public function sanitize_custom_sizes( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $sanitized = array();
        foreach ( $value as $name => $size ) {
            if ( ! is_array( $size ) || empty( $size['width'] ) || empty( $size['height'] ) ) {
                continue;
            }
            $clean_name = sanitize_title( $name );
            if ( empty( $clean_name ) ) continue;
            $sanitized[ $clean_name ] = array(
                'width'  => absint( $size['width'] ),
                'height' => absint( $size['height'] ),
                'crop'   => ! empty( $size['crop'] ),
            );
        }
        return $sanitized;
    }
    public function openai_settings_section() {
    ?>
        <p>
            <?php 
            /* translators: %s: Link to OpenAI API keys page */
            printf(esc_html__('Please enter your Openai API key. You can get your API key from %s .', 'ai-image'), '<a href="https://platform.openai.com/api-keys" target="_blank">here</a>');
            ?>
            <br>
            <?php 
            /* translators: %s: Link to OpenAI documentation */
            printf(esc_html__('Learn more about %s', 'ai-image'), '<a href="https://platform.openai.com/" target="_blank">how to use OpenAI API</a>');
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

    public function unsplash_settings_section() {
        printf(
            '<p>%s <a href="https://unsplash.com/developers" target="_blank" rel="noopener">%s</a>.</p>',
            esc_html__('Get your free Unsplash Access Key (Client ID) from', 'ai-image'),
            esc_html__('Unsplash Developers', 'ai-image')
        );
    }

    public function unsplash_api_key_callback() {
        $api_key = get_option('bdthemes_unsplash_access_key');
        printf(
            '<input type="text" name="bdthemes_unsplash_access_key" value="%s" class="large-text" placeholder="" />',
            isset($api_key) ? esc_attr($api_key) : ''
        );
    }

    public function giphy_settings_section() {
        printf(
            '<p>%s <a href="https://developers.giphy.com/" target="_blank" rel="noopener">%s</a>.</p>',
            esc_html__('Get your free Giphy API key from', 'ai-image'),
            esc_html__('GIPHY Developers', 'ai-image')
        );
    }

    public function giphy_api_key_callback() {
        $api_key = get_option('bdthemes_giphy_api_key');
        printf(
            '<input type="text" name="bdthemes_giphy_api_key" value="%s" class="large-text" placeholder="" />',
            isset($api_key) ? esc_attr($api_key) : ''
        );
    }

    public function gemini_settings_section() {
        printf(
            '<p>%s <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">%s</a>.</p>',
            esc_html__('Get your free Google Gemini API key from', 'ai-image'),
            esc_html__('Google AI Studio', 'ai-image')
        );
    }

    public function gemini_api_key_callback() {
        $api_key = get_option('bdthemes_gemini_api_key');
        printf(
            '<input type="text" name="bdthemes_gemini_api_key" value="%s" class="large-text" placeholder="AIza..." />',
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
            'settings' => '<a href="' . esc_url( admin_url( 'options-general.php?page=ai-image-settings' ) ) . '" aria-label="' . esc_attr(__('Go to Settings', 'ai-image')) . '">' . __('Settings', 'ai-image') . '</a>',
        ];

        $plugin_meta = array_merge($plugin_meta, $row_meta);

        return $plugin_meta;
    }
}

new BDTHEMES_OPENAI_ADMIN_SETTINGS_PAGE();
