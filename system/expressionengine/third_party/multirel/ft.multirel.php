<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Multirel_ft extends EE_Fieldtype
{
	public $info = array(
		'name' => 'Multirel',
		'version' => '1.0.0'
	);
	
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
		static $options;

		$data = $data ? explode('|', $data) : array();

        if ( ! $this->EE->session->cache(__CLASS__, __FUNCTION__))
        {
            $this->EE->load->library('javascript');
            
            $this->EE->cp->add_to_head('<script type="text/javascript" src="'.URL_THIRD_THEMES.'multirel/select2/select2.min.js"></script>');
            $this->EE->cp->add_to_head('<link rel="stylesheet" media="all" href="'.URL_THIRD_THEMES.'multirel/select2/select2.css">');
            $this->EE->cp->add_to_head('
            <style type="text/css">
            input.select2-input {
                -webkit-box-sizing: content-box;
                -moz-box-sizing: content-box;
                -o-box-sizing: content-box;
                box-sizing: content-box;
            }
            ul.select2-choices.ui-sortable li {
                cursor: move !important;
            }
            </style>
            ');

            $this->EE->javascript->output('$(".multirel").each(function(){

                var $this = $(this),
                    options = {
                        separator: "|",
                        multiple: true,
                        data: [],
                        initSelection : function ($element, callback) {
                            var data = [];
                            $.each($element.val().split(options.separator), function (i, v) {
                                if (dataById[v] !== undefined) {
                                    data.push(dataById[v]);
                                }
                            });
                            callback(data);
                        }
                    },
                    selected = $this.val(),
                    $input = $("<input>", {
                        type: "text",
                        name: $this.attr("name"),
                        value: selected ? selected.join(options.separator) : "",
                        style: "width: 100%;"
                    }),
                    dataById = {};

                $this.find("option").each(function() {
                    var $option = $(this),
                        option = {
                            id: $option.val(),
                            text: $option.text()
                        };

                    options.data.push(option);

                    dataById[option.id] = option;
                });

                $this.replaceWith($input);

                $input.select2(options);
                
                //$input.select2("val", selected);

                $input.select2("container").find("ul.select2-choices").sortable({
                    containment: "parent",
                    start: function() {
                        $input.select2("onSortStart");
                    },
                    update: function() {
                        $input.select2("onSortEnd");
                    }
                });
            });');

            $this->EE->session->set_cache(__CLASS__, __FUNCTION__, TRUE);
        }
		
		if (is_null($options))
		{
			$this->EE->db->select('entry_id, title')
				      ->from('channel_titles')
				      ->where('channel_id', $this->settings['field_related_id'])
				      ->order_by($this->settings['field_related_orderby'], $this->settings['field_related_sort']);
			
			if ($this->settings['field_related_max'])
			{
				$this->EE->db->limit($this->settings['field_related_max']);
			}
			
			$query = $this->EE->db->get();
			
			$options = array();
			
			foreach ($query->result() as $row)
			{
				$options[$row->entry_id] = $row->title;
			}
			
			$query->free_result();
		}
		
		if ( ! $options)
		{
			return lang('no_related_entries');
		}
		
		return form_multiselect($this->field_name.'[]', $options, $data, 'class="multirel" style="width:100%;"');
	}
	
	public function save($data)
	{
		return $data ? implode('|', $data) : '';
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
		return $data;
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
		$this->EE->load->model('channel_model');
		
		$query = $this->EE->channel_model->get_channels();
		
		$options = array();
		
		foreach ($query->result() as $row)
		{
			$options[$row->channel_id] = $row->channel_title;
		}

		$this->EE->table->add_row(
			lang('select_related_channel', 'field_related_channel_id'),
			form_dropdown('multirel_channel_id', $options, $data['field_related_id'], 'id="multirel_channel_id"')
		);
		
		$this->EE->table->add_row(
			'<strong>'.lang('display_criteria').'</strong>',
			form_dropdown('multirel_orderby', array('title' => lang('orderby_title'), 'entry_date' => lang('orderby_date')), $data['field_related_orderby'], 'id="multirel_orderby"').NBS.
				lang('in').NBS.form_dropdown('multirel_sort', array('asc' => lang('sort_asc'), 'desc' => lang('sort_desc')), $data['field_related_sort'], 'id="multirel_sort"').NBS.
				lang('limit').NBS.form_dropdown('multirel_max', array(lang('all'), 50 => 50, 100 => 100, 200 => 200), $data['field_related_max'], 'id="multirel_max"')
		);
		
		$query->free_result();
	}
	
	public function save_settings($data)
	{
		$_POST['update_formatting'] = 'y';
		
		return array(
			'field_related_id' => $this->EE->input->post('multirel_channel_id'),
			'field_related_orderby' => $this->EE->input->post('multirel_orderby'),
			'field_related_sort' => $this->EE->input->post('multirel_sort'),
			'field_related_max' => $this->EE->input->post('multirel_max'),
			'field_fmt' => 'none',
			'field_show_fmt' => 'n',
		);
	}
}

/* End of file ft.google_maps.php */
/* Location: ./system/expressionengine/third_party/google_maps/ft.google_maps.php */