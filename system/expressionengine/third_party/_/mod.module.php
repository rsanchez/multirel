<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Module
{
	public $return_data = '';

	/**
	 * constructor
	 * 
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
}

/* End of file mod.module.php */
/* Location: ./system/expressionengine/third_party/module/mod.module.php */