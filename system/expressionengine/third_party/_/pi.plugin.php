<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name' => '',
	'pi_version' => '1.0.0',
	'pi_author' => '',
	'pi_author_url' => 'http://github.com/rsanchez',
	'pi_description' => '',
	'pi_usage' => '',
);

class Plugin
{
	public $return_data = '';

	public function Plugin()
	{
		$this->EE =& get_instance();
		
		return $this->return_data;
	}
}
/* End of file pi.plugin.php */ 
/* Location: ./system/expressionengine/third_party/plugin/pi.plugin.php */ 