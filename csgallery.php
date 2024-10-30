<?php
/*
Plugin Name: CS Gallery
Description: Display thumbnails of photos/docs/mp3-sounds/youtubevideos in a responsive grid and popup dialogs.
Author: Conny Sparr
Author URI: http://connycms.connysparr.se/?pageId=9
Plugin URI: http://wp.connysparr.se/arkivet/
Version: 1.0.2
License: GPL2
*/
if ( ! defined( 'WPINC' ) ) {
	die;
}
//http://wp.connysparr.se/csgallery.zip
if ( ! class_exists( 'CSGallery' ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-csgallery.php';
	require_once dirname( __FILE__ ) . '/includes/class-csgallery-lang.php';
}

define( 'CSGALLERY_PLUGIN_VERSION', '1.0.0' );
define( 'CSGALLERY_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));
define( 'CSGALLERY_PLUGIN_FILE', __FILE__);
global $wpdb;

define( 'CSGALLERY_IMAGESTABLENAME', $wpdb->prefix . "csgalleryimages");
define( 'CSGALLERY_CATEGORIESTABLENAME', $wpdb->prefix . "csgallerycategories");
define( 'CSGALLERY_PLACESTABLENAME', $wpdb->prefix . "csgalleryplaces");

register_activation_hook( __FILE__, array('CSGallery', 'activate') );
register_deactivation_hook( __FILE__, array('CSGallery', 'deActivate') );
register_uninstall_hook( __FILE__, array('CSGallery', 'unInstall') );

//Ajax actions
add_action( 'wp_ajax_ajaxupdateorinsertcsgalleryimagetext', array('CSGallery', 'ajaxupdateorinsertcsgalleryimagetext_handler') );
add_action( 'wp_ajax_nopriv_ajaxupdateorinsertcsgalleryimagetext', array('CSGallery', 'ajaxupdateorinsertcsgalleryimagetext_handler') );

add_action( 'wp_ajax_ajaxdeletecsgalleryimagepanel', array('CSGallery', 'ajaxdeletecsgalleryimagepanel_handler') );
add_action( 'wp_ajax_nopriv_ajaxdeletecsgalleryimagepanel', array('CSGallery', 'ajaxdeletecsgalleryimagepanel_handler') );

add_action( 'wp_ajax_ajaxuploadcsgalleryimagefile', array('CSGallery', 'ajaxuploadcsgalleryimagefile_handler') );
add_action( 'wp_ajax_nopriv_ajaxuploadcsgalleryimagefile', array('CSGallery', 'ajaxuploadcsgalleryimagefile_handler') );

add_action( 'wp_ajax_ajaxdeletecsgalleryimageonly', array('CSGallery', 'ajaxdeletecsgalleryimageonly_handler') );
add_action( 'wp_ajax_nopriv_ajaxdeletecsgalleryimageonly', array('CSGallery', 'ajaxdeletecsgalleryimageonly_handler') );

add_action( 'wp_ajax_ajaxaddnewcsgallerycategory', array('CSGallery', 'ajaxaddnewcsgallerycategory_handler') );
add_action( 'wp_ajax_nopriv_ajaxaddnewcsgallerycategory', array('CSGallery', 'ajaxaddnewcsgallerycategory_handler') );

add_action( 'wp_ajax_ajaxupdatecsgallerycategory', array('CSGallery', 'ajaxupdatecsgallerycategory_handler') );
add_action( 'wp_ajax_nopriv_ajaxupdatecsgallerycategory', array('CSGallery', 'ajaxupdatecsgallerycategory_handler') );

add_action( 'wp_ajax_ajaxdeletecsgallerycategory', array('CSGallery', 'ajaxdeletecsgallerycategory_handler') );
add_action( 'wp_ajax_nopriv_ajaxdeletecsgallerycategory', array('CSGallery', 'ajaxdeletecsgallerycategory_handler') );

add_action( 'wp_ajax_ajaxaddnewcsgalleryplace', array('CSGallery', 'ajaxaddnewcsgalleryplace_handler') );
add_action( 'wp_ajax_nopriv_ajaxaddnewcsgalleryplace', array('CSGallery', 'ajaxaddnewcsgalleryplace_handler') );

add_action( 'wp_ajax_ajaxupdatecsgalleryplace', array('CSGallery', 'ajaxupdatecsgalleryplace_handler') );
add_action( 'wp_ajax_nopriv_ajaxupdatecsgalleryplace', array('CSGallery', 'ajaxupdatecsgalleryplace_handler') );

add_action( 'wp_ajax_ajaxdeletecsgalleryplace', array('CSGallery', 'ajaxdeletecsgalleryplace_handler') );
add_action( 'wp_ajax_nopriv_ajaxdeletecsgalleryplace', array('CSGallery', 'ajaxdeletecsgalleryplace_handler') );

//Shortcode: [csgallery pagesize="10" category="" place="" type="images/documents/videos/mp3sounds" maximagepanelwidth = 260 maximagepanelheight = 290]
//All shortcode parameters are optional. Default values: pagesize="10" category="" place="" type="" maximagepanelwidth = 260 maximagepanelheight = 290
add_shortcode('csgallery', 'CSGalleryStart');
function CSGalleryStart($atts){
  /* Do not use extract(). It was deprecated.
	extract(shortcode_atts(array(
     'category' => '','place' => '','type' => ''
  ), $atts)); */
	$args = shortcode_atts(
		array(
			'category' => '',
			'place' => '',
		  'type' => '', //images/documents/videos or mp3sounds
			'maximagepanelwidth' => 260,
			'maximagepanelheight' => 290,
			'pagesize' => 10,
		  'language' => 'en'), //en or se
		$atts
	);

  $searchCatAttr = esc_attr($args['category']);
	$searchPlaceAttr = esc_attr($args['place']);
	$searchFileTypeAttr = esc_attr($args['type']);
	$maxImgContainerWidthAttr = (int)$args['maximagepanelwidth'];
	$maxImgContainerHeightAttr = (int)$args['maximagepanelheight'];
	$pageSizeAttr = (int)$args['pagesize'];
	$langAttr = esc_attr($args['language']);

  $galleryObj = CSGallery::GetInstance();
  $galleryObj->initGallery($searchCatAttr, $searchPlaceAttr, $searchFileTypeAttr,
								 $maxImgContainerWidthAttr, $maxImgContainerHeightAttr, $pageSizeAttr, $langAttr);

  $galleryHtml = "<div class='csgallery'>";
	$galleryHtml .= "<div>" . $galleryObj->errorMessage;
	$galleryHtml .= $galleryObj->infoMessage . "</div>";
	if(isset($_GET["editgallerycats"]) && !empty($_GET["editgallerycats"]) && (current_user_can('administrator') || current_user_can('editor'))){
		$galleryHtml .= $galleryObj->GetEditCategoriesHtml();
	}
	else if(isset($_GET["editgalleryplaces"]) && !empty($_GET["editgalleryplaces"]) && (current_user_can('administrator') || current_user_can('editor'))){
		$galleryHtml .= $galleryObj->GetEditPlacesHtml();
	}
	else if(isset($_GET["updategalleryimage"]) && !empty($_GET["updategalleryimage"])
	&& isset($_GET["imageid"])
	&& (current_user_can('administrator') || current_user_can('editor'))){
		$imageId = $_GET["imageid"];
		$galleryHtml .= $galleryObj->GetUpdateGalleryImageHtml($imageId);
	}
	else{
		if(isset($_GET["showgalleryimagebyid"])){
			$showImageId = trim(stripslashes($_GET["showgalleryimagebyid"]));
			$showImageId = is_numeric($showImageId) == true ? (INT)$showImageId : 0;
			$showImageId =  is_int($showImageId) == true ? $showImageId : 0;
			global $wpdb;
			$sql = "SELECT * FROM ".CSGALLERY_IMAGESTABLENAME. " WHERE imageid=%s";
			$sql = $wpdb->prepare($sql, $showImageId);

			//$imageResult = $wpdb->get_row($sql,OBJECT);
			//$imageResult = $wpdb->get_row($sql, ARRAY_A);
			$imageResult = $wpdb->get_results($sql,OBJECT);
			//echo "Antal:".count($imageResult)." Isarray:".is_array($imageResult);
			if(is_array($imageResult) && count($imageResult) == 1){
				$galleryHtml .= $galleryObj->GetGalleryImageHtmlById($imageResult[0]);
			}
			else{
				$galleryHtml .= $galleryObj->GetGalleryHtml();
			}
		}
		else{
			$galleryHtml .= $galleryObj->GetGalleryHtml();
		}
	}
  $galleryHtml .= "</div>";
  return $galleryHtml;
}
?>
