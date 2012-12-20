<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Multirel_ft extends EE_Fieldtype
{
	public $info = array(
		'name' => 'Multirel',
		'version' => '1.0.0'
	);

    public $has_array_data = TRUE;
	
	public function display_field($data)
    {
        $this->EE->load->helper('array');

        $this->settings['multirel_channel_id'] = element('field_related_id', $this->settings);
        $this->settings['multirel_orderby'] = element('field_related_orderby', $this->settings);
        $this->settings['multirel_sort'] = element('field_related_sort', $this->settings);
        $this->settings['multirel_max'] = element('field_related_max', $this->settings);
        
        return $this->_display_field($data, $this->field_name);
    }
	
	public function save($data)
	{
        if ( ! is_array($data))
        {
            return $data ? $data : '';
        }

		return $data ? implode('|', $data) : '';
	}

    public function pre_loop($data)
    {
        $entry_ids = array();

        foreach ($data as $row)
        {
            foreach (explode('|', $row) as $entry_id)
            {
                $entry_ids[] = $entry_id;
            }
        }

        if ( ! $entry_ids)
        {
            return;
        }

        $query = $this->EE->db->where_in('entry_id', $entry_ids)
                            ->get('channel_titles');

        foreach ($query->result_array() as $row)
        {
            $this->EE->session->set_cache(__CLASS__.':entries', $row['entry_id'], $row);
        }
    }

    public function replace_tag($data, $params = array(), $tagdata)
    {
        if ( ! $data)
        {
            return '';
        }

        if ( ! $tagdata)
        {
            return $data;
        }

        $output = '';

        $rows = array();

        foreach (explode('|', $data) as $entry_id)
        {
            if ($row = $this->EE->session->cache(__CLASS__.':entries', $entry_id))
            {
                $rows[] = $row;
            }
        }

        $this->EE->load->helper('array');

        $site_pages = $this->EE->config->item('site_pages');

        $prefix = element('variable_prefix', $params);

        preg_match_all('/{'.$prefix.'(entry_date|edit_date|expiration_date) format=([\042\047])(.*?)\\2}/', $tagdata, $dates);
        preg_match_all('/{'.$prefix.'(url_title_path|title_permalink)=[\042\047]?(.*?)[\042\047]?}/', $tagdata, $url_title_paths);
        preg_match_all('/{'.$prefix.'entry_id_path=[\042\047]?(.*?)[\042\047]?}/', $tagdata, $entry_id_paths);

        $switches = array();

        if (preg_match_all('/{'.preg_quote($prefix).'switch=[\042\047](.*?)[\042\047]}/', $tagdata, $matches))
        {
            foreach ($matches[0] as $i => $full_match)
            {
                $switches[substr($full_match, 1, -1)] = explode('|', $matches[1][$i]);
            }
        }

        $count = 0;

        $total_results = count($result);

        foreach ($rows as $row)
        {
            $entry_id = $row['entry_id'];
            $url_title = $row['url_title'];

            if (isset($site_pages[$this->EE->config->item('site_id')]['uris'][$entry_id]))
            {
                $row['page_uri'] = $site_pages[$this->EE->config->item('site_id')]['uris'][$entry_id];
                $row['page_url'] = rtrim($this->EE->config->item('site_url'), '/').$row['page_uri'];
            }
            else
            {
                $row['page_uri'] = '';
                $row['page_url'] = '';
            }

            if ($prefix)
            {
                foreach (array_keys($row) as $key)
                {
                    $row[$prefix.$key] = $row[$key];

                    unset($row[$key]);
                }
            }

            if ($dates)
            {
                foreach ($dates[0] as $i => $full_match)
                {
                    $row[substr($full_match, 1, -1)] = $this->EE->localize->decode_date($dates[3][$i], $dates[1][$i]);
                }
            }

            if ($url_title_paths)
            {
                foreach ($url_title_paths[0] as $i => $full_match)
                {
                    $row[substr($full_match, 1, -1)] = $this->EE->functions->create_url($url_title_paths[2][$i].'/'.$url_title);
                }
            }

            if ($entry_id_paths)
            {
                foreach ($entry_id_paths[0] as $i => $full_match)
                {
                    $row[substr($full_match, 1, -1)] = $this->EE->functions->create_url($entry_id_paths[1][$i].'/'.$entry_id);
                }
            }

            $row[$prefix.'count'] = $count + 1;

            $row[$prefix.'total_results'] = $total_results;

            foreach ($switches as $key => $values)
            {
                $switch_count = count($values);

                $row[$key] = $values[$count % $switch_count];
            }

            $output .= $this->EE->TMPL->parse_variables_row($tagdata, $row);

            $count++;
        }

        if ($backspace = element('backspace', $params))
        {
            $output = substr($output, 0, -$backspace);
        }

        return $output;
    }

    //only grabs the first one of a set, if multiple
    public function replace_tag_catchall($data, $params = array(), $tagdata, $name)
    {
        //if there are tag params, they will be held in the name, split it out so that we just have the name itself
        $name = explode(' ', $name);
        $name = $name[0];

        if (method_exists($this, 'replace_'.$name))
        {
            return $this->{'replace_'.$name}($data, $params, $tagdata);
        }

        $entry_ids = explode('|', $data);

        if ( ! isset($entry_ids[0]))
        {
            return '';
        }

        //should've been cached in pre_loop
        $row = $this->EE->session->cache(__CLASS__.':entries', $entry_ids[0]);

        return isset($row[$name]) ? $row[$name] : '';
    }  
	
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
    public function replace_entries($data, $params = array(), $tagdata = FALSE)
    {
        if ( ! $data || ! $tagdata)
        {
            return '';
        }

        require_once APPPATH.'modules/channel/mod.channel.php';
    
        $channel = new Channel;

        $original_tagdata = $this->EE->TMPL->tagdata;
        $original_tagparams = $this->EE->TMPL->tagparams;

        $this->EE->TMPL->tagdata = $tagdata;
        $this->EE->TMPL->tagparams = $params;
        $this->EE->TMPL->tagparams['entry_id'] = $data;
        $this->EE->TMPL->tagparams['fixed_order'] = $data;
        $this->EE->TMPL->tagparams['dynamic'] = 'no';
        
        $output = $channel->entries();

        $this->EE->TMPL->tagdata = $original_tagdata;
        $this->EE->TMPL->tagparams = $original_tagparams;

        return $output;
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
        $this->EE->load->helper('array');

        $data['multirel_channel_id'] = element('field_related_id', $data);
        $data['multirel_orderby'] = element('field_related_orderby', $data);
        $data['multirel_sort'] = element('field_related_sort', $data);
        $data['multirel_max'] = element('field_related_max', $data);

        foreach ($this->_display_settings($data) as $row)
        {
            $this->EE->table->add_row($row[0], $row[1]);
        }
	}

    public function display_cell_settings($data)
    {
        return $this->_display_settings($data);
    }
    
    public function save_settings($data)
    {
        $_POST['update_formatting'] = 'y';

        return array(
            'field_related_id' => $this->EE->input->post('multirel_channel_id'),
            'field_related_orderby' => $this->EE->input->post('multirel_orderby'),
            'field_related_sort' => $this->EE->input->post('multirel_sort'),
            'field_related_max' => $this->EE->input->post('multirel_max'),
            'multirel_multiple' => $this->EE->input->post('multirel_multiple'),
            'field_fmt' => 'none',
            'field_show_fmt' => 'n',
        );
    }

    public function save_cell_settings($data)
    {
        $this->EE->load->helper('array');

        return array(
            'multirel_channel_id' => element('multirel_channel_id', $data),
            'multirel_orderby' => element('multirel_orderby', $data),
            'multirel_sort' => element('multirel_sort', $data),
            'multirel_max' => element('multirel_max', $data),
            'multirel_multiple' => element('multirel_multiple', $data),
        );
    }

    public function display_cell($data)
    {
        if ( ! $this->EE->session->cache(__CLASS__, __FUNCTION__))
        {
            $this->EE->javascript->output('
            Matrix.bind("multirel", "display", function(cell){
                cell.dom.$td.find(".multirel[multiple]").multirel();
            });
            ');

            $this->EE->cp->add_to_head('
            <style type="text/css">
            .matrix-multirel {
                padding: 0 !important;
                border: none !important;
            }
            .matrix-multirel .select2-container-multi {
            }
            .matrix-multirel .select2-container-multi ul.select2-choices {
                border-color: #e3e3e3;
                height: 100%;
                border-bottom-width: 0;
            }
            </style>
            ');

            $this->EE->session->set_cache(__CLASS__, __FUNCTION__, TRUE);
        }

        return array(
            'data' => $this->_display_field($data, $this->cell_name),
            'class' => 'matrix-multirel',
        );
    }

    public function save_cell($data)
    {
        return $this->save($data);
    }

    protected function _display_field($data, $field_name)
    {
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

            $this->EE->cp->add_to_head('
            <script type="text/javascript">
            $.fn.multirel = function() {
                return this.each(function(){
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
                        $input = $("<input>", {
                            type: "text",
                            name: $this.attr("name").replace(/\[\]$/, ""),
                            value: $this.data("value"),
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

                    $input.select2("container").find("ul.select2-choices").sortable({
                        containment: "parent",
                        start: function() {
                            $input.select2("onSortStart");
                        },
                        update: function() {
                            $input.select2("onSortEnd");
                        }
                    });
                });
            };
            </script>
            ');

            $this->EE->javascript->output('$(".multirel[multiple]").multirel();');

            $this->EE->session->set_cache(__CLASS__, __FUNCTION__, TRUE);
        }

        $hash = md5(serialize(array(
            $this->settings['multirel_channel_id'],
            $this->settings['multirel_orderby'],
            $this->settings['multirel_sort'],
            $this->settings['multirel_max'],
        )));

        $options = $this->EE->session->cache(__CLASS__, __FUNCTION__.$hash);
        
        if ($options === FALSE)
        {
            $this->EE->db->select('entry_id, title, entry_date')
                      ->from('channel_titles')
                      ->where('channel_id', $this->settings['multirel_channel_id'])
                      ->order_by($this->settings['multirel_orderby'], $this->settings['multirel_sort']);
            
            if ($this->settings['multirel_max'])
            {
                $this->EE->db->limit($this->settings['multirel_max']);
            }
            
            $query = $this->EE->db->get();

            $options = array();

            if (empty($this->settings['multirel_multiple']))
            {
                $options[''] = '---';
            }
            
            foreach ($query->result() as $row)
            {
                $options[$row->entry_id] = $row->title;

                if ($this->settings['multirel_orderby'] === 'entry_date')
                {
                    $options[$row->entry_id] .= date(' (Y-m-d)', $row->entry_date);
                }
            }
            
            $query->free_result();

            $this->EE->session->set_cache(__CLASS__, __FUNCTION__.$hash, $options);
        }

        if (empty($this->settings['multirel_multiple']))
        {
            return form_dropdown($field_name, $options, $data);
        }
        
        if ( ! $options)
        {
            return lang('no_related_entries');
        }

        $selected = $data ? explode('|', $data) : array();
        
        return form_multiselect($field_name.'[]', $options, $selected, 'class="multirel" data-value="'.$data.'"');
    }

    protected function _display_settings($data)
    {
        $this->EE->load->model('channel_model');
        
        $query = $this->EE->channel_model->get_channels();
        
        $options = array();
        
        foreach ($query->result() as $row)
        {
            $options[$row->channel_id] = $row->channel_title;
        }
        
        $query->free_result();

        $this->EE->load->helper('array');

        return array(
            array(
                '<strong>'.'Allow selection of multiple entries?'.'</strong>',
                form_label(form_checkbox('multirel_multiple', '1', element('multirel_multiple', $data)).' Yes')
            ),
            array(
                lang('select_related_channel', 'multirel_channel_id'),
                form_dropdown('multirel_channel_id', $options, element('multirel_channel_id', $data), 'id="multirel_channel_id"')
            ),
            array(
                '<strong>'.lang('display_criteria').'</strong>',
                form_dropdown('multirel_orderby', array('title' => lang('orderby_title'), 'entry_date' => lang('orderby_date')), element('multirel_orderby', $data), 'id="multirel_orderby"').NBS.
                    lang('in').NBS.form_dropdown('multirel_sort', array('asc' => lang('sort_asc'), 'desc' => lang('sort_desc')), element('multirel_sort', $data), 'id="multirel_sort"').NBS.
                    lang('limit').NBS.form_dropdown('multirel_max', array(lang('all'), 50 => 50, 100 => 100, 200 => 200), element('multirel_max', $data), 'id="multirel_max"')
            ),
        );
    }
}
