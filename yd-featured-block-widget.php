<?php
/**
 * @package YD_Featured_Box-Widget
 * @author Yann Dubois
 * @version 0.1.4
 */

/*
 Plugin Name: YD Featured Box Widget
 Plugin URI: http://www.yann.com/en/wp-plugins/yd-featured-block-widget
 Description: Customized square or rectangular image + title boxes or blocks to promote featured content on your homepage or in your sidebars. | Funded by <a href="http://www.nogent-citoyen.com">Nogent Citoyen</a>
 Version: 0.1.4
 Author: Yann Dubois
 Author URI: http://www.yann.com/
 License: GPL2
 */

/**
 * @copyright 2010  Yann Dubois  ( email : yann _at_ abc.fr )
 *
 *  Original development of this plugin was kindly funded by http://www.abc.fr
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 Revision 0.1.0:
 - Original beta release
 Revision 0.1.1:
 - Minor bug fixes, framework update
 Revision 0.1.2:
 - Minor bug fixes, framework update
 Revision 0.1.3:
 - Minor bug fixes, framework update
 Revision 0.1.4:
 - Minor bug fixes, framework update
 */

include_once( 'inc/yd-widget-framework.inc.php' );

$junk = new YD_Plugin( 
	array(
		'name' 				=> 'YD Featured Block',
		'version'			=> '0.1.3',
		'has_option_page'	=> true,
		'has_shortcode'		=> true,
		'has_widget'		=> true,
		'widget_class'		=> 'YD_FeaturedBoxWidget',
		'has_stylesheet'	=> true,
		'stylesheet_file'	=> 'css/yd_featured_box.css',
		'has_translation'	=> true,
		'translation_domain'=> 'yd-featured-block', // must be copied in the widget class!!!
		'translations'		=> array(
			array( 'English', 'Yann Dubois', 'http://www.yann.com/' ),
			array( 'French', 'Yann Dubois', 'http://www.yann.com/' )
		),		'initial_funding'	=> array( 'Nogent Citoyen', 'http://www.nogent-citoyen.com' ),
		'additional_funding'=> array(),
		'form_blocks'		=> array(
			//'bloc1' => array( 'Test'	=> 'text' ),
		),
		'option_field_labels'=>array(),
		'has_cache'			=> false,
		'option_page_text'	=> 'This plugin has no option... yet!',
		'backlinkware_text' => 'Featured Box Wordpress Plugin developed by YD',
		'plugin_file'		=> __FILE__
 	)
);

class YD_FeaturedBoxWidget extends YD_Widget {
    
	public $widget_name		= 'Featured Box';
	public $tdomain			= 'yd-featured-block'; // used for translation domain
	
	public $fields = array (
		//'title'		=> 'text',
		'box_title' => 'text',
		'url'		=> 'text',
		'image'		=> 'text',
		'subtitle'	=> 'text'
	);
	public $field_labels = array (
		//'title' 	=> 'Title:',
		'box_title' => 'Title:',
		'url'		=> 'Page address:',
		'image'		=> 'Image address:',
		'subtitle'	=> 'Subtitle:'
	);
	
    function display( $args, $instance ) {
    	?>
			<div 
				class="yd_featured_box" 
				onclick="location='<?php echo $instance['url']; ?>'"
			>
				<a 
					href="<?php echo $instance['url']; ?>"><img 
					src="<?php echo $instance['image']; ?>"
					title="<?php echo htmlentities( $instance['box_title'], ENT_COMPAT, 'UTF-8' ); ?>"
					alt="<?php echo htmlentities( $instance['box_title'], ENT_COMPAT, 'UTF-8' ); ?>"
					class="ydfb_image"
				/></a>
				<div class="ydfb_text_top"><h2><a 
					href="<?php echo $instance['url']; ?>"><?php echo $instance['box_title']; ?></a></h2>
				</div>
				<div class="ydfb_text_bottom"><h2><a 
					href="<?php echo $instance['url']; ?>"><?php echo $instance['subtitle']; ?></a></h2>
				</div>
			</div>
		<?php
    }
}
?>