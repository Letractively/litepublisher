<?php

class tthemeparser {
public $theme;
  private $abouts;

  public function parsetag(&$s, $tag, $replace) {
    $result = '';
    $opentag = "<!--$tag-->";
    $closetag = "<!--/$tag-->";
    if(is_int($i = strpos($s, $opentag)) && ($j = strpos($s, $closetag))) {
      $result = substr($s, $i + strlen($opentag), $j - $i - strlen($opentag));
      $s = substr_replace($s, $replace, $i, $j - $i + strlen($closetag));
    }
    return $result;
  }
  
public function parse($filename, $theme) {
$this->theme = $theme;
$s = file_get_contents($filename);
$this->parsemenu($s);
$this->parsecontent($s);
$theme->sitebarscount = $this->parsesitebars($s);
}

private function parsemenu(&$s) {
$menu = &$this->theme->menu;
$menus = $this->parsetag($s, 'menu, '$template->menu');
$item = $this->parsetag($menus, 'item', '%s');
if ($submenu = $this->parsetag($item, 'submenu', '%3$s')) $menu['submenu'] = $submenu;
$menu['item'] = $item;
$menu['current'] = $this->parsetag($menus, 'current', '');
$menu['menu'] = $menus;
//hover
if (preg_match('/<\w*?\s*.*?id\s*=\s*[\'"]([^"\'>]*)/i', $menus, $m)) {
$menu['id'] = $m[1];
preg_match('/\<(\w*?)\s/',$item, $t);
$menu['tag'] = $t[1];
}
}

private function parsecontent(&$s) {
$theme = $this->theme;
$ content = $this->parsetag($s, 'content', '$template->content');
$theme->excerpt = $this->parsetag($content, 'excerpt', '');
$post = $this->parsetag($content, 'post', '');
$comments = $this->parsetag($post, 'templatecomments', '$post->templatecomments');
$this->parsecomments($comments);
$theme->post = $post;

$theme->menucontent = $this->parsetag($content, 'menucontent', '');

$this->parsenavi($this->parsetag($content, 'navi', ''));
$this->parseadmin($this->parsetag($content, 'admin', '');
}

private function parsenavi($s) {
$theme = $this->theme;
$theme->navi['prev'] = $this->parsetag($s, 'prev', '%s');
$theme->navi['next'] = $this->parsetag($s, 'next', '');
$theme->navi['link'] = $this->parsetag($s, 'link', '');
$theme->navi['current'] = $this->parsetag($s, 'current', '');
$theme->navi['divider'] = $this->parsetag($s, 'divider', '');
$theme->navi['navi'] = $s;
}

private function parseadmin($s) {
$theme = $this->theme;
$theme->admin['area'] = trim($this->parsetag($s, 'area', ''));
$theme->admin['edit'] = trim($this->parsetag($s, 'edit', ''));
}

private function parsecomments($s) {
$theme = $this->theme;
    $comments = $this->parsetag($s, 'comments', '');
    $theme->comments['count'] = $this->parsetag($comments, 'count', '');
    $comment = $this->parsetag($comments, 'comment', '%1$s');
    $theme->comments['comments'] = $comments;
    $theme->comments['class1'] = $this->parsetag($comment, 'class1', '$class');
    $theme->comments['class2'] = $this->parsetag($comment, 'class2', '');
    $theme->comments['hold'] = $this->parsetag($comment, 'hold', '$hold');
    $theme->comments['comment'] = $comment;
    
    $pingbacks = $this->parsetag($s, 'pingbacks', '');
    $theme->comments['pingback'] = $this->parsetag($pingbacks, 'pingback', '%1$s');
    $theme->comments['pingbacks'] = $pingbacks;
    
    $theme->comments['closed'] = $this->parsetag($s, 'closed', '');
$theme->commentform = $this->parsetag($s, 'form', '');
  }

private function parse sitebars(&$s) {
$theme = $this->theme;
$index = 0;
while ($sitebar = $this->parsetag($s, 'sitebar', '$template->sitebar')) {
$theme->widgets[$index] = array();
$widgets = &$theme->widgets[$index];
$widgets['widget'] = $this->parsetag($sitebar, 'widget', '');
if ($categories =$this->parsetag($sitebar, 'categories', ''))  {
if ($item = $this->parsetag($categories, 'item', '%s')) $widgets['tag'] = $item;
$widgets['categories'] = $categories;
}

if ($archives =$this->parsetag($sitebar, 'archives', '')) $widgets['archives'] = $archives;
if ($links =$this->parsetag($sitebar, 'links', '')) $widgets['links'] = $links;

if ($posts =$this->parsetag($sitebar, 'posts', '')) {
if ($item = $this->parsetag($posts, 'item', '%s')) $widgets['post'] = $item;
$widgets['posts'] = $posts;
}

if ($comments =$this->parsetag($sitebar, 'comments', '')) {
if ($item = $this->parsetag($comments, 'item', '%s')) $widgets['comment'] = $item;
$widgets['comments'] = $comments;
}

if ($meta =$this->parsetag($sitebar, 'meta', '')) $widgets['meta'] = $meta;
$index++;
}
return $index;
}

//manager
  public function getabout($name) {
    global $paths, $options;
    if (!isset($this->abouts)) $this->abouts = array();
    if (!isset($this->abouts[$name])) {
$about = parse_ini_file($paths['themes'] . $name . DIRECTORY_SEPARATOR . 'about.ini', true);
//����� �������� ������ � ��������
if (isset($about[$options->language])) {
$about['about'] = $about[$options->language] + $about['about'];
}
$this->abouts[$name] = $about['about'];
    }
    return $this->abouts[$name];
  }
  
public function changetheme($old, $name) {
    global $paths, $options;
$template = ttemplate::instance();

      if ($about = $this->getabout($old)) {
        if (!empty($about['about']['pluginclassname'])) {
          $plugins = tplugins::instance();
          $plugins->delete($old);
        }
      }

      $template->data['theme'] = $name;
      $template->path = $paths['themes'] . $name . DIRECTORY_SEPARATOR  ;
      $template->url = $options->url  . '/themes/'. $template->theme;

$theme = ttheme::instance();
$this->parse($template->path . 'index.tml', $theme);
$theme->basename = 'themes' . DIRECTORY_SEPARATOR . "$template->theme-$template->tml";
$theme->save();
$template->save();

      $about = $this->getabout($name);
      if (!empty($about['about']['pluginclassname'])) {
        $plugins = tplugins::instance();
        $plugins->addext($name, $about['about']['pluginclassname'], $about['about']['pluginfilename']);
      }

      $urlmap = turlmap::instance();
      $urlmap->clearcache);
  }
  
}//class

?>