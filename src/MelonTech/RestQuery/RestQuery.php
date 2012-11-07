<?php
namespace MelonTech\RestQuery;

class RestQuery {
  const METHOD_POST = CURLOPT_POST;
  const METHOD_PUT = CURLOPT_PUT;
  const METHOD_GET = CURLOPT_HTTPGET;
  const METHOD_DELETE = 128;

  protected $method = NULL;
  protected $post_data = NULL;
  protected $url = NULL;

  protected $curl_handle = NULL;

  protected $result = NULL;

  /**
   * Constructor. Optional $url argument, can be set later.
   * @param string $url
   * @throws RestQueryException
   */
  public function __construct($url = NULL) {
    if(!function_exists('curl_init')) {
      throw new RestQueryException('CURL module not available!');
    }

    if(!empty($url))
      $this->url = $url;

    if(ini_get('open_basedir') == '' && strtolower(ini_get('safe_mode')) == 'off') {
      $this->setCurlOpt(CURLOPT_FOLLOWLOCATION, TRUE);
    }

    $this->curl_handle = curl_init($url);
    $this->setCurlOpt(CURLOPT_RETURNTRANSFER, TRUE);
  }

  /**
   * Set appropriate HTTPAuth options using RestQuery::setCurlOpt()
   * @param string $user
   * @param string $pass
   * @param string $auth
   */
  public function setAuth($user, $pass, $auth = 'basic') {
    $this->setCurlOpt(CURLOPT_HTTPAUTH, constant('CURLAUTH_'.strtoupper($auth)));
    $this->setCurlOpt(CURLOPT_USERPWD, "{$user}:{$pass}");
  }

  /**
   * Set appropriate proxy options using RestQuery::setCurlOpt()
   * @param string $host
   * @param string|int $port
   * @param string $user Optional. HTTP Proxy user name
   * @param string $pass Optional. HTTP
   * @throws \InvalidArgumentException
   */
  public function setProxy($host, $port, $user = NULL, $pass = NULL) {
    if(!is_numeric($port))
      throw new \InvalidArgumentException('Port must be numeric!');

    $this->setCurlOpt(CURLOPT_PROXYTYPE, 'HTTP')
    ->setCurlOpt(CURLOPT_PROXY, $host)
    ->setCurlOpt(CURLOPT_PROXYPORT, $port);

    if(!empty($user) && !empty($pass))
      $this->setCurlOpt(CURLOPT_PROXYUSERPWD, "{$user}:{$pass}");
  }

  /**
   * Static function to validate the input URL. This can be overridden by
   * subclasses to include custom validation. For instance, to validate
   * certain host patterns for API-specific implementations. Returns boolean
   * based on whether URL validates. Default behavior is to use filter_var()
   * with FILTER_VALIDATE_URL set.
   * @param string $url
   * @return boolean
   */
  public static function validate_url($url) {
    return (filter_var($url, FILTER_VALIDATE_URL)!==FALSE);
  }

  /**
   * Sets the data for a POST request.
   * @param unknown $data
   * @throws RestQueryException
   * @return \MelonTech\RestQuery\RestQuery
   */
  public function setPostData($data) {
    if($this->method != self::METHOD_POST)
      throw new RestQueryException('Method is not POST!');
    $this->post_data = $data;
    $this->setCurlOpt(CURLOPT_POSTFIELDS, $data);
    return $this;
  }

  /**
   * Sets the URL for the request.
   * @param string $url
   * @throws RestQueryException
   * @return \MelonTech\RestQuery\RestQuery
   */
  public function setUrl($url) {
    if(!RestQuery::validate_url($url))
      throw new RestQueryException('Invalid URL!');
    $this->url = $url;
    $this->setCurlOpt(CURLOPT_URL, $url);
    return $this;
  }

  /**
   * curl_setopt() wrapper.
   * @param int $option CURLOPT_* constant
   * @param unknown $value Value as would be used in curl_setopt()
   * @throws RestQueryException
   * @return \MelonTech\RestQuery\RestQuery
   */
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

  /**
   * Returns the currently-set HTTP method.
   * @return Ambigous <NULL, unknown>
   */
  public function getMethod() {
    return $this->method;
  }
  /**
   * Sets the HTTP method to use. Subclasses can include their own constants, but
   * should obviously override this method.
   * @param int $method Any of the RestQuery::METHOD_* constants.
   * @throws RestQueryException
   * @return \MelonTech\RestQuery\RestQuery
   */
  public function setMethod($method) {
    $this->method = $method;
    switch($method) {
      case self::METHOD_DELETE:
        $this->setCurlOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
      case self::METHOD_POST:
        $this->setCurlOpt(CURLOPT_POST, TRUE)
          ->setCurlOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        break;
      case self::METHOD_PUT:
        $this->setCurlOpt(CURLOPT_PUT, TRUE)
          ->setCurlOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->method = $method;
        break;
      case self::METHOD_GET:
        break;
      default:
        $this->method = NULL;
        throw new RestQueryException('Invalid HTTP Method');
    }
    return $this;
  }

  /**
   * Set multiple CURL options.
   * @param array $curlopts Array of CURLOPTs and their values. E.g., [CURLOPT_SSLVERSION, 3]
   * @throws RestQueryException
   * @return \MelonTech\RestQuery\RestQuery
   */
  public function setCurlOpts(array $curlopts) {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    foreach($curlopts as $opt => $value) {
      $this->setCurlOpt($opt, $value);
    }
    return $this;
  }

  /**
   * Execute the query and return the raw string result.
   * @throws RestQueryException
   * @return mixed
   */
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