alert('rea');
  $(document).ready(function() {
alert('rea');
function show_lazybuttons() {
		  var url = document.location;
		  var title = document.title.replace("'",'&apos;');
//plus one callback
$.plusone_callback = function(r) {
if (_gaq != undefined) {
if(r.state=='on'){
_gaq.push(['_trackEvent','google', 'plussed', title]);
}else{
_gaq.push(['_trackEvent','google', 'unplussed', title]);
}
}
};


				$(".lazybuttons").append(
'<g:plusone size="standard" count="true callback="$.plusone_callback" href="'+url +'"></g:plusone>');

  var script = document.createElement( "script" );
  script.async = "async";
  script.src = document.location.protocol + '//apis.google.com/js/plusone.js';
if (ltoptions.lang != 'en') $(script).append("{lang: '"+ltoptions.lang+"'}");
		    		    $('head:first').append(script);

//facebook

var h = $("<code></code>").appendTo("body");
h.text($(".lazybuttons").html());
}

window.setTimeout(function() {
var cookie  = get_cookie("lazybuttons");
if (cookie  == "hide") {
$('<a href="">Show buttons</a>').appentTo(".lazzybuttons").click(function() {
$(this).remove();
show_lazybuttons();
return false;
});

} else {
show_lazybuttons();
}
    }, 120);
});