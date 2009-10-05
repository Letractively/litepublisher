<?php
function Update276() {
$names = array(
'TCategories' => 'categories',
'TTags' => 'tagcloud',
'TArchives' => 'archives',
'TLinksWidget' => 'links',
'TFoaf' => 'myfriends',

'TPosts' => 'recentposts',
'TCommentManager' => 'recentcomments',
'TMetaWidget' => 'meta',
);

$template = TTemplate::Instance();
foreach ($template->widgets as $id => $widget) {
$class = $widget['class'];
if (isset($names[$class])) {
$widget['template'] = $names[$class];
$widget['title'] = TLocal::$data['default'][$names[$class]];
} else {
$widget['title'] = '';
$widget['template'] = '';
}

$template->widgets[$id] = $widget;
}
$template->save();

$urlmap = TUrlmap::Instance();
$urlmap->ClearCache();
$urlmap->Redir301('/admin/service/');
}

?>