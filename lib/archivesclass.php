<?php

class TArchives extends TItems implements  ITemplate {
  public $date;
  
  public static function &Instance() {
    return GetNamedInstance('archives', __class__);
  }
  
  protected function CreateData() {
    parent::CreateData();
    $this->basename   = 'archives';
    $this->Data['lite'] = false;
    $this->Data['showcount'] = false;
  }
  
  public function GetWidgetContent($id) {
    global $Options;
    $result = '';
    
    foreach ($this->items as $date => $item) {
  $result  .= "<li><a rel=\"archives\" href=\"$Options->url{$item['url']}\">{$item['title']}</a>";
      if ($this->showcount) $result .= '(' . count($item['posts']) . ')';
      $result .= "</li>\n";
    }
    
    return $result;
  }
  
  public function GetHeadLinks() {
    global $Options;
    $result = '';
    foreach ($this->items as $date => $item) {
  $result  .= "<link rel=\"archives\" title=\"{$item['title']}\" href=\"$Options->url{$item['url']}\" />\n";
    }
    return $result;
  }
  
  protected function Setlite($value) {
    if ($value != $this->lite) {
      $this->Data['lite'] = $value;
      $this->Save();
    }
  }
  
  public function PostsChanged() {
    $this->lock();
    $posts = &TPosts::Instance();
    $this->items = array();
    //sort archive by months
    $Linkgen = &TLinkGenerator::Instance();
    foreach ($posts->archives as $id => $date) {
      $d = getdate($date);
      $this->date = mktime(0,0,0, $d["mon"] , 1, $d["year"]);
      if (!isset($this->items[$this->date])) {
        $this->items[$this->date] = array(
        'url' => $Linkgen->Create($this, 'archive', false),
        'title' => TLocal::date($this->date, 'F Y'),
        'posts' => array()
        );
      }
      $this->items[$this->date]['posts'][] = $id;
    }
    $this->CreatePageLinks();
    $this->unlock();
  }
  
  public function CreatePageLinks() {
    global $Options;
    $Urlmap = &TUrlmap::Instance();
    $Urlmap->Lock();
    $this->Lock();
    //Compare links
    $old = &$Urlmap->GetClassItems(get_class($this));
    foreach ($this->items as $date => $item) {
      $j = array_search($item['url'], $old);
      if (is_int($j))  {
        array_splice($old, $j, 1);
      } else {
        $Urlmap->Add($item['url'], get_class($this), $date);
      }
    }
    foreach ($old as $url) {
      $Urlmap->Delete($url);
    }
    
    $this->Unlock();
    $Urlmap->Unlock();
  }
  
  //ITemplate
  public function request($date) {
    $this->date = $date;
  }
  
  public function gettitle() {
    return $this->items[$this->date]['title'];
  }
  
public function gethead() {}
public function getkeywords() {}
public function getdescription() {}
  
  public function GetTemplateContent() {
    global $Options, $Urlmap;
    if (!isset($this->items[$this->date]['posts'])) return '';
    $items = &$this->items[$this->date]['posts'];
    $TemplatePost = &TTemplatePost::Instance();
    if ($this->lite) {
      
      $postsperpage = 1000;
      $list = array_slice($items, ($Urlmap->pagenumber - 1) * $postsperpage, $postsperpage);
      $result = $TemplatePost->LitePrintPosts($list);
      $result .=$TemplatePost->PrintNaviPages($this->items[$this->date]['url'], $Urlmap->pagenumber, ceil(count($items)/ $postsperpage));
      return $result;
    } else {
      $list = array_slice($items, ($Urlmap->pagenumber - 1) * $Options->postsperpage, $Options->postsperpage);
      $result = $TemplatePost->PrintPosts($list);
      $result .=$TemplatePost->PrintNaviPages($this->items[$this->date]['url'], $Urlmap->pagenumber, ceil(count($items)/ $Options->postsperpage));
      return $result;
    }
  }
  

private function SortArchives() {
/*
"SELECT YEAR(created) AS `year`, MONTH(created) AS `month`, count(ID) as 'count' FROM 
where status = 'published' GROUP BY YEAR(created), MONTH(created) ORDER BY created DESC ";
*/
}
}

?>