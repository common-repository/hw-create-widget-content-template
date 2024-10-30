<?php
if ( ! class_exists( 'APF_imageSelector_hwskin' ) && class_exists('AdminPageFramework_FieldType')) :
class APF_imageSelector_hwskin extends AdminPageFramework_FieldType {
    /**
     * hw_skin config
     * @var
     */
    private $skin_config;
    /*function __construct(){
        
    }*/
	/**
	 * Defines the field type slugs used for this field type.
	 */
	public $aFieldTypeSlugs = array( 'hw_skin_link', );
	
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
	 * Returns the output of the field type.
	 */
	protected function getField( $aField ) {
        $this->hw_aField = $aField;
        /**
         * parse hw_skin params
         */
        $group = $this->get_field_value('group');   //group
        $skins_folder = $this->get_field_value('skins_folder');
        $external_skins_folder = $this->get_field_value('external_skins_folder');
        $skin_filename = $this->get_field_value('skin_filename');
        $apply_current_path = $this->get_field_value('apply_current_path', plugin_dir_path(__FILE__));  //path or url
        $plugin_url = $this->get_field_value('plugin_url', plugins_url('',__FILE__));
        $enable_external_callback = $this->get_field_value('enable_external_callback' );
        $files_skin_folder = $this->get_field_value('files_skin_folder');   //other for skin_link type

        /**
         * instance hw_skin
         */
        //you can extend skin template by storing your images to path 'wp-content/hw_wpcf7_ajax_images'
        $this->skin = new HW_SKIN($this,$apply_current_path,$external_skins_folder,$skin_filename,$skins_folder);
	    if($files_skin_folder) $this->skin->files_skin_folder = $files_skin_folder;
	    $this->skin->plugin_url = $plugin_url;
	    $this->skin->enable_external_callback = $enable_external_callback;
        if($group) $this->skin->set_group($group);  //set skin group
        #$this->skin->init();

        $this->skin_config = $this->skin->get_config(true); //get skin config
	    #return $this->skin->get_skins_select_tag(null,'sdf',null,HW_SKIN::DROPDOWN_DDSSLICK_THEME,HW_SKIN::SKIN_LINKS);

		return 
			$aField['before_label']
			. $aField['before_input']
			. "<div class='repeatable-field-buttons'></div>"	// the repeatable field buttons will be replaced with this element.
			. $this->_getInputs( $aField )
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
				//get skin link
				$skin_url_value = isset( $aField['attributes']['value']['url'] ) ? $aField['attributes']['value']['url'] : '';
				$skin_url_field_id = $aField['input_id'].'_'.$_sSlug.'_url';

                //get skin config
                $skin_config_value = isset( $aField['attributes']['value']['hwskin_config'] ) ? $aField['attributes']['value']['hwskin_config'] : '';
                if(!$skin_config_value) $skin_config_value = $this->skin_config;

                $skin_config_field_id = $aField['input_id'].'_'.$_sSlug.'_hwskinconfig';
				//$this->generateAttributes( $_aAttributes )
				$atts = array(
				        'name'	=>	"{$_aAttributes['name']}[hash_skin]",
				        'id'	=>	"{$aField['input_id']}_{$_sSlug}",
				);
				$this->skin->saveCallbackJs4SkinChangeEvent('$("#'.$skin_url_field_id.'").val(skin.screenshot);');  //call this before render skins selector
				$field_output = $this->skin->get_skins_select_tag(null,$hash_skin_value, $atts,HW_SKIN::DROPDOWN_DDSSLICK_THEME,HW_SKIN::SKIN_LINKS);
				$_aOutput[] = 
					"<div class='admin-page-framework-input-label-container my_custom_field_type'>"
						. "<label for='{$aField['input_id']}_{$_sSlug}'>"
							. "<span class='admin-page-framework-input-label-string' style='min-width:" .  $aField['label_min_width'] . "px;'>" 
								. $_sLabel
							. "</span>" . PHP_EOL					
							. "" . $field_output . ""
							."<input type='hidden' name='{$_aAttributes['name']}[url]' id='{$skin_url_field_id}' value='{$skin_url_value}'/>"
                    ."<input type='hidden' name='{$_aAttributes['name']}[hwskin_config]' id='{$skin_config_field_id}' value='{$skin_config_value}'/>"
						. "</label>"
					. "</div>";				
			}
			return implode( PHP_EOL, $_aOutput );
		}
	
}
endif;