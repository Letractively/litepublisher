<?php
/**
* Lite Publisher
* Copyright (C) 2010 - 2013 Vladimir Yushko http://litepublisher.ru/ http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tmarkdownplugin extends tplugin {
  public $parser;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->data['deletep'] = true;
    litepublisher::$classes->include_file(litepublisher::$paths->plugins . 'markdown' . DIRECTORY_SEPARATOR . 'markdown.parser.class.php');
    $this->parser = new Markdown_Parser();
  }
  
  public function filter(&$content) {
    if ($this->deletep) $content = str_replace('_', '&#95;', $content);
    $content = $this->parser->transform($content);
    if ($this->deletep) $content = strtr($content, array(
    '<p>' => '',
    '</p>' => '',
    '&#95;' => '_'
    ));
  }
  
  public function install() {
    $filter = tcontentfilter::i();
    $filter->lock();
    $filter->onsimplefilter = $this->filter;
    $filter->oncomment = $this->filter;
    $filter->unlock();
  }
  
  public function uninstall() {
    $filter = tcontentfilter::i();
    $filter->unbind($this);
  }
  
}//class