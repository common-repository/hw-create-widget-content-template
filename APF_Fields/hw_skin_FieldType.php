<?php
#this file inherit from my plugin hw-wpcf7/lib/APF_Fields/
if(!class_exists('HW_SKIN_Option')) {
    include_once(dirname(dirname(__FILE__)).'/includes/hw_skin_options.php');
}

if ( ! class_exists( 'APF_hw_skin_Selector_hwskin' ) && class_exists('AdminPageFramework_FieldType')) :
class APF_hw_skin_Selector_hwskin extends AdminPageFramework_FieldType {
    const DEFAULT_TEMPLATE = '-1';
    /**
     * hw_skin config
     * @var
     */
    private $skin_config = null;

    /**
     * manage skin options
     * @var null
     */
    private $skin_options = null;
    /*function __construct(){
        
    }*/
	/**
	 * Defines the field type slugs used for this field type.
	 */
	public $aFieldTypeSlugs = array( 'hw_skin', );
	
	/**
	 * Defines the default key-values of this field type. 
	 * 
	 * @remark			$_aDefaultKeys holds shared default key-values defined in the base class.
	 */
	protected $aDefaultKeys = array(
		//HW_SKIN params
	        'group'	=> '',
	    'skins_folder' => 'skins',
	    'external_skins_folder' => '',
	    'skin_filename' => '',
	    'enable_external_callback' => false,
        'set_dropdown_ddslick_setting' => array('width'=>'350'), //ddslick setting

	                  
		'attributes'	=>	array(
			'size'	=>	10,
			'maxlength'	=>	400,
		),
		'label'			=>	'',
	);
	/**
	 * Returns the field type specific CSS rules.
	 */ 
	protected function getStyles() {
		return ".admin-page-framework-input-label-container.my_custom_field_type { padding-right: 2em;' }";
	}
	function get_ref_plugin_url(){
	    return plugins_url(__FILE__);
	}

    /**
     * build json object for js consuming, clone this static method to class HW_SKIN_Option
     * @param $data
     */
    public static function build_json_options($data, $except = array()) {
        if(is_string($except)) $except = explode(',', $except);
        if(is_array($data)) {
            $json = array();
            //excludes options
            foreach ($except as $key) {
                if(isset($data[$key])) unset($data[$key]);
            }
            foreach ($data as $key => $value) {
                if(empty($value)) continue;
                //valid value
                if( $value == 'on') $value = true;
                if(is_numeric($value)) $value = (int) $value;

                $json[$key] = $value;
            }
            return json_encode($json);
        }
    }

    /**
     * resume hw_skin instance from this field type value
     * @param $apf_hwskin_aValue
     */
    public static function resume_hwskin_instance($apf_hwskin_aValue = array()){
        if(!is_array($apf_hwskin_aValue)) return;   //an array of 2 keys: 'hash_skin', 'hwskin_config' created by this field type
        $return = array();

        if(isset($apf_hwskin_aValue['hash_skin'])) {
            $hash_skin = $apf_hwskin_aValue['hash_skin'];
            $return['hash_skin'] = $hash_skin;
        }
        if(isset($apf_hwskin_aValue['hwskin_config'])) $skin_config = $apf_hwskin_aValue['hwskin_config'];

        if(isset($skin_config) && $skin_config ){
            $skin = HW_SKIN::resume_skin($skin_config);
            $return['instance'] = $skin;
            //$return['skins_data'] = $skin->get_skin_data($hash_skin);
            //$return['hash_skin'] = $hash_skin;
            $return['skin_config'] = $skin_config;  //i know it nerver in use later

        }
        return (object) $return;
    }

