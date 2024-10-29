<?php

/*
	Plugin Name: aSlyder for Wordpress
	Plugin URI: http://www.avant5.com/aslyder/wordpress/
	Description: WP Interface plugin for making use of the aSlyder slideshow engine for jQuery.
	Author: Avant 5 Multimedia
	Version: 1.0.1
	Author URI: http://www.avant5.com
	
	Copyright 2013  Avant 5 Multimedia  ( email : info@avant5.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
	
*/

$aslyder_locs['site_url'] = get_bloginfo('url');
$aslyder_locs['file'] = plugin_basename(__FILE__);
$aslyder_locs['url'] = plugin_dir_url(__FILE__);
$aslyder_locs['dir'] = dirname(__FILE__);

function aslyder_first_image($content){
	$begPoint = stripos($content, "<img");
	if (!$begPoint) return false;
	$content = substr($content,$begPoint);
	$begPoint = stripos($content, "/>");
	$content = substr($content, 0, $begPoint+2);
	return $content;
} // aslyder_first_image()



function aslyder() {

	$aslyder_options = get_option('aslyder_options');
	
	if ( $aslyder_options['slides'] == "all" ):
		$aslyderMax = -1;
	else:
		$aslyderMax = ($aslyder_options['slides'])?$aslyder_options['slides']:5;
	endif;
	
	$aslyder_temp[] = "pause-{$aslyder_options['pause']}";
	$aslyder_temp[] = $aslyder_options['slide_style'];
	$aslyder_temp[] = $aslyder_options['slide_type'];
	$aslyder_temp[] = "speed-{$aslyder_options['speed']}";
	if ($aslyder_options['disableauto']) $aslyder_temp[] = "autooff";
	
	$aslyderClasses = implode(" ",$aslyder_temp);
	$aslyderClasses = " class=\"$aslyderClasses\"";	

	print "<div id=\"aslyder\"$aslyderClasses>";
		print "<ul>";
$aslyder = new WP_Query("category_name=aslyder&posts_per_page=$aslyderMax");
if ( $aslyder->have_posts() ):
	while ( $aslyder->have_posts() ):
		$aslyder->the_post();
		print "<li>";
			$thisLink = get_permalink($aslyder->post->ID);
			switch ($aslyder_options['source']) {
			
				case "featured":
					$thisSlide = get_the_post_thumbnail($aslyder->post->ID);
				break;
			
				default:
					$thisSlide = aslyder_first_image($aslyder->post->post_content);
			} // $aslyder_options switch
			
			if ( !$aslyder_options['disable_link'] ) $thisSlide = "<a href=\"$thisLink\">$thisSlide</a>";
			print $thisSlide;
		
		print "</li>";
	endwhile;
endif;
		print "</ul>";
	print "</div>";
	
	if ( $aslyder_options['advance']) print "<div id=\"aslyder-prev\"></div><div id=\"aslyder-next\"></div>";
	if ( $aslyder_options['navigation'] )  print "<div id=\"aslyder-nav\"></div>";
	
}

function aslyder_header(){
	GLOBAL $aslyder_locs;
	$aslyder_options = get_option("aslyder_options");
	print "<script type=\"text/javascript\" src=\"{$aslyder_locs['url']}aslyder.js\"></script>";
}

