(function( $ ){
$.fileman.templates = {
item: '<div class="file-item">\
<div class="file-toolbar">\
<a href="#" title="{{lang.add}}" class="add-toolbutton"><img src="{{iconurl}}add.png" title="{{lang.add}}" alt="{{lang.add}}" /></a>\
<a href="#" title="{{lang.del}}" class="delete-toolbutton"><img src="{{iconurl}}delete.png" title="{{lang.del}}" alt="{{lang.del}}" /></a>\
<a href="#" title="{{lang.property}}" class="property-toolbutton"><img src="{{iconurl}}property.png" title="{{lang.property}}" alt="{{lang.property}}" /></a>\
</div>\
<div class="file-content">\
{{content}}\
</div>\
</div>',

image: '<a rel="prettyPhoto[gallery-fileman]" href="{{link}}"><img src="{{previewlink}}" title="{{title}}" alt="{{description}}" /></a>',

file: '<p>\
{{iconurl =ltoptions.files + "/js/litepublisher/icons/";file}}: <a href="{{link}}" title="{{title}}">{{description}}</a><br />\
{{lang.filesize}}: <span class="text-right">{{size}}</span><br />\
{{lang.title}}: {{title}}<br />\
{{lang.description}}: {{description}}<br />\
{{lang.keywords}}: {{keywords}}<br />\
</p>',

tabs: '<div id="upload"><span id="uploadbutton"></span></div>\
<div id="progressbar"></div>\
$fileperm\
<div id="posteditor-files-tabs">\
    <ul>\
        <li><a href="#current-files"><span>{{lang.currentfiles}}</span></a></li>\
        <li><a href="#new-files"><span>{{lang.newupload}}</span></a></li>\
    </ul>\
<div id="current-files"></div>\
<div id="new-files" class="files-tab"></div>\
</div>\
<p class="hidden"><input type="hidden" name="files" value="" /></p>',

tab: '<div class="files-tab" id="filepage-{{index}}"></div>',
fileprops: '<p><label><input type="text" name="fileprop-title" value="" size="22" />\
<strong>{{lang.title}}</strong></label></p>\
\
<p><label><input type="text" name="fileprop-description" value="" size="22" />\
<strong>{{lang.description}}</strong></label></p>\
\
<p><label><input type="text" name="fileprop-keywords" value="" size="22" />\
<strong>{{lang.keywords}}</strong></label></p>'
};

})( jQuery );