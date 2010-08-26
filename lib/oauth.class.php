<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class toauth extends tevents {
  public $urllist;
  /*
  public $key;
  public $secret;
  public $token;
  public $tokensecret;
  */
  public $timeout;
  
  protected function create() {
    parent::create();
    $this->basename = 'oauth';
    $this->data['key'] = $_SERVER['HTTP_HOST'];
    $this->data['secret'] = '';
    $this->data['token'] = '';
    $this->data['tokensecret'] = '';
    $this->timeout = 2;
    $this->addmap('urllist',  array(
    'request' => 'https://www.google.com/accounts/OAuthGetRequestToken',
    'authorize' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
    'access' => 'https://www.google.com/accounts/OAuthGetAccessToken',
    'callback' => ''
    ));
  }
  
  //to override in child classes
  public function getkeys() {
    return array();
  }
  
  public function getextraheaders() {
    return array();
  }
  
  
  private function getsign($keys, $url, $method='GET'){
    $parsed = parse_url($url);
    if (isset($parsed['query'])){
      parse_str($parsed['query'], $query);
      $keys = array_merge($keys, $query);
    }
    
    if (!isset($keys['oauth_key'])) $keys['oauth_key'] = $this->key;
    $keys['oauth_version']		= '1.0';
    $keys['oauth_nonce']		= md5('_oauth_rand_' . microtime() . mt_rand());
    $keys['oauth_timestamp']	= time();
    $keys['oauth_consumer_key']	= $keys['oauth_key'];
    $keys['oauth_signature_method']	= 'HMAC-SHA1';
    $keys['oauth_signature']	= $this->getsignature($keys, $url, $method);
    return $keys;
  }
  
  public function geturl(array $keys, $url, $method='GET'){
    return $this->normalize_url($url) . "?" . $this->getparams($this->getsign($keys, $url, $method));
  }
  
  public function getdata(array $keys, $url, $params=array(), $method="GET"){
    $url = $this->geturl($keys, $url, $params, $method);
    if ($method == 'POST'){
      list($url, $postdata) = explode('?', $url, 2);
    }else{
      $postdata = null;
    }
    
    return $this->dorequest($url, $method, $postdata);
  }
  
  private function getsignature($keys, $url, $method){
    $sig = array(
    rawurlencode(strtoupper ($method)),
    preg_replace('/%7E/', '~', rawurlencode($this->normalize_url($url))),
    rawurlencode($this->get_signable($keys))
    );
    
    $key = rawurlencode($this->secret) . '&';
    if ($this->tokensecret != '') {
      $key .= rawurlencode($this->tokensecret);
    }
    
    $raw = implode("&", $sig);
    return base64_encode($this->hmac_sha1($raw, $key, TRUE));
  }
  
  private function normalize_url($url){
    $parts = parse_url($url);
    $port = '';
    if (array_key_exists('port', $parts) && $parts['port'] != '80'){
      $port = ':' . $parts['port'];
    }
    return $parts['scheme'] . '://' .  $parts['host'] . $port . $parts['path'];
  }
  
  private function get_signable($params){
    if (isset($params['oauth_signature'])) unset($params['oauth_signature']);
    ksort($params);
    $total = array();
    foreach ($params as $k => $v) {
      $total[] = rawurlencode($k) . "=" . rawurlencode($v);
    }
    return implode("&", $total);
  }
  
  private function getparams($params){
    $result = array();
    foreach ($params as $k => $v) {
      $result[] = rawurlencode($k) . '=' . rawurlencode($v);
    }
    return implode("&", $result);
  }
  
  public function getauthorization($keys, $url) {
    $params = $this->getsign($keys, $url, 'post');
    ksort($params);
    $result = array();
    foreach ($params as $k => $v) {
      $result[] = sprintf('%s="%s"', $k, rawurlencode($v));
    }
    return implode(', ', $result);
  }
  
  private function hmac_sha1($data, $key, $raw=TRUE){
    if (strlen($key) > 64){
      $key =  pack('H40', sha1($key));
    }
    
    if (strlen($key) < 64){
      $key = str_pad($key, 64, chr(0));
    }
    
    $_ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
    $_opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
    
    $hex = sha1($_opad . pack('H40', sha1($_ipad . $data)));
    if (!$raw) return $hex;
    $bin = '';
    while (strlen($hex)){
      $bin .= chr(hexdec(substr($hex, 0, 2)));
      $hex = substr($hex, 2);
    }
    return $bin;
  }
  
  public function gettoken(array $keys){
    if ($bits = $this->getbits($this->geturl($keys, $this->urllist['request']))) {
      $this->token 	= $bits['oauth_token'];
      $this->tokensecret = $bits['oauth_token_secret'];
      if ($this->token && $this->tokensecret) return true;
    }
    return false;
  }
  
  private function getbits($url){
    if ($crap = $this->dorequest($url)) {
      $bits = explode("&", $crap);
      $result = array();
      foreach ($bits as $bit){
        list($k, $v) = explode('=', $bit, 2);
        $result[urldecode($k)] = urldecode($v);
      }
      
      return $result;
    }
    return false;
  }
  
  public function getaccess($keys) {
    if ($bits = $this->getbits($this->geturl($keys, $this->urllist['access']))) {
      $this->token = $bits['oauth_token'];
      $this->tokensecret = $bits['oauth_token_secret'];
      if ($this->token && $this->tokensecret) return true;
    }
    return false;
  }
  
  private function dorequest($url, $method='GET', $postdata=null){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); 	// Get around error 417
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
    
    if ($method == 'GET'){
    }elseif ($method == 'POST'){
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    }else{
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    $response = curl_exec($ch);
    $headers = curl_getinfo($ch);
    curl_close($ch);
    if ($headers['http_code'] != '200') return false;
    return $response;
  }
  
  
  public function getrequesttoken() {
    $keys = $this->getkeys();
    if ($this->gettoken($keys)) {
      $keys['oauth_token'] = $this->token;
      if ($this->getaccess($keys)) return true;
      ini_set('session.use_cookies', false);
      session_cache_limiter(false);
      session_id (md5($this->token));
      session_start();
      $_SESSION['tokens'] = array(
      'token' => $this->token,
      'secret' => $this->tokensecret
      );
      session_write_close();
      return $this->urllist['authorize'] . sprintf('?oauth_token=%s&&oauth_callback=%s',
      rawurlencode($this->token), rawurlencode($this->urllist['callback']));
    }
    return false;
  }
  
  public function getaccesstoken() {
    ini_set('session.use_cookies', false);
    session_cache_limiter(false);
    session_id (md5($_GET['oauth_token']));
    session_start();
    if (!isset($_SESSION['tokens'])) return false;
    $tokens = $_SESSION['tokens'];
    $this->token = $tokens['token'];
    $this->tokensecret = $tokens['secret'];
    $keys = $this->getkeys();
    $keys['oauth_token'] = $this->token;
    if ($this->getaccess($keys)) {
      session_destroy();
      $this->save();
      return true;
    }
    return false;
  }
  
  public function postdata($postdata, $url) {
    $keys = array();
    $keys['oauth_token'] = $this->token;
    $authorization = $this->getauthorization($keys, $url);
    $headers = array(
    'Authorization: OAuth '. $authorization,
    'Content-Length: ' . strlen($postdata )
    );
    $headers = array_merge($headers, $this->getextraheaders());
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata );
    
    $response = curl_exec($ch);
    $headers = curl_getinfo($ch);
    curl_close($ch);
    //var_dump($response , $headers);
    //echo htmlspecialchars($response );
    if ($headers['http_code'] != "200") return false;
    return $response;
  }
  
}//class
?>