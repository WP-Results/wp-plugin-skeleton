<?php

if ( !class_exists( 'Admin_Page_Framework' ) ) 
  require('Admin_Page_Framework.class.php');

class WhatStoryPublisherSettings extends Admin_Page_Framework {

  const API_KEY_LENGTH=20;
  const API_PASSWORD_LENGTH=32;
  
  function __construct($parent, $option_name, $file)
  {
    parent::__construct($option_name, $file);
    $this->parent = $parent;
  }

	function SetUp() {
					
		$this->SetRootMenu( 'Settings' );	
		
		$this->SetCapability( 'manage_options' );		// *Optional: allow subscribers to access the pages
		$this->AddSubMenu(
			'WhatStory Publisher',
			'whatstory_publisher'
		);	

		$this->AddFormSections( 
			array( 	
				array(  
					'pageslug' => 'whatstory_publisher',
					'id' => 'settings', 
					'title' => 'Publisher Settings',
					'description' => 'These are your settings for the WhatStory Publisher. An API key is required.',
					'fields' => array(
						array(  
							'id' => 'api_key', 
							'title' => 'API Key',
							'description' => 'Your API key',
							'type' => 'text',
							'default' => '',
							'size' => self::API_KEY_LENGTH 
						),
						array(  
							'id' => 'api_password',
							'title' => 'API Password',
							'tip' => 'Your API secret password.',
							'type' => 'password',
							'size' => self::API_PASSWORD_LENGTH
						),
						array(  
							'id' => 'position',
							'title' => 'Story Position',
							'description' => 'Where do you want the WhatStory stories to display?',
							'type' => 'select',
							'default' => 'bottom',
							'label' => array( 'top'=>"Above my Post Body", 'bottom'=>"Below my Post Body"),
						),		
						array(  
							'id' => 'story_count',
							'title' => '# Stories',
							'description' => 'How many WhatStory network stories do you want to display?',
							'type' => 'select',
							'default' => 8,
							'label' => array( 1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12),
						),		
					)
				),
			)
		);
	}

	function do_whatstory_publisher() {	
	  echo("<h3>Preview</h3>");
	  echo($this->parent->get_widget($this->parent->settings('story_count')));

		submit_button();	// the save button
	}

	function validation_whatstory_publisher( $arrInput ) 
	{	
		$values = &$arrInput['whatstory_publisher']['settings'];
		
		extract($values);
		
		if(strlen(trim($api_key)) != self::API_KEY_LENGTH)
		{
			$arrErrors['settings']['api_key'] = "API Key must be ".self::API_KEY_LENGTH." characters";
			unset($values['api_key']);
		}
		if(strlen(trim($api_password)) != self::API_PASSWORD_LENGTH)
		{
			$arrErrors['settings']['api_password'] = "API Password must be ".self::API_PASSWORD_LENGTH." characters";
			unset($values['api_password']);
		}
		
		if($arrErrors)
		{
  		
  
  		$this->SetFieldErrors( $arrErrors );
  		$this->SetSettingsNotice( 
  			__( 'There are errors in your selections.' )
  		);	
  		
  		// Returning an empty array will not change options.
  		return $arrInput;
    }
  		
		$this->SetSettingsNotice( 
			__( 'The options were updated.' ), 	// the message to display
			'updated' 	// the type. Use 'error' for a red box.
		);
		return $arrInput;

	}			
	

	
}