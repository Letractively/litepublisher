(function( $ ){
$.posteditor = {

  init: function() {
    $("#tabs").tabs({
      cache: true,
    select: function(event, ui) {
if ($("#datetime-holder", ui.panel).length) {
$.posteditor.init_datetime_tab(ui.panel);
} else  if ($("#seo-holder", ui.panel).length) {
$.posteditor.init_seo_tab(ui.panel);
}
},

      load: function(event, ui) {
          $(".posteditor-tag", ui.panel).click(function() {
$.posteditor.addtag($(this).text());
return false;
});
      }
    });
    
    $("#posteditor-init-raw-tabs").one('click', function() {
      $.posteditor.init_raw_tabs();
      return false;
    });

    $("#posteditor-init-files").one('click', function() {
$(this).replaceWith($(this).text());
      $.posteditor.init_files();
      return false;
    });
    
    $('form:first').submit(function() {
      if ("" == $.trim($("input[name='title']").val())) {
$.messagebox(lang.dialog.error, lang.posteditor.emptytitle);
        return false;
      }
    });
    
  },

  init_files: function() {
    $.get(ltoptions.url + '/admin/ajaxposteditor.htm',
  {id: ltoptions.idpost, get: "files"},
    function (html) {
      $("#filebrowser").html(html);
    $('#filetabs').tabs({cache: true});
      //$("input[id^='addfilesbutton']").live('click', addtocurrentfiles);
      $(document).on("click", "input[id^='addfilesbutton']", addtocurrentfiles);
      
      $("#deletecurrentfiles").click(function() {
        $("input:checked[id^='currentfile']").each(function() {
          $(this).parent().remove();
        } );
        return false;
      });
      
      $('form:first').submit(function() {
        $("input[name='files']").val(getpostfiles());
      });
      
        ltoptions.swfu = createswfu($.posteditor.uploaded);
  },

uploaded: function(file, serverData) {
  var haschilds = $("#newfilestab").children().length > 0;
  $("#newfilestab").append(serverData);
  var html = $("#newfilestab").children(":last").html();
  if (haschilds) {
    $("#newfilestab").children(":last").remove();
    $("#newfilestab").children(":first").append(html);
  }
  html =str_replace(
  ['uploaded-', 'new-post-', 'newfile-'],
  ['curfile-', 'curpost-', 'currentfile-'],
  html);
  $('#currentfilestab > :first').append(html);
}



  function addtocurrentfiles() {
    $("input:checked[id^='itemfilepage']").each(function() {
      $(this).attr('checked', false);
      var id = $(this).val();
      if ($("#currentfile-" + id).length == 0) {
        var html =str_replace(
        ['pagefile-', 'pagepost-', 'itemfilepage-'],
        ['curfile-', 'curpost-', 'currentfile-'],
        $('<div></div>').append($( this).parent().clone() ).html());
        // outer html prev line
        //alert(html);
        $('#currentfilestab > :first').append(html);
      }
    });
  }
  
  function getpostfiles() {
    var files = [];
    $("input[id^='currentfile']").each(function() {
      files.push($(this).val());
    });
    return files.join(',');
  }
  
  
  addtag: function(newtag) {
    var tags = $('#text-tags').val();
    if (tags == '') {
      $('#text-tags').val(newtag);
    } else {
      var re = /\s*,\s*/;
      var list = tags.split(re);
      for (var i = list.length; i >= 0; i--) {
        if (newtag == list[i]) return false;
      }
      $('#text-tags').val(tags + ', ' + newtag);
    }
  },

init_seo_tab: function (uipanel) {
//replace html in comment
var holder = $("#seo-holder", uipanel);
holder.replaceWith(holder.get(0).firstChild.nodeValue);
},

    init_datetime_tab: function (uipanel) {
//replace html in comment
var holder = $("#datetime-holder", uipanel);
holder.replaceWith(holder.get(0).firstChild.nodeValue);
this.load_ui_datepicker(function() {
    var cur = $("#text-date").val();
    $("#datepicker").datepicker({
      altField: "#text-date",
      altFormat: "dd.mm.yy",
      dateFormat: "dd.mm.yy",
      changeYear: true
      //showButtonPanel: true
    });
    
    $("#datepicker").datepicker("setDate", cur);
});
  },
  
load_ui_datepicker: function(callback) {
          $.load_script(ltoptions.files + '/js/jquery/ui-' + $.ui.version + '/jquery.ui.datepicker.min.js', function() {
            if (ltoptions.lang == 'en') return callback();
              $.load_script(ltoptions.files + '/js/jquery/ui-' + $.ui.version + '/jquery.ui.datepicker-' + ltoptions.lang + '.js', callback);
          });
},

  init_raw_tabs: function() {
    $("#posteditor-init-raw-tabs").remove();
var holder = $("#posteditor-raw-holder");
var html = holder.get(0).firstChild.nodeValue;
$(holder.get(0).firstChild).remove();

html = html.replace(/<comment>/gim, '<div class="tab-holder"><!--')
.replace(/<\/comment>/gim, '--></div>');
//divide on list and div's
var i = html.indexOf('<div');
$("#posteditor-raw").before(html.substring(0, i)).after(html.substring(i));

    holder.tabs({
cache: true,
    select: function(event, ui) {
var inner = $(".tab-holder", ui.panel);
if (inner.length) inner.replaceWith(inner.get(0).firstChild.nodeValue);
}
});
  },

init_visual_link: function(url, text) {
$('<a href="#">' + text + '</a>').appendTo("#posteditor-visual").data("url", url).one("click", function() {
          $.load_script($(this).data("url"));
          $("#posteditor-visual").remove();
          return false;
        });
}
  
}//posteditor

  $(document).ready($.posteditor.init);
})( jQuery );