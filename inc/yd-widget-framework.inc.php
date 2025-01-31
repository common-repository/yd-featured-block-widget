<?php
if ( !class_exists( 'YD_Plugin' ) ) {
    class YD_Plugin {
	    function __construct( $p_arr ) {
	    	// php5 constructor
			$this->YD_Plugin( $p_arr );
		}
		function YD_Plugin( $p_arr ) {
			// Constructor
			$this->plugin_name 		= $p_arr['name'];
			$this->version			= $p_arr['version'];
			$this->sanitized_name	= sanitize_title( $this->plugin_name );
			$this->option_key		= $this->sanitized_name;
			$this->has_option_page	= $p_arr['has_option_page'];
			$this->has_shortcode	= $p_arr['has_shortcode'];
			$this->has_widget		= $p_arr['has_widget'];
			$this->widget_class		= $p_arr['widget_class'];
			$this->has_cron			= $p_arr['has_cron'];
			$this->crontab			= $p_arr['crontab'];
			$this->has_stylesheet	= $p_arr['has_stylesheet'];
			$this->stylesheet_file	= $p_arr['stylesheet_file'];
			$this->has_translation 	= $p_arr['has_translation'];
			$this->tdomain			= $p_arr['translation_domain'];
			$this->translations		= $p_arr['translations']; // array of arrays
			$this->plugin_file 		= $p_arr['plugin_file'];
			$this->plugin_dir 		= dirname( plugin_basename( $this->plugin_file ) );
			$this->initial_funding 	= $p_arr['initial_funding']; // array( name, url )
			$this->additional_funding=$p_arr['additional_funding']; // array of arrays
			$this->form_blocks		= $p_arr['form_blocks'];
			$this->form_blocks['Other options:'] = array( 'disable_backlink' => 'bool' );
			$this->option_field_labels=$p_arr['option_field_labels'];
			$this->form_add_actions	= $p_arr['form_add_actions'];
			$this->has_cache		= $p_arr['has_cache'];
			$this->option_page_text = $p_arr['option_page_text'];
			$this->backlinkware_text= $p_arr['backlinkware_text'];
			$this->support_url 		= 'http://www.yann.com/en/wp-plugins/' . $this->sanitized_name;
			
			register_activation_hook( $this->plugin_file, array( &$this, 'activate' ) );
			
			if( $this->has_option_page )	add_action( 'admin_menu', array( &$this, 'create_menu' ) );
			if( $this->has_widget ) 		add_action( 'widgets_init', array( &$this, 'load_widget' ) );
			if( $this->has_cron )			$this->schedule_cron();
			if( $this->has_stylesheet ) 	add_action( 'wp_print_styles', array( &$this, 'add_stylesheet' ) );
			if( $this->has_translation ) 	add_action( 'plugins_loaded', array( &$this, 'load_translation' ) );
			add_action( 'wp_footer', array( &$this, 'add_linkware' ) );
		}
		function activate() {
			if( $this->has_cron ) {
				if( !wp_next_scheduled( 'yd_hourly_event' ) ) {
					wp_schedule_event( time(), 'hourly', 'yd_hourly_event' );
				}
				if( !wp_next_scheduled( 'yd_daily_event' ) ) {
					wp_schedule_event( time(), 'daily', 'yd_daily_event' );
				}
			}
		}
		function schedule_cron() {
			foreach( $this->crontab as $sched => $entry ) {
				$event = 'yd_' . $sched . '_event';
				add_action( $event, $entry );
				//echo $event . '<br/>';
			}
			//exit(0);
		}
		function load_translation () {
			load_plugin_textdomain(
				$this->tdomain,
				'wp-content/plugins/' . $this->plugin_dir . '/languages', 
				$this->plugin_dir . '/languages' 
			);
			if( $this->has_option_page ) {
				load_plugin_textdomain(
					'yd-options-page', // must be same as in YD_OptionPage tpl_tdomain private setting 
					'wp-content/plugins/' . $this->plugin_dir . '/languages', 
					$this->plugin_dir . '/languages' 
				);
			}
		}
		function load_widget() {
			register_widget( $this->widget_class );
		}
		function create_menu() {
			add_options_page(
				__( $this->plugin_name, $this->tdomain ), 
				__( $this->plugin_name, $this->tdomain ),
				'manage_options',
				$this->sanitized_name, 
				array( &$this, 'plugin_options' )
			);	
		}
    	function plugin_options() {
			if ( !current_user_can( 'manage_options' ) )  {
	   			wp_die( __('You do not have sufficient permissions to access this page.') );
	  		}
	  		$op = new YD_OptionPage();
	  		$op->title = __( $this->plugin_name, $this->tdomain );
	  		$op->sanitized_name = $this->sanitized_name;
	  		$op->yd_logo = 'http://www.yann.com/' . $this->sanitized_name . '-logo.gif';
	  		$op->support_url = $this->support_url;
	  		$op->initial_funding = $this->initial_funding; // array( name, url )
	  		$op->additional_funding = $this->additional_funding; // array of arrays
	  		$op->version = $this->version;
	  		$op->translations = $this->translations;
	  		$op->plugin_dir = $this->plugin_dir;
	  		$op->has_cache = $this->has_cache;
	  		$op->option_page_text = $this->option_page_text;
	  		$op->plg_tdomain = $this->tdomain;
	  		$this->option_field_labels['disable_backlink'] = 'Disable backlink in the blog footer:';
	  		$op->option_field_labels = $this->option_field_labels;
	  		$op->form_add_actions = $this->form_add_actions;
	  		if( $_GET['do'] || $_POST['do'] ) $op->do_action( &$this );
	  		$op->header();
	  		$op->option_values = get_option( $this->option_key );
	  		$op->sidebar();
	  		$op->form_header();
	  		foreach( (array)$this->form_blocks as $block_name => $block_fields ) {
	  			$op->form_block( $block_name, $block_fields );
	  		}
    		foreach( (array)$this->form_add_actions as $action_name => $action ) {
	  			$op->form_action_button( $action_name, $action );
	  		}
	  		//$op->form_block( 'Other options:', array( 'disable_backlink' => 'bool' ) );
	  		$op->form_footer();
	  		if( $this->has_cron ) $op->cron_status( $this->crontab );
	  		$op->footer();
		}
		function add_stylesheet() {
    		$myStyleUrl = WP_PLUGIN_URL . '/' . $this->plugin_dir . '/' . $this->stylesheet_file;
    		$myStyleFile = WP_PLUGIN_DIR . '/' . $this->plugin_dir . '/' . $this->stylesheet_file;
    		/*
    		echo $myStyleFile;
    		exit( 0 );
    		*/
     		if ( file_exists( $myStyleFile ) ) {
		        wp_register_style( 'myStyleSheets', $myStyleUrl );
        		wp_enqueue_style( 'myStyleSheets' );
    		}
		}
		function add_linkware() {
			$options = get_option( $this->option_key );
			if( $options['disable_backlink'] ) echo "<!--\n";
			echo '<p style="text-align:center" class="yd_linkware"><small><a href="' .
				$this->support_url . '">' . $this->backlinkware_text . '</a></small></p>';
			if( $options['disable_backlink'] ) echo "\n-->";
		}
		
		/** actions **/
		
		function clear_cache() {
			
		}
		
		function reset_options() {
			
		}
		
		function update_options( $params ) {
		
			$options = $newoptions = get_option( $this->option_key );

			foreach( $this->form_blocks as $block_name => $fields ) {

				foreach( $fields as $field => $type ) {
					$key = $field;
					$value = $params[$key];

					// reset the value
					if( is_array( $newoptions[$key] ) ) {
						$newoptions[$key] = array();
					} else {
						$newoptions[$key] = '';
					}
	
					if( !is_array( $value ) ) {
						$clean_value = html_entity_decode( stripslashes( $value ) );
						$newoptions[$key] = $clean_value;
					} else {
						//it's a multi-valued field, make an array...
						if( !is_array( $newoptions[$key] ) )
							$newoptions[$key] = array();
						foreach( $value as $v )
							if( is_array( $v ) ) {
								$newoptions[$key][] = $v;
							} else {
								$newoptions[$key][] = html_entity_decode( stripslashes( $v ) );
							}
					}
					
				}
				
			}
			if ( $options != $newoptions ) {
				$options = $newoptions;
				update_option( $this->option_key, $options );
				return true;
			} else {
				return false;
			}
		}
    }
}

