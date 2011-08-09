<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Module_upd
{
	public $version = '1.0.0';
	
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
	
	/**
	 * install
	 * 
	 * @access	public
	 * @return	void
	 */
	public function install()
	{
		$this->EE->db->insert(
			'modules',
			array(
				'module_name' => 'Module',
				'module_version' => $this->version, 
				'has_cp_backend' => 'y',
				'has_publish_fields' => 'n'
			)
		);
		
		$this->EE->db->insert(
			'actions',
			array(
				'class' => 'Module',
				'method' => 'method_name',
			)
		);
		
		return TRUE;
	}
	
	/**
	 * uninstall
	 * 
	 * @access	public
	 * @return	void
	 */
	public function uninstall()
	{
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Module'));
		
		if ($query->row('module_id'))
		{
			$this->EE->db->where('module_id', $query->row('module_id'))->delete('module_member_groups');
		}

		$this->EE->db->where('module_name', 'Module')->delete('modules');

		$this->EE->db->where('class', 'Module')->delete('actions');

		return TRUE;
	}
	
	/**
	 * update
	 * 
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		return TRUE;
	}
}

/* End of file upd.module.php */
/* Location: ./system/expressionengine/third_party/module/upd.module.php */