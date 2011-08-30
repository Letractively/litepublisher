/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

$(document).ready(function() {
  $("input[rel='checkall']").click(function() {
    $(this).closest("form").find("input:checkbox").attr("checked", true);
    $(this).attr("checked", false);
  });
  
  $("input[rel='invertcheck']").click(function() {
    $(this).closest("form").find("input:checkbox").each(function() {
      $(this).attr("checked", ! $(this).attr("checked"));
    });
    $(this).attr("checked", false);
  });
  
});