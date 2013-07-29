<?php

require('WprBase.class.php');

class IvnSitewideActivityWidget extends WprBase
{
  function __construct()
  {
    parent::__construct();

    $this->options = new WhatStoryPublisherSettings($this, self::OPTION_NAME,__FILE__);
    
    $this->ajax_actions = array(
      'activities',
    );
  }
  
  function get_activities($args = array())
  {
    $defaults = array(
      'type'=>'sitewide',
      'max'=>100,
      'page'=>1,
      'per_page'=>20,
    );
    $args = array_merge($defaults, $args);
    
    $activities = array();
    
    $this->args = $args;
    add_filter('bp_activity_get_user_join_filter', array($this, 'bp_activity_get_user_join_filter'), 10, 6);
    $res = bp_has_activities($args);
    remove_filter(array($this, 'bp_activity_get_user_join_filter'));
    
    if ($res)
    {
      while ( bp_activities() )
      {
        bp_the_activity();
        $rec = array(
          'class'=>bp_get_activity_css_class(),
          'id'=>bp_get_activity_id(),
          'user_link'=>bp_get_activity_user_link(),
          'avatar'=>bp_get_activity_avatar( 'type=thumb&width=50&height=50' ),
          'action'=>bp_get_activity_action(),
          'content'=>bp_get_activity_content_body(),
        );
        $activities[] = (object)$rec;
      }
    }
    
    if(get_current_user_id())
    {
      for($i=0;$i<rand(1,5);$i++) 
      {
        $id = bp_activity_post_update(array('content'=>"Did an activity at ".time()));
      }
    }
    
    return $activities;
  }
  
  function bp_activity_get_user_join_filter($sql, $select_sql, $from_sql, $where_sql, $sort, $pag_sql )
  {
    if($this->args['since'])
    {
      $where_sql .= " and a.ID > {$this->args['since']}";
    }
    if($this->args['after'])
    {
      $where_sql .= " and a.ID < {$this->args['after']}";
    }
    $sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort} {$pag_sql}";
    return $sql;
  }
  
  function ajax_activities()
  {
    $args = array(
      'since'=>(int)W::p('since'),
      'after'=>(int)W::p('after'),
      'user_id'=>(int)W::p('user_id'),
      
    );
    
    $activities = $this->get_activities($args);
    $json = json_encode($activities);
    header('Content-Type: application/json');
    die($json);    
  }


  function render($args = array())
  {
    echo self::build($args);
  }
  
  function build($args)
  {
    $defaults = array(
      'height'=>600,
      'list_size'=>100,
    );
    $args = array_merge($defaults, $args);
    
    wp_enqueue_style('swa-style', plugins_url('assets/css/style.css', dirname(__FILE__)."/../.."));
    wp_enqueue_script( 'doT',  $this->plugin_url('/vendor/doT/doT.js'), array('jquery'), null,true );
    wp_enqueue_script( 'retry-ajax',  $this->plugin_url('/vendor/jQuery.retryAjax/lib/jquery.retryAjax.js'), array('jquery'), null,true );
    wp_enqueue_script( 'waypoints',  $this->plugin_url('/vendor/jquery-waypoints/waypoints.js'), array('jquery'), null,true );
    wp_enqueue_script( 'swa-activities',  $this->plugin_url('/assets/js/activities.js'), array('jquery', 'doT', 'retry-ajax'), null,true );
    wp_enqueue_script( 'jquery-effects-highlight', null, null, true);
    wp_localize_script('swa-activities', 'SwaActivitiesInfo', array(
      'API'=>array('url'=>site_url('/api')),
      'REFRESH_RATE'=>10000,
      'args'=>$args,
    ));
    $html = W::haml_eval_file(dirname(__FILE__).'/../templates/widget_container.haml', array('args'=>$args));
    return $html;
  }
}

