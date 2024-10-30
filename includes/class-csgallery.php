<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
class CSGallery
{
  private static $_instance = null; //The single instance of the class.
  public $infoMessage = "";
	public $errorMessage = "";
  private $themeDir = "";
  private $pluginDir = "";
  private $pluginUrl = "";

	private $searchCatNameAttr = "";
  private $searchPlaceNameAttr = "";
  private $searchFileTypeAttr = "";
  private $maxImgContainerWidth = 0;
  private $maxImgContainerHeight = 0;
  private $pageSize = 10;
  private $lan = null; //language

  private function __construct()
	{
		wp_enqueue_script( 'csgalleryscripts', plugin_dir_url('/') . CSGALLERY_PLUGIN_NAME . '/js/csgallery.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style('csgallerystyles', plugin_dir_url('/') . CSGALLERY_PLUGIN_NAME . '/css/csgallery.css', '1.0', true);
		// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
		wp_localize_script( 'csgalleryscripts', 'ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'test_value' => 1234 ) );
  }

  public static function GetInstance(){
    if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
  }

	public function initGallery($searchCatAttr, $searchPlaceAttr, $searchFileTypeAttr,
													$maxImgContainerWidthAttr, $maxImgContainerHeightAttr, $pageSizeAttr, $langAttr)
  {
    $this->themeDir = ABSPATH . 'wp-content/themes/' . get_template();
    $this->pluginDir = WP_PLUGIN_DIR . '/' . CSGALLERY_PLUGIN_NAME;
    $this->pluginUrl = WP_PLUGIN_URL . '/' . CSGALLERY_PLUGIN_NAME;
    $this->searchPlaceNameAttr = $searchPlaceAttr;
    $this->searchCatNameAttr = $searchCatAttr;
    $this->searchFileTypeAttr = $searchFileTypeAttr;
    $this->maxImgContainerWidth = $maxImgContainerWidthAttr;
    $this->maxImgContainerHeight = $maxImgContainerHeightAttr;
    $this->pageSize = $pageSizeAttr;
		$this->lan = new CSGalleryLang($langAttr);
  }

	public function GetGalleryHtml()
	{
    global $wpdb;
		$allCategories = $wpdb->get_results("SELECT * FROM ".CSGALLERY_CATEGORIESTABLENAME." ORDER BY catvalue ASC", OBJECT);
		$allPlaces = $wpdb->get_results("SELECT * FROM ".CSGALLERY_PLACESTABLENAME." ORDER BY placevalue ASC", OBJECT);
		$allTypes = array("all"=>$this->lan->allTypes, "images"=>$this->lan->images, "documents"=>$this->lan->documents, "videos"=>$this->lan->videos, "mp3sounds"=>$this->lan->sounds);

		$catsIdKeyToNameArray = array(); //show infolabel when hover each image-block
		$placesIdKeyToNameArray = array(); //show infolabel when hover each image-block

		$searchText = "";
    if(isset($_GET["searchgallerybtn"])){
      $searchText = trim($_GET["searchgallerytext"]);
      if(strlen($searchText) > 16){
        $searchText = substr($searchText, 0, 16);
      }
    }
    //search catId from GET[searchgallerycategory]-id or startattribute-cat-name
		$searchCatId = 0;
		foreach($allCategories as $catItem){
			$catsIdKeyToNameArray[$catItem->catid] = $catItem->cattitle;
			if(isset($_GET["searchgallerybtn"]) && isset($_GET["searchgallerycategory"])){
         if($catItem->catid == $_GET["searchgallerycategory"]){
					 $searchCatId = $catItem->catid;
				 }
			}
			else if(mb_strtolower($catItem->cattitle) == mb_strtolower($this->searchCatNameAttr)){
        $searchCatId = $catItem->catid;
      }
		}
		//search placeId from GET[searchgalleryplace]-id or startattribute-place-name
		$searchPlaceId = 0;
		foreach($allPlaces as $placeItem){
			$placesIdKeyToNameArray[$placeItem->placeid] = $placeItem->placetitle;
			if(isset($_GET["searchgallerybtn"]) && isset($_GET["searchgalleryplace"])){
         if($placeItem->placeid == $_GET["searchgalleryplace"]){
					 $searchPlaceId = $placeItem->placeid;
				 }
			}
			else if(mb_strtolower($placeItem->placetitle) == mb_strtolower($this->searchPlaceNameAttr)){
        $searchPlaceId = $placeItem->placeid;
      }
		}
    //search filetype from GET[searchgallerytype] or startattribute-type
		$searchFileType = "";
		foreach ($allTypes as $sTypeKey => $sTypeValue) {
      if(isset($_GET["searchgallerybtn"])){
        if(isset($_GET["searchgallerytype"]) && $sTypeKey == $_GET["searchgallerytype"]){
          $searchFileType = $sTypeKey;
        }
      }
      else if(mb_strtolower($sTypeKey) == mb_strtolower($this->searchFileTypeAttr)){
        $searchFileType = $sTypeKey;
      }
    }
    //paging
		$pageNr = isset($_GET["pagenr"]) == true ? $_GET["pagenr"] : 0;
    $pageNr = is_numeric($pageNr) == true ? (INT)$pageNr : 0;
    $pageNr =  is_int($pageNr) == true ? $pageNr : 0;
		$offset = $pageNr * $this->pageSize;
		//build sql and get result from database
		$sqlWhere = "";
    $whereArray = array();
    if($searchText != ""){
      $sqlWhere .= count($whereArray) == 0 ? " where (imgtitle LIKE %s OR imgtext LIKE %s)" : " and (imgtitle LIKE %s OR imgtext LIKE %s)";
      array_push($whereArray, "%".$searchText."%");
      array_push($whereArray, "%".$searchText."%");
    }
    if(!empty($searchCatId) && $searchCatId != 0){
      $sqlWhere .= count($whereArray) == 0 ? " where catid = %s " : " and catid = %s ";
      array_push($whereArray, $searchCatId);
    }
    if(!empty($searchPlaceId) && $searchPlaceId != 0){
      $sqlWhere .= count($whereArray) == 0 ? " where placeid = %s " : " and placeid = %s ";
      array_push($whereArray, $searchPlaceId);
    }
    if(!empty($searchFileType) && $searchFileType == "videos"){
      $sqlWhere .= count($whereArray) == 0 ? " where ( youtubecode > %s)" : " and (youtubecode > %s)";
      $youtubeEmpty = "";
      array_push($whereArray, $youtubeEmpty);
    }
    else if(!empty($searchFileType) && $searchFileType == "images"){
      $sqlWhere .= count($whereArray) == 0 ? " where (imgfilename LIKE %s OR imgfilename LIKE %s OR imgfilename LIKE %s)" : " and (imgfilename LIKE %s OR imgfilename LIKE %s OR imgfilename LIKE %s)";
      $jpg = ".jpg";
      $png = ".png";
      $gif = ".gif";
      array_push($whereArray, "%".$jpg);
      array_push($whereArray, "%".$png);
      array_push($whereArray, "%".$gif);
    }
    else if(!empty($searchFileType) && $searchFileType == "documents"){
      $sqlWhere .= count($whereArray) == 0 ? " where (imgfilename LIKE %s OR imgfilename LIKE %s)" : " and (imgfilename LIKE %s OR imgfilename LIKE %s)";
      $pdf = ".pdf";
      $xls = ".xls";
      array_push($whereArray, "%".$pdf);
      array_push($whereArray, "%".$xls);
    }
    else if(!empty($searchFileType) && $searchFileType == "mp3sounds"){
      $sqlWhere .= count($whereArray) == 0 ? " where (imgfilename LIKE %s)" : " and (imgfilename LIKE %s)";
      $mp3 = ".mp3";
      array_push($whereArray, "%".$mp3);
    }
    $sqlWhere .= " ORDER BY imageid DESC";
    //return only 10 records, start on record 16 (OFFSET 15)":
    $sql = "SELECT * FROM ".CSGALLERY_IMAGESTABLENAME . $sqlWhere." LIMIT ".$this->pageSize." OFFSET ".$offset;
    $sqlSearchCount = "SELECT count(imageid) FROM " . CSGALLERY_IMAGESTABLENAME . $sqlWhere;

    if(count($whereArray) > 0){
      $sql = $wpdb->prepare($sql, $whereArray );
      $sqlSearchCount = $wpdb->prepare($sqlSearchCount, $whereArray );
    }
    $totalSearchCount = $wpdb->get_var($sqlSearchCount);
    $imagesResults = $wpdb->get_results($sql, OBJECT);

    $htmlContent = "";
		if(current_user_can('administrator') || current_user_can('editor')){
      $htmlContent .= "<div style='font-size:0.8em'><a href='".get_permalink()."?editgallerycats=1'>Edit categories</a>";
      $htmlContent .= " <a style='margin-left:10px' href='".get_permalink()."?editgalleryplaces=1'>Edit places</a>";
      $htmlContent .= " <a style='margin-left:10px' href='".get_permalink()."?updategalleryimage=1&imageid=0'>Add new image/document/mp3sound/video</a></div>";
    }
    $htmlContent .= $this->MakeSearchSelectHtml($allCategories, $allPlaces, $searchCatId, $searchPlaceId, $searchText, $allTypes, $searchFileType);
    $htmlContent .= $this->MakePrevNextPagingHtml($pageNr, $offset, $totalSearchCount, $searchText, $searchCatId, $searchPlaceId, $searchFileType);
    //Make thumbnails html
		$jsonImagesResult = json_encode($imagesResults, JSON_UNESCAPED_UNICODE);
	  $htmlContent .= "<div style='clear:both'>";
		foreach($imagesResults as $imageItem){
			$htmlContent .= $this->MakeThumbnailHtml($jsonImagesResult, $imageItem, $pageNr, $searchCatId, $searchPlaceId, $searchFileType, $searchText, $catsIdKeyToNameArray, $placesIdKeyToNameArray);
		}
    $htmlContent .= "</div>";

		$htmlContent .= $this->MakePrevNextPagingHtml($pageNr, $offset, $totalSearchCount, $searchText, $searchCatId, $searchPlaceId, $searchFileType);
    $htmlContent .= $this->MakeCSModalPopup(); //Custom modal thumbnail popup
		return $htmlContent;
	}
  private function MakeThumbnailHtml($jsonImagesResult, $imageItem, $pageNr, $searchCatId, $searchPlaceId, $searchFileType, $searchText, $catsIdKeyToNameArray, $placesIdKeyToNameArray)
	{
			$imgUrl = add_query_arg('showgalleryimagebyid', $imageItem -> imageid, get_permalink());
      $imgUrl .= "&pagenr=".$pageNr;
      $imgUrl .= "&searchgallerycategory=".$searchCatId;
      $imgUrl .= "&searchgalleryplace=".$searchPlaceId;
      $imgUrl .= "&searchgallerytype=".$searchFileType;
      $imgUrl .= "&searchgallerytext=".$searchText;
      $showPopupInfoText = $imageItem->isimgpopupshowfulltext;
			//$imgInfoFullText = nl2br($byResult->imgminitext);
      $imgInfoFullText = str_replace(array("\r\n", "\r", "\n"), "<br/>", $imageItem->imgtext);
      $imgInfoShortText = $imageItem->imgtext;
			if(strlen($imageItem->imgtext) > $imageItem->shortimgtextlength)
      {
         $imgInfoShortText = substr($imgInfoShortText, 0, $imageItem->shortimgtextlength);
         if(strlen($imgInfoShortText) > 0){
           $imgInfoShortText .= "...";
         }
      }
			$imgInfoShortText = str_replace(array("\r\n", "\r", "\n"), "<br/>",$imgInfoShortText);
			$catAndPlaceInfoStr = array_key_exists($imageItem->catid, $catsIdKeyToNameArray) ? $catsIdKeyToNameArray[$imageItem->catid]." " : $this->lan->allCategories;
			$catAndPlaceInfoStr .= array_key_exists($imageItem->placeid, $placesIdKeyToNameArray) ? ", " . $placesIdKeyToNameArray[$imageItem->placeid] : $this->lan->allPlaces;

			$htmlContent = "<div class='galleryimagediv' style='position:relative;float:left;overflow:hidden; width:".$this->maxImgContainerWidth."px;height:".$this->maxImgContainerHeight."px'>";

			$htmlContent .= "<div class='thumbnailimagetitle' title='".$catAndPlaceInfoStr."'>
      <a href='".$imgUrl."'>".$imageItem->imgtitle."</a></div>";

			$htmlContent .= "<div style=''>";
      if($imageItem->imgfilename != "" || $imageItem->youtubecode != ""){
				$fileExt = pathinfo($imageItem->imgfilename,PATHINFO_EXTENSION);
        $fileExt = strtolower($fileExt);
        $displayYoutubeVideo = $imageItem->youtubecode == "" ? false : true;
        $displayImage = ($displayYoutubeVideo == false && ($fileExt == "jpg" || $fileExt == "png" || $fileExt == "gif")) ? true : false;
        $displayMP3Sound = $displayYoutubeVideo == false && $fileExt == "mp3" ? true : false;
        $displayDocLink = ($displayYoutubeVideo == false && $displayImage == false && $displayMP3Sound == false && $fileExt != "") ? true : false;

        $imgStyle = "max-height:".$this->maxImgContainerHeight."px;";
        if($imageItem->exactimgheight != 0){
          $imgStyle = $imageItem->keepimgprops == 1 ? "max-height:".$imageItem->exactimgheight."px;" : "height:".$imageItem->exactimgheight."px;";
        }
        if($imageItem->exactimgwidth != 0){
          $imgStyle .= $imageItem->keepimgprops == 1 ? "max-width:".$imageItem->exactimgwidth."px;" : "width:".$imageItem->exactimgwidth."px;";
        }
        $imgStyle .= "padding-left:".$imageItem->exactimgleft."px;";
        $imgStyle .= "padding-top:".$imageItem->exactimgtop."px;";
				if($displayImage == true){
					$htmlContent .= "<img class='img-responsive' src='".$this->pluginUrl."/images/galleryimages/".$imageItem->imgfilename.
						"' onclick='CSGalleryThumbnailClick(".$jsonImagesResult.",".$imageItem -> imageid.",\"".$this->pluginUrl."\");' style='".$imgStyle."'/>";
				}
				else if($displayMP3Sound == true){
					$htmlContent .= "<div style='max-width:50px;margin:auto'><img class='img-responsive' src='".$this->pluginUrl."/images/sound256.png".
						"' onclick='CSGalleryThumbnailClick(".$jsonImagesResult.",".$imageItem -> imageid.",\"".$this->pluginUrl."\");'/><span style='font-size:0.9em;padding-left:5px'>".$fileExt."</span></div>";
				}
				else if($displayDocLink == true){
					$htmlContent .= "<div style='max-width:60px;margin:auto'><a href='".$this->pluginUrl."/images/galleryimages/".$imageItem->imgfilename."'><img class='img-responsive;' src='".$this->pluginUrl."/images/dokument256.png'/></a><span style='font-size:0.9em;padding-left:15px'>".$fileExt."</span></div>";
				}
				else if($displayYoutubeVideo == true){
					$htmlContent .= "<div style='max-width:60px;margin:auto'><img class='img-responsive' src='".$this->pluginUrl."/images/film256.png".
						"' onclick='CSGalleryThumbnailClick(".$jsonImagesResult.",".$imageItem -> imageid.",\"".$this->pluginUrl."\");'/></div>";
				}
			}  //end if($imageItem->imgfilename != "" || $imageItem->youtubecode != "")
      $htmlContent .= "</div>";
      //Thumbnailtext
			$htmlContent .= "<div onclick='CSGalleryThumbnailTextClick(this);' class='thumbnailtext' style='position:absolute; overflow:hidden; width:".($this->maxImgContainerWidth - 10)."px '>
      <div style=''>".$imgInfoShortText;
      if(strlen($imageItem->imgtext) > $imageItem->shortimgtextlength){
          $htmlContent .= " <span class='thumbnailtextreadmorelink' style=''> <a href='".$imgUrl."' style=''>Read more &raquo;</a></span>";
      }
			$htmlContent .= "</div>";
			if(current_user_can('administrator') || current_user_can('editor')){
        $editUrl = add_query_arg('updategalleryimage', "1", get_permalink());
        $editUrl = add_query_arg('imageid', $imageItem -> imageid, $editUrl);
        $editUrl = add_query_arg('pagenr', $pageNr, $editUrl);
        $editUrl = add_query_arg('searchgallerycategory', $searchCatId, $editUrl);
        $editUrl = add_query_arg('searchgalleryplace', $searchPlaceId, $editUrl);
        $editUrl = add_query_arg('searchgallerytype', $searchFileType, $editUrl);
        $editUrl = add_query_arg('searchgallerytext', $searchText, $editUrl);
        $htmlContent .= "<div style='float:right'><a href='".$editUrl."'>Edit</a></div>";
      }
      $htmlContent .= "</div>"; //end thumbnailtext
      $htmlContent .= "</div>"; //end galleryimagediv
		return $htmlContent;
	}
	private function MakeSearchSelectHtml($allCategories, $allPlaces, $searchCatId, $searchPlaceId, $searchText, $allTypes, $searchFileType)
  {
    $catSelected = $searchCatId == 0 ? " selected " : "";
    $placeSelected = $searchPlaceId == 0 ? " selected " : "";
    $typeSelected = ($searchFileType == "" || $searchFileType == "all") ? " selected " : "";
    $searchHtml = "<div class='searchDiv'>";

    $searchHtml .= "<div style='word-wrap:normal;clear:both'>";
    foreach ($allTypes as $typesKey => $typesValue){
      $typeLinkActive = ($typesKey == $searchFileType || ($searchFileType == "" && $typesKey == "all")) ? "searchTypeLinkActive" : "";
      $searchTypeLinksUrl = "?searchgallerycategory=".$searchCatId."&searchgalleryplace=".$searchPlaceId."&searchgallerytype=".$typesKey."&searchgallerytext=".$searchText."&searchgallerybtn=1";
      $searchHtml .= "<a class='searchTypeLink ".$typeLinkActive."' style='float:left; margin:4px;padding:2px' href='".$searchTypeLinksUrl."'>".$typesValue."</a>";
    }
    $searchHtml .= "</div><div style='clear:both'></div>";

    $searchHtml .= "<form action='' method='get'>
    <div><span title='search title or in'>".$this->lan->textSearch."</span>
    <input type='text' class='form-control' name='searchgallerytext' value='".$searchText."' style='max-width:120px;font-size:0.9em;padding:4px' title='search in title or text'/>
    </div>
    <select class='searchCategorySelect' name='searchgallerycategory'>";
      $searchHtml .= "<option value='0' ".$catSelected.">" . $this->lan->allCategories . "</option>";
      foreach($allCategories as $catItem){
        $catSelected = $catItem->catid == $searchCatId ? " selected " : "";
        $searchHtml .= "<option value='".$catItem->catid."' ".$catSelected.">".$catItem->cattitle."</option>";
      }
    $searchHtml .= "</select>
    <select class='searchPlaceSelect' name='searchgalleryplace'>";
      $searchHtml .= "<option value='0' ".$placeSelected.">" . $this->lan->allPlaces . "</option>";
      foreach($allPlaces as $placeItem){
        $placeSelected = $placeItem->placeid == $searchPlaceId ? " selected " : "";
        $searchHtml .= "<option value='".$placeItem->placeid."' ".$placeSelected.">".$placeItem->placetitle."</option>";
      }
    $searchHtml .="</select>";
    /* $searchHtml .= "<select name='searchgallerytype'>";
      foreach ($allTypes as $typesKey => $typesValue) {
        $typeSelected = $typesKey == $searchFileType ? " selected " : "";
        $searchHtml .= "<option value='".$typesKey."' ".$typeSelected.">".$typesValue."</option>";
      }
    $searchHtml .= "</select>"; */
    $searchHtml .= "<input type='hidden' name='searchgallerytype' value='".$searchFileType."'/>
    <input type='submit' class='btn btn-primary' name='searchgallerybtn' value=' ".$this->lan->search." ' style='border:2px solid #808080'/>
    </form></div>";
    return $searchHtml;
  }
	private function MakePrevNextPagingHtml($pageNr, $offset, $totalSearchCount, $searchText, $searchCatId, $searchPlaceId, $searchFileType){
		$fromNum = $offset + 1;
    $toNum = $offset + $this->pageSize;
    $toNum = $toNum > $totalSearchCount ? $totalSearchCount : $toNum;
    $countInfo = $this->lan->total . $totalSearchCount;
    if($totalSearchCount > $this->pageSize){
      $countInfo= $this->lan->showing . $fromNum . $this->lan->to . $toNum . $this->lan->ofTotal . $totalSearchCount;
    }
		$pagingHtml = "<div class='prevNextPagingDiv' style='clear:both;overflow:auto'>";
		$pagingHtml .= "<div style='padding-left:10px'>" . $countInfo . "</div>";
		if($pageNr > 0){
      $pagingHtml .= "<form action='' method='get' style='float:left;margin:5px'>";
      $pagingHtml .= "<input type='hidden' name='pagenr' value='". ($pageNr - 1) ."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerytext' value='".$searchText."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerycategory' value='".$searchCatId."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgalleryplace' value='".$searchPlaceId."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerytype' value='".$searchFileType."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerybtn' value='1'/>";
      $pagingHtml .= "<input type='submit' class='btn btn-link' name='prev' value='&laquo; ".$this->pageSize." ". $this->lan->prev." ' style='border:2px solid #808080'/>";
      $pagingHtml .= "</form>";
    }
    if($totalSearchCount > $offset + $this->pageSize){
      $nextStr = ($offset + $this->pageSize + $this->pageSize) > $totalSearchCount ? $totalSearchCount - ($offset + $this->pageSize)  : $this->pageSize;
      $pagingHtml .= "<form action='' method='get' style='float:left;margin:5px'>";
      $pagingHtml .= "<input type='hidden' name='pagenr' value='". ($pageNr + 1) ."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerytext' value='".$searchText."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerycategory' value='".$searchCatId."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgalleryplace' value='".$searchPlaceId."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerytype' value='".$searchFileType."'/>";
      $pagingHtml .= "<input type='hidden' name='searchgallerybtn' value='1'/>";
      $pagingHtml .= "<input type='submit' class='btn btn-link' name='next' value=' ".$this->lan->next." ".$nextStr." &raquo;' style='border:2px solid #808080'/>";
      $pagingHtml .= "</form>";
    }
		$pagingHtml .= "</div>";
		return $pagingHtml;
	}
	private function MakeCSModalPopup()
	{
		$popupHtml ="<div id='CSGalleryPopupOverlay' style='display:none'>
     <div class='CSGalleryPopupContent' style=''>
       <div style='clear:both;padding-top:5px' class='CSGalleryPopupHeader'>
         <div style='margin:2px;font-weight:bold' id='CSGalleryPopupTitle'>Title</div>
       </div>

       <div class='CSGalleryPopupPrevSidePanel' title='".$this->lan->prev."' style='position:absolute;
            top:0;bottom:0;width:5%;left:0;z-index:1;cursor:pointer;
            border:0px solid black;color:rgba(0,0,0,0);
            background-color:rgba(0,0,0,0.05);
            text-align:middle;padding-top:10%;font-weight:bold;font-size:2.0em;
            '>&laquo;</div>
       <div class='CSGalleryPopupNextSidePanel' title='".$this->lan->next."' style='position:absolute;
            top:0;bottom:0;width:5%;right:0;z-index:1;cursor:pointer;
            border:0px solid black;color:rgba(0,0,0,0);
            background-color:rgba(0,0,0,0.05);
            text-align:middle;padding-top:10%;font-weight:bold;font-size:2.0em;
            '>&raquo;</div>

      <div style='position:absolute;top:0;right:5px;z-index:2'>
      <button id='CSGalleryPopupCloseBtn' style='float:right;margin:5px;padding:5px;font-weight: bold' class='btn btn-primary' title='".$this->lan->close."' type='button'>X</button>
      </div>

       <div style='clear:both' id='CSGalleryPopupBody'></div>

       <div style='padding-top:5px;padding-bottom:5px;' id='CSGalleryPopupFooter'>
         <button id='CSGalleryPopupPrevBtn' style='float:left;margin-left:5px;padding:5px;color:#606060' class='btn btn-primary'>&laquo; ".$this->lan->prev."</button>
         <button id='CSGalleryPopupCloseBtn2' style='padding:5px;color:#606060' class='btn btn-primary'>".$this->lan->close."</button>
         <button id='CSGalleryPopupNextBtn' style='float:right;margin-right:5px;padding:5px;color:#606060' class='btn btn-primary'>".$this->lan->next." &raquo;</button>
       </div>

      </div>
    </div>";
    return $popupHtml;
	}
	public function GetGalleryImageHtmlById($imageResult)
	{
		$searchCatId = isset($_GET["searchgallerycategory"]) ? $_GET["searchgallerycategory"] : 0;
    $searchPlaceId = isset($_GET["searchgalleryplace"]) ? $_GET["searchgalleryplace"] : 0;
    $searchFileType = isset($_GET["searchgallerytype"]) ? $_GET["searchgallerytype"] : "";
    $searchText = isset($_GET["searchgallerytext"]) ? $_GET["searchgallerytext"] : "";
    $pageNr = isset($_GET["pagenr"]) ? $_GET["pagenr"] : 0;

    $htmlContent = "<div style='padding:10px'>";
    $linkUrl = get_permalink()."?pagenr=".$pageNr."&searchgallerycategory=".$searchCatId."&searchgalleryplace=".$searchPlaceId."&searchgallerytype=".$searchFileType."&searchgallerytext=".$searchText."&searchgallerybtn=1";
    $htmlContent .= "<a href='".$linkUrl."' style='font-weight:bold'>&laquo; ".$this->lan->backToArchive."</a>";
		$htmlContent .= "<div style='font-size:1.2em'>".$imageResult->imgtitle."</div>";
		$imgInfoFullText = str_replace(array("\r\n", "\r", "\n"), "<br/>", $imageResult->imgtext);
    if($imageResult->imgfilename != "" || $imageResult->youtubecode != ""){
			$fileExt = pathinfo($imageResult->imgfilename,PATHINFO_EXTENSION);
			$fileExt = strtolower($fileExt);
			$displayYoutubeVideo = $imageResult->youtubecode == "" ? false : true;
      $displayImage = ($displayYoutubeVideo == false && ($fileExt == "jpg" || $fileExt == "png" || $fileExt == "gif")) ? true : false;
      $displayMP3Sound = $displayYoutubeVideo == false && $fileExt == "mp3" ? true : false;
      $displayDocLink = ($displayYoutubeVideo == false && $displayImage == false && $displayMP3Sound == false && $fileExt != "") ? true : false;
      $imgUrl = $this->pluginUrl."/images/galleryimages/".$imageResult->imgfilename;
      $showPopupInfoText = $imageResult->isimgpopupshowfulltext;
			$jsonImage = json_encode(array($imageResult), JSON_UNESCAPED_UNICODE);
			$imgClick = "' onclick='CSGalleryThumbnailClick(".$jsonImage.",".$imageResult -> imageid.",\"".$this->pluginUrl."\");' ";
      $htmlContent .= "<div>";
			if($displayImage == true){
				$htmlContent .= "<img src='".$imgUrl."' ".$imgClick." style='cursor:pointer'/>";
			}
			else if($displayDocLink == true){
        $htmlContent .= "<a href='".$this->pluginUrl."/images/galleryimages/".$imageResult->imgfilename."'>Document - ".$fileExt."</a>";
      }
			else if($displayMP3Sound == true){
        $htmlContent .= "<div>
        <audio class='wp-audio-shortcode' preload='none' style='width: 100%; margin-bottom:0; visibility: visible;' controls='controls'>
        <source type='audio/mpeg' src='".$imgUrl."' />
        </audio>
        </div>";
      }
			else if($displayYoutubeVideo == true){
        $youtubeCode = $imageResult->youtubecode;
        //$youtubeCode = "8cvBnJDMVxA";//våmhus
        $youtubeUrl = "https://www.youtube.com/embed/".$youtubeCode."?feature=player_detailpage&wmode=transparent";
        $htmlContent .= "<div style='overflow:hidden;padding-bottom:56.25%;position:relative;height:0;'>
        <iframe id='youtubeIFrame' width='640' height='360'
          src='".$youtubeUrl."'
          frameborder='0' allow='autoplay; encrypted-media' allowfullscreen
          style='left:0;top:0;height:100%;width:100%;position:absolute;'>
        </iframe>
        </div>";
      }
			$htmlContent .= "</div>";
		}
		$htmlContent .= "<div style='clear:both'>".$imgInfoFullText. "</div>";
    $htmlContent .= "</div>";
		$htmlContent .= $this->MakeCSModalPopup(); //Custom modal thumbnail popup
		return $htmlContent;
	}
	public function GetEditCategoriesHtml()
	{
    $htmlContent = "<div><a href='".get_permalink()."'>&laquo; back</a></div>";
    $htmlContent .= "<div style='float:left;font-size:1.2em'>Edit categories</div>";
    $htmlContent .= "<div style='float:right'>";
		$htmlContent .= "<form id='newcsgallerycatform' name='newcsgallerycatform' method='post' action='' onsubmit='return ajaxAddNewCSGalleryCategory(this);'>
		<img class='spinnerimg' src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
		<input type='hidden' name='pluginUrl' value='".$this->pluginUrl."' />
		<div class='newCatOkresult' style='font-weight:bold;color:#00aa00'></div>
		<div class='newCatErrorResult' style='font-weight:bold;color:#cc0000'></div>
    <input type='submit' name='createnewgallerycatbtn' id='createnewgallerycatbtn' class='btn btn-primary' value='Add new category'/>
    </form></div>";
		global $wpdb;
		$catResults = $wpdb->get_results("SELECT * FROM ".CSGALLERY_CATEGORIESTABLENAME." ORDER BY cattitle ASC", OBJECT);
    $htmlContent .= "<div id='csgalleryEditAllCatsDiv' style='clear:both'>";
    foreach($catResults as $catItem){
			$catDivId = "csgalleryEditCatDiv" . $catItem->catid;
      $htmlContent .= "<div id='".$catDivId."' style='clear:both;overflow:auto;border:4px solid #404040;background-color:#aaaaaa; padding:5px; margin-bottom:15px'>";

        $htmlContent .= "<div>";
				$htmlContent .= "<form action='' method='post' onsubmit='return ajaxEditCSGalleryCategory(this);'>
				<input type='text' class='form-control' name='updateCatTitleInput' value='".$catItem->cattitle."' required='required' style='font-weight:bold'/> <br />
				<input type='hidden' name='updateCatId' value='".$catItem->catid."' />
	      <img class='spinnerimg' src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
	      <div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>
	      <div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>
	      <input class='btn btn-primary' name='updateCatBtn' type='submit' value='Update' />";
				$htmlContent .= "</form>";
				$htmlContent .= "</div>";

			  $htmlContent .= "<div style='float:right;margin:5px'>";
				$htmlContent .= "<form action='' method='post' onsubmit='return ajaxDeleteCSGalleryCategory(this);'>";
				$htmlContent .= "<input type='hidden' name='deleteCatId' value='".$catItem->catid."' />
				<input type='hidden' name='deleteCatName' value='".$catItem->cattitle."'/>
				<img class='spinnerimg' src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
				<div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>
				<div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>
				<input class='btn btn-primary' name='deleteCatBtn' type='submit' value='Delete category' />";
				$htmlContent .= "</form>";
			  $htmlContent .= "</div>";

			$htmlContent .= "</div>"; //end csgalleryEditCatDiv + catid
		}
		$htmlContent .= "</div>";
		return $htmlContent;
	}
	public function GetEditPlacesHtml()
	{
		$htmlContent = "<div><a href='".get_permalink()."'>&laquo; back</a></div>";
    $htmlContent .= "<div style='float:left;font-size:1.2em'>Edit places</div>";
    $htmlContent .= "<div style='float:right'>";
		$htmlContent .= "<form id='newcsgalleryplaceform' name='newcsgalleryplaceform' method='post' action='' onsubmit='return ajaxAddNewCSGalleryPlace(this);'>
		<img class='spinnerimg' src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
		<input type='hidden' name='pluginUrl' value='".$this->pluginUrl."' />
		<div class='newPlaceOkresult' style='font-weight:bold;color:#00aa00'></div>
		<div class='newPlaceErrorResult' style='font-weight:bold;color:#cc0000'></div>
    <input type='submit' name='createnewgalleryplacebtn' id='createnewgalleryplacebtn' class='btn btn-primary' value='Add new place'/>
    </form></div>";
		global $wpdb;
		$placeResults = $wpdb->get_results("SELECT * FROM ".CSGALLERY_PLACESTABLENAME." ORDER BY placetitle ASC", OBJECT);
    $htmlContent .= "<div id='csgalleryEditAllPlacesDiv' style='clear:both'>";
    foreach($placeResults as $placeItem){
			$placeDivId = "csgalleryEditPlaceDiv" . $placeItem->placeid;
      $htmlContent .= "<div id='".$placeDivId."' style='clear:both;overflow:auto;border:4px solid #404040;background-color:#aaaaaa; padding:5px; margin-bottom:15px'>";

        $htmlContent .= "<div>";
				$htmlContent .= "<form action='' method='post' onsubmit='return ajaxEditCSGalleryPlace(this);'>
				<input type='text' class='form-control' name='updatePlaceTitleInput' value='".$placeItem->placetitle."' required='required' style='font-weight:bold'/> <br />
				<input type='hidden' name='updatePlaceId' value='".$placeItem->placeid."' />
	      <img class='spinnerimg' src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
	      <div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>
	      <div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>
	      <input class='btn btn-primary' name='updatePlaceBtn' type='submit' value='Update' />";
				$htmlContent .= "</form>";
				$htmlContent .= "</div>";

			  $htmlContent .= "<div style='float:right;margin:5px'>";
				$htmlContent .= "<form action='' method='post' onsubmit='return ajaxDeleteCSGalleryPlace(this);'>";
				$htmlContent .= "<input type='hidden' name='deletePlaceId' value='".$placeItem->placeid."' />
				<input type='hidden' name='deletePlaceName' value='".$placeItem->placetitle."'/>
				<img class='spinnerimg' src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
				<div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>
				<div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>
				<input class='btn btn-primary' name='deletePlaceBtn' type='submit' value='Delete place' />";
				$htmlContent .= "</form>";
			  $htmlContent .= "</div>";

			$htmlContent .= "</div>"; //end csgalleryEditPlacesDiv + placeid
		}
		$htmlContent .= "</div>";
		return $htmlContent;
	}
	public function GetUpdateGalleryImageHtml($paramImageId)
	{
		$headLbl = "Add new imagepanel";
    $imageId = "0";
    $catId = "0";
    $placeId = "0";
    $imgTitle = "New imagepanel";
    $imgText = "New imagepanel";
    $imgFileName = "";
    $imgFullWidth = "0";
    $imgFullHeight = "0";
    $exactMiniImgWidth = "0";
    $exactMiniImgHeight = "0";
    $exactMiniImgLeft = "0";
    $exactMiniImgTop = "0";
    $keepImgProps = "1";
    $isImgPopupShowFullText = "1";
    $linkUrl = "";
    $linkText = "";
    $shortImgTextLength = "1000";
    $youtubeCode = "";

		global $wpdb;
    $categoriesResults = $wpdb->get_results("SELECT * FROM ".CSGALLERY_CATEGORIESTABLENAME." ORDER BY catvalue ASC", OBJECT);
    $placesResults = $wpdb->get_results("SELECT * FROM ".CSGALLERY_PLACESTABLENAME." ORDER BY placevalue ASC", OBJECT);
		//$imageResult = $wpdb->get_row( $wpdb->prepare(
		 //"SELECT * FROM ".CSGALLERY_IMAGESTABLENAME." WHERE imageid = %s", $paramImageId) );
		 $imageResultArray = $wpdb->get_results( $wpdb->prepare(
 		 "SELECT * FROM ".CSGALLERY_IMAGESTABLENAME." WHERE imageid = %s", $paramImageId) );

		 if(count($imageResultArray) == 1){
			  $imageResult = $imageResultArray[0];
        $headLbl = "Update";
        $imageId = $imageResult->imageid;
        $catId = $imageResult->catid;
        $placeId = $imageResult->placeid;
        $imgTitle = $imageResult->imgtitle;
        $imgText = $imageResult->imgtext;
        $exactMiniImgWidth = $imageResult->exactimgwidth;
        $exactMiniImgHeight = $imageResult->exactimgheight;
        $exactMiniImgLeft = $imageResult->exactimgleft;
        $exactMiniImgTop = $imageResult->exactimgtop;
        $keepImgProps = $imageResult->keepimgprops;
        $isImgPopupShowFullText = $imageResult->isimgpopupshowfulltext;
        $shortImgTextLength = $imageResult->shortimgtextlength;
        $imgFileName = $imageResult->imgfilename;
        $imgFullWidth = $imageResult->imgfullwidth;
        $imgFullHeight = $imageResult->imgfullheight;
        $youtubeCode = $imageResult->youtubecode;
     }
		 $isKeepImgPropsChecked = $keepImgProps == 1 ? "checked" : "";
     $isShowPopupTextChecked = $isImgPopupShowFullText == 1 ? "checked" : "";

		 $fileExt = pathinfo($imgFileName,PATHINFO_EXTENSION);
     $fileExt = strtolower($fileExt);
     $displayImage = ($fileExt == "jpg" || $fileExt == "png" || $fileExt == "gif") ? "block" : "none";

     $docLinkText = $fileExt == "mp3" ? "sound " : "Document ";
     $displayDocLink = ($displayImage == "none" && $fileExt != "") ? "block" : "none";

     $displayUploadFileForm = $imageId == "" || $imageId == "0" ? "none" : "block";
     $displayDeleteFileOnlyForm = $imgFileName == "" ? "none" : "block";

		 $searchCatId = isset($_GET["searchgallerycategory"]) ? $_GET["searchgallerycategory"] : 0;
     $searchPlaceId = isset($_GET["searchgalleryplace"]) ? $_GET["searchgalleryplace"] : 0;
     $searchFileType = isset($_GET["searchgallerytype"]) ? $_GET["searchgallerytype"] : "";
     $searchText = isset($_GET["searchgallerytext"]) ? $_GET["searchgallerytext"] : "";
     $pageNr = isset($_GET["pagenr"]) ? $_GET["pagenr"] : 0;
     $linkBackUrl = get_permalink()."?pagenr=".$pageNr."&searchgallerycategory=".$searchCatId."&searchgalleryplace=".$searchPlaceId."&searchgallerytype=".$searchFileType."&searchgallerytext=".$searchText."&searchgallerybtn=1";
     $htmlContent = "<div><a href='".$linkBackUrl."'>&laquo; back</a></div>";
     $htmlContent .= "<div style='clear:both'>";
		 $displayUpdateImageTextForm = "block";
     if($paramImageId != 0 && count($imageResultArray) == 0){
       $htmlContent .= "<div style='font-size:1.2em;font-weight:bold;color:#ff0000'>Imagepanel not found</div>";
       $displayUpdateImageTextForm = "none";
     }
		 $htmlContent .= "<div id='csgalleryupdateImageTextFormDiv' style='float:left; display:".$displayUpdateImageTextForm."; min-width:360px; padding:2px;padding-bottom:10px'>
     <form action='' method='post' onsubmit='return ajaxUpdateCSGalleryImageText(this);'>
     <span style='font-size:0.9em'>Title</span><br />
     <input type='text' class='form-control' name='updateImageTitleInput' value='".$imgTitle."' style='font-weight:bold'/> <br />
     <span style='font-size:0.9em'>Imagetext</span><br />
     <textarea rows='6' class='form-control' name='updateImageTextInput' style='font-size:0.9em'>".$imgText."</textarea> <br />
     <div>
     <span style='font-size:0.9em'>Youtube code:</span>
     <input type='text' class='form-control' name='updateImageYoutubeCodeInput' value='".$youtubeCode."' style='max-width:260px;font-size:0.9em;padding:4px'/>
     </div>";
		 if(!empty($youtubeCode) && !empty($imgFileName)){
       $htmlContent .= "<div style='font-size:0.9em'>Youtube code must be empty, otherwise image/sound/document are not displayed in the archive.</div>";
     }
		 $htmlContent .= "<div>
     <select name='updateCSGalleryCatSelect'>";
       $catSelected = $catId == 0 ? " selected " : "";
       $htmlContent .= "<option value='0' ".$catSelected.">All categories</option>";
       foreach($categoriesResults as $catResult){
         $catSelected = $catResult->catid == $catId ? " selected " : "";
         $htmlContent .= "<option value='".$catResult->catid."' ".$catSelected.">".$catResult->cattitle."</option>";
       }
     $htmlContent .= "</select>
     <select name='updateCSGalleryPlaceSelect'>";
       $placeSelected = $placeId == 0 ? " selected " : "";
       $htmlContent .= "<option value='0' ".$placeSelected.">All places</option>";
       foreach($placesResults as $placeResult){
         $placeSelected = $placeResult->placeid == $placeId ? " selected " : "";
         $htmlContent .= "<option value='".$placeResult->placeid."' ".$placeSelected.">".$placeResult->placetitle."</option>";
       }
     $htmlContent .= "</select>";
     $htmlContent .= "</div>";

		 $htmlContent .= "<div style='border:1px solid #606060;padding:5px;font-size:0.8em'>
     <div>Thumbnailimage</div>
     <span>Width 0=auto:</span>
     <input type='text' name='updateExactMiniImgWidth' value='".$exactMiniImgWidth."' style='width:40px;font-size:0.9em;padding:3px'/>
     <span>Height 0=auto:</span>
     <input type='text' name='updateExactMiniImgHeight' value='".$exactMiniImgHeight."' style='width:40px;font-size:0.9em;padding:3px' />
     <br/><span>Pos left:</span>
     <input type='text' name='updateExactMiniImgLeft' value='".$exactMiniImgLeft."' style='width:40px;font-size:0.9em;padding:3px'/>
     <span>Pos top:</span>
     <input type='text' name='updateExactMiniImgTop' value='".$exactMiniImgTop."' style='width:40px;font-size:0.9em;padding:3px'/>
     <span title='Keep proportions'>Props:</span>
     <input type='checkbox' name='updateKeepImgProps' ".$isKeepImgPropsChecked."/>
     <span title='Show fulltext in popup'>Popup text:</span>
     <input type='checkbox' name='updateShowPopupText' ".$isShowPopupTextChecked."/>
     <span>Shorttext length:</span>
     <input type='text' name='updateShortTextLength' value='".$shortImgTextLength."' style='width:40px;font-size:0.9em;padding:3px'/>
     </div>
     <input type='hidden' name='updateImageId' value='".$imageId."' />
     <img src='".$this->pluginUrl."/images/spinner.gif' style='display:none'/>
     <div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>
     <div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>
     <input class='btn btn-primary' name='updateImageTextBtn' type='submit' value='".$headLbl."' />
     </form></div>";

		 $htmlContent .= "<div style='float:left;padding:2px; padding-bottom:10px; max-width:280px'>
     <form id='csgalleryuploadfileform' style='display:".$displayUploadFileForm."' action='' enctype='multipart/form-data' method='post' class='form-horizontal'>

     <div style='font-size:0.9em'>
		 File:
     <a class='galleryEditImgFileLink' href='".$this->pluginUrl."/images/galleryimages/".$imgFileName."' target='_blank'>".$imgFileName."</a>
		 <span class='galleryImgFileLblSize' style='display:".($imgFileName == "" ? "none" : "block")."'> width:".$imgFullWidth."px height:".$imgFullHeight."px</span>
		 </div>

     <img class='galleryImgMiniimage img-responsive' src='".$this->pluginUrl."/images/galleryimages/".$imgFileName."' style='display:".$displayImage.";max-height:300px' />
     <a class='galleryDocLink' href='".$this->pluginUrl."/images/galleryimages/".$imgFileName."' style='display:".  $displayDocLink .";'>".$docLinkText." - ".$fileExt."</a>

     <input type='hidden' name='imageid' value='".$imageId."' />
     <input type='hidden' name='imagesbaseurl' value='".$this->pluginUrl."' />
     <input type='hidden' name='plugindir' value='".$this->pluginDir."'/>
     <input type='hidden' name='oldfilename' value='". $imgFileName."' />
     <div style='font-size:0.8em'>Upload file (jpg, png, gif, pdf, mp3, zip) max 60 mb</div>
     <input type='file' name='imgFileInput' class='form-control' style='float:left' />
     <div><img class='spinnerimg' src='". $this->pluginUrl."/images/spinner.gif' style='display:none;float:left'/></div>
     <div class='updateOkresult' style='clear:both;font-size:0.9em;color:#20aa20'></div>
     <div class='updateErrorResult' style='clear:both;font-size:0.9em;color:#cc0000'></div>
     <input type='submit' name='imgFileSubmit' class='btn btn-default' value='Upload file' onclick='return ajaxUploadCSGalleryImageFile(this.form);' />
     </form>
     <div style='padding:5px; padding-top:10px'>
     <form id='csgallerydeleteimageonlyform' style='display:".$displayDeleteFileOnlyForm."' action='' method='post' onsubmit='return ajaxDeleteCSGalleryImageOnly(this);'>
       <input type='hidden' name='deleteimageid' value='".$imageId."' />
       <input type='hidden' name='plugindir' value='".$this->pluginDir."'/>
       <div><img class='spinnerimg' src='". $this->pluginUrl."/images/spinner.gif' style='display:none;float:left'/></div>
       <div class='updateOkresult' style='clear:both;font-weight:bold;color:#20aa20'></div>
       <div class='updateErrorResult' style='clear:both;font-weight:bold;color:#cc0000'></div>
       <input class='btn btn-primary' name='deleteGalleriImageOnlyBtn' type='submit' value='Delete file' />
     </form>
     </div>
     </div>";

		 $htmlContent .= "<div style='float:right;margin:5px'>
     <form id='csgallerydeleteimageform' style='display:".$displayUploadFileForm."' action='' method='post' onsubmit='return ajaxDeleteCSGalleryImagePanel(this);'>
     <input type='hidden' name='deleteimageid' value='".$imageId."' />
     <input type='hidden' name='plugindir' value='".$this->pluginDir."'/>
     <div><img class='spinnerimg' src='". $this->pluginUrl."/images/spinner.gif' style='display:none;float:left'/></div>
     <div class='updateOkresult' style='clear:both;font-weight:bold;color:#20aa20'></div>
     <div class='updateErrorResult' style='clear:both;font-weight:bold;color:#cc0000'></div>
     <input class='btn btn-primary' name='deleteGalleriImageBtn' type='submit' value='Delete file and text' />
     </form>
     </div>";

		 $htmlContent .= "</div>";
		return $htmlContent;
	}

  public static function activate(){
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    if ( !$wpdb->get_var( "SHOW TABLES LIKE '".CSGALLERY_IMAGESTABLENAME."'" ) )
    {
      //_id int(11) NOT NULL AUTO_INCREMENT,
      $sql = "CREATE TABLE `".CSGALLERY_IMAGESTABLENAME."` (
            imageid INT(11) NOT NULL AUTO_INCREMENT,
            catid INT(11) DEFAULT 0,
            placeid INT(11) DEFAULT 0,
            imgtitle varchar(80) DEFAULT 'Ny' NOT NULL,
            imgtext mediumtext DEFAULT '',
            imgfilename varchar(100) DEFAULT '',
            imgfullwidth INT(11) DEFAULT 0,
            imgfullheight INT(11) DEFAULT 0,
            exactimgwidth INT(10) DEFAULT 0,
            exactimgheight INT(10) DEFAULT 0,
            exactimgleft INT(10) DEFAULT 0,
            exactimgtop INT(10) DEFAULT 0,
            keepimgprops TINYINT(1) DEFAULT 1,
            isimgpopupshowfulltext tinyint(1) DEFAULT 1,
            linkurl varchar(120) DEFAULT '',
            linktext varchar(80) DEFAULT '',
            shortimgtextlength INT(10) DEFAULT 1000,
            youtubecode varchar(100) DEFAULT '',
            PRIMARY KEY  (imageid)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8; AUTO_INCREMENT=1;";
      dbDelta( $sql );
      //$newGalleryImageId = str_replace(".", "", uniqid('', true));
      /*
      $insertDBResult = $wpdb->insert( $this->byarConfigTableName,
        array('configid' => $newByarConfigId,'frontpagetitle' => "Byar",
        'frontpagetext' => "Byar text"),
        array( '%s','%s','%s')
      );*/
    }
		if ( !$wpdb->get_var( "SHOW TABLES LIKE '".CSGALLERY_CATEGORIESTABLENAME."'" ) )
    {
      //_id int(11) NOT NULL AUTO_INCREMENT,
      $sql = "CREATE TABLE `".CSGALLERY_CATEGORIESTABLENAME."` (
            catid INT(11) NOT NULL AUTO_INCREMENT,
            cattitle varchar(80) DEFAULT 'Ny kategori' NOT NULL,
            catvalue varchar(80) DEFAULT 'nykategori' NOT NULL,
            PRIMARY KEY  (catid)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8; AUTO_INCREMENT=1;";
      dbDelta( $sql );
    }
		if ( !$wpdb->get_var( "SHOW TABLES LIKE '".CSGALLERY_PLACESTABLENAME."'" ) )
    {
      //_id int(11) NOT NULL AUTO_INCREMENT,
      $sql = "CREATE TABLE `".CSGALLERY_PLACESTABLENAME."` (
            placeid INT(11) NOT NULL AUTO_INCREMENT,
            placetitle varchar(80) DEFAULT 'Ny plats' NOT NULL,
            placevalue varchar(80) DEFAULT 'nyplats' NOT NULL,
            PRIMARY KEY  (placeid)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8; AUTO_INCREMENT=1;";
      dbDelta( $sql );
    }
  }
  public static function deActivate(){
  }
  public static function unInstall(){
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS " . CSGALLERY_IMAGESTABLENAME );
    $wpdb->query( "DROP TABLE IF EXISTS " . CSGALLERY_CATEGORIESTABLENAME );
    $wpdb->query( "DROP TABLE IF EXISTS " . CSGALLERY_PLACESTABLENAME );
  }
	//** Ajax handlers **/
	public static function ajaxupdateorinsertcsgalleryimagetext_handler(){
		$arrayData = array('msg' => 'ok', 'newimageid' => '');
		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			$arrayData[msg] = 'Access denied. Only administrator or editors';
		}
		$imageId = trim(stripslashes($_POST['imageid']));
	  $arrayData[msg] = !isset($imageId) ? 'Error No Id found' : $arrayData[msg];
	  $catId = trim(stripslashes($_POST['catid']));
	  $placeId = trim(stripslashes($_POST['placeid']));
	  $title = trim(stripslashes($_POST['title']));
	  $descript = trim(stripslashes($_POST['descript']));
	  $youtubeCode = trim(stripslashes($_POST['youtubecode']));

		$exactMiniImgWidth = trim(stripslashes($_POST['exactminiimgwidth']));
	  $exactMiniImgHeight = trim(stripslashes($_POST['exactminiimgheight']));
	  $exactMiniImgLeft = trim(stripslashes($_POST['exactminiimgleft']));
	  $exactMiniImgTop = trim(stripslashes($_POST['exactminiimgtop']));
	  $shortTextLength = trim(stripslashes($_POST['shorttextlength']));
	  $isShowFullText = $_POST['isshowfulltext'];
	  $isShowFullTextNum = ($isShowFullText === 1 || $isShowFullText === "1" || $isShowFullText === true || $isShowFullText === "true") ? 1 : 0;
	  $isKeepImgProps = $_POST['iskeepimgprops'];
	  $isKeepImgPropsNum = ($isKeepImgProps === 1 || $isKeepImgProps === "1" || $isKeepImgProps === true || $isKeepImgProps === "true") ? 1 : 0;
		if(strlen($title) > 79){
	    $title = substr($title, 0, 79);
	  }
	  if(strlen($youtubeCode) > 99){
	    $youtubeCode = substr($youtubeCode, 0, 99);
	  }
		global $wpdb;
	  if($arrayData[msg] == "ok"){
			if($imageId == 0){
				$insertDBResult = $wpdb->insert(CSGALLERY_IMAGESTABLENAME,
	        array('imgtitle' => $title, 'imgtext' => $descript,
	           'catid' => $catId, 'placeid' => $placeId,
	           'exactimgwidth' => $exactMiniImgWidth,
	           'exactimgheight' => $exactMiniImgHeight,
	           'exactimgleft' => $exactMiniImgLeft,
	           'exactimgtop' => $exactMiniImgTop,
	           'shortimgtextlength' => $shortTextLength,
	           'keepimgprops' => $isKeepImgPropsNum,
	           'isimgpopupshowfulltext' => $isShowFullTextNum,
	           'youtubecode' => $youtubeCode),
	        array('%s','%s','%d','%d','%d','%d','%d','%d','%d','%d','%d','%s')
	       );
	       if($insertDBResult != 1){
	         $arrayData[msg] = "Error, could not create new imagepanel";
	       }
	       else{
	         $arrayData[newimageid] = $wpdb->insert_id;
	       }
			}
			else{
				$updateDBResult = $wpdb->update(CSGALLERY_IMAGESTABLENAME,
	      array('imgtitle' => $title, 'imgtext' => $descript,
	            'catid' => $catId, 'placeid' => $placeId,
	            'exactimgwidth' => $exactMiniImgWidth,
	            'exactimgheight' => $exactMiniImgHeight,
	            'exactimgleft' => $exactMiniImgLeft,
	            'exactimgtop' => $exactMiniImgTop,
	            'shortimgtextlength' => $shortTextLength,
	            'keepimgprops' => $isKeepImgPropsNum,
	            'isimgpopupshowfulltext' => $isShowFullTextNum,
	            'youtubecode' => $youtubeCode
	          ),
	        array( 'imageid' => $imageId ),
	        array( '%s','%s','%d','%d','%d','%d','%d','%d','%d','%d','%d','%s'),
	        array( '%d' ) //where format %d = number
	      );
	      if($updateDBResult === false){
	        $arrayData[msg] = "Error, could not update imagepanel ".$updateDBResult;
	      }
			}
		}
		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		wp_die();
	} //end ajaxupdateorinsertcsgalleryimagetext_handler

  public static function ajaxdeletecsgalleryimagepanel_handler(){
		$arrayData = array('errormsg' => '');
		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			$arrayData[errormsg] = 'Access denied. Only administrator or editors';
		}
	  $pluginDir = $_POST["plugindir"];
	  $imageId = stripslashes($_POST["deleteimageid"]);
	  if(empty($imageId) || $imageId == "0"){
	    $arrayData[errormsg] = "Missing id or id 0";
	  }
		if ($arrayData[errormsg] == ""){
			$deleteFilePath = $pluginDir . "/images/galleryimages/";
	    global $wpdb;
	    $deleteRow = $wpdb->get_row("SELECT * FROM ".CSGALLERY_IMAGESTABLENAME." WHERE imageid = '".$imageId."'", OBJECT);
	    $filenameToDelete = "";
	    if($deleteRow == null || count($deleteRow) == 0){
	      $arrayData[errormsg] = "Imagepanel could not be found.";
	    }
	    else{
	      $filenameToDelete = $deleteRow->imgfilename;
	      /*$updateDBResult = $wpdb->update(CSGALLERY_IMAGESTABLENAME,
	        array('imgfilename' => "",
	        'imgfullwidth' => 0, 'imgfullheight' => 0),
	        array( 'imageid' => $imageId ),
	        array( '%s', '%d', '%d'),
	        array( '%d' ) //where format %d = number
	      );*/
	      $deleteResult = $wpdb->delete(CSGALLERY_IMAGESTABLENAME,
	        array( 'imageid' => $imageId ), array( '%d' ) );
	      if($deleteResult === false){
	        $arrayData[errormsg] .= "Could not update database.";
	      }
	      else{
	        if($filenameToDelete != ""){
	          if(file_exists($deleteFilePath.$filenameToDelete)){
	            if (!unlink($deleteFilePath.$filenameToDelete)){
	              $arrayData[errormsg] .= "Could not delete file.";
	            }
	          }
	        }
	      }
	    }
		} //end if ($arrayData[errormsg] == "")
		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		wp_die();
	} //end ajaxdeletecsgalleryimagepanel_handler