if ( !class_exists( 'YD_Widget' ) ) {
	class YD_Widget extends WP_Widget {
		
		public $widget_name = 'Default Widget Name';
		
		public $fields = array (
			'title' => 'text'
		);
		public $field_labels = array (
			'title' => 'Title:'
		);
		
	    /** constructor */
	    function YD_Widget() {
	        parent::WP_Widget( false, $name = __( $this->widget_name, $this->tdomain ) );	
	    }
	    
	    function display( $args, $instance ) {
	    }
	
	    /** @see WP_Widget::widget */
	    function widget( $args, $instance ) {		
	        extract( $args );
	        $title = apply_filters( 'widget_title', $instance['title'] );
	        ?>
	              <?php echo $before_widget; ?>
	                  <?php if ( $title )
	                        echo $before_title . $title . $after_title; ?>
	                  <?php $this->display( $args, $instance ) ?>
	              <?php echo $after_widget; ?>
	        <?php
	    }
	    
	    /** @see WP_Widget::update */
	    function update( $new_instance, $old_instance ) {				
			$instance = $old_instance;
			foreach( $this->fields as $field => $type ) {
				$instance[ $field ] = strip_tags( $new_instance[ $field ] );
			}
	        return $instance;
	    }
	    
	    /** @see WP_Widget::form */
	    function form( $instance ) {
	    	foreach( $this->fields as $field => $type ) {
		        $value = esc_attr( $instance[ $field ] );
		        $label = __( $this->field_labels[ $field ], $this->tdomain ); //translation is applied here!
		        $field_id = $this->get_field_id( $field );
		        $name = $this->get_field_name( $field );
		        if( $type == 'text' ) {
		        	?>
		            <p>
			            <label for="<?php echo $field_id; ?>"><?php echo $label; ?>
			            	<input 
			            		class="widefat" id="<?php echo $field_id; ?>" 
			            		name="<?php echo $name; ?>" 
			            		type="text" 
			            		value="<?php echo $value; ?>" 
			            	/>
			            </label>
		            </p>
		            <?php
		        }
		        if( $type == 'image' ) {
		        	// This does not work yet
		        	?>
		        	<p>
		        		<a 
		        			href="media-upload.php?type=image&amp;TB_iframe=1" 
		        			id="yd_add_image" 
		        			class="thickbox" 
		        			title="Add image"
		        			onclick="tb_show( '', tinymce.DOM.get('yd_add_image').href );"
		        		>
		        			<img 
		        				src="/wp-admin/images/media-button-image.gif?ver=20100531" 
		        				alt="Add image"
		        			>
		        		</a>
		        	</p>
		        	<?php	
		        }
	    	}
	    }
	}
}

