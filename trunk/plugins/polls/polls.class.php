<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tpolls extends titems {
public $userstable;
public $resulttable;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = dbversion;
    parent::create();
    $this->table = 'pols';
    $this->basename = 'pols';
$this->userstable = 'polusers';
$this->resulttable = 'polsresult';'
  }

public function addvote($idpoll, $iduser, $vote) {
if (!$this->itemexists($id)) return  false;
$vote = (int) $vote;
$db = $this->getdb($this->resulttable)
$db->add(array(
'poll' => $idpoll, 
'user' => $iduser,
'vote' => $vote
));
$table = $db->prefix . $this->votestable;
$res = $db->query("select vote as vote, count(user) as count from $table
where poll = $idpoll  group by vote order by vote asc");

$votes = array();
while($item = $db->fetchassoc($res)) {
$votes[$item['vote']] = $item['count'];
}

$this->db->setvalue($idpoll, 'votes', implode(',', $votes));
return $votes;
}
  
  public function add($url) {
    $id = $this->IndexOf('url', $url);
    if ($id > 0) return $id;
    $item = array(
    'url' => $url,
    'clicked' => 0
    );
    
    if ($this->dbversion) {
      $id = $this->db->add($item);
      $this->items[$id] = $item;
      return $id;
    } else {
      $this->items[++$this->autoid]  = $item;
      $this->save();
      return $this->autoid;
    }
  }


private function extractitems($s) {
$result = array();
$lines = explode("\n", $s);
foreach ($lines as $name) {
$name = trim($name);
if (($name == '')  || ($name[0] == '[')) continue;
$result[] = $name;
}
return $result;
}

private function extractvalues($s) {
$result = array();
$lines = explode("\n", $s);
foreach ($lines as $line) {
$line = trim($line);
if (($line == '')  || ($line[0] == '[')) continue;
      if ($i = strpos($line, '=')) {
        $name = trim(substr($line, 0, $i));
        $value = trim(substr($line, $i + 1));
if (($name != '') && ($value != '')) $result[$name] = $value;
}
}
return $result;
}

 public function beforefilter($idpost, &$content) {
    $content = str_replace(array("\r\n", "\r"), "\n", $content);
$i = 0;
while (is_int($i = strpos($content, '[poll]', $i))) {
$j = strpos($content, '[/poll]', $i);
if ($j == false) {
// ������ ������� ����� � ���� ����� ������ ������ ������
$j = strpos($content, "\n\n", $i);
$s = substr($content, $i, $j - $i);
$items = $this->extractitems($s);
$id = $this->add('', $items);
$item = $this->getitem($id);
$stritems = implode("\n", $items);
$replace = "[poll]\nid=$id\nstatus={$item['status']}\ntitle={$item['title']}\n[items]\n$stritems\n[/items]\n[/poll]";
$content = substr_replace($content, $replace, $i, $j - $i);
$i = min($j, strlen($content));
} else {
// ���������, ���� id � �����������
$j += strlen("[/poll]");
$s = substr($content, $i, $j - $i);
// ��������� ������ items
$k = strpos($s, '[items]');
$l = strpos($s, '[/items]');
$items = $this->extractitems(substr($s, $k, $l));
$s = substr_replace($s, '', $k, $l - $k);
$values = $this->extractvalues($s);
$title = isset($values['title'] ? $values['title'] : '';
$status = isset($values['status'] ? $values['status'] : '';
if (!isset($values['id'])) {
$id = $this->add($title, $items);
} else {
if (!$this->edit($values['id'], $title, $items, $status)){
$i = min($j, strlen($content));
continue;
}
}
$item = $this->getitem($id);
$stritems = implode("\n", $items);
$replace = "[poll]\nid=$id\nstatus={$item['status']}\ntitle={$item['title']}\n[items]\n$stritems\n[/items]\n[/poll]";
$content = substr_replace($content, $replace, $i, $j - $i);
$i = min($j, strlen($content));
}
}
}
 
  public function filter(&$content) {
// ����� ������ �������� ��� ����������� �� html
}

public function postdeleted($id) {
$list = $this->db->idselect("post = $id");
if (count($list) == 0) return;
$items = sprintf('(%s)', implode(',', $list));
$this->db->delete("id in $items");
$this->getdb($this->resulttable)->delete("id in $items");
$db = $this->getdb($this->userstable);
$db->delete("id not in (
select DISTINCT user from $db->prefix.$this->resulttable)");
}

public function xmlrpcpol($idpoll, $vote) {
if (!$this->itemexists($idpoll)) return $this->error("Poll not found', 404);
$cookie = isset($_COOKIE['polluser']) ? $_COOKIE['polluser'] : '';
if ($cookie == '') {
$cookie = md5uniq();
$this->getdb($this->userstable)->add(array('cookie' => $cookie));
return array('cookie' => $cookie);
} elseif( $iduser = $this->getdb($this->userstable)->findid('cookie = ' .dbquote($cookie))) {
if ($this->hasvote($idpoll, $iduser)) return $this->error('You already vote'), 403);
$this->addvote($idpoll, $iduser, $vote);
return array();
} else {
}
$cookie = md5uniq();
$this->getdb($this->userstable)->add(array('cookie' => $cookie));
return array('cookie' => $cookie);
}
}

}//class
?>