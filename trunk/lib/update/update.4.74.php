<?php

function update474() {
litepublisher::$classes->add('tjsmerger', 'jsmerger.class.php');
litepublisher::$classes->add('tadminjsmerger', 'admin.jsmerger.class.php');

//fix install for backward
$template = ttemplate::instance();
  $template->deletefromhead($template->getjavascript('$site.files$template.jsmerger_default'));


$admin = tadminmenus::instance();
$admin->lock();
    $admin->deleteurl('/admin/views/admin/');
    $admin->createitem($admin->url2id('/admin/views/'), 
'jsmerger', 'admin', 'tadminjsmerger');

$admin->heads =  str_replace('/js/litepublisher/admin.$site.jquery_version.min.js', '$template.jsmerger_admin', $admin->heads);
$admin->unlock();
}