<?php


class WprBase
{
  var $default_settings = array();
  
  function settings($settings = null)
  {
    $defaults = $this->default_settings;
    if($settings)
    {
      if(is_array($settings))
      {
        $settings = array_merge($defaults, $settings);
        update_option(static::OPTION_NAME, $settings);
      }
      if(is_string($settings))
      {
        $s = $this->settings();
        return $s[$settings];
      }
    } else {
      $settings = get_option(static::OPTION_NAME);
      if(!$settings) $settings = $defaults;
      $settings = array_merge($defaults, $settings);
    }
    return $settings;
  }
  
  function __construct()
  {
    add_action( 'add_meta_boxes', array($this,'add_metaboxes') );
    add_action( 'save_post', array($this, 'save_post'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    add_filter('the_content',array ($this, 'the_content)'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
  }
  
  function add_metaboxes() {}
  function save_post($post_id) {}
  function admin_enqueue_scripts() {}
  function admin_menu() {}
  function the_content($content) { return $content; }
  
  function do_post_request($url, $data, $optional_headers = null)
  {
    $fields_string = http_build_query($data);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $res = array($data, $error, $info);
    return $res;
  }
  
  function truncate($string, $length, $stopanywhere=false) {
    //truncates a string to a certain char length, stopping on a word if not specified otherwise.
    if (strlen($string) > $length) {
      //limit hit!
      $string = substr($string,0,($length -3));
      if ($stopanywhere) {
        //stop anywhere
        $string .= '...';
      } else{
        //stop on a word.
        $string = substr($string,0,strrpos($string,' ')).'...';
      }
    }
    return $string;
  }
  
  
  const HTTP_URL_REPLACE = 1;              // Replace every part of the first URL when there's one of the second URL
  const HTTP_URL_JOIN_PATH = 2;            // Join relative paths
  const HTTP_URL_JOIN_QUERY = 4;           // Join query strings
  const HTTP_URL_JOIN = 6;
  const HTTP_URL_STRIP_USER = 8;           // Strip any user authentication information
  const HTTP_URL_STRIP_PASS = 16;          // Strip any password authentication information
  const HTTP_URL_STRIP_AUTH = 32;          // Strip any authentication information
  const HTTP_URL_STRIP_PORT = 64;          // Strip explicit port numbers
  const HTTP_URL_STRIP_PATH = 128;         // Strip complete path
  const HTTP_URL_STRIP_QUERY = 256;        // Strip query string
  const HTTP_URL_STRIP_FRAGMENT = 512;     // Strip any fragments (#identifier)
  const HTTP_URL_STRIP_ALL = 1024;         // Strip anything but scheme and host

  // Build an URL
  // The parts of the second URL will be merged into the first according to the flags argument. 
  // 
  // @param   mixed           (Part(s) of) an URL in form of a string or associative array like parse_url() returns
  // @param   mixed           Same as the first argument
  // @param   int             A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
  // @param   array           If set, it will be filled with the parts of the composed url like parse_url() would return 
  function http_build_url($url, $parts=array(), $flags=HTTP_URL_REPLACE, &$new_url=false)
  {
      $keys = array('user','pass','port','path','query','fragment');

      // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
      if ($flags & self::HTTP_URL_STRIP_ALL)
      {
          $flags |= self::HTTP_URL_STRIP_USER;
          $flags |= self::HTTP_URL_STRIP_PASS;
          $flags |= self::HTTP_URL_STRIP_PORT;
          $flags |= self::HTTP_URL_STRIP_PATH;
          $flags |= self::HTTP_URL_STRIP_QUERY;
          $flags |= self::HTTP_URL_STRIP_FRAGMENT;
      }
      // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
      else if ($flags & self::HTTP_URL_STRIP_AUTH)
      {
          $flags |= self::HTTP_URL_STRIP_USER;
          $flags |= self::HTTP_URL_STRIP_PASS;
      }

      // Parse the original URL
      $parse_url = $url;
      if(!is_array($parse_url))
      {
        $parse_url = parse_url($url);
      }
      $parse_url['query'] = $this->parse_str($parse_url['query']);
      
      if(is_string($parts['query'])) $parts['query'] = $this->parse_str($parts['query']);

      // Scheme and Host are always replaced
      if (isset($parts['scheme']))
          $parse_url['scheme'] = $parts['scheme'];
      if (isset($parts['host']))
          $parse_url['host'] = $parts['host'];

      // (If applicable) Replace the original URL with it's new parts
      if ($flags & self::HTTP_URL_REPLACE)
      {
          foreach ($keys as $key)
          {
              if (isset($parts[$key]))
                  $parse_url[$key] = $parts[$key];
          }
      }
      else
      {
          // Join the original URL path with the new path
          if (isset($parts['path']) && ($flags & self::HTTP_URL_JOIN_PATH))
          {
              if (isset($parse_url['path']))
                  $parse_url['path'] = str_replace('//', '/', $parse_url['path']."/{$parts['path']}");
              else
                  $parse_url['path'] = $parts['path'];
          }

          // Join the original query string with the new query string
          if (isset($parts['query']) && ($flags & self::HTTP_URL_JOIN_QUERY))
          {
            $parse_url['query'] = array_merge($parse_url['query'], $parts['query']);
          }
      }

      // Strips all the applicable sections of the URL
      // Note: Scheme and Host are never stripped
      foreach ($keys as $key)
      {
          if ($flags & (int)constant(get_called_class().'::HTTP_URL_STRIP_' . strtoupper($key)))
              unset($parse_url[$key]);
      }

      return 
           ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
          .((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
          .((isset($parse_url['host'])) ? $parse_url['host'] : '')
          .((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
          .((isset($parse_url['path'])) ? $parse_url['path'] : '')
          .((isset($parse_url['query']) && count($parse_url['query'])>0) ? '?' . http_build_query($parse_url['query']) : '')
          .((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
      ;
  }
  
  function parse_str($str) {
    if(!$str) return array();
    # result array
    $arr = array();
  
    # split on outer delimiter
    $pairs = explode('&', $str);
  
    # loop through each pair
    foreach ($pairs as $i) {
      # split into name and value
      list($name,$value) = explode('=', $i, 2);
      
      # if name already exists
      if( isset($arr[$name]) ) {
        # stick multiple values into an array
        if( is_array($arr[$name]) ) {
          $arr[$name][] = $value;
        }
        else {
          $arr[$name] = array($arr[$name], $value);
        }
      }
      # otherwise, simply stick it in a scalar
      else {
        $arr[$name] = $value;
      }
    }
  
    # return result array
    return $arr;
  }

  function plugin_url($path)
  {
    return plugins_url($path, dirname(__FILE__).'/../..');
  }
}