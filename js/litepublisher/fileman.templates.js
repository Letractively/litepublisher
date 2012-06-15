(function( $ ){
$.fileman.templates = {
item: '<div class="file-item">\
<span class="value-title" title="{{id}}"/>\
<div class="file-toolbar">\
<a href="#" title="{{add}}" class="add-toolbutton"><img src="{{iconurl}}add.png" title="{{add}}" alt="{{add}}" /></a>\
<a href="#" title="{{del}}" class="delete-toolbutton"><img src="{{iconurl}}delete.png" title="{{del}}" alt="{{del}}" /></a>\
<a href="#" title="{{property}}" class="property-toolbutton"><img src="{{iconurl}}property.png" title="{{property}}" alt="{{property}}" /></a>\
</div>\
<div class="file-content">\
{{content}}\
</div>\
</div>',

image: '',

tabs: '<div id="upload"><span id="uploadbutton"></span></div>\
<div id="progressbar"></div>\
$fileperm\
<div id="posteditor-files-tabs">\
    <ul>\
        <li><a href="#current-files"><span>{{currentfiles}}</span></a></li>\
        <li><a href="#new-files"><span>{{newupload}}</span></a></li>\
    </ul>\
<div id="current-files"></div>\
<div id="new-files"></div>\
</div>\
<p class="hidden"><input type="hidden" name="files" value="" /></p>'
};

})( jQuery );