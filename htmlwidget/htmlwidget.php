<?php
/*
Plugin Name: HtmlWidget
Description: HtmlWidget, and other utilities and widgets for easier and modular site development
Author: Nikos M.
Version: 0.1.0
Author URI: https://github.com/foo123/HtmlWidget-WP
Text Domain: htmlwidget
*/

define('HTMLWIDGET_VERSION', '0.1.0');
define('HTMLWIDGET_DIR', dirname(__FILE__));
define('HTMLWIDGET_URI', untrailingslashit(plugins_url('/',__FILE__)));
if ( !defined("HTMLWIDGET_DEV") ) define("HTMLWIDGET_DEV", false);
if ( !defined("HTMLWIDGET_CDN") ) define("HTMLWIDGET_CDN", true);
if ( !defined("HTMLWIDGET_EXTRA") ) define("HTMLWIDGET_EXTRA", true);

function htmlwidget_import( )
{
    global $htmlwidget_importer;
    if ( empty($htmlwidget_importer) )
    {
        if ( !class_exists('Importer') ) require(HTMLWIDGET_DIR.'/classes/Importer.php');
        
        $wplang = get_option('WPLANG', '');
        if ( empty($wplang) || in_array($wplang, array('en','en_GB','en_US','en_AU','en_NZ')) ) $wplang = 'en';
        
        $htmlwidget_importer = Importer::_(HTMLWIDGET_DIR, HTMLWIDGET_URI);
        $htmlwidget_importer
            ->register('classes', array(
                array('HtmlWidget', 'HtmlWidget', './classes/HtmlWidget.php')
            ))
            ->register('assets', array(
                 array('scripts', 'jquery', 'WP')
                ,array('scripts', 'Importer', './classes/Importer.min.js')
                ,array('scripts', 'HtmlWidget', './classes/HtmlWidget.min.js')
                ,array('scripts', 'datex-locale', array(("en"===$wplang ? "" : ("
if ('undefined' !== typeof jQuery)
{
jQuery(function(\$){
if ( !$.htmlwidget ) return;
\$.htmlwidget.locale['DateX'] = ".$htmlwidget_importer->get("./i18n/datex/{$wplang}.json",array('default'=>'null')).";
});
}"))
                ))
                ,array('scripts', 'pikadaytime-locale', array(("en"===$wplang ? "" : ("
if ('undefined' !== typeof jQuery)
{
jQuery(function(\$){
if ( !$.htmlwidget ) return;
\$.htmlwidget.locale['Pikadaytime'] = ".$htmlwidget_importer->get("./i18n/pikadaytime/{$wplang}.json",array('default'=>'null')).";
});
}"))
                ), array('datex-locale'))
                ,array('scripts', 'datatables-locale', array(("en"===$wplang ? "" : ("
if ('undefined' !== typeof jQuery)
{
jQuery(function(\$){
if ( !$.htmlwidget ) return;
\$.htmlwidget.locale['DataTables'] = ".$htmlwidget_importer->get("./i18n/datatables/{$wplang}.json",array('default'=>'null')).";
});
}"))
                ))
                ,array('scripts', 'tinymce-locale', array(("en"===$wplang ? "" : ("
if ('undefined' !== typeof jQuery)
{
jQuery(function(\$){
if ( !$.htmlwidget ) return;
\$.htmlwidget.locale['tinymce'] = {uri:\"".$htmlwidget_importer->path_url("./i18n/tinymce/{$wplang}.js")."\"};
});
}"))
                ))
            ))
            ->on('enqueue-asset-htmlwidgets.css', 'htmlwidget_extra_assets')
            ->on('enqueue-asset-datex', 'htmlwidget_l10n')
            ->on('enqueue-asset-pikadaytime', 'htmlwidget_l10n')
            ->on('enqueue-asset-tinymce', 'htmlwidget_l10n')
            ->on('enqueue-asset-datatables', 'htmlwidget_l10n')
            ->on('enqueue-asset-jquery', 'htmlwidget_enqueue_wp')
            ->on('import-asset-jquery', 'htmlwidget_bypass')
        ;
        
        $htmlwidget_importer->load('HtmlWidget');
        HtmlWidget::enqueueAssets(array($htmlwidget_importer,'enqueue'));
        $htmlwidget_importer->register('assets', HtmlWidget::assets(array(
            'base'      => $htmlwidget_importer->path_url('./classes/'),
            'full'      => true,
            'jquery'    => false,
            'dev'       => HTMLWIDGET_DEV,
            'cdn'       => HTMLWIDGET_CDN
        )));
    }
}
function htmlwidget_extra_assets( $importer, $id, $type, $asset )
{
    if ( HTMLWIDGET_EXTRA )
    {
        $importer->enqueue('styles', 'fontawesome.css');
    }
}
function htmlwidget_enqueue_wp( $importer, $id, $type, $asset )
{
    if ( 'scripts' === $type ) wp_enqueue_script( $id );
    else if ( 'styles' === $type ) wp_enqueue_style( $id );
}
function htmlwidget_bypass( $importer, $id, $type, $asset, &$ret )
{
    $ret['return'] = '';
}
function htmlwidget_l10n( $importer, $id, $type, $asset, &$ret )
{
    $importer->enqueue('scripts', "{$id}-locale");
}

function html_widget_options( $options, $key=null, $val=null )
{
    return HtmlWidget::options( $options, $key, $val );
}
function html_widget( $widget, $attr=array(), $data=array() )
{
    htmlwidget_import( );
    echo HtmlWidget::widget( $widget, $attr, $data );
}
function htmlwidget_shortcode( $sc_atts, $content=null )
{
    //$sc_atts = shortcode_atts(array(), $sc_atts);
    $sc_atts = (array)$sc_atts;
    $widget = !empty($sc_atts['widget']) ? $sc_atts['widget'] : (!empty($sc_atts[0]) ? $sc_atts[0] : null);
    if ( empty($widget) ) return;
    $attr = array(); $data = array();
    foreach($sc_atts as $key=>$val)
    {
        $prefix = substr($key,0,5);
        if ( "attr-" === $prefix )
        {
            $attr[substr($key,5)] = $val;
        }
        elseif ( "data-" === $prefix )
        {
            $data[substr($key,5)] = $val;
        }
        else
        {
            // add it as attr parameter
            $attr[$key] = $val;
        }
    }
    html_widget($widget, $attr, $data);
    if ( null !== $content ) echo do_shortcode( $content );
}
add_shortcode('htmlwidget', 'htmlwidget_shortcode'); 

function htmlwidget_disable_template_tinymce( )
{
    echo '<style type="text/css">
#content-tmce, #content-tmce:hover, #qt_content_fullscreen{display: none !important;}
</style>';
    echo '<script type="text/javascript">
jQuery(function(){jQuery("#content-tmce").attr("onclick", null);});
</script>';
}
function htmlwidget_template_syntaxhighlight( )
{
    $current_screen = get_current_screen();
    if ( !$current_screen || !$current_screen->post_type ) return;
    if ( ('template' === $current_screen->post_type) ||
        apply_filters('htmlwidget_syhi_type', false, $current_screen->post_type)
    )
    {
        wp_enqueue_style( 'codemirror', HTMLWIDGET_URI.'/syhi/codemirror.min.css', null, HTMLWIDGET_VERSION );
        wp_enqueue_style( 'heshcss', HTMLWIDGET_URI.'/syhi/hesh.min.css', array('codemirror'), HTMLWIDGET_VERSION );
        wp_enqueue_script( 'codemirror', HTMLWIDGET_URI.'/syhi/codemirror.min.js', null, HTMLWIDGET_VERSION, true );
        wp_enqueue_script( 'heshjs', HTMLWIDGET_URI.'/syhi/hesh.min.js', array('codemirror'), HTMLWIDGET_VERSION, true );
    }
    if ( 'template' === $current_screen->post_type )
        add_action('admin_footer', 'htmlwidget_disable_template_tinymce');
}
add_action('current_screen', 'htmlwidget_template_syntaxhighlight');

function htmlwidget_widget_form( $widget, $return, $instance )
{
    if ( !isset( $instance['classes'] ) ) $instance['classes'] = '';

    echo "\t<p><label for='widget-{$widget->id_base}-{$widget->number}-classes'>".esc_html__( 'CSS Class', 'htmlwidget' ).":</label>
    <input type='text' name='widget-{$widget->id_base}[{$widget->number}][classes]' id='widget-{$widget->id_base}-{$widget->number}-classes' value='{$instance['classes']}' class='widefat' /></p>\n";

    return $instance;
}
add_action('in_widget_form', 'htmlwidget_widget_form', 10, 3);

function htmlwidget_update_widget( $instance, $new_instance )
{
    $instance['classes'] = $new_instance['classes'];
    return $instance;
}
add_filter('widget_update_callback', 'htmlwidget_update_widget', 10, 2);

function htmlwidget_widget_classes( $params )
{
    global $wp_registered_widgets, $widget_number;

    $arr_registered_widgets = wp_get_sidebars_widgets(); // Get an array of ALL registered widgets
    $this_id                = $params[0]['id']; // Get the id for the current sidebar we're processing
    $widget_id              = $params[0]['widget_id'];
    $widget_obj             = $wp_registered_widgets[$widget_id];
    $widget_num             = $widget_obj['params'][0]['number'];
    $widget_opt             = null;
    
    $active_plugins = apply_filters('active_plugins', get_option( 'active_plugins' ));
    
    if ( in_array('widget-logic/widget_logic.php', $active_plugins) )
    {
        // If Widget Logic plugin is enabled, use it's callback
        $widget_logic_options = get_option( 'widget_logic' );
        if ( isset( $widget_logic_options['widget_logic-options-filter'] ) && ('checked' === $widget_logic_options['widget_logic-options-filter']) )
        {
            $widget_opt = get_option( $widget_obj['callback_wl_redirect'][0]->option_name );
        }
        else
        {
            $widget_opt = get_option( $widget_obj['callback'][0]->option_name );
        }

    }
    elseif ( in_array('widget-context/widget-context.php', $active_plugins) )
    {
        // If Widget Context plugin is enabled, use it's callback
        $callback = isset($widget_obj['callback_original_wc']) ? $widget_obj['callback_original_wc'] : null;
        $callback = !$callback && isset($widget_obj['callback']) ? $widget_obj['callback'] : null;

        if ($callback && is_array($widget_obj['callback']))
        {
            $widget_opt = get_option( $callback[0]->option_name );
        }
    }
    else
    {
        // Default callback
        // Check if WP Page Widget is in use
        global $post;
        $id = ( isset( $post->ID ) ? get_the_ID() : null );
        if ( isset( $id ) && get_post_meta( $id, '_customize_sidebars' ) )
        {
            $custom_sidebarcheck = get_post_meta( $id, '_customize_sidebars' );
        }
        if ( isset( $custom_sidebarcheck[0] ) && ( 'yes' === $custom_sidebarcheck[0] ) )
        {
            $widget_opt = get_option( 'widget_'.$id.'_'.substr($widget_obj['callback'][0]->option_name, 7) );
        }
        elseif ( isset( $widget_obj['callback'][0]->option_name ) )
        {
            $widget_opt = get_option( $widget_obj['callback'][0]->option_name );
        }
    }

    // Add classes
    if ( isset( $widget_opt[$widget_num]['classes'] ) && !empty( $widget_opt[$widget_num]['classes'] ) )
    {
        // Add all classes
        $params[0]['before_widget'] = preg_replace( '/class="/', "class=\"{$widget_opt[$widget_num]['classes']} ", $params[0]['before_widget'], 1 );
    }

    // Add first, last, even, and odd classes
    if ( !$widget_number )
    {
        $widget_number = array();
    }

    if ( !isset( $arr_registered_widgets[$this_id] ) || !is_array( $arr_registered_widgets[$this_id] ) )
    {
        return $params;
    }

    if ( isset( $widget_number[$this_id] ) )
    {
        $widget_number[$this_id]++;
    }
    else
    {
        $widget_number[$this_id] = 1;
    }

    $class = 'class="';
    $class .= 'widget-'.$widget_number[$this_id].' ';
    $widget_first = 'widget-first';
    $widget_last = 'widget-last';
    if ( 1 === $widget_number[$this_id] )
    {
        $class .= $widget_first.' ';
    }
    if ( count( $arr_registered_widgets[$this_id] ) === $widget_number[$this_id] )
    {
        $class .= $widget_last.' ';
    }

    $widget_even = 'widget-even';
    $widget_odd  = 'widget-odd';
    $class .= ( ( $widget_number[$this_id] % 2 ) ? $widget_odd.' ' : $widget_even.' ' );

    $params[0]['before_widget'] = str_replace( 'class="', $class, $params[0]['before_widget'] );

    return $params;
}
add_filter('dynamic_sidebar_params', 'htmlwidget_widget_classes');

function htmlwidget_import_assets( )
{
    global $htmlwidget_importer;
    if ( !empty($htmlwidget_importer) )
    {
        echo $htmlwidget_importer->assets('styles');
        echo $htmlwidget_importer->assets('scripts');
    }
}
add_action('wp_print_footer_scripts', 'htmlwidget_import_assets');

function htmlwidget_init( )
{
    load_plugin_textdomain( 'htmlwidget', false, basename(HTMLWIDGET_DIR).'/i18n/' );
    
    // Templates
    $labels = array(
        'name' => __('Templates', 'htmlwidget'),
        'singular_name' => __('Template','htmlwidget'),
        'add_new' => __('Add New','htmlwidget'),
        'add_new_item' => __('Add New Template', 'htmlwidget'),
        'edit_item' => __('Edit Template', 'htmlwidget'),
        'new_item' => __('New Template', 'htmlwidget'),
        'view_item' => __('View Template', 'htmlwidget'),
        'search_items' => __('Search Template', 'htmlwidget'),
        'not_found' => __('No templates have been added yet', 'htmlwidget'),
        'not_found_in_trash' => __('Nothing found in Trash'),
        'parent_item_colon' => '',
    );
    $args = array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => false,
        'hierarchical'      => false,
        'rewrite'           => false,
        'supports'          => array('title','editor','custom-fields'),
        'has_archive'       => false,
		'menu_icon'         => 'dashicons-editor-table',
        'taxonomies'        => null
    );
    register_post_type('template', $args);
    
    $labels = array(
        'name'              => __( 'Template Tags', 'htmlwidget' ),
        'singular_name'     => __( 'Template Tag', 'htmlwidget' ),
        'search_items'      => __( 'Search Template Tags', 'htmlwidget' ),
        'all_items'         => __( 'All Template Tags', 'htmlwidget' ),
        'parent_item'       => __( 'Parent Template Tag', 'htmlwidget' ),
        'parent_item_colon' => __( 'Parent Template Tag:', 'htmlwidget' ),
        'edit_item'         => __( 'Edit Template Tag', 'htmlwidget' ),
        'update_item'       => __( 'Update Template Tag', 'htmlwidget' ),
        'add_new_item'      => __( 'Add New Template Tag', 'htmlwidget' ),
        'new_item_name'     => __( 'New Template Tag Name', 'htmlwidget' ),
        'menu_name'         => __( 'Template Tag', 'htmlwidget' )
    );
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => false
    );
    register_taxonomy('template_tag', array( 'template' ), $args);
}
add_action('init', 'htmlwidget_init');

