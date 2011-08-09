<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Accessory_acc
{
	public $name = '';
	public $id = 'accessory_acc';
	public $version = '1.0.0';
	public $description = '';
	public $sections = array();

	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function set_sections()
	{
	}
}

/* End of file acc.accessory.php */
/* Location: ./system/expressionengine/third_party/accessory/acc.accessory.php */