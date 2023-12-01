<?php
/**
 * @package  FDS Advance Search
Plugin Name: FDS Advance Search
Plugin URI: http://www.finaldatasolutions.com/
Description: This is advance search plugin.
Version: 1.2.3
Author: Ibrar Ayoub
Author URI: http://www.finaldatasolutions.com/
License: GPLv2 or later
*/

require 'plugin-update-checker-master/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/manager-wiseTech/fds-advance-search/',
	__FILE__,
	'fds-advance-search'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
$myUpdateChecker->setAuthentication('your-token-here');


defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

//adding styles and sript of select2
add_action('wp_enqueue_scripts', 'callback_for_setting_up_scripts');
function callback_for_setting_up_scripts() {
    wp_register_style( 'fds-selectcss', plugin_dir_url(__FILE__).'css/bootstrap-select.min.css' );
    wp_register_style( 'fds-bootstrapcss', plugin_dir_url(__FILE__).'css/bootstrap.min.css' );
    wp_enqueue_style( 'fds-bootstrapcss' );
    wp_enqueue_style( 'fds-selectcss' );
    wp_enqueue_script( 'fds-bootstrapjs', plugin_dir_url(__FILE__).'js/bootstrap.bundle.min.js', array( 'jquery' ) );
    wp_enqueue_script( 'fds-selectjs', plugin_dir_url(__FILE__).'js/bootstrap-select.min.js', array( 'jquery' ) );
}
add_action("admin_menu","fds_advance_search");

// add async and defer attributes to enqueued scripts
function fds_script_loader_tag($tag, $handle, $src) {
	
	if ( $handle === 'fds-bootstrapjs' || $handle === 'fds-selectjs') {
		
// 		if (false === stripos($tag, 'async')) {
			
// 			$tag = str_replace(' src', ' async="async" src', $tag);
			
// 		}
		
		if (false === stripos($tag, 'defer')) {
			
			$tag = str_replace('<script ', '<script defer ', $tag);
			
		}
		
	}
	return $tag;
	
}
add_filter('script_loader_tag', 'fds_script_loader_tag', 10, 3);




function fds_advance_search()
{
	add_menu_page("FDS Advance Search","FDS Advance Search","manage_options","fds-advance-search","fds_advance_search_menu_fn","dashicons-search");
}
$plugin_dir_path = dirname(__FILE__);
function fds_advance_search_menu_fn()
{
	echo "<h1>Place this shortcode in footer widget.</h1>";
	echo"[fds-search-form]";
	echo "Add a new page and copy its url in fds-settings page and place this short code in newly created page.";
	echo "[fds-search-result]";
}

