<?php

/*
Plugin Name: Sitewide activity
Author: WP Results
*/

require('classes/IvnSitewideActivityWidget.class.php');
require('vendor/wax/plugin.php');
W::load('haml');
W::load('request');

$ivn_sitewide_activity_widget = new IvnSitewideActivityWidget();
