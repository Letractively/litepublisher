<?php

function update526() {
litepublisher::$classes->items['tcommentform'][2] = dbversion ? 'comments.form.class.db.php' : 'comments.form.class.files.php';
  litepublisher::$options->comments_status = 'guest';

if (dbversion) {
$db = litepublisher::$db;
$db->table = 'users';
$db->insertrow($db->assoctorow(array(
'id' =>1,
    'email' =>litepublisher::$options->email,
    'name' => litepublisher::$site->author,
    'website' => litepublisher::$site->url . '/',
    'password' => litepublisher::$options->password,
    'cookie' => litepublisher::$options->cookie,
    'expired' => sqldate(litepublisher::$options->cookieexpired ),
    'status' => 'approved',
    'idgroups' => '1',
)));

$db->table = 'usergroup';
        $db->add(array(
        'iduser' => '1',
        'idgroup' => 1
        ));

$man = tdbmanager::i();
//$man->alter($table, "add `includeparents` boolean default " . ($tags->includeparents ? 'true' : 'false'));
$man->delete_enum('users', 'status', 'lock');
$man->addenum('users', 'status', 'notconfirmed');

$man->alter('posts', "add `comments_status` enum('closed','reg','guest','notconfirmed') default 'notconfirmed'");

$db->table = 'posts';
$db->update("comments_status = 'closed'", "commentsenabled = 0");
$man->alter('posts', "drop commentsenabled");

$group = 'comment';
    $from = 0;
$db->table = 'comusers';
    while ($items = $db->res2assoc($db->query("select * from $db->comusers limit $from, 100"))) {
$from += count($items);
      foreach ($items as $item) {
$db->table = 'users';
if ($id = $db->findid('email = '. dbquote($item['email']))) {
} else {
$id = $db->add(array(
    'email' => $item['email'],
    'name' =>$item['name'],
    'website' => $item['url'],
    'password' => '',
    'cookie' =>  '',
    'expired' => sqldate(),
    'idgroups' => $group,
    'trust' => 0,
    'status' => 'notconfirmed'
));
}

        $db->query("update $db->comments set author = $id where $author= " . $item['id']);
      }

$db->table = 'comusers';
    }

}
}