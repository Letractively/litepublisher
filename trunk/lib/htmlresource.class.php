<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class thtmltag {
  public $tag;
public function __construct($tag) { $this->tag = $tag; }
  public function __get($name) {
    $lang = tlocal::i();
  return "<$this->tag>{$lang->$name}</$this->tag>\n";
  }
  
}//class

class tadminhtml {
  public static $tags = array('h1', 'h2', 'h3', 'h4', 'p', 'li', 'ul', 'strong');
  public $section;
  public $ini;
  private $map;
  
  public static function i() {
    $self = getinstance(__class__);
    if (count($self->ini) == 0) $self->load();
    return $self;
  }
  
  public static function getinstance($section) {
    $self = getinstance(__class__);
    $self->section = $section;
    tlocal::i($section);
    return $self;
  }
  
  public function __construct() {
    $this->ini = array();
    tlocal::usefile('admin');
  }
  
  public function __get($name) {
    if (in_array($name, self::$tags)) return new thtmltag($name);
    if (isset($this->ini[$this->section][$name]))  {
      $s = $this->ini[$this->section][$name];
    } elseif (isset($this->ini['common'][$name]))  {
      $s = $this->ini['common'][$name];
    } else {
      throw new Exception("the requested $name item not found in $this->section section");
    }
    return $s;
  }
  
  public function __call($name, $params) {
    if (isset($this->ini[$this->section][$name]))  {
      $s = $this->ini[$this->section][$name];
    } elseif (isset($this->ini['common'][$name]))  {
      $s = $this->ini['common'][$name];
    } else {
      throw new Exception("the requested $name item not found in $this->section section");
    }
    $args = isset($params[0]) && $params[0] instanceof targs ? $params[0] : targs::i();
    return $this->parsearg($s, $args);
  }
  
  public function parsearg($s, targs $args) {
    if (!is_string($s)) $s = (string) $s;
    $theme = ttheme::i();
    $admin = $theme->content->admin;
    $admin->tostring = true;
    // parse tags [form] .. [/form]
    if (is_int($i = strpos($s, '[form]'))) {
      $replace = substr($admin->form, 0, strpos($admin->form, '$items'));
      $s = substr_replace($s, $replace, $i, strlen('[form]'));
    }
    
    if ($i = strpos($s, '[/form]')) {
      $replace = substr($admin->form, strrpos($admin->form, '$items') + strlen('$items'));
      $s = substr_replace($s, $replace, $i, strlen('[/form]'));
    }
    
    if (preg_match_all('/\[(editor|checkbox|text|password|combo|hidden)(:|=)(\w*+)\]/i', $s, $m, PREG_SET_ORDER)) {
      foreach ($m as $item) {
        $type = $item[1];
        $name = $item[3];
        $varname = '$' . $name;
        //convert spec charsfor editor
        if (!(($type == 'checkbox') || ($type == 'combo'))) {
          if (isset($args->data[$varname])) {
            $args->data[$varname] = self::specchars($args->data[$varname]);
          } else {
            $args->data[$varname] = '';
          }
        }
        $tag = strtr($admin->$type, array(
        '$name' => $name,
        '$value' =>$varname
        ));
        $s = str_replace($item[0], $tag, $s);
      }
    }
    $s = strtr($s, $args->data);
    return $theme->parse($s);
  }
  
  public static function specchars($s) {
    return strtr(            htmlspecialchars($s), array(
    '"' => '&quot;',
    "'" =>'&#39;',
    '$' => '&#36;',
    '%' => '&#37;',
    '_' => '&#95;'
    ));
  }
  
  public function fixquote($s) {
    $s = str_replace("\\'", '\"', $s);
    $s = str_replace("'", '"', $s);
    return str_replace('\"', "'", $s);
  }
  
  public function load() {
    $filename = tlocal::getcachedir() . 'adminhtml';
    if (tfilestorage::loadvar($filename, $v) && is_array($v)) {
      $this->ini = $v + $this->ini;
    } else {
      $merger = tlocalmerger::i();
      $merger->parsehtml();
    }
  }
  
  public function loadinstall() {
    if (isset($this->ini['installation'])) return;
    tlocal::usefile('install');
    if( $v = parse_ini_file(litepublisher::$paths->languages . 'install.ini', true)) {
      $this->ini = $v + $this->ini;
    }
  }
  
  public static function getparam($name, $default) {
    return !empty($_GET[$name]) ? $_GET[$name] : (!empty($_POST[$name]) ? $_POST[$name] : $default);
  }
  
  public static function idparam() {
    return (int) tadminhtml::getparam('id', 0);
  }
  
  public static function getadminlink($path, $params) {
    return litepublisher::$site->url . $path . litepublisher::$site->q . $params;
  }
  
  public static function array2combo(array $items, $selected) {
    $result = '';
    foreach ($items as $i => $title) {
      $result .= sprintf('<option value="%s" %s>%s</option>', $i, $i == $selected ? 'selected' : '', self::specchars($title));
    }
    return $result;
  }
  