	public static function ajaxuploadcsgalleryimagefile_handler(){
		$arrayData = array('errormsg' => '', 'newfilename' => '', 'imgwidth' => 0, 'imgheight' => 0); //'{"msg": "No data"}';
		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			$arrayData[errormsg] = 'Access denied. Only administrator or editors';
		}
		$imageId = stripslashes($_POST["imageId"]);
		$oldFileName = $_POST["oldFilename"];
	  $pluginDir = $_POST["plugindir"];
		if( !(isset($_FILES['imagefile']) && $_FILES['imagefile']['error'] == UPLOAD_ERR_OK ) ){
			$arrayData[errormsg] = "No file uploaded";
		}
		if($arrayData[errormsg] == ""){
			$saveFilePath = $pluginDir . "/images/galleryimages/";
	    $uploadedFileName = strtolower($_FILES["imagefile"]["name"]);
	    $imageFileType = pathinfo($uploadedFileName,PATHINFO_EXTENSION);
	    //if(getimagesize($_FILES["imagefile"]["tmp_name"]) == false){
				//$arrayData[errormsg] = "Invalid filtype. Only jpg, png or gif";
			//}
			//if(!($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "gif")){
				//$arrayData[errormsg] = "Invalid filtype. Only jpg, png or gif";
			//}
	    if($imageFileType == "exe" || $imageFileType == "bat" || $imageFileType == ""){
	      $arrayData[errormsg] = "Invalid filetype";
	    }
			//62914560-60mb 52428800-50mb 3145728-3mb 2097152-2mb 1048576-1mb //524288-500kb
			if ($_FILES["imagefile"]["size"] > 62914560){
				$arrayData[errormsg] .= "Filesize max 60mb.";
			}
			$imgWidth = 0;
	    $imgHeight = 0;
	    $imgMime = "";
	    $imgInfo = getimagesize($_FILES["imagefile"]["tmp_name"]);
	    if($imgInfo != false){
	      $imgWidth = $imgInfo[0];
	      $imgHeight = $imgInfo[1];
	    }
			$saveFileNameWithoutExtension = pathinfo($uploadedFileName, PATHINFO_FILENAME);
			$saveFileNameWithoutExtension = preg_replace("/[^a-zA-Z0-9\s]/", "", $saveFileNameWithoutExtension);
			if(strlen($saveFileNameWithoutExtension) < 1){
				$arrayData[errormsg] .= "Invalid filename";
			}
			if(strlen($saveFileNameWithoutExtension) > 30){
				$saveFileNameWithoutExtension = substr($saveFileNameWithoutExtension, 0, 29);
			}
			if ($arrayData[errormsg] == ""){
				$ticks = microtime();
	  		$ticks = number_format(($ticks * 10000000) + 621355968000000000 , 0, '.', '');
	  		$saveFileName = $saveFileNameWithoutExtension.$ticks.".".$imageFileType;
	  		global $wpdb;
	  		$updateDBResult = $wpdb->update(CSGALLERY_IMAGESTABLENAME,
	  			array('imgfilename' => $saveFileName,
	        'imgfullwidth' => $imgWidth, 'imgfullheight' => $imgHeight),
	  			array( 'imageid' => $imageId ),
	  			array( '%s', '%d', '%d'),
	  			array( '%d' ) //where format %d = number
	  		);
				if($updateDBResult === false || $updateDBResult === 0){
	  			$arrayData[errormsg] .= "Could not update database.";
	  		}
				else if(!move_uploaded_file($_FILES["imagefile"]["tmp_name"], $saveFilePath.$saveFileName)){
	  			 $arrayData[errormsg] .= "Could not save file.";
	  		}
				else{
	  				if ($oldFileName != "" && file_exists($saveFilePath.$oldFileName)){
	  					if (!unlink($saveFilePath.$oldFileName)){
	  						$arrayData[errormsg] .= "Could not delete old file.";
	  					}
	  				}
	  				$arrayData[imgwidth] = $imgWidth;
	          $arrayData[imgheight] = $imgHeight;
	          $arrayData[newfilename] = $saveFileName;
	  		}
			} //end inner if($arrayData[errormsg] == "")
		} //end outer if($arrayData[errormsg] == "")
		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		wp_die();
	} //end ajaxuploadcsgalleryimagefile_handler

	public static function ajaxdeletecsgalleryimageonly_handler(){
		$arrayData = array('errormsg' => '');
		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			$arrayData[errormsg] = 'Access denied. Only administrator or editors';
		}
		$pluginDir = $_POST["plugindir"];
	  $imageId = stripslashes($_POST["deleteimageid"]);
	  if(empty($imageId) || $imageId == "0"){
	    $arrayData[errormsg] = "Id missing or 0";
	  }
    if ($arrayData[errormsg] == ""){
			$deleteFilePath = $pluginDir . "/images/galleryimages/";
	    global $wpdb;
	    $deleteRow = $wpdb->get_row("SELECT * FROM ".CSGALLERY_IMAGESTABLENAME." WHERE imageid = '".$imageId."'", OBJECT);
	    $filenameToDelete = "";
	    if($deleteRow == null || count($deleteRow) == 0){
	      $arrayData[errormsg] = "File could not be found.";
	    }
	    else{
	      $filenameToDelete = $deleteRow->imgfilename;
	      $deleteResult = $wpdb->update(CSGALLERY_IMAGESTABLENAME,
	        array('imgfilename' => "",
	        'imgfullwidth' => 0, 'imgfullheight' => 0),
	        array( 'imageid' => $imageId ),
	        array( '%s', '%d', '%d'),
	        array( '%d' ) //where format %d = number
	      );
	      if($deleteResult === false){
	        $arrayData[errormsg] .= "Could not update database.";
	      }
	      else{
	        if($filenameToDelete != ""){
	          if(file_exists($deleteFilePath.$filenameToDelete)){
	            if (!unlink($deleteFilePath.$filenameToDelete)){
	              $arrayData[errormsg] .= "Could not delete the file.";
	            }
	          }
	        }
	      }
	    }
	  }
		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		wp_die();
	}

  public static function ajaxaddnewcsgallerycategory_handler(){
		$arrayData = array('errormsg' => '', 'newcatid' => '');
		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			$arrayData[errormsg] = 'Access denied. Only administrator or editors';
		}
	  if($arrayData[errormsg] == ""){
			  global $wpdb;
				$insertDBResult = $wpdb->insert(CSGALLERY_CATEGORIESTABLENAME,
	        array('cattitle' => 'New category',
	           'catvalue' => 'newcategory'),
	        array('%s','%s')
	       );
	       if($insertDBResult != 1){
	         $arrayData[errormsg] = "Error, could not create new category";
	       }
	       else{
	         $arrayData[newcatid] = $wpdb->insert_id;
	       }
		}
		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		wp_die();
	}

	public static function ajaxupdatecsgallerycategory_handler(){
		$arrayData = array('errormsg' => '');
		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			$arrayData[errormsg] = 'Access denied. Only administrator or editors';
			echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			wp_die();
		}
		$catId = $_POST['catid'];
	  $arrayData[errormsg] = empty($catId) ? 'Error No Id found' : $arrayData[errormsg];
	  $title = trim(stripslashes($_POST['title']));
	  if(strlen($title) > 79){
	    $title = substr($title, 0, 79);
	  }
	  $tv = mb_strtolower($title);
	  $tv = str_replace("å",'a', $tv);
	  $tv = str_replace("ä",'a', $tv);
	  $tv = str_replace("ö",'o', $tv);
	  $tv = str_replace(" ",'', $tv);

		if($arrayData[errormsg] == "")
	  {
	    global $wpdb;
	    $updateTableName = CSGALLERY_CATEGORIESTABLENAME;
	    $updateDBResult = $wpdb->update($updateTableName,
	    array('cattitle' => $title, 'catvalue' => $tv),
	      array( 'catid' => $catId ),
	      array( '%s','%s'),
	      array( '%d' ) //where format %d = number
	    );
	    if($updateDBResult === false ){
	       $arrayData[errormsg] = 'Database error when updating.';
	    }
	  }
		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		wp_die();
	}

	 public static function ajaxdeletecsgallerycategory_handler(){
		 $arrayData = array('errormsg' => '');
		 if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			 $arrayData[errormsg] = 'Access denied. Only administrator or editors';
			 echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			 wp_die();
		 }
		 $catId = $_POST['catid'];
 	   $arrayData[errormsg] = empty($catId) ? 'Error No Id found' : $arrayData[errormsg];
 	   if($arrayData[errormsg] == ""){
			 global $wpdb;
       $deleteCatDBResult = $wpdb->delete(CSGALLERY_CATEGORIESTABLENAME,
         array( 'catid' => $catId ), array( '%d' ) );
        if($deleteCatDBResult === false){
          $arrayData[errormsg] = "Database delete error.";
        }
        else{
          $updateDBResult = $wpdb->update(CSGALLERY_IMAGESTABLENAME,
          array('catid' => 0 ),
            array( 'catid' => $catId ),
            array( '%d'),
            array( '%d' ) //where format %d = number
          );
          if($updateDBResult === false){
            $arrayData[errormsg] = "Database error at updating";
          }
        }
		 }
		 echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
 		 wp_die();
	 }

   public static function ajaxaddnewcsgalleryplace_handler(){
		 $arrayData = array('errormsg' => '', 'newplaceid' => '');
		 if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			 $arrayData[errormsg] = 'Access denied. Only administrator or editors';
		 }
		 if($arrayData[errormsg] == ""){
				 global $wpdb;
				 $insertDBResult = $wpdb->insert(CSGALLERY_PLACESTABLENAME,
					 array('placetitle' => 'New place',
							'placevalue' => 'newplace'),
					 array('%s','%s')
					);
					if($insertDBResult != 1){
						$arrayData[errormsg] = "Database error, could not create new place";
					}
					else{
						$arrayData[newplaceid] = $wpdb->insert_id;
					}
		 }
		 echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
		 wp_die();
	 }

	 public static function ajaxupdatecsgalleryplace_handler(){
		 $arrayData = array('errormsg' => '');
 		if( !(current_user_can('editor') || current_user_can('administrator'))  ){
 			$arrayData[errormsg] = 'Access denied. Only administrator or editors';
 			echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
 			wp_die();
 		}
 		$placeId = $_POST['placeid'];
 	  $arrayData[errormsg] = empty($placeId) ? 'Error No Id found' : $arrayData[errormsg];
 	  $title = trim(stripslashes($_POST['title']));
 	  if(strlen($title) > 79){
 	    $title = substr($title, 0, 79);
 	  }
 	  $tv = mb_strtolower($title);
 	  $tv = str_replace("å",'a', $tv);
 	  $tv = str_replace("ä",'a', $tv);
 	  $tv = str_replace("ö",'o', $tv);
 	  $tv = str_replace(" ",'', $tv);

 		if($arrayData[errormsg] == "")
 	  {
 	    global $wpdb;
 	    $updateTableName = CSGALLERY_PLACESTABLENAME;
 	    $updateDBResult = $wpdb->update($updateTableName,
 	    array('placetitle' => $title, 'placevalue' => $tv),
 	      array( 'placeid' => $placeId ),
 	      array( '%s','%s'),
 	      array( '%d' ) //where format %d = number
 	    );
 	    if($updateDBResult === false ){
 	       $arrayData[errormsg] = 'Database error when updating.';
 	    }
 	  }
 		echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
 		wp_die();
	 }

	 public static function ajaxdeletecsgalleryplace_handler(){
		 $arrayData = array('errormsg' => '');
		 if( !(current_user_can('editor') || current_user_can('administrator'))  ){
			 $arrayData[errormsg] = 'Access denied. Only administrator or editors';
			 echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			 wp_die();
		 }
		 $placeId = $_POST['placeid'];
 	   $arrayData[errormsg] = empty($placeId) ? 'Error No Id found' : $arrayData[errormsg];
 	   if($arrayData[errormsg] == ""){
			 global $wpdb;
       $deletePlaceDBResult = $wpdb->delete(CSGALLERY_PLACESTABLENAME,
         array( 'placeid' => $placeId ), array( '%d' ) );
        if($deletePlaceDBResult === false){
          $arrayData[errormsg] = "Database delete error.";
        }
        else{
          $updateDBResult = $wpdb->update(CSGALLERY_IMAGESTABLENAME,
          array('placeid' => 0 ),
            array( 'placeid' => $placeId ),
            array( '%d'),
            array( '%d' ) //where format %d = number
          );
          if($updateDBResult === false){
            $arrayData[errormsg] = "Database error at updating";
          }
        }
		 }
		 echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
 		 wp_die();
	 }
}  //end class CSGallery
?>
