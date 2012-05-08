/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

$(document).ready(function() {
  $(document).on("click", "input[name^='checkbox-showcolumn-']", function() {
    var index = $(this).val();
    var sel = 'td:nth-child(' + index + '),th:nth-child(' + index + ')';
    if ($(this).attr("checked")) {
      $(sel).show();
    } else {
      $(sel).hide();
    }
  });
  
  $("input[name='invert_checkbox']").click(function() {
    $(this).closest("form").find("input:checkbox").each(function() {
      if ('togglecolumn' != $(this).attr('rel')) {
        $(this).attr("checked", ! $(this).attr("checked"));
      }
    });
    $(this).attr("checked", false);
  });
});