    /**
     * filter skin data return in method HW_SKIN::load_skins_data
     * @param $data
     * @return mixed
     */
    public function _hw_skin_data_filter($data){
        $data['md5'] = md5($data['screenshot']);
        return $data;
    }
	/**
	 * Returns the output of the field type.
	 */
	protected function getField( $aField ) {
        $this->hw_aField = $aField;     //save aField object
        /*global $wp_filter;
        $exists_filter = array_key_exists('apf_hwskin',$wp_filter);*/
        /**
         * parse hw_skin params
         */
        $group = $this->get_field_value('group');   //$group='';
	    /*if(isset($aField['group'])) {
            $group = apply_filters('apf_hwskin', $aField['group'],'group');
        }  //group
        else $group = '';*/
        $skins_folder = $this->get_field_value('skins_folder');
        $external_skins_folder = $this->get_field_value('external_skins_folder');
        $skin_filename = $this->get_field_value('skin_filename');
        $apply_current_path = $this->get_field_value('apply_current_path', plugin_dir_path(__FILE__));  //path or url
        $plugin_url = $this->get_field_value('plugin_url', plugins_url('',__FILE__));
        $enable_external_callback = $this->get_field_value('enable_external_callback');
        $set_dropdown_ddslick_setting = $this->get_field_value('set_dropdown_ddslick_setting');

        //set template header data
        if(isset($aField['template_header_data']) && is_array($aField['template_header_data'])) {
            $template_header_data = apply_filters('apf_hwskin', $aField['template_header_data'],'template_header_data');
        }
        /**
         * instance hw_skin
         */
	    //you also extend skins with this path 'wp-content/hw_livechat_skins' 
	    $this->skin = new HW_SKIN($this,$apply_current_path,$external_skins_folder,$skin_filename,$skins_folder);
	    $this->skin->plugin_url = $plugin_url;
	    if($group) $this->skin->set_group($group);  //set skin group
	    $this->skin->enable_external_callback = $enable_external_callback;
        $this->skin->set_dropdown_ddslick_setting($set_dropdown_ddslick_setting);
        //set template header data
        if(isset($template_header_data) && is_array($template_header_data)) {
            $this->skin->set_template_header_info($template_header_data);
        }

        add_filter('hw_skin_data', array($this, '_hw_skin_data_filter'));

        #$this->skin->init();

        $this->skin_config = $this->skin->get_config(true); //get skin config

	    #return $this->skin->get_skins_select_tag(null,'sdf',null,HW_SKIN::DROPDOWN_DDSSLICK_THEME,HW_SKIN::SKIN_LINKS);
        //skin options
        $this->skin_options = HW_SKIN_Option::init_class($this, $this->skin);

		return 
			$aField['before_label']
			. $aField['before_input']
			. "<div class='repeatable-field-buttons'></div>"	// the repeatable field buttons will be replaced with this element.
			. $this->_getInputs( $aField )
            //.'XX'
			. $aField['after_input']
			. $aField['after_label'];
		
	}
    /**
     * get field value
     * @param $fname
     * @param $default: default value if not found
     */
    function get_field_value($fname, $default = ''){
        $aField = $this->hw_aField;    //$this->field;
        if(isset($aField[$fname]) ) {
            $value = apply_filters('apf_hwskin',$aField[$fname],$fname);
        }
        elseif(!empty($this->aDefaultKeys[$fname])) $value = $this->aDefaultKeys[$fname];
        return isset($value)? $value : $default;
    }
    /**
     * get input output
    * @param $aField
    * @return string
     */
		private function _getInputs( $aField ) {

			$_aOutput = array();
			foreach( ( array ) $aField['label'] as $_sSlug => $_sLabel ) {
				
				$_aAttributes = isset( $aField['attributes'][ $_sSlug ] ) && is_array( $aField['attributes'][ $_sSlug ] )
					? $aField['attributes'][ $_sSlug ] + $aField['attributes']
					: $aField['attributes'];
				/*$aAttributes = array(
					//'name'	=>	"{$_aAttributes['name']}[{$_sSlug}]",
					'name'	=>	"{$_aAttributes['name']}",   #[hash_skin]
					'id'	=>	"{$aField['input_id']}_{$_sSlug}",
					//'value'	=>	isset( $aField['attributes']['value'][ $_sSlug ] ) ? $aField['attributes']['value'][ $_sSlug ] : '',
					'value'	=>	isset( $aField['attributes']['value'] ) ? $aField['attributes']['value'] : '',
				); 
				$_aAttributes = $aAttributes+ $_aAttributes;
				*/

			    //get hash skin value
			    $hash_skin_value = isset( $aField['attributes']['value']['hash_skin'] ) ? $aField['attributes']['value']['hash_skin'] : '';
				//get skin config
				$skin_config_value = isset( $aField['attributes']['value']['hwskin_config'] ) ? esc_attr($aField['attributes']['value']['hwskin_config']) : '';
                if(!$skin_config_value) $skin_config_value = esc_attr($this->skin_config);
                $notice_skin_option = 'Vui lòng nhấn save để tùy chỉnh options của theme này nếu có.';  //skin options notice

                //valid skin options file for this skin
                if($hash_skin_value) {
                    $option_file = $this->skin->get_file_skin_options($hash_skin_value);
                    //get document
                    $readme = $this->skin->get_file_skin_resource('readme.html');//_print($readme);
                    if(file_exists($readme)) $readme_link = $this->skin->get_skin_url('readme.html');

                    if(file_exists($option_file)) {
                        include($option_file);
                        if(isset($theme_options) && is_array($theme_options)){
                            $skin_data = $this->skin->get_skin_data($hash_skin_value);  //get active skin data
                            $parse_id = md5($skin_data['screenshot']);
                            $theme_options_output = array();
                            $theme_options_output[] = '<hr/><div id="'.$parse_id.'">';
                            //create fields
                            foreach($theme_options as $_field){
                                $theme_options_output[] = $this->skin_options->renderOptionField_APF($_field, $aField, $_aAttributes,$_sSlug);

                            }
                            $theme_options_output[] = '</div><div class="hw-skin-options-notice" style="display: none;color:red !important;">'.$notice_skin_option.'</div>';
                        }

                    }

                }

				$skin_config_field_id = $aField['input_id'].'_'.$_sSlug.'_hwskinconfig';

				//$this->generateAttributes( $_aAttributes )
				$atts = array(
				        'name'	=>	"{$_aAttributes['name']}[hash_skin]",
				        'id'	=>	"{$aField['input_id']}_{$_sSlug}",
                    //'onchange' => ''
				);
                $msg_id = 'hwskin_msg_'.$atts['id'];    //div message id

                $theme_options_output[] = '<div class="message hw-skin-options-notice" id="'.$msg_id.'"></div>';
                if(isset($readme_link)) $theme_options_output[] = '<a href="'.$readme_link.'" target="_blank">Xem tài liệu</a>';

                if(isset($parse_id)){
                    $hwskin_event_change_name = 'hwskin_field_'.$atts['id'].'_'.$parse_id.'_change_event';  //change event callback name
                    $this->skin->saveCallbackJs4SkinChangeEvent("if(typeof {$hwskin_event_change_name} == 'function')".$hwskin_event_change_name.'(skin);');
                    //$this->skin->saveCallbackJs4SkinChangeEvent('$("#'.$skin_config_field_id.'").val(skin.screenshot);');  //call this before render skins selector

                    $_aOutput[] = '<script>
                        function hwskin_field_'.$atts['id'].'_'.$parse_id.'_change_event(skin){
                            //console.log(skin.md5,"'.$parse_id.'");
                            if(skin.md5 === "'.$parse_id.'"){
                                jQuery("#'.$parse_id.'").removeClass("hw-skin-options-none").show();jQuery("#'.$parse_id.'").next().hide();
                                jQuery("#'.$parse_id.'").find("input,select").removeAttr("disabled").css({"display":""});
                            }
                            else{
                                jQuery("#'.$parse_id.'").addClass("hw-skin-options-none").hide();jQuery("#'.$parse_id.'").next().show();
                                jQuery("#'.$parse_id.'").find("input,select").attr({"disabled":"disabled"}).css("display","none");
                            }

                        }
                        </script>
                    ';
                }
                else $this->skin->saveCallbackJs4SkinChangeEvent('jQuery("#'.$msg_id.'").html("'.$notice_skin_option.'")');

                $field_output = $this->skin->get_skins_select_tag(null,$hash_skin_value, $atts,false);  //skin select tag
				$_aOutput[] = 
					"<div class='admin-page-framework-input-label-container my_custom_field_type'>"
						. "<label for='{$aField['input_id']}_{$_sSlug}'>"
							. "<span class='admin-page-framework-input-label-string' style='min-width:" .  $aField['label_min_width'] . "px;'>" 
								. $_sLabel
							. "</span>" . PHP_EOL					
							. "" . $field_output . ""
							."<input type='hidden' name='{$_aAttributes['name']}[hwskin_config]' id='{$skin_config_field_id}' value='".($skin_config_value)."'/>"
						. "</label>"
					. "</div>";
                if(isset($theme_options_output)) $_aOutput = array_merge($_aOutput, $theme_options_output);

                if(isset($aField['hwskin_field_output_callback']) && is_callable($aField['hwskin_field_output_callback'])){    //filter field output
                    $_aOutput = call_user_func($aField['hwskin_field_output_callback'],$_aOutput,$this->skin, $field_output);
                }

                else $_aOutput = apply_filters('hwskin_field_output', $_aOutput,$this->skin, $field_output);    //filter with hook of version
			}

			return implode( PHP_EOL, $_aOutput );
		}

    /**
     *extend from nhp/../options/fields/field_hw_skin.php
     * @return array
     */
    public static function FieldType_data_sidebar($field= array()){
        static $field_setting;
        if(!$field_setting){
            global $wp_registered_sidebars;
            #if(0&&class_exists('HW_Sidebar_Settings')) $sidebars = HW_Sidebar_Settings::get_all_sidebars();
            #else
            {//get all registered sidebars
                $sidebars = array();
                foreach($wp_registered_sidebars as $sidebar => $param){
                    //$sidebars[$sidebar] = $param['name'];
                    $sidebars[] = $sidebar;
                }
            }
            $_field = array(
                'type' => 'select',
                'options' => $sidebars
            );
            $field_setting = array_merge($field,$_field);
        }
        return $field_setting;
    }
}
endif;