<?php
namespace MelonTech\RestQuery;

class RestQuery {
  const METHOD_POST = CURLOPT_POST;
  const METHOD_PUT = CURLOPT_PUT;
  const METHOD_GET = CURLOPT_HTTPGET;

  protected $method = NULL;
  protected $post_data = NULL;
  protected $url = NULL;

  protected $curl_handle = NULL;

  protected $result = NULL;

  public function __construct($url = NULL) {
    if(!function_exists('curl_init')) {
      throw new RestQueryException('CURL module not available!');
    }

    if(!empty($url))
      $this->url = $url;

    $this->curl_handle = curl_init($url);
    $this->setCurlOpt(CURLOPT_RETURNTRANSFER, TRUE);
  }

  public function setAuth($user, $pass, $auth = 'basic') {
    $this->setCurlOpt(CURLOPT_HTTPAUTH, constant('CURLAUTH_'.strtoupper($auth)));
    $this->setCurlOpt(CURLOPT_USERPWD, "{$user}:{$pass}");
  }

  public function setProxy($host, $port, $user = NULL, $pass = NULL) {
    if(!is_numeric($port))
      throw new InvalidArgumentException('Port must be numeric!');

    $this->setCurlOpt(CURLOPT_PROXYTYPE, 'HTTP')
    ->setCurlOpt(CURLOPT_PROXY, $host)
    ->setCurlOpt(CURLOPT_PROXYPORT, $port);

    if(!empty($user) && !empty($pass))
      $this->setCurlOpt(CURLOPT_PROXYUSERPWD, "{$user}:{$pass}");
  }

  public static function validate_url($url) {
    return (filter_var($url, FILTER_VALIDATE_URL)!==FALSE);
  }

  public function setPostData($data) {
    if($this->method != self::METHOD_POST)
      throw new RestQueryException('Method is not POST!');
    $this->postdata = $post_data;
    return $this;
  }

  public function setUrl($url) {
    if(!RestQuery::validate_url($url))
      throw new RestQueryException('Invalid URL!');
    $this->url = $url;
    $this->setCurlOpt(CURLOPT_URL, $url);
    return $this;
  }

  public function setCurlOpt($option, $value) {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    $result = curl_setopt($this->curl_handle, $option, $value);
    if($result === FALSE) {
      $error = curl_error($this->curl_handle);
      throw new RestQueryException("Error setting CURL option '{$opt}'=>'{$value}': '{$error}'");
    }
    return $this;
  }

  public function setMethod($method) {
    switch($method) {
      case self::METHOD_GET:
      case self::METHOD_POST:
      case self::METHOD_PUT:
        $this->method = $method;
        break;
      default:
        throw new RestQueryException('Invalid HTTP Method');
    }
    return $this;
  }

  public function setCurlOpts(array $curlopts) {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    foreach($curlopts as $opt => $value) {
      $this->setCurlOpt($opt, $value);
    }
    return $this;
  }

  public function execute() {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    if(empty($this->url))
      throw new RestQueryException('No URL set!');
    if(empty($this->method))
      throw new RestQueryException('HTTP Method isn\'t set!');
    
    $this->result = curl_exec($this->curl_handle);
    return $this->result;
  }

  public function getResult() {
    return $this->result;
  }
}

class RestQueryException extends \Exception {}