if ( !class_exists( 'YD_OptionPage' ) ) {
	class YD_OptionPage {

		// defaults, should be overridden
		public $title = 'Default Title';
		public $sanitized_name = 'default-name';
		public $update_msg = '';
		public $error_msg = '';
		public $support_url = 'http://www.yann.com/en/wp-plugins/';
		public $yd_logo = 'http://www.yann.com/yd-default-logo.gif';
		public $initial_funding = array( 'Yann.com', 'http://www.yann.com' );
		public $additional_funding = array();
		public $version = '0';
		public $translations = array();
		public $plugin_dir = '';
		public $option_page_text = '';
		public $plg_tdomain = '';	// plugin-specific translation domain
		public $option_field_labels = array();
		public $option_values = array();

		// init settings
		private $tpl_tdomain = 'yd-options-page';	// template translation domain
		private	$jstext	= 'This will disable the link in your blog footer. If you are using this plugin on your site and like it, did you consider making a donation? - Thanks.';

		function header() {
			$jstext = preg_replace( "/'/", "\\'", __( $this->jstext, $this->tpl_tdomain ) );
			?>
				<script type="text/javascript">
				<!--
				function donatemsg( value ) {
					if( value ) { alert( '<?php echo $jstext ?>' ) };
				}
				//-->
				</script>
			<?php
			echo '<div class="wrap">';
			echo '<h2>' . $this->title . '</h2>';
			if( $this->update_msg ) {
				echo '<div class="updated">';
				echo $this->update_msg;
				echo '</div>';
			}
			if( $this->error_msg ) {
				echo '<div class="error">';
				echo $this->error_msg;
				echo '</div>';
			}
			echo '<div class="metabox-holder has-right-sidebar">';
		}
		
		function sidebar() {	
			echo '<div class="inner-sidebar">';
			echo '<div class="meta-box-sortabless ui-sortable">';
			$this->logo_block();
			$this->credits_block();
			$this->support_block();
			echo '</div>'; // / meta-box-sortabless ui-sortable
			echo '</div>'; // / inner-sidebar
			
		}

		function logo_block() {
			echo '<div class="postbox">';
			echo '<h3 class="hndle">' . __( 'Considered donating?', 'yd-options-page' ) . '</h3>';
			echo '<div class="inside" style="text-align:center;"><br/>';
			echo '<a href="' . $this->support_url . '" target="_blank" title="Plugin FAQ">'
			. '<img src="' . $this->yd_logo . '" alt="YD logo" /></a>'
			. '<br/><small>' . __( 'Enjoy this plugin?', 'yd-options-page' ) . '<br/>' . __( 'Help me improve it!', 'yd-options-page' ) . '</small><br/>'
			. '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">'
			. '<input type="hidden" name="cmd" value="_s-xclick"/>'
			. '<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----'
			. '" />'
			. '<input type="image" src="https://www.paypal.com/' . __( 'en_US', 'yd-options-page' ) . '/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />'
			. '<img alt="" border="0" src="https://www.paypal.com/' . __( 'en_US', 'yd-options-page' ) . '/i/scr/pixel.gif" width="1" height="1" />'
			. '</form>'
			. '<small><strong>' . __( 'Thanks', 'yd-options-page' ) . ' - Yann.</strong></small><br/><br/>';
			
			echo '</div>'; // / inside
			echo '</div>'; // / postbox
		}
		
		function credits_block() {
			echo '<div class="postbox">';
			echo '<h3 class="hndle">' . __( 'Credits', 'yd-options-page' ) . '</h3>';
			echo '<div class="inside" style="padding:10px;">';
			echo 'v.' . $this->version . '<br/>';
			echo '<b>' . __( 'Funding', $this->tpl_tdomain ) . '</b>';
			echo '<ul>';
			echo '<li>' . __( 'Initial:', $this->tpl_tdomain ) . ' <a href="' . $this->initial_funding[1] . '">' . $this->initial_funding[0] . '</a></li>';
			if( $this->additional_funding ) {
				foreach( $this->additional_funding as $funding ) {
					echo '<li>' . __( 'Additional:', 'yd-options-page' ) . 
						'  <a href="' . $this->$funding[1] . '">' . $this->$funding[0] . '</a></li>';
				}
			}
			echo '</ul>';
			if( $this->translations ) {
				echo '<b>' . __( 'Translations', $this->tpl_tdomain ) . '</b>';
				echo '<ul>';
				foreach( $this->translations as $translation ) {
					echo '<li>' . __( $translation[0] . ':', $this->tpl_tdomain ) . 
						' <a href="' . $translation[2] . '">' . $translation[1] . '</a></li>';
				}
				echo '</ul>';
			}
			echo __( 'If you want to contribute to a translation of this plugin, please drop me a line by ', 'yd-options-page' );
			echo '<a href="mailto:yann@abc.fr">' . __('e-mail', 'yd-options-page' ) . '</a> ';
			echo __( 'or leave a comment on the ', 'yd-options-page' );
			echo '<a href="' . $this->support_url . '">' . __( 'plugin\'s page', 'yd-options-page' ) . '</a>. ';
			echo __( 'You will get credit for your translation in the plugin file and the documentation page, ', 'yd-options-page' );
			echo __( 'as well as a link on this page and on my developers\' blog.', 'yd-options-page' );
				
			echo '</div>'; // / inside
			echo '</div>'; // / postbox
		}
		
		function support_block() {
			echo '<div class="postbox">';
			echo '<h3 class="hndle">' . __( 'Support' ) . '</h3>';
			echo '<div class="inside" style="padding:10px;">';
			echo '<b>' . __( 'Free support', 'yd-options-page' ) . '</b>';
			echo '<ul>';
			echo '<li>' . __( 'Support page:', 'yd-options-page' );
			echo ' <a href="' . $this->support_url . '">' . __( 'here.', 'yd-options-page' ) . '</a>';
			echo ' ' . __( '(use comments!)', 'yd-options-page' ) . '</li>';
			echo '</ul>';
			echo '<p><b>' . __( 'Professional consulting', 'yd-options-page' ) . '</b><br/>';
			echo '<a href="http://www.yann.com/en/about">';
			echo '<img src="' . WP_PLUGIN_URL . '/' . $this->plugin_dir . '/img/yann_80x80.jpg" style="width:80px;height:80px;float:left;margin-right:4px;" alt="Yann" />';
			echo '</a>';
			echo __( 'I am available as an experienced free-lance Wordpress plugin developer and web consultant. ', 'yd-options-page' );
			echo __( 'Please feel free to <a href="mailto:yann@abc.fr">check with me</a> for any adaptation or specific implementation of this plugin. ', 'yd-options-page' );
			echo '<a href="http://www.yann.com/en/custom-developments">';
			echo __( 'Or for any WP-related custom development or consulting work. Hourly rates available.', 'yd-options-page' ) . '</a></p>';
			echo '</div>'; // / inside
			echo '</div>'; // / postbox
		}
	
		function form_header() {
			echo '<div class="has-sidebar sm-padded">';
			echo '<div id="post-body-content" class="has-sidebar-content">';
			echo '<div class="meta-box-sortabless">';
			if( $this->option_page_text ) {
				echo '<p>' . __( $this->option_page_text, $this->plg_tdomain ) . '</p>';
			}
			echo '<form method="get" style="display:inline;" action="">';
			if ( function_exists('wp_nonce_field') )
				wp_nonce_field( 'plugin-' . $this->sanitized_name . '-action_update-options' );
		}
		
		function form_block( $name, $fields ) {
			echo '<div class="postbox">';
			echo '<h3 class="hndle">' . __( $name, $this->plg_tdomain ) . '</h3>';
			echo '<div class="inside">';
			echo '<table style="margin:10px;table-layout:fixed;width:95%">';
			echo '<tr><th valign="top" align="left" style="width:50%">' . __('Setting:', 'yd-options-page') .
					'</th><th align="left" style="width:50%">' . __('Value:', 'yd-options-page') . '</th></tr>';
			foreach( $fields as $field => $type ) {
				$tdomain = $this->plg_tdomain;
				if( $field == 'disable_backlink' ) $tdomain = $this->tpl_tdomain;
				echo '<tr><td>' . __( $this->option_field_labels[$field] , $tdomain ) . '</td>';
				if( $type == 'bool' ) {
					echo '<td><input type="checkbox" name="' . $field . '" value="1" ';
					if( $this->option_values[$field] ) echo 'checked="checked" ';
					if( $field == 'disable_backlink' ) echo ' onclick="donatemsg( this.checked )" ';
					echo '></td>';
				}
				if( $type == 'text' ) {
					echo '<td><input type="text" name="' . $field . '" value="' 
						. $this->option_values[$field] . '" ';
					echo '></td>';
				}
				echo '</tr>';
			}
			echo '</table>';
			echo '</div>'; // / inside
			echo '</div>'; // / postbox
		}

		function form_action_button( $name, $action ) {
			echo '<p class="submit">';
			echo '<input type="submit" name="do" value="' . __( $name, $this->plg_tdomain ) . '"'
				. ' />';
			echo '</p>';
		}
		
		function form_footer() {
			echo '<p class="submit">';
			echo '<input type="submit" name="do" value="' . __('Update plugin settings', 'yd-options-page') . '" />';
			echo '<input type="hidden" name="page" value="' . $_GET["page"] . '" />';
			echo '<input type="hidden" name="time" value="' . time() . '" />';
			echo '<input type="submit" name="do" value="' . __('Reset plugin settings', 'yd-options-page') . '" />';
			if( $this->has_cache ) {
				echo '<input type="submit" name="do" value="' . __('Clear cache', 'yd-options-page') . '" /><br/>';
			}			
			echo '</p>'; // / submit
			echo '</form>';
			echo '</div>'; // / meta-box-sortabless
			echo '</div>'; // / has-sidebar-content
			echo '</div>'; // / has-sidebar sm-padded
		}
	
		function cron_status( $crontab ) {
			echo '<div class="crontab_status">';
			foreach( $crontab as $sched => $entry ) {
				$name = join( '->', $entry );
				$event = 'yd_' . $sched . '_event';
				if( $next = wp_next_scheduled( $event ) ) {
					
					if( has_action( $event, $entry ) ) {
						echo '<p>' . $name .' is hooked to: ' . $event . '<br/>';
						echo $name .' is scheduled to run next at: ' . date( DATE_RSS, $next ) . '</p>';
					} else {
						echo '<p>Crontab warning: ' . $name . ' is currently not registered.</p>';
						global $wp_filter;
						echo '<pre>';
						var_dump( $wp_filter );
						echo '</pre>';
					}
				} else {
					echo '<p>Crontab warning: ' . $name . ' is currently not scheduled.</p>';	
				}
			}
			echo '</div>';
		}
		
		function footer() {
			echo '</div>'; // / metabox-holder has-right-sidebar
			echo '</div>'; // /wrap
		}
		
		function do_action( $yd_plugin ) {
			check_admin_referer( 'plugin-' . $this->sanitized_name . '-action_update-options' );
			if ( $_SERVER['REQUEST_METHOD'] == 'POST' ){
				$p = $_POST;
			} else {
				$p = $_GET;
			}
			if(			$p["do"] == __('Clear cache', 'yd-options-page') ) {
				$yd_plugin->clear_cache();
				$this->update_msg = '<p>' . __('Caches are cleared', 'yd-options-page') . '</p>';
			} elseif(	$p["do"] == __('Reset plugin settings', 'yd-options-page') ) {
				$yd_plugin->reset_options();
				$this->update_msg = '<p>' . __('Plugin settings are reset', 'yd-options-page') . '</p>';
			} elseif(	$p["do"] == __('Update plugin settings', 'yd-options-page') ) {
				$yd_plugin->update_options( $p );
				$this->update_msg = '<p>' . __('Plugin settings are updated', 'yd-options-page') . '</p>';
			}
			foreach( (array)$this->form_add_actions as $name => $action ) {
				if(		$p["do"] == $name ) call_user_func( $action, &$this );
			}
		}
	}
}
?>
