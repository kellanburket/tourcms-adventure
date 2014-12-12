<?php

//TourCMS Config Menu

$options = array();

$checkout_options = get_posts(array('post_type'=>'tourcms_checkout'));

$test_mode = get_option('authorize_net_in_test_mode') ? 'checked' : '';			

$debug_mode = get_option('tourcms_debug_mode') ? 'checked' : '';


foreach($checkout_options as $co_page) {
	//$co_options_id[] = $co_page->ID;
	//$co_options_title[] = $co_page->post_title;
	$co_options[$co_page->ID] = $co_page->post_title;
}

$menu_fields = array(
    'menu_slug'	 	=>	'tourcms-config',
    'menu_title' 	=> 	'TourCMS Config',
    'page_title' 	=>	'TourCMS Configuration',
    'capabilities'	=>	'administrator',
    'submenu'=> array(
        'parent_slug'	=> 	'options-general.php',
        'page_title'	=> 	'Configure TourCMS Settings', 
        'menu_title' 	=> 	'TourCMS Settings', 
        'capabilities'	=>	'administrator',	
        'menu_slug' 	=> 	'tourcms_settings_menu'
    ),
    'priority' 		=> 	8
);

$form_data = array(
	array(
        'title'			=>	'Checkout Configuration',
        'id'			=>	'tourcms-options',
        'callback'		=>	'submit_file',
        'form_title'	=>	'Import File',
        'settings_group' => 'tourcms_config_settings',
		'inputs'		=>	array(
            array('tag' => 'h3', 'text' => 'Authorize.Net Configuration'),
            array('tag'		=>	'input',
                'label'		=>	'API KEY',
				'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_api_login',
                    'value'		=>	get_option('authorize_net_api_login')
                )
            ),
            array('tag'		=>	'input',
				'label'		=>	'Transaction Key',
                'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_transaction_key',
                    'value'		=>	get_option('authorize_net_transaction_key')
                )
            ),
            array('tag'		=>	'input',
                'label'		=>	'MD5 Setting',
				'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_md5_setting',
                    'value'		=>	get_option('authorize_net_md5_setting'),
                )
            ),
            array('tag'		=>	'input',
                 'label'		=>	'Authorize.Net URL',
                 'validation_callback' => 'esc_url',
				'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_url',
                    'value'		=>	get_option('authorize_net_url'),
                )
            ),
            array('tag'		=>	'input',
                 'label'		=>	'Test API Key',
				'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_api_login_test',
                    'value'		=>	get_option('authorize_net_api_login_test')
                )
            ),
            array('tag'		=>	'input',
                'label'		=>	'Test Transaction Key',
				'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_transaction_key_test',
                    'value'		=>	get_option('authorize_net_transaction_key_test')
                )
            ),
            array('tag'		=>	'input',
                'label'		=>	'Test MD5 Setting',
                'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_md5_setting_test',
                    'value'		=>	get_option('authorize_net_md5_setting_test'),
                )
            ),
            array('tag'		=>	'input',
                'label'		=>	'Test Authorize.Net URL',
                 'validation_callback' => 'esc_url',
				'attributes'	=>	array(
                    'type'		=>	'text',
                    'name'		=>	'authorize_net_url_test',
                    'value'		=>	get_option('authorize_net_url_test'),
                )
            ),
            array('tag'		=>	'input',
                'label'		=>	'Use Authorize.Net in Test Mode?',
				'attributes'	=>	array(
                    'type'		=>	'checkbox',
                    'name'		=>	'authorize_net_in_test_mode',
                    
                    'checked'	=>	$test_mode,
                )
            ),			
            array('tag' => 'h3', 'text' => 'Checkout Page Configuration'),
            array('tag'	=>	'select',
				'validation_callback' => 'intval',
                'label'		=>	'Select a Checkout Page',
                'attributes'	=>	array(
                    'name'		=>	'tourcms_checkout_page',
                    'value'		=>	get_option('tourcms_checkout_page')		
                ),
                'options'	=> $co_options
            ),				
            array('tag' => 'h3', 'text' => 'Debug Configuration'),
            array(
                'tag'	=>	'input',
                'label'		=>	'Run TourCMS in Debug Mode?',
                'attributes'	=>	array(
                    'type'		=>	'checkbox',
                    'name'		=>	'tourcms_debug_mode',
                    'checked'	=> 	$debug_mode,
                )
            ),
            array('tag' => 'h3', 'text' => 'Promo Code Configuration'),


            array('tag' => 'h3', 'text' => 'Error Messages'),
            array('tag' => 'h4', 'text' => 'Interior Pages'),
            array('tag'	=>	'textarea',
                 'label'		=>	'Invalid Date',
				'attributes'	=>	array(
                    'name'		=>	'invalid_date',
                ), 'text'	=>	get_option('invalid_date')
            ),
            array('tag'	=>	'textarea',
                'label'		=>	'No Availabilities',
				'attributes'	=>	array(
                    'name'		=>	'no_availabilities',
                ), 'text'	=>	get_option('no_availabilities')
            ),
            array('tag'	=>	'textarea',
                'label'		=>	'Invalid Tour',
                'attributes'	=>	array(
                    'name'		=>	'invalid_tour',
                ), 'text'	=>	get_option('invalid_tour')
            ),
            array(
                'tag'	=>	'textarea',
                'label'		=>	'TourCMS Technical Problem',
				'attributes'	=>	array(
                    'name'		=>	'tourcms_technical_problem',
                ), 'text'	=>	get_option('tourcms_technical_problem')
            ),
            array(
                'tag'	=>	'textarea',
                'label'		=>	'Invalid Promo Code',
				'attributes'	=>	array(
                   'name'		=>	'invalid_promo',
                ), 'text'	=>	get_option('invalid_promo')
            ),
            array(
                'tag'	=>	'textarea',
                'label'		=>	'Invalid Title',
				'attributes'	=>	array(
                   'name'		=>	'invalid_title',
                ), 'text'	=>	get_option('invalid_title')
            ),
            array(
                'tag'	=>	'textarea',
                'label'		=>	'Invalid E-Mail',
                'attributes'	=>	array(
                    'name'		=>	'invalid_email',
                ), 'text'	=>	get_option('invalid_email')
            ),
			array(
                'tag'	=>	'textarea',
                'label'		=>	'Availability Access Error',
                'attributes'	=>	array(
                    'name'		=>	'tourcms_access_error',
                ), 'text'	=>	get_option('tourcms_access_error')
            )
        ), 'submit_text'=>'Submit'
    )
);
?>