  public static function getcombobox($name, array $items, $selected) {
    return sprintf('<select name="%1$s" id="%1$s">%2$s</select>', $name,
    self::array2combo($items, $selected));
  }
  
  public function adminform($tml, targs $args) {
    $args->items = $this->parsearg($tml, $args);
    return $this->parsearg(ttheme::i()->content->admin->form, $args);
  }
  
  public function getcheckbox($name, $value) {
    return $this->getinput('checkbox', $name, $value ? 'checked="checked"' : '', '$lang.' . $name);
  }
  
  public function getradioitems($name, array $items, $selected) {
    $result = '';
    $theme = ttheme::i();
    $tml = $theme->templates['content.admin.radioitems'];
    foreach ($items as $index => $value) {
      $result .= strtr($tml, array(
      '$index' => $index,
      '$checked' => $value == $selected ? 'checked="checked"' : '',
      '$name' => $name,
      '$value' => self::specchars($value)
      ));
    }
    return $result;
  }
  
  public function getinput($type, $name, $value, $title) {
    $theme = ttheme::i();
    return strtr($theme->templates['content.admin.' . $type], array(
    '$lang.$name' => $title,
    '$name' => $name,
    '$value' => $value
    ));
  }
  
  public function getedit($name, $value, $title) {
    return $this->getinput('text', $name, $value, $title);
  }
  
  public function getcombo($name, $value, $title) {
    return $this->getinput('combo', $name, $value, $title);
  }
  
  public function gettable($head, $body) {
    return strtr($this->ini['common']['table'], array(
    '$tablehead' => $head,
    '$tablebody' => $body));
  }
  
  public function buildtable(array $items, array $tablestruct) {
    $head = '';
    $body = '';
    $tml = '<tr>';
    foreach ($tablestruct as $elem) {
      $head .= sprintf('<th align="%s">%s</th>', $elem[0], $elem[1]);
      $tml .= sprintf('<td align="%s">%s</td>', $elem[0], $elem[2]);
    }
    $tml .= '</tr>';
    
    $theme = ttheme::i();
    $args = targs::i();
    foreach ($items as $id => $item) {
      $args->add($item);
      if (!isset($item['id'])) $args->id = $id;
      $body .= $theme->parsearg($tml, $args);
    }
    $args->tablehead  = $head;
    $args->tablebody = $body;
    return $theme->parsearg($this->ini['common']['table'], $args);
  }
  
  public function confirmdelete($id, $adminurl, $mesg) {
    $args = targs::i();
    $args->id = $id;
    $args->action = 'delete';
    $args->adminurl = $adminurl;
    $args->confirm = $mesg;
    return $this->confirmform($args);
  }
  
  public static function check2array($prefix) {
    $result = array();
    foreach ($_POST as $key => $value) {
      if (strbegin($key, $prefix)) {
        $result[] = (int) $value;
      }
    }
    return $result;
  }
  
}//class

class tautoform {
  const editor = 'editor';
  const text = 'text';
  const checkbox = 'checkbox';
  const hidden = 'hidden';
  
  public $obj;
  public $props;
  public $section;
  public $_title;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  public function __construct(tdata $obj, $section, $titleindex) {
    $this->obj = $obj;
    $this->section = $section;
    $this->props = array();
    $lang = tlocal::i($section);
    $this->_title = $lang->$titleindex;
  }
  
  public function __set($name, $value) {
    $this->props[] = array(
    'obj' => $this->obj,
    'propname' => $name,
    'type' => $value
    );
  }
  
  public function __get($name) {
    if (isset($this->obj->$name)) {
      return array(
      'obj' => $this->obj,
      'propname' => $name
      );
    }
    //tlogsubsystem::error(sprintf('The property %s not found in class %s', $name, get_class($this->obj));
  }
  
  public function __call($name, $args) {
    if (isset($this->obj->$name)) {
      $result = array(
      'obj' => $this->obj,
      'propname' => $name,
      'type' => $args[0]
      );
      if (($result['type'] == 'combo') && isset($args[1]))  $result['items'] = $args[1];
      return $result;
    }
  }
  
  public function add() {
    $a = func_get_args();
    foreach ($a as $prop) {
      $this->addprop($prop);
    }
  }
  
  public function addsingle($obj, $propname, $type) {
    return $this->addprop(array(
    'obj' => $obj,
    'propname' => $propname,
    'type' => $type
    ));
  }
  
  public function addeditor($obj, $propname) {
    return $this->addsingle($obj, $propname, 'editor');
  }
  
  public function addprop(array $prop) {
    if (isset($prop['type'])) {
      $type = $prop['type'];
    } else {
      $type = 'text';
    $value = $prop['obj']->{$prop['propname']};
      if (is_bool($value)) {
        $type = 'checkbox';
      } elseif(strpos($value, "\n")) {
        $type = 'editor';
      }
    }
    
    $item = array(
    'obj' => $prop['obj'],
    'propname' => $prop['propname'],
    'type' => $type,
    'title' => isset($prop['title']) ? $prop['title'] : ''
    );
    if (($type == 'combo') && isset($prop['items'])) $item['items'] = $prop['items'];
    $this->props[] = $item;
    return count($this->props) - 1;
  }
  
