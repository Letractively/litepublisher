<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class TXMLRPCMetaWeblog extends TXMLRPCAbstract {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function MWSetPingCommentStatus(array &$Struct, tpost $post) {
    global $options;
    if(isset($struct["mt_allow_comments"])) {
      if(!is_numeric($struct["mt_allow_comments"])) {
        switch($struct["mt_allow_comments"]) {
          case "closed":
          $post->commentsenabled = false;
          break;
          case "open":
          $post->commentsenabled = true;
          break;
          default:
          $post->commentsenabled = $options->commentsenabled;
          break;
        }
      }
      else {
        switch((int) $struct["mt_allow_comments"]) {
          case 0:
          $post->commentsenabled = false;
          break;
          case 1:
          $post->commentsenabled = true;
          break;
          default:
          $post->commentsenabled = $options->commentsenabled;
          break;
        }
      }
    }
    else {
      $post->commentsenabled = $options->commentsenabled;
    }
    
    if(isset($struct["mt_allow_pings"])) {
      if(!is_numeric($struct["mt_allow_pings"])) {
        switch($struct['mt_allow_pings']) {
          case "closed":
          $post->pingenabled = false;
          break;
          case "open":
          $post->pingenabled = true;
          break;
          default:
          $post->pingenabled = $options->pingenabled;
          break;
        }
      }
      else {
        switch((int) $struct["mt_allow_pings"]) {
          case 0:
          $post->pingenabled = false;
          break;
          case 1:
          $post->pingenabled = true;
          break;
          default:
          $post->pingenabled = $options->pingenabled;
          break;
        }
      }
    }
    else {
      $post->pingenabled = $options->pingenabled;
    }
  }
  
  protected function MWSetDate(array &$struct, $post) {
    foreach (array('dateCreated', 'pubDate') as $name) {
      if (!empty($struct[$name])) {
        if (is_object($struct[$name])) {
          $post->posted = $struct[$name]->getTimestamp();
        } else {
          $post->pubdate  = $struct[$name];
        }
        return;
      }
    }
    $post->posted = time();
  }
  
  //forward implementation
  public function wp_newPage($blogid, $username, $password, $struct, $publish) {
    $this->auth($username, $password, 'editor');
    $menus = tmenus::instance();
    $menu = tmenu::instance(0);
    $menu->status = $publish ? 'published' : 'draft';
    $this->WPAssignPage($struct, $menu);
    return (int) $menus->add($menu);
  }
  
  protected function  WPAssignPage(array &$struct, tmenu $menu) {
    $menu->title = $struct['title'];
    if (empty($struct['mt_text_more'])) {
      $menu->content = $struct['description'];
    } else {
      $menu->content = $struct['description'] . $struct['mt_text_more'];
    }
    
    if(isset($struct["wp_slug"])) {
      $linkgen = tlinkgenerator::instance();
      $menu->url = $linkgen->AddSlashes($struct['wp_slug']);
    }
    
    if(isset($struct["wp_password"])) {
      $menu->password = $struct["wp_password"];
    }
    
    if(isset($struct["wp_page_parent_id"])) {
      $menu->parent = (int) $struct["wp_page_parent_id"];
    }
    
    if(isset($struct["wp_page_order"])) {
      $menu->order = (int) $struct["wp_page_order"];
    }
    
    $this->MWSetDate($struct, $menu);
    
    /* custom_fields is not supported */
  }
  
  /* <item> in RSS 2.0, providing a rich variety of item-level metadata, with well-understood applications.
  The three basic elements are title, link and description.  */
  protected function  MWSetPost(array &$struct, tpost $post) {
    $post->title = $struct['title'];
    $more = isset($struct['mt_text_more']) ? trim($struct['mt_text_more']) : '';
    if ($more == '') {
      $post->content = $struct['description'];
    } else {
      $morelink = sprintf("\n<!--more %s-->\n", tlocal::$data['post']['more']);
      $post->content = $struct['description']. $morelink . $more;
    }
    
    $excerpt =isset($struct['mt_excerpt']) ? trim($struct['mt_excerpt']) : '';
    if ($excerpt != '') $post->excerpt = $excerpt;
    
    if (isset($struct['categories']) && is_array($struct['categories'])) {
      $post->catnames = $struct['categories'];
    }
    
    if(isset($struct["wp_slug"])) {
      $linkgen = tlinkgenerator::instance();
      $post->url = $linkgen->AddSlashes($struct["wp_slug"] . '/');
    } elseif (!empty($struct['link'])) {
      $post->link = $struct['link'];
    } elseif (!empty($struct['guid'])) {
      $post->link = $struct['guid'];
    } elseif (!empty($struct['permaLink'])) {
      $post->link = $struct['permaLink'];
    }
    
    if(isset($struct["wp_password"])) {
      $post->password = $struct["wp_password"];
    }
    
    if (!empty($struct['mt_keywords'])) {
      $post->tagnames = $struct['mt_keywords'];
    }
    
    $this->MWSetDate($struct, $post);
    $this->MWSetPingCommentStatus($struct, $post);
    
    /* not supported yet
    if (isset($struct['flNotOnHomePage']) && $struct['flNotOnHomePage']) {
      //exclude post from homepage
    }
    
    if (!empty($struct['enclosure'])) {
      //enclosure Describes a media object that is attached to the item.
      <enclosure> is an optional sub-element of <item>.
      
      It has three required attributes. url says where the enclosure is located, length says how big it is in bytes, and type says what its type is, a standard MIME type.
      
      The url must be an http url.
      
      <enclosure url="http://www.scripting.com/mp3s/weatherReportSuite.mp3" length="12216320" type="audio/mpeg" />
      
      A use-case narrative for this element is here.
    }
    
    */
  }
  
  public function wp_editPage($blogid, $id, $username, $password, $struct, $publish) {
    $this->auth($username, $password, 'editor');
    $id = (int) $id;
    $menus = tmenus::instance();
    if (!$menus->itemexists($id))  return xerror(404, "Sorry, no such page.");
    $menu = tmenu::instance($id);
    $menu->status = $publish ? 'published' : 'draft';
    $this->WPAssignPage($struct, $menu);
    $menus->edit($menu);
    return true;
  }
  
  /* returns struct.
  The struct returned contains one struct for each category, containing the following elements: description, htmlUrl and rssUrl. */
  
  public function getCategories($blogid, $username, $password) {
    global $options;
    $this->auth($username, $password, 'editor');
    
    $categories = tcategories::instance();
    if (dbversion) {
      global $db;
      $res = $db->query("select $categories->thistable.*, $db->urlmap.url as url  from $categories->thistable,  $db->urlmap
      where $db->urlmap.id  = $categories->thistable.idurl");
      $items =  $res->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $Items = &$categories->items;
    }
    $result = array();
    foreach ( $Items as $item) {
      $result[] = array(
      'categoryId' => $item['id'],
      'parentId' => $item['parent'],
      'description' => $categories->contents->getdescription($item['id']),
      'categoryName' => $item['title'],
      'title' => $item['title'],
      'htmlUrl' => $options->url . $item['url'],
      'rssUrl' =>  $options->url . $item['url']
      );
    }
    
    return $result;
  }
  
  //returns string
  public function newPost($blogid, $username, $password, $struct, $publish) {
    if(!empty($struct["post_type"]) && ($struct["post_type"] == "page")) {
      return 'menu_' .  $this->wp_newPage($blogid, $username, $password, $struct, $publish);
    }
    
    $this->auth($username, $password, 'editor');
    $posts = tposts::instance();
    $post = tpost::instance(0);
    
    switch ($publish) {
      case 1:
      case true:
      case 'publish':
      $post->status = 'published';
      break;
      
      default:
      $post->status =  'draft';
    }
    
    $this->MWSetPost($struct, $post);
    $id = $posts->add($post);
    return (string) $id;
  }
  
  // returns true
  public function editPost($postid, $username, $password, $struct, $publish) {
    if(!empty($struct["post_type"]) && ($struct["post_type"] == "page")) {
      return  $this->wp_editPage(0, $postid, $username, $password, $struct, $publish);
    }
    
    $this->auth($username, $password, 'editor');
    $postid = (int)$postid;
    $posts = tposts::instance();
    if (!$posts->itemexists($postid))  return $this->xerror(404, "Invalid post id.");
    
    $post = tpost::instance($postid);
    switch ($publish) {
      case 1:
      case true:
      case 'publish':
      $post->status = 'published';
      break;
      
      default:
      $post->status =  'draft';
    }
    
    $this->MWSetPost($struct, $post);
    $posts->edit($post);
    return true;
  }
  
  // returns struct
  public function getPost($id, $username, $password) {
    $this->auth($username, $password, 'editor');
    $id=(int) $id;
    $posts = tposts::instance();
    if (!$posts->itemexists($id))  return $this->xerror(404, "Invalid post id.");
    
    $post = tpost::instance($id);
    return $this->GetStruct($post);;
  }
  
  private function GetStruct(tpost $post) {
    global $options;
    return array(
    'dateCreated' => new IXR_Date($post->posted),
    'userid' => (string) $post->author,
    'postid' =>  (string) $post->id,
    'description' => $post->rawcontent,
    'title' => $post->title,
    'link' => $post->link,
    'permaLink' => $post->link,
    'categories' => $post->catnames,
    'mt_excerpt' => $post->excerpt,
    'mt_text_more' => '',
    'mt_allow_comments' => $post->commentsenabled ? 1 : 0,
    'mt_allow_pings' => $post->pingenabled ? 1 : 0,
    'mt_keywords' => $post->tagnames,
    'wp_slug' => $post->url,
    'wp_password' => $post->password,
    'wp_author_id' => $post->author,
    'wp_author_display_name'	=> 'admin',
    'date_created_gmt' => new IXR_Date($post->posted- $options->gmt),
    'publish' => $post->status == 'published' ? 1 : 0
    );
  }
  
  // returns array of structs
  public function getRecentPosts($blogid, $username, $password, $numberOfPosts) {
    $this->auth($username, $password, 'editor');
    $count = (int) $numberOfPosts;
    $posts = tposts::instance();
    $list = $posts->getrecent($count);
    $posts->loaditems($list);
    $result = array();
    foreach ($list as $id) {
      $post = tpost::instance($id);
      $result[] = $this->GetStruct($post);
    }
    
    return $result;
  }
  
  // returns struct
  public function newMediaObject($blogid, $username, $password, $struct) {
    global $options;
    $this->auth($username, $password, 'editor');
    
    //The struct must contain at least three elements, name, type and bits.
    $filename = $struct['name'] ;
    $mimetype =$struct['type'];
    $overwrite = isset($struct["overwrite"]) && $struct["overwrite"]  ? true : false;
    
    if (empty($filename)) return $this->xerror(500, "Empty filename");
    
    
    $parser = tmediaparser::instance();
    $id = $parser->upload($filename, $struct['bits'], '', $overwrite );
    if (!$id)  return $this->xerror(500, "Could not write file $name");
    $files = tfiles::instance();
    $item = $files->getitem($id);
    
    return array(
    'file' => $item['filename'],
    'url' => $files->geturl($id),
    'type' => $item['mime']
    );
  }
  
}//class

?>