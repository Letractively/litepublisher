(function ($, document, window) {
litepubl.uploader = Class.extend({
progressbar: "#progressbar",
maxsize: "100",
types: "*.*",
//events
onupload: $.noop,

init: function(upload_success_handler) {
  var url = ltoptions.uploadurl == undefined ? ltoptions.url: ltoptions.uploadurl;
  var cookie = get_cookie("litepubl_user");
  if (cookie == "") cookie = get_cookie("admin");
var self = this;
  var settings = {
    flash_url : url + "/js/swfupload/swfupload.swf",
    upload_url: url + "/admin/jsonserver.php",
    // prevent_swf_caching: false,
    post_params: {
      litepubl_user: cookie,
      litepubl_user_id: get_cookie("litepubl_user_id"),
      method: "files_upload"
    },

    file_size_limit : this.maxsize + " MB",
    file_types : this.types,
    file_types_description : "All Files",
    file_upload_limit : 0,
    file_queue_limit : 0,
    button_placeholder_id : "uploadbutton",

    /*
    custom_settings : {
      progressTarget : "fsUploadProgress",
      cancelButtonId : "btnCancel"
    },
    */
    //debug: true,
    
    file_dialog_complete_handler : function(numFilesSelected, numFilesQueued) {
$(self.progressbar).progressbar({value: 0});
  var url = ltoptions.uploadurl == undefined ? ltoptions.url: ltoptions.uploadurl;
  this.setUploadURL(url + '/admin/jsonserver.php?random=' + Math.random());
  var perm = $("#combo-idperm_upload");
  if (perm.length) this.addPostParam("idperm", perm.val());
  this.startUpload();
},

    upload_start_handler : function(file) {
  return true;
},

    upload_progress_handler : function(file, bytesLoaded, bytesTotal) {
  try {
    var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
    $(self.progressbar).progressbar( "value" , percent );
  } catch (ex) {
    this.debug(ex);
  }
},

    upload_error_handler : function(file, errorCode, message) {
  //alert('uploadError');
  $.messagebox(lang.dialog.error, message);
},

    upload_success_handler : function(file, serverData) {
try {
        var r = $.parseJSON(serverData);
self.onupload(file, r);
    } catch(e) { alert('error ' + e.message); }
},

    upload_complete_handler : function(file) {
  //alert('uploadComplete' + file);
  try {
    /*  I want the next upload to continue automatically so I'll call startUpload here */
    if (this.getStats().files_queued === 0) {
      $(self.progressbar).progressbar( "destroy" );
    } else {
      this.startUpload();
    }
  } catch (ex) {
    this.debug(ex);
  }
}

  };

    // Button settings  
  if (ltoptions.lang == 'en') {
    settings.button_image_url: ltoptions.files + "/js/swfupload/images/XPButtonUploadText_61x22.png";
    settings.button_width: 61;
    settings.button_height: 22;
} else {    
    settings.button_text= '<span class="upload_button">' + lang.posteditor.upload + '</span>';
    settings.button_image_url= ltoptions.files + "/js/swfupload/images/XPButtonNoText_160x22.png";
    settings.button_width =  160;
  settings.button_text_style = '.upload_button { font-family: Helvetica, Arial, sans-serif; font-size: 14pt; text-align: center; }';
    settings.button_text_top_padding= 1;
    settings.button_text_left_padding= 5;
  }
  
  try {
    this.uploader= new SWFUpload(settings);
} catch(e) { alert('Error create swfupload ' + e.message); }
}

});
}(jQuery, document, window));