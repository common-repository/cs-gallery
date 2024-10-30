//alert("testar " + ajax_object.ajax_url + " test_value:" + ajax_object.test_value);
function ajaxUpdateCSGalleryImageText(updateForm){
  var $ = jQuery;
  if (!updateForm.checkValidity())
      return false;
  var okResultDiv = $(updateForm).find(".updateOkresult").first();
  $(okResultDiv).html("");
  var errorResultDiv = $(updateForm).find(".updateErrorResult").first();
  $(errorResultDiv).html("");
  var spinner = $(updateForm).find("img").first().css("display", "none");

  var titleInput = $(updateForm.updateImageTitleInput);
  $(titleInput).val($.trim(titleInput.val().replace(/\'/g, "").replace(/\"/g, "")));
  if($(titleInput).val().length > 79){
    var subtitle = $(titleInput).val().substring(0, 79);
    $(titleInput).val(subtitle);
  }
  var descriptInput = $(updateForm.updateImageTextInput);
  $(descriptInput).val($.trim(descriptInput.val().replace(/\'/g, "").replace(/\"/g, "")));

  var youtubeCodeInput = $(updateForm.updateImageYoutubeCodeInput);
  $(youtubeCodeInput).val($.trim(youtubeCodeInput.val().replace(/\'/g, "").replace(/\"/g, "")));

  var imageIdInput = $(updateForm.updateImageId);
  var imageId = $(imageIdInput).val();
  if (imageId == "") {
      $(errorResultDiv).html("Id missing");
      return false;
  }
  var exactMiniImgWidthInput = $(updateForm.updateExactMiniImgWidth);
  //$(approvedLimitInput).val($.trim(approvedLimitInput.val()));
  var exactMiniImgWidth = parseInt(exactMiniImgWidthInput.val(), 10);
  if (isNaN(exactMiniImgWidth) ) {
      $(errorResultDiv).html("Width, invalid value");
      return false;
  }
  var exactMiniImgHeightInput = $(updateForm.updateExactMiniImgHeight);
  var exactMiniImgHeight = parseInt(exactMiniImgHeightInput.val(), 10);
  if (isNaN(exactMiniImgHeight) ) {
      $(errorResultDiv).html("Height, invalid value");
      return false;
  }
  var exactMiniImgLeftInput = $(updateForm.updateExactMiniImgLeft);
  var exactMiniImgLeft = parseInt(exactMiniImgLeftInput.val(), 10);
  if (isNaN(exactMiniImgLeft) ) {
      $(errorResultDiv).html("Pos left, invalid value");
      return false;
  }
  var exactMiniImgTopInput = $(updateForm.updateExactMiniImgTop);
  var exactMiniImgTop = parseInt(exactMiniImgTopInput.val(), 10);
  if (isNaN(exactMiniImgTop) ) {
      $(errorResultDiv).html("Pos top, invalid value");
      return false;
  }
  var shortTextLengthInput = $(updateForm.updateShortTextLength);
  var shortTextLength = parseInt(shortTextLengthInput.val(), 10);
  if (isNaN(shortTextLength) ) {
      $(errorResultDiv).html("Shorttext length, invalid value");
      return false;
  }
  var isKeepImgProps = $(updateForm.updateKeepImgProps).prop("checked");
  var isShowFullText = $(updateForm.updateShowPopupText).prop("checked");

  var catId = $(updateForm.updateCSGalleryCatSelect).val();
  var placeId = $(updateForm.updateCSGalleryPlaceSelect).val();

  var updateImageTextBtn = $(updateForm.updateImageTextBtn);
  $(spinner).css("display", "block");

  var updatedCSGalleryText = {
      'action': 'ajaxupdateorinsertcsgalleryimagetext', 'imageid': imageId,
      'catid': catId, 'placeid': placeId,
      'title': $(titleInput).val(), 'descript': $(descriptInput).val(),
      'exactminiimgwidth': exactMiniImgWidth, 'exactminiimgheight': exactMiniImgHeight,
      'exactminiimgleft': exactMiniImgLeft, 'exactminiimgtop': exactMiniImgTop,
      'isshowfulltext': isShowFullText, 'iskeepimgprops': isKeepImgProps,
      'shorttextlength': shortTextLength, 'youtubecode': $(youtubeCodeInput).val()
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: updatedCSGalleryText,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.msg == "ok") {
              var uploadFileForm = document.getElementById("csgalleryuploadfileform");
              var deleteImgForm = document.getElementById("csgallerydeleteimageform");
              if(data.newimageid != ""){
                $(okResultDiv).html("New imagepanel saved, id:" + data.newimageid);
                $(imageIdInput).val(data.newimageid);
                $(uploadFileForm.imageid).val(data.newimageid);
                $(deleteImgForm.deleteimageid).val(data.newimageid);
                $(updateImageTextBtn).val("Update");
              }
              else{
                $(okResultDiv).html("Updated");
              }
              $(uploadFileForm).css("display", "block");
              $(deleteImgForm).css("display", "block");
          }
          else {
              $(errorResultDiv).html("Error at updating " + data.msg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

function ajaxDeleteCSGalleryImagePanel(deleteForm){
  var $ = jQuery;
  if (!window.FormData || window.FormData == "undefined") {
      alert("Your browser do not support this function");
      return false;
  }
  var okResultDiv = $(deleteForm).find(".updateOkresult").first().html("");
  var errorResultDiv = $(deleteForm).find(".updateErrorResult").first().html("");
  var spinner = $(deleteForm).find(".spinnerimg").first().css("display", "none");
  var deleteGalleriImageBtn = $(deleteForm.deleteGalleriImageBtn);
  var deleteImageId = deleteForm.deleteimageid.value;
  if (deleteImageId == "" || deleteImageId == "0") {
      $(errorResultDiv).html("Id missing or 0");
      return false;
  }
  var updateImageTextFormDiv = $("#csgalleryupdateImageTextFormDiv");
  var uploadImageFileform = $("#csgalleryuploadfileform");
  var pluginDir = deleteForm.plugindir.value;
  if (!confirm("Delete imagepanel, file and text ?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var deleteCSGalleryImage = {
      'action': 'ajaxdeletecsgalleryimagepanel',
      'deleteimageid': deleteImageId,
      'plugindir': pluginDir
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: deleteCSGalleryImage,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.errormsg == "") {
                $(okResultDiv).html("Ok deleted");
                $(deleteGalleriImageBtn).css("display", "none");
                $(updateImageTextFormDiv).css("display", "none");
                $(uploadImageFileform).css("display", "none");
                $("#csgallerydeleteimageonlyform").css("display", "none");
          }
          else {
              $(errorResultDiv).html("Delete error: " + data.errormsg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

function ajaxUploadCSGalleryImageFile(updateForm){
  var $ = jQuery;
  if (!window.FormData || window.FormData == "undefined") {
      alert("Your browser do not support this function");
      return false;
  }
  var okResultDiv = $(updateForm).find(".updateOkresult").first().html("");
  var errorResultDiv = $(updateForm).find(".updateErrorResult").first().html("");
  var spinner = $(updateForm).find(".spinnerimg").first().css("display", "none");
  var galleryImage = $(updateForm).find(".galleryImgMiniimage").first();
  var galleryDocLink = $(updateForm).find(".galleryDocLink").first();
  var galleryImgFileLink = $(updateForm).find(".galleryEditImgFileLink").first();
  var galleryImgFileLabelSize = $(updateForm).find(".galleryImgFileLblSize").first();

  var imageId = updateForm.imageid.value;
  var oldFilename = updateForm.oldfilename.value;
  var imagesBaseUrl = updateForm.imagesbaseurl.value;
  var pluginDir = updateForm.plugindir.value;
  if (imageId == "") {
      $(errorResultDiv).html("Id missing");
      return false;
  }
  if (imageId == "0") {
      $(errorResultDiv).html("Id 0, new imagepanel must be created first.");
      return false;
  }
  var fileInput = updateForm.imgFileInput;
  var file = fileInput.files[0];
  var formdata = new FormData();
  if (file) {
     formdata.append("imagefile", file);
  }
  else {
      $(errorResultDiv).html("No file selected");
      return false;
  }
  var uploadedFileSize = fileInput.files[0].size;
  var uploadedFileName = fileInput.files[0].name;
  var uploadedFileExt = uploadedFileName.substring(uploadedFileName.lastIndexOf('.')+1);
  uploadedFileExt = uploadedFileExt.toLowerCase();
  /*if( !(uploadedFileExt == "jpg" || uploadedFileExt == "png" || uploadedFileExt == "gif") ){
    alert("Ogiltig filtyp. Endast jpg, png, gif");
    return false;
  }*/
  if(uploadedFileExt == "exe" || uploadedFileExt == "bat" || uploadedFileExt == ""){
    alert("Invalid filetype");
    return false;
  }
  if(uploadedFileSize / 1024 / 1024 > 60){
    alert("Filesize max 60 mb");
    return false;
  }
  formdata.append("action", "ajaxuploadcsgalleryimagefile");
  formdata.append("imageId", imageId);
  formdata.append("oldFilename", oldFilename);
  formdata.append("plugindir", pluginDir);
  if (!confirm("Upload file ?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var ajax = new XMLHttpRequest();
  ajax.upload.addEventListener("progress", function (event) {
      //loadedNTotal.innerHTML = "Uploaded " + event.loaded + " bytes of " + event.total;
      //var percent = (event.loaded / event.total) * 100;
      //progress.value = Math.round(percent);
      //status.innerHTML = Math.round(percent) + "% uploaded... please wait ";
  }, false);

  //ajax.addEventListener("load", completeHandler, false);
  ajax.addEventListener("load", function (event) {
      $(spinner).css("display", "none");
      if (event.target.status == 200) {
          try {
              //alert(JSON.stringify(data));
              //alert(event.target.responseText);
              var dataResult = JSON.parse(event.target.responseText);
              if (dataResult.errormsg != "") {
                  $(okResultDiv).html("");
                  $(errorResultDiv).html("Error: " + dataResult.errormsg);
              }
              else {
                  $(okResultDiv).html("OK " + " Filename: " + dataResult.newfilename);
                  $(errorResultDiv).html("");
                  $(galleryImage).attr("src", "");
                  var newFileName = dataResult.newfilename;
                  updateForm.oldfilename.value = newFileName;
                  $(galleryImgFileLink).html(newFileName);
                  $(galleryImgFileLink).attr("href", imagesBaseUrl + "/images/galleryimages/" + newFileName);
                  $(galleryImgFileLabelSize).html(" width:" + dataResult.imgwidth + "px height:" + dataResult.imgheight + "px");
                  $(galleryImgFileLabelSize).css("display", "block");
                  $(galleryImage).attr("src", imagesBaseUrl + "/images/galleryimages/" + newFileName);
                  $(galleryDocLink).attr("href", imagesBaseUrl + "/images/galleryimages/" + newFileName);

                  if (newFileName == "") {
                      $(galleryDocLink).css("display", "none");
                      $(galleryImage).css("display", "none");
                  }
                  else {
                      if(uploadedFileExt == "jpg" || uploadedFileExt == "png" || uploadedFileExt == "gif"){
                        $(galleryDocLink).css("display", "none");
                        $(galleryImage).css("display", "block");
                      }
                      else{
                        $(galleryDocLink).css("display", "block");
                        $(galleryImage).css("display", "none");
                        if(uploadedFileExt == "mp3"){
                          $(galleryDocLink).html("Sound - " + uploadedFileExt);
                        }
                        else{
                          $(galleryDocLink).html("Document - " + uploadedFileExt);
                        }
                      }
                      $("#csgallerydeleteimageonlyform").css("display", "block");
                  }
                  $(okResultDiv).html("OK uploaded. Filename: " + newFileName);
              }
          }
          catch (er) {
              $(errorResultDiv).html("ResponseText parse Fel " + er.message);
          }
      }
      else {
          $(errorResultDiv).html("Complete error " + event.target.status);
      }
      fileInput.value = "";
  }, false);

  //ajax.addEventListener("error", errorHandler, false);
  ajax.addEventListener("error", function (event) {
      $(spinner).css("display", "none");
      $(errorResultDiv).html("errorListener error " + event.target.status);
  }, false);
  //ajax.addEventListener("abort", abortHandler, false);
  ajax.addEventListener("abort", function (event) {
      $(spinner).css("display", "none");
      $(errorResultDiv).html("Upload Aborted");
  }, false);

  ajax.open("POST", ajax_object.ajax_url);
  ajax.send(formdata);
  return false;
}

function ajaxDeleteCSGalleryImageOnly(deleteForm){
  var $ = jQuery;
  if (!window.FormData || window.FormData == "undefined") {
      alert("Your browser do not support this function");
      return false;
  }
  var okResultDiv = $(deleteForm).find(".updateOkresult").first().html("");
  var errorResultDiv = $(deleteForm).find(".updateErrorResult").first().html("");
  var spinner = $(deleteForm).find(".spinnerimg").first().css("display", "none");
  var deleteGalleriImageBtn = $(deleteForm).find(".deleteGalleriImageOnlyBtn");
  var deleteImageId = deleteForm.deleteimageid.value;
  if (deleteImageId == "" || deleteImageId == "0") {
      $(errorResultDiv).html("Id missing or 0");
      return false;
  }
  var uploadImageFileform = document.getElementById("csgalleryuploadfileform");
  var galleryImage = $(uploadImageFileform).find(".galleryImgMiniimage").first();
  var galleryDocLink = $(uploadImageFileform).find(".galleryDocLink").first();
  var galleryImgFileLink = $(uploadImageFileform).find(".galleryEditImgFileLink").first();
  var galleryImgFileLabelSize = $(uploadImageFileform).find(".galleryImgFileLblSize").first();

  var imageId = deleteForm.deleteimageid.value;
  var pluginDir = deleteForm.plugindir.value;
  if (!confirm("Delete image/file from the imagepanel?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var deleteCSGalleryImage = {
      'action': 'ajaxdeletecsgalleryimageonly',
      'deleteimageid': deleteImageId,
      'plugindir': pluginDir
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: deleteCSGalleryImage,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.errormsg == "") {
                $(okResultDiv).html("Ok deleted");
                uploadImageFileform.oldfilename.value = "";
                $(galleryImgFileLink).html("");
                $(galleryImgFileLabelSize).html("");
                $(galleryImage).attr("src", "");
                $(galleryImage).css("display", "none");
                $(galleryDocLink).attr("href", "");
                $(galleryDocLink).css("display", "none");
                $(deleteForm).css("display", "none");
          }
          else {
              $(errorResultDiv).html("Error at updating: " + data.errormsg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

function ajaxAddNewCSGalleryCategory(addNewForm){
  var $ = jQuery;
  var okResultDiv = $(addNewForm).find(".newCatOkresult").first().html("");
  var errorResultDiv = $(addNewForm).find(".newCatErrorResult").first().html("");
  var spinner = $(addNewForm).find(".spinnerimg").first().css("display", "none");
  var pluginUrl = addNewForm.pluginUrl.value;
  if (!confirm("Add new category ?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var addNewCSGalleryCategory = {
      'action': 'ajaxaddnewcsgallerycategory'
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: addNewCSGalleryCategory,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.errormsg == "" && data.newcatid != "") {
            var allCatsDiv = $("#csgalleryEditAllCatsDiv");
            var catDivId = "csgalleryEditCatDiv" + data.newcatid;
            var catDiv = $("<div id='" + catDivId + "' style='display:none;clear:both;overflow:auto;border:4px solid #404040;background-color:#aaaaaa; padding:5px; margin-bottom:15px'></div>");

            var updateDiv = $("<div></div>");
            var updateForm = $("<form action='' method='post' onsubmit='return ajaxEditCSGalleryCategory(this);'>"
            + "<input type='text' class='form-control' name='updateCatTitleInput' value='New category' required='required' style='font-weight:bold'/> <br />"
    				+ "<input type='hidden' name='updateCatId' value='" + data.newcatid + "' />"
    	      + "<img class='spinnerimg' src='" + pluginUrl + "/images/spinner.gif' style='display:none'/>"
    	      + "<div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>"
    	      + "<div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>"
    	      + "<input class='btn btn-primary' name='updateCatBtn' type='submit' value='Update' />"
            + "</form>");
            updateDiv.append(updateForm);

            var deleteDiv = $("<div style='float:right;margin:5px'></div>");
            var deleteForm = $("<form action='' method='post' onsubmit='return ajaxDeleteCSGalleryCategory(this);'>"
    				+ "<input type='hidden' name='deleteCatId' value='" + data.newcatid + "' />"
    				+ "<input type='hidden' name='deleteCatName' value='New category'/>"
    				+ "<img class='spinnerimg' src='" + pluginUrl + "/images/spinner.gif' style='display:none'/>"
    				+ "<div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>"
    				+ "<div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>"
    				+ "<input class='btn btn-primary' name='deleteCatBtn' type='submit' value='Delete category' />"
    				+ "</form>");
            deleteDiv.append(deleteForm);

            catDiv.append(updateDiv);
            catDiv.append(deleteDiv);
            allCatsDiv.prepend(catDiv);
            catDiv.slideDown();
            $(okResultDiv).html("Ok");
          }
          else {
              $(errorResultDiv).html("Error at updating: " + data.errormsg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

function ajaxEditCSGalleryCategory(updateForm){
   var $ = jQuery;
   if (!updateForm.checkValidity()){
      return false;
   }
   var okResultDiv = $(updateForm).find(".updateOkresult").first();
   $(okResultDiv).html("");
   var errorResultDiv = $(updateForm).find(".updateErrorResult").first();
   $(errorResultDiv).html("");
   var spinner = $(updateForm).find("img").first().css("display", "none");
   var titleInput = $(updateForm.updateCatTitleInput);
   $(titleInput).val($.trim(titleInput.val().replace(/\'/g, "").replace(/\"/g, "")));
   if($(titleInput).val().length > 79){
     var subtitle = $(titleInput).val().substring(0, 79);
     $(titleInput).val(subtitle);
   }
   var catId = updateForm.updateCatId.value;
   if (catId == "") {
      $(errorResultDiv).html("Id missing");
      return false;
   }
   $(spinner).css("display", "block");
   var updatedCSGalleryCategory = {
       'action': 'ajaxupdatecsgallerycategory', 'catid': catId,
       'title': $(titleInput).val()
   };
   $.ajax({
       type: 'POST',
       cache: false,
       data: updatedCSGalleryCategory,
       url: ajax_object.ajax_url,
       dataType: 'JSON',
       success: function (data) {
           $(spinner).css("display", "none");
             //alert(JSON.stringify(data));
           if (data.errormsg == "") {
               $(okResultDiv).html("Updated");
           }
           else {
               $(errorResultDiv).html("Update error: " + data.errormsg);
           }
       },
       error: function (xhr, ajaxOptions, thrownError) {
           $(spinner).css("display", "none");
           $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
       }
   });
   return false;
}

function ajaxDeleteCSGalleryCategory(deleteForm){
  var $ = jQuery;
  var catId = $(deleteForm.deleteCatId).val();
  var catDivId = "csgalleryEditCatDiv" + catId;
  var catDiv = $("#" + catDivId);
  var catName = deleteForm.deleteCatName.value;
  var okResultDiv = $(deleteForm).find(".updateOkresult").first();
  $(okResultDiv).html("");
  var errorResultDiv = $(deleteForm).find(".updateErrorResult").first();
  $(errorResultDiv).html("");
  var spinner = $(deleteForm).find("img").first().css("display", "none");
  if (!confirm("Delete category " + catName + " ?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var deleteCSGalleryCategory = {
      'action': 'ajaxdeletecsgallerycategory', 'catid': catId
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: deleteCSGalleryCategory,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.errormsg == "") {
              $(okResultDiv).html("Updated");
              catDiv.slideUp();
          }
          else {
              $(errorResultDiv).html("Delete error: " + data.errormsg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

function ajaxAddNewCSGalleryPlace(addNewForm){
  var $ = jQuery;
  var okResultDiv = $(addNewForm).find(".newPlaceOkresult").first().html("");
  var errorResultDiv = $(addNewForm).find(".newPlaceErrorResult").first().html("");
  var spinner = $(addNewForm).find(".spinnerimg").first().css("display", "none");
  var pluginUrl = addNewForm.pluginUrl.value;
  if (!confirm("Add new place ?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var addNewCSGalleryPlace = {
      'action': 'ajaxaddnewcsgalleryplace'
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: addNewCSGalleryPlace,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.errormsg == "" && data.newplaceid != "") {
            var allPlacesDiv = $("#csgalleryEditAllPlacesDiv");
            var placeDivId = "csgalleryEditPlaceDiv" + data.newplaceid;
            var placeDiv = $("<div id='" + placeDivId + "' style='display:none;clear:both;overflow:auto;border:4px solid #404040;background-color:#aaaaaa; padding:5px; margin-bottom:15px'></div>");

            var updateDiv = $("<div></div>");
            var updateForm = $("<form action='' method='post' onsubmit='return ajaxEditCSGalleryPlace(this);'>"
            + "<input type='text' class='form-control' name='updatePlaceTitleInput' value='New place' required='required' style='font-weight:bold'/> <br />"
    				+ "<input type='hidden' name='updatePlaceId' value='" + data.newplaceid + "' />"
    	      + "<img class='spinnerimg' src='" + pluginUrl + "/images/spinner.gif' style='display:none'/>"
    	      + "<div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>"
    	      + "<div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>"
    	      + "<input class='btn btn-primary' name='updatePlaceBtn' type='submit' value='Update' />"
            + "</form>");
            updateDiv.append(updateForm);

            var deleteDiv = $("<div style='float:right;margin:5px'></div>");
            var deleteForm = $("<form action='' method='post' onsubmit='return ajaxDeleteCSGalleryPlace(this);'>"
    				+ "<input type='hidden' name='deletePlaceId' value='" + data.newplaceid + "' />"
    				+ "<input type='hidden' name='deletePlaceName' value='New place'/>"
    				+ "<img class='spinnerimg' src='" + pluginUrl + "/images/spinner.gif' style='display:none'/>"
    				+ "<div class='updateOkresult' style='font-weight:bold;color:#00aa00'></div>"
    				+ "<div class='updateErrorResult' style='font-weight:bold;color:#cc0000'></div>"
    				+ "<input class='btn btn-primary' name='deletePlaceBtn' type='submit' value='Delete place' />"
    				+ "</form>");
            deleteDiv.append(deleteForm);

            placeDiv.append(updateDiv);
            placeDiv.append(deleteDiv);
            allPlacesDiv.prepend(placeDiv);
            placeDiv.slideDown();
            $(okResultDiv).html("Ok");
          }
          else {
              $(errorResultDiv).html("Error at updating: " + data.errormsg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

function ajaxEditCSGalleryPlace(updateForm){
   var $ = jQuery;
   if (!updateForm.checkValidity()){
      return false;
   }
   var okResultDiv = $(updateForm).find(".updateOkresult").first();
   $(okResultDiv).html("");
   var errorResultDiv = $(updateForm).find(".updateErrorResult").first();
   $(errorResultDiv).html("");
   var spinner = $(updateForm).find("img").first().css("display", "none");
   var titleInput = $(updateForm.updatePlaceTitleInput);
   $(titleInput).val($.trim(titleInput.val().replace(/\'/g, "").replace(/\"/g, "")));
   if($(titleInput).val().length > 79){
     var subtitle = $(titleInput).val().substring(0, 79);
     $(titleInput).val(subtitle);
   }
   var placeId = updateForm.updatePlaceId.value;
   if (placeId == "") {
      $(errorResultDiv).html("Id missing");
      return false;
   }
   $(spinner).css("display", "block");
   var updatedCSGalleryPlace = {
       'action': 'ajaxupdatecsgalleryplace', 'placeid': placeId,
       'title': $(titleInput).val()
   };
   $.ajax({
       type: 'POST',
       cache: false,
       data: updatedCSGalleryPlace,
       url: ajax_object.ajax_url,
       dataType: 'JSON',
       success: function (data) {
           $(spinner).css("display", "none");
             //alert(JSON.stringify(data));
           if (data.errormsg == "") {
               $(okResultDiv).html("Updated");
           }
           else {
               $(errorResultDiv).html("Update error: " + data.errormsg);
           }
       },
       error: function (xhr, ajaxOptions, thrownError) {
           $(spinner).css("display", "none");
           $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
       }
   });
   return false;
}

function ajaxDeleteCSGalleryPlace(deleteForm){
  var $ = jQuery;
  var placeId = $(deleteForm.deletePlaceId).val();
  var placeDivId = "csgalleryEditPlaceDiv" + placeId;
  var placeDiv = $("#" + placeDivId);
  var placeName = deleteForm.deletePlaceName.value;
  var okResultDiv = $(deleteForm).find(".updateOkresult").first();
  $(okResultDiv).html("");
  var errorResultDiv = $(deleteForm).find(".updateErrorResult").first();
  $(errorResultDiv).html("");
  var spinner = $(deleteForm).find("img").first().css("display", "none");
  if (!confirm("Delete place " + placeName + " ?")) {
      return false;
  }
  $(spinner).css("display", "block");
  var deleteCSGalleryPlace = {
      'action': 'ajaxdeletecsgalleryplace', 'placeid': placeId
  };
  $.ajax({
      type: 'POST',
      cache: false,
      data: deleteCSGalleryPlace,
      url: ajax_object.ajax_url,
      dataType: 'JSON',
      success: function (data) {
          $(spinner).css("display", "none");
            //alert(JSON.stringify(data));
          if (data.errormsg == "") {
              $(okResultDiv).html("Updated");
              placeDiv.slideUp();
          }
          else {
              $(errorResultDiv).html("Delete error: " + data.errormsg);
          }
      },
      error: function (xhr, ajaxOptions, thrownError) {
          $(spinner).css("display", "none");
          $(errorResultDiv).html("Error status: " + xhr.status + " " + thrownError);
      }
  });
  return false;
}

/* CSGalleryPopup dialog */
var CSGalleryModalPopupObj = null;
function CSGalleryThumbnailClick(jsonImages, imgId, imgBaseUrl)
{
   if(CSGalleryModalPopupObj == null){
     CSGalleryModalPopupObj = new CSGalleryModalPopup(jsonImages, imgBaseUrl);
   }
   CSGalleryModalPopupObj.startCSGalleryPopup(imgId);
}
function CSGalleryModalPopup(jsonImages, imgBaseUrl)
{
  this.jsonImages = jsonImages;
  this.imgBaseUrl = imgBaseUrl;
  var currentIndex = 0;
  var currentJSONImage = null;

  jQuery("#CSGalleryPopupOverlay").click(function(ev){
    ev.stopPropagation();
    if(ev.target.id === "CSGalleryPopupOverlay" || ev.target.id === "CSGalleryPopupCloseBtn" || ev.target.id === "CSGalleryPopupCloseBtn2"){
      //$(this.event.target).slideUp();
      jQuery("#CSGalleryPopupOverlay").slideUp();
      var video = jQuery(this).find("iframe").first();
      if(video){
        var videoSrc = jQuery(video).attr("src");
        jQuery(video).attr("src", "");
      }
      var mp3Ljud = jQuery(this).find("audio").first();
      if(mp3Ljud){
        var mp3Src = jQuery(mp3Ljud).attr("src");
        jQuery(mp3Ljud).attr("src", "");
      }
      return false;
    }
  });

  jQuery("#CSGalleryPopupPrevBtn").click(function(ev){
    loadPrev();
  });
  jQuery("#CSGalleryPopupNextBtn").click(function(ev){
    loadNext();
  });
  jQuery(".CSGalleryPopupPrevSidePanel").click(function(ev){
    loadPrev();
  });
  jQuery(".CSGalleryPopupNextSidePanel").click(function(ev){
    loadNext();
  });
  var loadPrev = function(){
    if(currentIndex < 1){
      return;
    }
    currentIndex = currentIndex -1;
    currentJSONImage = jsonImages[currentIndex];
    CSGalleryModalPopupObj.openImgPopup(currentJSONImage.imageid);
  }
  var loadNext = function(){
    if(currentIndex + 1 >= jsonImages.length){
      return;
    }
    currentIndex = currentIndex +1;
    currentJSONImage = jsonImages[currentIndex];
    CSGalleryModalPopupObj.openImgPopup(currentJSONImage.imageid);
  }
  this.startCSGalleryPopup = function(imgId){
    jQuery(this.jsonImages).each(function(i, obj){
      if(obj.imageid == imgId){
        currentIndex = i;
        currentJSONImage = obj;
      }
    });
    this.openImgPopup(imgId);
  };
  this.openImgPopup = function(imgId){
    var showNextBtn = (currentIndex + 1 >= jsonImages.length) ? "none" : "block";
    var showPrevBtn = currentIndex < 1 ? "none" : "block";
    jQuery("#CSGalleryPopupPrevBtn").css("display", showPrevBtn);
    jQuery("#CSGalleryPopupNextBtn").css("display", showNextBtn);
    jQuery(".CSGalleryPopupPrevSidePanel").css("display", showPrevBtn);
    jQuery(".CSGalleryPopupNextSidePanel").css("display", showNextBtn);

    jQuery('#CSGalleryPopupBody').empty();
    jQuery('#CSGalleryPopupTitle').html(currentJSONImage.imgtitle);
    var fileName = currentJSONImage.imgfilename;
    var dotIndex = fileName.lastIndexOf(".");
    var ext = fileName.substring(dotIndex, fileName.length).toLowerCase();
    var modalImg = null;
    var soundDiv = null;
    if(currentJSONImage.youtubecode.length > 1){
      var youtubeUrl = "https://www.youtube.com/embed/" + currentJSONImage.youtubecode +"?feature=player_detailpage&wmode=transparent";
      var youtubeDiv = "<div style='overflow:hidden;padding-bottom:56.25%;position:relative;height:0;'>" +
       "<iframe id='youtubeIFrame' width='640' height='360' " +
       " src='" + youtubeUrl + "'" +
       " frameborder='0' allow='autoplay; encrypted-media' allowfullscreen " +
       " style='left:0;top:0;height:100%;width:100%;position:absolute;'>" +
       "</iframe></div>";
        jQuery(youtubeDiv).appendTo('#CSGalleryPopupBody');
    }
    else if(ext == ".jpg" || ext == ".png" || ext == "gif"){
      var imgUrl = imgBaseUrl + "/images/galleryimages/" + fileName;
      var imgFullWidth = currentJSONImage.imgfullwidth;
      var imgFullHeight = currentJSONImage.imgfullheight;
      modalImg = jQuery("<img src='" + imgUrl + "' title='Show full size' class='' style=''/>");
       //jQuery(modalImg).css("cursor", "crosshair");
       jQuery(modalImg).css("cursor", "move");
       var fullSizeImgDiv = null;
       jQuery(modalImg).click( function(){
         if(fullSizeImgDiv == null){
           fullSizeImgDiv = jQuery("<div class='CSGalleryPopupFullSizeImgDiv'><img src='" + imgUrl + "' title='Fit size' style=''/></div>");
           jQuery(fullSizeImgDiv).css("width", imgFullWidth);
           jQuery(fullSizeImgDiv).css("overflow", "visible");
           jQuery(fullSizeImgDiv).appendTo('#CSGalleryPopupBody');
           jQuery(fullSizeImgDiv).click(function(ev){
             jQuery(fullSizeImgDiv).css("display", "none");
             jQuery(modalImg).css("visibility", "visible");
           });
         }
         jQuery(fullSizeImgDiv).css("display", "block");
         jQuery(modalImg).css("visibility", "hidden");
         //jQuery(modalImg).css("display", "none");
       });
       jQuery(modalImg).appendTo('#CSGalleryPopupBody');
    }
    else if(ext == ".mp3"){
      var mp3Url = imgBaseUrl + "/images/galleryimages/" + fileName;
      soundDiv = "<div>"+
      //"<audio class='wp-audio-shortcode' preload='none' style='width: 100%; margin-bottom:0; visibility: visible;' controls='controls'>"+
      //"<source type='audio/mpeg' src='"+soundUrl+"' />"+
      "<audio src='" + mp3Url + "' class='wp-audio-shortcode' preload='none' style='width: 90%; margin-bottom:0; visibility: visible;' controls='controls'>"+
      "</audio>" +
      "</div>";
      jQuery(soundDiv).appendTo('#CSGalleryPopupBody');
    }
    else{
      var docUrl = imgBaseUrl + "/images/galleryimages/" + fileName;
      var docDiv = "<div style='font-weight:bold'><a href='"+docUrl+"'>Dokument - " + ext + "</a></div>";
      jQuery(docDiv).appendTo('#CSGalleryPopupBody');
    }
    var infoTextDiv = null;
    if(currentJSONImage.isimgpopupshowfulltext == true){
      var infoText = currentJSONImage.imgtext.replace(/\r\n/g, "<br/>").replace(/\n/g, "<br/>")
      infoTextDiv = jQuery("<div class='CSGalleryPopupInfoText' style='text-align:left;padding-left:10px;padding-right:10px'>" + infoText + "</div>");
      jQuery(infoTextDiv).appendTo('#CSGalleryPopupBody');
    }
    jQuery('#CSGalleryPopupOverlay').slideDown(function(){
      //var bodyWidth = jQuery('#CSPopupBody').width();
      var paddLeft = 10;
      if(modalImg != null){
        paddLeft = modalImg.offset().left / 2;
      }
      else if(soundDiv != null){
        paddLeft = "10%";
      }
      if(infoTextDiv != null){
       jQuery(infoTextDiv).css("padding-left", paddLeft);
       jQuery(infoTextDiv).css("padding-right", paddLeft);
      }
    });
  }; //end this.openImgPopup
} //end CSGalleryModalPopup

function CSGalleryThumbnailTextClick(div){
  var imgDiv = jQuery(div).parent();
  var overflow = jQuery(imgDiv).css("overflow");
  if(overflow == "visible"){
    jQuery(imgDiv).css("overflow", "hidden");
  }
  else{
    jQuery(imgDiv).css("z-index","1 !important");
    jQuery(imgDiv).css("overflow","visible");
  }
}
