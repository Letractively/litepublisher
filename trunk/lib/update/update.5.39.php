<?php

function update539() {
$parser = tthemeparser::i();
$parser->data['extrapaths'] = array();
$parser->save();

if (litepublisher::$classes->exists('tusernews')) {
$u = tusernews::i();
$u->data['dir'] = 'usernews';
    $u->data['checkspam'] = false;
    $u->data['editorfile'] = 'editor.htm';
$u->save();

tlocalmerger::i()->addplugin('usernews');
}

$js = tjsmerger::i();
$js->lock();
  $section = 'posteditor';
$js->deletefile($section, '/js/litepublisher/swfuploader.min.js');
  $js->add($section, '/js/litepublisher/uploader.min.js');
$js->unlock();
}