function aslyder_options() {

	$aslyder_options = get_option('aslyder_options');

	print "<div class=\"wrap\">";
		print "<h2>aSlyder Options</h2>";
		
		switch($_GET['tab']){
			case "help":
			$helpTab = " nav-tab-active";
			break;
			
			default:
			$mainTab = " nav-tab-active";
		}
		
		print "<h3 class=\"nav-tab-wrapper\">";
			print " &nbsp;<a href=\"admin.php?page=aslyder&tab=main\" class=\"nav-tab$mainTab\">Options</a>";
			print "<a href=\"admin.php?page=aslyder&tab=help\" class=\"nav-tab$helpTab\">Help</a>";
		print "</h3>";


	switch ( $_GET['tab'] ) {
	
	case "help":
		include("aslyder-help.php");
	break;

	default:
	// MAIN TAB

	if ( $_POST['main_options'] ):
		check_admin_referer( 'aslyder_main_options', 'aslyder_nonce' );

		if ( !preg_match("/^\d+$/",$_POST['maximum_posts']) ) $_POST['maximum_posts'] = 5;
		$aslyder_options['slides'] = ($_POST['maximum_posts'])?$_POST['maximum_posts']:-1;
		
		if ( !preg_match("/^\d+\.?\d*$/",$_POST['aslyder_pause']) ) $_POST['aslyder_pause'] = 5;
		$aslyder_options['pause'] = ($_POST['aslyder_pause'])?($_POST['aslyder_pause'] * 1000):5000;
		
		if ( !preg_match("/^\d+\.?\d*$/",$_POST['aslyder_speed']) ) $_POST['aslyder_speed'] = .8;
		$aslyder_options['speed'] = ($_POST['aslyder_speed'])?($_POST['aslyder_speed'] * 1000):800;
		
		if ( $_POST['slide_type'] == "" || $_POST['slide_type'] == "fadestyle" || $_POST['slide_type'] == "peel" ) $aslyder_options['slide_type'] = ($_POST['slide_type'])?$_POST['slide_type']:"";
		
		if ( $_POST['peel_direction'] != "up" && $_POST['peel_direction'] != "down" && $_POST['peel_direction'] != "left" && $_POST['peel_direction'] != "right" ) $_POST['peel_direction'] = "down";
		$aslyder_options['peel_direction'] = ($_POST['peel_direction'])?$_POST['peel_direction']:"down";
			if ($aslyder_options['slide_type'] == "peel") $aslyder_options['slide_type'] = ($_POST['peel_direction'])?"peel-{$_POST['peel_direction']}":"peel-down";
		
		if ( $_POST['aslyder_source'] != "featured" && $_POST['aslyder_source'] != "first" ) $_POST['aslyder_source'] = "first";
		$aslyder_options['source'] = $_POST['aslyder_source'];
		
		if ( $_POST['slide_style'] != "" && $_POST['slide_style'] != "aslyder-flow" ) $_POST['slide_style'] = "";
		$aslyder_options['slide_style'] = $_POST['slide_style'];
		
		// True or False only POST - no other validation needed

		$aslyder_options['disable_link'] = ($_POST['aslyder_disable_link'])?1:"";
		$aslyder_options['navigation'] = ($_POST['aslyder_navigation'])?1:"";
		$aslyder_options['disableauto'] = ($_POST['aslyder_autostart'])?1:"";
		$aslyder_options['advance'] = ($_POST['aslyder_advance'])?1:"";
		
		
		update_option('aslyder_options',$aslyder_options);
		print "<div class=\"fade\">Options Updated</div>";
	endif; // main options POST
	
	// for display purposes only.  Will reset to -1 on save.
	if ( $aslyder_options['slides'] == -1 ) $aslyder_options['slides'] = "";

	$aslyderSpeed = ($aslyder_options['speed'])?($aslyder_options['speed'] / 1000):.8;
	$aslyderPause = ($aslyder_options['pause'])?($aslyder_options['pause'] / 1000):5;
	
	if ($aslyder_options['navigation']) $aslydernavcheck = " checked=\"checked\"";
	if ($aslyder_options['disableauto']) $aslyderautocheck = " checked=\"checked\"";
	if ($aslyder_options['advance']) $aslyderadvancecheck = " checked=\"checked\"";
	if ($aslyder_options['disable_link']) $aslyderlinkcheck = " checked=\"checked\"";

	if ($aslyder_options['slide_style'] == ""):
		$aslydersnapback = " checked=\"checked\"";
	else:
		$aslydercontinuousflow = " checked=\"checked\"";
	endif; // slide style check

	switch ($aslyder_options['source']){
		case "featured":
			 $sourcefeaturedchecked = " checked=\"checked\"";
		break;
		default:
			$sourcefirstchecked = " checked=\"checked\"";
	} // source switch
	
	switch($aslyder_options['slide_type']) {
		case "fadestyle":
			$style_fade_checked = " checked=\"checked\"";
		break;
		case "peel-up":
		case "peel-down":
		case "peel-left":
		case "peel-right":
			$style_peel_checked = " checked=\"checked\"";
		break;
		default:
			$style_slide_checked = " checked=\"checked\"";
	} // switch slide type
	
	switch($aslyder_options['peel_direction']) {
		case "up":
			$peel_up_checked = " checked=\"checked\"";
		break;
		case "left":
			$peel_left_checked = " checked=\"checked\"";
		break;		
		case "right":
			$peel_right_checked = " checked=\"checked\"";
		break;
		default:
			$peel_down_checked = " checked=\"checked\"";
	} // switch direction
	
	$thisNonce = wp_nonce_field( 'aslyder_main_options', 'aslyder_nonce', true, false );
	
print <<<ThisHTML
<form method="post" action="admin.php?page=aslyder&tab=main">
	$thisNonce
	<table class="form-table">
		<tr>
			<th scope="row">Number of posts</th>
			<td>
				<input type="text" class="small-text" name="maximum_posts" id="maximum_posts" value="{$aslyder_options['slides']}" />
				<span class="description" style="margin-left:15px;"> Number of posts from the category to display.</span>
			</td>
		</tr>
		<tr>
			<th>Slider transition type</th>
			<td>
				<input type="radio" name="slide_type" value="" id="slide_type_slide"$style_slide_checked /> <label for="slide_type_slide">Slide</label><br />
				<input type="radio" name="slide_type" value="fadestyle" id="slide_type_fade"$style_fade_checked /> <label for="slide_type_fade">Fade</label><br />
				<input type="radio" name="slide_type" value="peel" id="slide_type_peel"$style_peel_checked /> <label for="slide_type_peel">Peel</label><br />
			</td>
		</tr>
		<tr>
			<th>Slide reset type</th>
			<td>
				<input type="radio" name="slide_style" id="slide_style_snapback" value=""$aslydersnapback /> Snapback<br />
				<input type="radio" name="slide_style" id="slide_style_flow" value="aslyder-flow"$aslydercontinuousflow /> Continuous flow<br />
			</td>
		</tr>
		<tr>
			<th>Peel direction</th>
			<td>
				<input type="radio" name="peel_direction" value="up" id="peel_direction_up"$peel_up_checked /> <label for="peel_direction_up">Up</label><br />
				<input type="radio" name="peel_direction" value="down" id="peel_direction_down"$peel_down_checked /> <label for="peel_direction_down">Down</label><br />
				<input type="radio" name="peel_direction" value="left" id="peel_direction_left"$peel_left_checked /> <label for="peel_direction_left">Left</label><br />
				<input type="radio" name="peel_direction" value="right" id="peel_direction_right"$peel_right_checked /> <label for="peel_direction_right">Right</label><br />
			</td>
		</tr>
		<tr>
			<th>Transition speed</th>
			<td>
				<input type="text" name="aslyder_speed" id="aslyder_speed" value="{$aslyderSpeed}" class="small-text" /> seconds
			</td>
		</tr>
		<tr>
			<th>Slide pause</th>
			<td>
				<input type="text" name="aslyder_pause" id="aslyder_pause" value="{$aslyderPause}" class="small-text" /> seconds
			</td>
		</tr>
		<tr>
			<th>Slide source</th>
			<td>
				<input type="radio" name="aslyder_source" id="aslyder_source_first" value="first"$sourcefirstchecked /> First post image<br />
				<input type="radio" name="aslyder_source" id="aslyder_source_featured" value="featured"$sourcefeaturedchecked /> Featured image<br />
			</td>
		</tr>
		<tr>
			<th>Display navigation buttons</th>
			<td>
				<input type="checkbox" name="aslyder_navigation" id="aslyder_navigation"$aslydernavcheck /> Check for Yes
			</td>
		</tr>
		<tr>
			<th>Display advancing buttons</th>
			<td>
				<input type="checkbox" name="aslyder_advance" id="aslyder_advance"$aslyderadvancecheck /> Check to display
			</td>
		</tr>
		<tr>
			<th>Disable linking to post</th>
			<td>
				<input type="checkbox" name="aslyder_disable_link" id="aslyder_disable_link"$aslyderlinkcheck /> Check to disable
			</td>
		</tr>
		<tr>
			<th>Disable autostart</th>
			<td>
				<input type="checkbox" name="aslyder_autostart" id="aslyder_autostart"$aslyderautocheck /> Check to disable
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" class="button-primary" value="Update Options" name="main_options" /></p>
</form>
ThisHTML;

	
	
	} // switch GET tab
	
	print "</div>"; // .wrap
	
} // aslyder_options()

function aslyder_register_admin(){
	// UPDATE - set this up with menu mobility
	add_menu_page( "aSlyder Options","aSlyder","manage_options","aslyder","aslyder_options",$icon );
}

function aslyder_activate(){
	// set defaults
	$aslyder_options['slides'] = 5;
	update_option('aslyder_options',$aslyder_options);
}

function aslyder_deactivate(){
	// nothing at the moment
}

function aslyder_uninstall() {
	delete_option('aslyder_options');
	delete_option('aslyder_settings');
}

function aslyder_scripts(){
	wp_enqueue_script( 'jquery' );
}


add_action( 'wp_enqueue_scripts', 'aslyder_scripts' );
add_action('wp_head','aslyder_header');

if ( is_admin() ):
	add_action('admin_menu', 'aslyder_register_admin');
	register_activation_hook(__FILE__,'aslyder_activate');
endif; // is_admin

/*

add_shortcode('aslyder','aslyder_shortcode');
add_action('admin_head', 'aslyder_admin_header');

*/



?>