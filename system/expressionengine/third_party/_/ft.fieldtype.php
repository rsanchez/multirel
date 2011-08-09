<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fieldtype_ft extends EE_Fieldtype
{
	public $info = array(
		'name' => '',
		'version' => '1.0.0'
	);
	
	public function __construct()
	{
		if (method_exists('EE_Fieldtype', 'EE_Fieldtype'))
		{
			parent::EE_Fieldtype();
		}
		else
		{
			parent::__construct();
		}
	}
	
	/**
	 * Display Field on Publish
	 *
	 * @access	public
	 * @param	$data
	 * @return	field html
	 *
	 */
	public function display_field($data)
	{
		return '';
	}
	
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		return '';
	}
	
	/**
	 * Display Global Settings
	 *
	 * @access	public
	 * @return	form contents
	 *
	 */
	public function display_global_settings()
	{
		return '';
	}
	
	/**
	 * Save Global Settings
	 *
	 * @access	public
	 * @return	global settings
	 *
	 */
	public function save_global_settings()
	{
		return array_merge($this->settings, $_POST);
	}
	
	/**
	 * Display Settings Screen
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	public function display_settings($data)
	{
		$this->EE->table->add_row(
			lang('latitude', 'latitude'),
			form_input('latitude', $latitude)
		);
	}
	
	/**
	 * Save Settings
	 *
	 * @access	public
	 * @return	field settings
	 *
	 */
	public function save_settings($data)
	{
		return array(
			'latitude'	=> $this->EE->input->post('latitude'),
			'longitude'	=> $this->EE->input->post('longitude'),
			'zoom'		=> $this->EE->input->post('zoom')
		);
	}
	
	/**
	 * Install Fieldtype
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	public function install()
	{
		return array(
			'latitude'	=> '44.06193297865348',
			'longitude'	=> '-121.27584457397461',
			'zoom'		=> 13
		);
	}
}

/* End of file ft.google_maps.php */
/* Location: ./system/expressionengine/third_party/google_maps/ft.google_maps.php */