function fds_set_first_post_image($post) {
  $first_img = '';
  ob_start();
  ob_end_clean();
  $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
  $first_img = $matches [1][0];

  if(empty($first_img)){
    $first_img = "";
  }
  return $first_img;
}
function fds_form_creation($atts){
	$content = NULL;
$content .='<style type="text/css">
    .dropdown-menu .inner{
    	width:auto;
    }
    .mom-select{
    display:none;
    }
    .bs-searchbox .form-control{
    width:-webkit-fill-available;
    }
    /* This is pagination styling*/
    .active-page{
    	background-color: #0d6efd;
    	color: white;
    }

	</style>';	
	$default_key = NULL;
	if (isset($_GET['s'])) {
		$default_key = $_GET['s'];
	}
$content .= "<div>";
$content .= '<form class="advsrch_form" method="get" target="_blank" action="'. get_option('fds_search_option') .'">
	<div>
		<div><label class="advsrch_lbl" style="font-size:20px;">Enter Keyword:</label></div>
		<div><input style="width:100%;border-radius:4px;box-sizing: border-box;" type="text" name="srchbox" value="'.$default_key.'"></div>
	</div>
	<div class="mt-2">		    
		      <select class="form-control selectpicker fds-select" name="categories[]" multiple data-actions-box="true"title="Select Categories" data-selected-text-format="count > 3" data-container="body" data-live-search="true">';
		    $args = array(
			    'orderby' => 'name',
			    'hierarchical' => 1,
			    'taxonomy' => 'category',
			    'hide_empty' => 0,
			    'parent' => 0,
			    );
		     $categories = get_categories($args);  
	$content .=	'';
		    	foreach($categories as $category) {
		    	if ($category->name == "Uncategorized") {
		    		continue;
		    	}
		$content .=   '<option data-tokens="'.$category->name.'" value="'.$category->name.'">'.$category->name.'</option>';
		      		    $child_cat = get_categories(
										    array( 'parent' => $category->cat_ID )
										);
		      		    if ($child_cat) {
		      		    	
		      		    	foreach($child_cat as $cat)
		$content.=		'<option data-tokens="'.$cat->name.'" value="'.$cat->name.'">'.$cat->name.'></option>'; 
		      		    }
		     		    

		      		    } 
	$content .=' </select>
			
	</div>
	<div class="d-flex justify-content-center">
		<input type="submit" class="btn btn-primary mt-3" name="search" value="Search">
	</div>
</form></div>';
$content .= "<div style='clear:both'></div>";
$content .= '<script type="text/javascript">
(function($) {
					$(".page-numbers").addClass("page-link");
					$(".current").addClass("active-page");
			})( jQuery );
			
</script>';
		return $content;
}
add_shortcode('fds-search-form','fds_form_creation');
function fds_result_generator(){
	if (isset($_GET['search']))
	{
	$keyword = $_GET['srchbox'];
	if (isset($_GET['categories'])) {
	$categories = $_GET['categories'];
	$cat = null;
		if (!empty($categories)) {
		foreach ($categories as $category) {
			$cat .= $category.','; 
			}
			 $cat = ltrim($cat);
			}	
	}
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$args = array(
		's'=>$keyword,
		'category_name' => $cat,
		'posts_per_page' => 10,
		'paged' => $paged,
		'post_type' => 'post'
	);
	$data="";
	$filter_form = '';
	$filter_form .= '<style type="text/css">
.dropdown-menu .inner{
    	width:auto;
    }
    .mom-select{
display:none;
}
    .bs-searchbox .form-control{
    width:-webkit-fill-available;
    }
	</style>
	<div>
	<form method="get" target="_blank" class="advsrch_form" action="'. get_option('fds_search_option') .'">
	<div>
		<div><label class="advsrch_lbl" style="font-size:20px;">Enter Keyword: </label></div>
		<div><input style="width:100%;border-radius:4px;box-sizing: border-box;" type="text" name="srchbox" value="'.$keyword.'"></div>
	</div>
	<div class="mt-2">
		<select class="form-control selectpicker fds-select" name="categories[]" multiple data-actions-box="true"title="Select Categories" data-selected-text-format="count > 3" data-container="body" data-live-search="true">';
	$args1 = array(
			    'orderby' => 'name',
			    'hierarchical' => 1,
			    'taxonomy' => 'category',
			    'hide_empty' => 0,
			    'parent' => 0,
			    );
	$categories = get_categories($args1);  
	$filter_form .=	'';
		    	foreach($categories as $category) {
		    	if ($category->name == "Uncategorized") {
		    		continue;
		    	}
		    	$selected_cat = explode(',', $cat);
		    	if (in_array($category->name, $selected_cat))
				  {
				  	$checked = "selected";
				  }
				else
				  {
				  	$checked = " ";
				  }
		$filter_form .=   '
		      		       <option '.$checked.' value="'.$category->name.'">'.$category->name.'</option>';
		      		       $child_cat = $categories=get_categories(
										    array( 'parent' => $category->cat_ID )
										);
		      		    if ($child_cat) {
		      		    	foreach($child_cat as $cc){
		      		    		if (in_array($cc->name, $selected_cat))
											  {
											  	$checked = "selected";
											  }
											else
											  {
											  	$checked = " ";
											  }
		$filter_form.=		'<option '.$checked.' value="'.$cc->name.'">'.$cc->name.'</option>'; 
		      		    	
		      		    	    $checked=" ";
		      		    	}
		      		    	    
		      		    	}
		      		  $checked=" ";
		      		    } 
	$filter_form .=' </select>
		  
			
	</div>
	<div class="d-flex justify-content-center">
		<input type="submit" class="btn btn-primary mt-3" name="search" value="Search">
	</div>
</form></div>';
$filter_form .= "<div style='clear:both'></div>";
	$query = new WP_Query($args);
		$posts = $query->posts;
		if (!empty($posts)) {
			$data = '<div class="container">';
		}
		
		foreach($posts as $post) {
		  $data .= '<div class="bp-vertical-share" style="width:100%">
								<div class="bp-entry">
								<div class="bp-head">
								<h2><a href="'.get_permalink($post->ID).'" data-wpel-link="internal">'.get_the_title($post->ID).'</a></h2>
								  <div class="mom-post-meta bp-meta">
								  <span>In:'.get_the_category( $post->ID )[0]->name .'</span>';
						$post_tags = get_the_tags($post->ID);
 						$tags =NULL;
						if ( $post_tags ) {
						    foreach( $post_tags as $tag ) {
						    $tags .= $tag->name . ', ';
						    }
						}
						$data .=  '<span> Tags: '.$tags.'</span>
								  </div>
								</div> 
								<div class="bp-details">
								<div class="post-img">
								<a href="'.get_permalink($post->ID).'">';
								if(get_the_post_thumbnail($post->ID)){
									$data .= get_the_post_thumbnail($post->ID);
								}
								else{
									 $data .= '<img width="750" height="560" src="'.fds_set_first_post_image($post).'" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="">';
								}
						 $data .='  </a>
								</div> 
								'.get_the_excerpt( $post->ID ).'
								<a href="'.get_permalink($post->ID).'" class="read-more-link" data-wpel-link="internal">Read more <i class="fa-icon-double-angle-right"></i></a>
								</div> 
								</div> 
								<div class="clear"></div>
								</div>';
		}
		if (!empty($posts)) {
			$data .= '<div>
			<nav aria-label="Page navigation example">
			<ul class="pagination"><!--start of pagination-->
  							'. paginate_links( array(
            'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
            'total'        => $query->max_num_pages,
            'current'      => max( 1, get_query_var( 'paged' ) ),
            'format'       => '?paged=%#%',
            'show_all'     => false,
            'type'         => 'plain',
            'end_size'     => 2,
            'mid_size'     => 1,
            'prev_next'    => true,
            'prev_text'    => sprintf( '<i></i> %1$s', __( 'Previous', 'text-domain' ) ),
            'next_text'    => sprintf( '%1$s <i></i>', __( 'Next', 'text-domain' ) ),
            'add_args'     => false,
            'add_fragment' => '',
        ) ).'	  		
        				</ul>
        				</nav>
        				</div><!--end-of-pagination-->';
			$data .= '</div><!--end-of-container-->';
		}
		

		if (empty($data)) {
			$data ="<h3>No Data Found.</h3>";
		}
		
		wp_reset_postdata();
	
		return $filter_form.$data;
	}
}

add_shortcode('fds-search-result','fds_result_generator');
	 function fds_add_admin_pages() {
			add_options_page('FDS Search Settings', 'FDS Search settings', 'manage_options', 'fds-search-settings', 'fds_admin_index' );
		}
	function fds_admin_index() {
			require plugin_dir_path( __FILE__ ) . 'templates/fds-setting.php';
		}
add_action( 'admin_menu', 'fds_add_admin_pages' );
?>
