<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tpagenator3000 extends tplugin {
  
  public static function i() {
    return getinstance(__class__);
  }
  
  public function themeparsed(ttheme $theme) {
    if (strpos($theme->templates['content.navi'], 'paginator3000.min.js')) return;
    $url = litepublisher::$site->files . '/plugins/' . basename(dirname(__file__)) . '/';
    $about = tplugins::getabout(tplugins::getname(__file__));
    $head = '<script type="text/javascript"><!--
    function init_paginator3000() {
      $.load_css("' . $url . 'paginator3000.css");
      $.load_script("' . $url . 'paginator3000.min.js", function() {
        $(document).ready(function() {
          var tag = $("#paginator, .navigation");
          tag.addClass("paginator");
          tag.paginator({
            pagesTotal : %%count%%,
            pagesSpan : %%perpage%%,
            pageCurrent : %%page%% - 1,
            baseUrl : function(page) {
              window.location= ++page == 1 ? "%%link%%" :
              "%%pageurl%%" + page + "/";
            },
            
            returnOrder : false,
            lang : {
              next : "' . $about['next'] . '",
              last : "' . $about['last'] . '",
              prior : "' . $about['prior'] . '",
              first : "' . $about['first'] . '",
              arrowRight : String.fromCharCode(8594),
              arrowLeft : String.fromCharCode(8592)
            }
          });
        });
      });
    }
    
    if (ltoptions.paginator3000 ==undefined) {
      ltoptions.paginator3000  = "init";
      init_paginator3000();
    }
    //-->
    </script>';
    
    $theme->templates['content.navi'] = $head . $theme->templates['content.navi'];
  }
  
  public function install() {
    $parser = tthemeparser::i();
    $parser->parsed = $this->themeparsed;
    ttheme::clearcache();
  }
  
  public function uninstall() {
    $parser = tthemeparser::i();
    $parser->unsubscribeclass($this);
    ttheme::clearcache();
  }
  
}//class