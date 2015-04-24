<?php

/**
*
* @link              http://cheffism.com
* @since             1.0.1
* @package           Post_Series_Manager
*
* @wordpress-plugin
* Plugin Name:       Post Series Manager
* Plugin URI:        http://cheffism.com/post-series-manager/
* Description:       This plugin will help you manage and display post series more easily. You'll be able to create/assign series and display other posts in the series.
* Version:           1.0.1
* Author:            Jeffrey de Wit, Adam Soucie
* Author URI:        http://cheffism.com/
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       post-series-manager
* Domain Path:       /languages
*
*
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License, version 2, as 
* published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Post_Series_Manager {

    function __construct() {
        register_activation_hook( __FILE__, array( &$this, 'post_series_manager_activate' ) );
        register_deactivation_hook( __FILE__, array( &$this, 'post_series_manager_deactivate' ) );
        add_action( 'init', array( &$this, 'post_series_taxonomy' ) );
        add_action( 'plugins_loaded', array( &$this, 'post_series_i18n' ) );
        add_action( 'init', array( &$this, 'post_series_shortcodes' ) );
        add_filter( 'the_content', array( &$this, 'post_series_before' ) );
        add_filter( 'the_content', array( &$this, 'post_series_after' ) );
        add_action( 'pre_get_posts', array( &$this, 'post_series_sort_order' ) );
    }

    // register taxonomy and force rewrite flush when plugin is activated
    function post_series_manager_activate() {
        $this->post_series_taxonomy();
        flush_rewrite_rules();
    }

    // force rewrite flush when plugin is deactivated
    function post_series_manager_deactivate() {
        flush_rewrite_rules();
    }


    public function post_series_taxonomy() {
        register_taxonomy(
            'post-series',
            'post',
            array(
               'label' => __( 'Post Series' ),
               'rewrite' => array( 'slug' => 'post-series' ),
               'labels' => array( 'name' => __( 'Post Series' ),
                  'singular_name' => __( 'Post Series' ),
                  'all_items' => __( 'All Post Series' ),
                  'edit_item' => __( 'Edit Post Series' ),
                  'view_item' => __( 'View Post Series' ),
                  'update_item' => __( 'Update Post Series' ),
                  'add_new_item' => __( 'Add New Post Series' ), 
                  'new_item_name' => __( 'New Post Series Name' ),
                  'search_items' => __( 'Search Post Series' ),
                  'popular_items' => __( 'Popular Post Series' ),
                  'separate_items_with_commas' => __( 'Separate post series with commas' ),
                  'add_or_remove_items' => __( 'Add or remove post series' ),
                  'choose_from_most_used' => __( 'Choose from most used post series' ),
                  'not_found' => __( 'No post series found' ) )
               )
        );
    }

    public function post_series_i18n() {
        load_plugin_textdomain(
            'post-series-manager',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
            );
    }

    public function post_series_shortcodes() {
        add_shortcode('post_series_block', array( &$this, 'post_series_block_function') );
        add_shortcode('post_series_nav', array( &$this, 'post_series_nav_function') );
    }

    // post_series_manager shortcode output
    public function post_series_block_function() {
        global $post;

        $shortcode_html = NULL;
        $all_series = get_the_terms( $post->ID, 'post-series' );

        if ( $all_series ) {
            foreach( $all_series as $series ) {
               $series_text = __('This post is part of the series');
               $series_block = '<div class="post-series-manager-block"><p>%s %s</p>%s';
               $series_link = sprintf('<a href="%s">%s</a>', get_term_link($series), $series->name);

               if( is_single() )
               {
                  $series_list_HTML = $this->get_series_list_HTML( $series );
                  $shortcode_html .= sprintf($series_block, $series_text, $series_link, $series_list_HTML);
              }
              else
              {
                  $shortcode_html .= sprintf($series_block, $series_text, $series_link);
              }
          }
      }
      return $shortcode_html;
    }

    /**
    * Generates the markup for the Post Series list.
    *
    * @since  1.0.0
    * 
    * @param  object $series The post series to work through
    * @return string $series_list_HTML Completed HTML string of all the series lists
    */
    public function get_series_list_HTML( $series )
    {
        $current_post_id = get_the_ID();
        $series_list_HTML = '<p>' . __('Other posts in this series:') . '</p><ol class="post-series-manager-post-list">';

        $args = array(
            'tax_query' => array(
               array(
                  'taxonomy' => 'post-series',
                  'field' => 'slug',
                  'terms' => $series->name
                  )
               ),
            'order' => 'ASC'
            );

        $series_posts = get_posts( $args );

        foreach ($series_posts as $series_post ) 
        {
            $post_title 	= get_the_title( $series_post->ID );
            $post_permalink	= get_permalink( $series_post->ID );

            $list_item = "<li class='post-series-manager-post'>%s</li>";

            if ( $series_post->ID === $current_post_id ) {
               $title_markup = $post_title . __(' (Current)');
           } else {
               $title_markup = "<a href='$post_permalink'>" . $post_title . "</a>";
           }

           $series_list_HTML .= sprintf($list_item, $title_markup);
       }

       $series_list_HTML .= '</ol>';

       return $series_list_HTML;
    }

    public function post_series_nav_function() {
        global $post;

        $shortcode_html = NULL;
        $all_series = get_the_terms( $post->ID, 'post-series' );

        if ( $all_series ) {
            $series_text = __('Continue reading this series:');
            $series_nav = '<div class="post-series-nav"><p>%s<br /> %s</p></div>';
            $next = get_next_post_link('%link', '%title', true, NULL, 'post-series' );

            if ( $next && is_single() ) {
               $shortcode_html = sprintf($series_nav, $series_text, $next);
           }
       }

       return $shortcode_html;

    }

    // Automatically add shortcodes to post content, before and after the post content
    public function post_series_before( $content ) {
        if( is_single() ) {
            $series_box = do_shortcode("[post_series_block]");
            $content = $series_box . $content;
        }

        return $content;
    }

    public function post_series_after( $content ) {
        if( is_single() ) {
            $series_nav = do_shortcode("[post_series_nav]");
            $content = $content . $series_nav;
        }

        return $content;
    }

    // Reverse sort order, since part 1 is generally older than part X
    public function post_series_sort_order( $query ) {
        if( ( $query->is_main_query() ) && ( is_tax('post-series') ) ) {
            $query->set( 'order', 'ASC' );
        }
    }
}

$post_series_manager = new Post_Series_Manager();