  public function getcontent() {
    $result = '';
    $lang = tlocal::i();
    $theme = ttheme::i();
    $admin = $theme->content->admin;
    $admin->tostring = true;
    foreach ($this->props as $prop) {
    $value = $prop['obj']->{$prop['propname']};
      switch ($prop['type']) {
        case 'text':
        case 'editor':
        $value = tadminhtml::specchars($value);
        break;
        
        case 'checkbox':
        $value = $value ? 'checked="checked"' : '';
        break;
        
        case 'combo':
        $value = tadminhtml  ::array2combo($prop['items'], $value);
        break;
      }
      
    $result .= strtr($admin->{$prop['type']}, array(
    '$lang.$name' => empty($prop['title']) ? $lang->{$prop['propname']} : $prop['title'],
      '$name' => $prop['propname'],
      '$value' => $value
      ));
    }
    return $result;
  }
  
  public function getform() {
    $args = targs::i();
    $args->formtitle = $this->_title;
    $args->items = $this->getcontent();
    $theme = ttheme::i();
    return $theme->parsearg($theme->content->admin->form, $args);
  }
  
  public function processform() {
    foreach ($this->props as $prop) {
      if (method_exists($prop['obj'], 'lock')) $prop['obj']->lock();
    }
    
    foreach ($this->props as $prop) {
      $name = $prop['propname'];
      if (isset($_POST[$name])) {
        $value = trim($_POST[$name]);
        if ($prop['type'] == 'checkbox') $value = true;
      } else {
        $value = false;
      }
      $prop['obj']->$name = $value;
    }
    
    foreach ($this->props as $prop) {
      if (method_exists($prop['obj'], 'unlock')) $prop['obj']->unlock();
    }
  }
  
}//class

class ttablecolumns {
  public $style;
  public $head;
  public $checkboxes;
  public $checkbox_tml;
  public $item;
  public $changed_hidden;
  public $index;
  
  public function __construct() {
    $this->index = 0;
    $this->style = '';
    $this->checkboxes = array();
    $this->checkbox_tml = '<input type="checkbox" name="checkbox-showcolumn-%1$d" value="%1$d" %2$s />
    <label for="checkbox-showcolumn-%1$d"><strong>%3$s</strong></label>';
    $this->head = '';
    $this->body = '';
    $this->changed_hidden = 'changed_hidden';
  }
  
  public function addcolumns(array $columns) {
    foreach ($columns as $column) {
      list($tml, $title, $align, $show) = $column;
      $this->add($tml, $title, $align, $show);
    }
  }
  
  public function add($tml, $title, $align, $show) {
    $class = 'col_' . ++$this->index;
    if (isset($_POST[$this->changed_hidden])) $show  = isset($_POST["checkbox-showcolumn-$this->index"]);
    $display = $show ? 'block' : 'none';
  $this->style .= ".$class { text-align: $align; display: $display; }\n";
    $this->checkboxes[]=  sprintf($this->checkbox_tml, $this->index, $show ? 'checked="checked"' : '', $title);
    $this->head .= sprintf('<th class="%s">%s</th>', $class, $title);
    $this->body .= sprintf('<td class="%s">%s</td>', $class, $tml);
    return $this->index;
  }
  
  public function build($body, $buttons) {
    $args = targs::i();
    $args->style = $this->style;
    $args->checkboxes = implode("\n", $this->checkboxes);
    $args->head = $this->head;
    $args->body = $body;
    $args->buttons = $buttons;
    $tml = file_get_contents(litepublisher::$paths->languages . 'tablecolumns.ini');
    $theme = ttheme::i();
    return $theme->parsearg($tml, $args);
  }
  
}//class

class tuitabs {
  public $head;
  public $body;
  public $tabs;
  private static $index = 0;
  private $items;
  
  public function __construct() {
    self::$index++;
    $this->items = array();
    $this->head = '<li><a href="#tab-' . self::$index. '-%d"><span>%s</span></a></li>';
    $this->body = '<div id="tab-' . self::$index . '-%d">%s</div>';
    $this->tabs = '<div id="tabs-' . self::$index . '" rel="tabs">
    <ul>%s</ul>
    %s
    </div>';
  }
  
  public function get() {
    $head= '';
    $body = '';
    foreach ($this->items as $i => $item) {
      $head .= sprintf($this->head, $i, $item['title']);
      $body .= sprintf($this->body, $i, $item['body']);
    }
    return sprintf($this->tabs, $head, $body);
  }
  
  public function add($title, $body) {
    $this->items[] = array(
    'title' => $title,
    'body' => $body
    );
  }
  
  public static function gethead() {
    $template = ttemplate::i();
    return $template->getready('$($("div[rel=\'tabs\']").get().reverse()).tabs()');
  }
  
}//class