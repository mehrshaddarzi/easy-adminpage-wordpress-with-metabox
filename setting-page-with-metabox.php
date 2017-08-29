<?php
/*
Plugin Name: HowTo - Metabox Showcase Plugin
Plugin URI: 	http://www.code-styling.de
Description: This Plugin demonstrates how you can build your own plugin pages using the WordPress provided draggable metaboxes, requires WordPress 2.7 version, supports WordPress 2.8 changed boxing layout engine
Author: Heiko Rabe
Author URI: http://www.code-styling.de/
Version: 1.2

License:
 ==============================================================================
 Copyright 2009 Heiko Rabe  (email : info@code-styling.de)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//avoid direct calls to this file where wp core files not present
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

define('HOWTO_METABOX_ADMIN_PAGE_NAME', 'howto_metaboxes');

//class that reperesent the complete plugin
class howto_metabox_plugin {

	//constructor of class, PHP4 compatible construction for backward compatibility
	function howto_metabox_plugin() {
		//add filter for WordPress 2.8 changed backend box system !
		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
		//register callback for admin menu  setup
		add_action('admin_menu', array(&$this, 'on_admin_menu')); 
		//register the callback been used if options of page been submitted and needs to be processed
		add_action('admin_post_save_howto_metaboxes_general', array(&$this, 'on_save_changes'));
	}
	
	//for WordPress 2.8 we have to tell, that we support 2 columns !
	function on_screen_layout_columns($columns, $screen) {
		//bugfix: $this->pagehook is not valid because it will be set at hook 'admin_menu' but 
		//multisite pages or user dashboard pages calling different menu an menu hooks!
		if (!defined( 'WP_NETWORK_ADMIN' ) && !defined( 'WP_USER_ADMIN' )) {
			if ($screen == $this->pagehook) {
				$columns[$this->pagehook] = 2;
			}
		}
		return $columns;
	}
	
	//extend the admin menu
	function on_admin_menu() {
		//add our own option page, you can also add it to different sections or use your own one
		$this->pagehook = add_options_page('Howto Metabox Page Title', "HowTo Metaboxes", 'manage_options', HOWTO_METABOX_ADMIN_PAGE_NAME, array(&$this, 'on_show_page'));
		//register  callback gets call prior your own page gets rendered
		add_action('load-'.$this->pagehook, array(&$this, 'on_load_page'));
	}
	
	//will be executed if wordpress core detects this page has to be rendered
	function on_load_page() {
		//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');

		//add several metaboxes now, all metaboxes registered during load page can be switched off/on at "Screen Options" automatically, nothing special to do therefore
		add_meta_box('howto-metaboxes-sidebox-1', 'Sidebox 1 Title', array(&$this, 'on_sidebox_1_content'), $this->pagehook, 'side', 'core');
		add_meta_box('howto-metaboxes-contentbox-1', 'Contentbox 1 Title', array(&$this, 'on_contentbox_1_content'), $this->pagehook, 'normal', 'core');
		add_meta_box('howto-metaboxes-contentbox-2', 'Contentbox 2 Title', array(&$this, 'on_contentbox_2_content'), $this->pagehook, 'normal', 'core');
		add_meta_box('howto-metaboxes-contentbox-additional-1', 'Contentbox Additional 1 Title', array(&$this, 'on_contentbox_additional_1_content'), $this->pagehook, 'additional', 'core');
		add_meta_box('howto-metaboxes-contentbox-additional-2', 'Contentbox Additional 2 Title', array(&$this, 'on_contentbox_additional_2_content'), $this->pagehook, 'additional', 'core');
	}
	
	//executed to show the plugins complete admin page
	function on_show_page() {
		//we need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		//add a 3rd content box now for demonstration purpose, boxes added at start of page rendering can't be switched on/off, 
		//may be needed to ensure that a special box is always available
		add_meta_box('howto-metaboxes-contentbox-3', 'Contentbox 3 Title (impossible to hide)', array(&$this, 'on_contentbox_3_content'), $this->pagehook, 'normal', 'core');
		add_meta_box('howto-metaboxes-sidebox-2', 'Sidebox 2 Title 5', array(&$this, 'on_sidebox_2_content'), $this->pagehook, 'side', 'core');
		//define some data can be given to each metabox during rendering
		$data = array('My Data 1', 'My Data 2', 'Available Data 1');
		?>
		<div id="howto-metaboxes-general" class="wrap">
		<?php screen_icon('options-general'); ?>
		<h2>Metabox Showcase Plugin Page</h2>
		<form action="admin-post.php" method="post">
			<?php wp_nonce_field('howto-metaboxes-general'); ?>
			<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
			<input type="hidden" name="action" value="save_howto_metaboxes_general" />
		
			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>
				</div>
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes($this->pagehook, 'normal', $data); ?>
						<h4>Static text and input section</h4>
						<p>Here is some static paragraph or your own static content. Can be placed where ever you want.</p>
						<textarea name="static-textarea" style="width:100%;">Change this text ....</textarea>
						<br/>
						<?php do_meta_boxes($this->pagehook, 'additional', $data); ?>
						<p>
							<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>	
						</p>
					</div>
				</div>
				<br class="clear"/>
								
			</div>	
		</form>
		</div>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			// close postboxes that should be closed
			$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			// postboxes setup
			postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
		});
		//]]>
	</script>
		
		<?php
	}

	//executed if the post arrives initiated by pressing the submit button of form
	function on_save_changes() {
		//user permission check
		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );			
		//cross check the given referer
		check_admin_referer('howto-metaboxes-general');
		
		//process here your on $_POST validation and / or option saving
		
		//lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect($_POST['_wp_http_referer']);		
	}

	//below you will find for each registered metabox the callback method, that produces the content inside the boxes
	//i did not describe each callback dedicated, what they do can be easily inspected and compare with the admin page displayed
	
	function on_sidebox_1_content($data) {
		?>
		<ul style="list-style-type:disc;margin-left:20px;">
			<?php foreach($data as $item) { echo "<li>$item</li>"; } ?>
		</ul>
		<?php
	}
	function on_sidebox_2_content($data) {
		?>
		<p>You can also use static text or any markup to be shown inside the boxes.</p>
		<?php
	}
function on_contentbox_1_content($data) {
	sort($data);
	?>
		<p>The given parameter at <b>sorted</b> order are: <em><?php echo implode(' | ', $data); ?></em></p>
	<?php
}
	function on_contentbox_2_content($data) {
		sort($data);
		?>
		<p>The given parameter at <b>reverse sorted</b> order are: <em><?php echo implode(' | ', array_reverse($data)); ?></em></p>
		<?php
	}
	function on_contentbox_3_content($data) {
		?>
		<p>This metabox can be dragged an placed where ever you want but <b>can't be hidden</b> using the <em>Screen Options</em> tab slider!</p>
		<?php
	}
	function on_contentbox_additional_1_content($data) {
		?>
		<p>This and the 2nd <em>additional</em> box will be addressed by an other group identifier to render it by calling with this dedicated name.</p>
		<p>You can have as much as needed box groups.</p>
		<?php
	}
	function on_contentbox_additional_2_content($data) {
		?>
			<p>metabox showcase - copyright &copy; 2009 Heiko Rabe (<a target="_blank" href="http://www.code-styling.de">www.code-styling.de</a>)</p>
			<p>requires at least WordPress 2.7 version, supports new box management of WordPress 2.8</p>
		<?php
	}
	
}

$my_howto_metabox_plugin = new howto_metabox_plugin();

?>