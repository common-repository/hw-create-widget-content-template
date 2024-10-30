<?php
/**
 * Created by PhpStorm.
 * User: Hoang
 * Date: 12/06/2015
 * Time: 15:34
 */
if(! class_exists('HW_SKIN_Option')) :
class HW_SKIN_Option {
    const DEFAULT_TEMPLATE = '-1';

    /**
     * field type reference
     */
    public $fieldType_ref = null;

    /**
     * HW_SKIN instance
     * @var null
     */
    public $skin = null;

    /**
     * bind class to field which to build skin options
     * @param $field_obj: field type class instance
     */
    public function __construct($field_obj, HW_SKIN $skin = null) {
        $this->fieldType_ref = $field_obj;
        //if current context for HW_SKIN class
        if(empty($skin) && $field_obj instanceof HW_SKIN/*gettype($field_obj) == 'object' && get_class($field_obj) == 'HW_SKIN'*/) {
            $skin = $field_obj;
        }
        if(!empty($skin)) $this->skin = $skin;    //save skin object

        add_filter('renderOptionField' , array(__CLASS__, '_renderOptionField') );
        if(!class_exists('HW_UI_Component') && class_exists('HW_HOANGWEB')) HW_HOANGWEB::load_class('HW_UI_Component');
    }

    /**
     * filter field setting by 'renderOptionField'
     * @param $field
     */
    public static function _renderOptionField($field) {
        if(isset($field['value'])) {
            if(!isset($field['description']) ) $field['description'] = '';      //init field description

            if(isset($field['method']) && $field['method'] == 'append') {
                $field['description'] .= '<br/>Thêm vào sau giá trị mặc định "<em><span style="color:blue">'.htmlspecialchars($field['value'],ENT_QUOTES).'</span></em>" (thuộc về skin hiện tại). Và giá trị này sẽ thay thế nếu thiết lập trong theme-setting.php';
            }
            elseif(isset($field['method']) && $field['method'] == 'override'){
                $field['description'] .= '<br/>Sẽ thay thế giá trị mặc định "<em><span style="color:blue">'.htmlspecialchars($field['value'],ENT_QUOTES).'</span></em>" (thuộc về skin hiện tại). Và giá trị này sẽ thay thế nếu thiết lập trong theme-setting.php';
            }
            else{
                $field['description'] .= '<br/>Giá trị mặc định "<em><span style="color:blue">'.htmlspecialchars($field['value'],ENT_QUOTES).'</span></em>" (thuộc về skin hiện tại)';
            }
        }
        return $field;
    }
    /**
     * detect where calling the method, call method inside other to get parent from
     * @return mixed
     */
    private function get_from_context() {
        $backtrace = debug_backtrace();//print_r($backtrace);
        return $backtrace[2]['function'];	//exclude this function
    }
    /**
     * create instance of class
     * @param $field_obj: extend from constructor
     * @param HW_SKIN $skin: HW_SKIN object
     * @return HW_SKIN_Option
     */
    public static function init_class($field_obj, HW_SKIN $skin = null) {
        return new HW_SKIN_Option($field_obj, $skin);
    }
    /**
     * get avaiable templates in mlslider skin, note: this call after user choose a skin
     * call in the method 'renderOptionField'
     */
    private function get_list_templates(){
        if(empty($this->skin)) return array();

        $config = (object)$this->skin->get_config(false);
        $skin_info = $this->skin->get_active_skin_info();
        $skin_dir = $skin_info[0].'/'.$skin_info[1];

        $filename = trim(trim($config->skin_name),'.php');  //skin file name without extension
        $template  = $filename.'_template';     //detect template file
        $templates = array(
            //default skin
            array(
                'name' => $config->skin_name,
                'file_path' => base64_encode($skin_dir.$config->skin_name)
            )
        );

        $skins_iterator = new RecursiveDirectoryIterator( $skin_dir );
        foreach ( new RecursiveIteratorIterator( $skins_iterator ) as $skin ) {
            if ( $skin->getExtension() == 'php' && basename( $skin ) != '.' && basename( $skin ) != '..'
                && strpos(basename( $skin ) ,$template) === 0) {
                $temp = array();
                $temp['name'] =  basename( ( $skin ) );
                $temp['file_path'] = $skin_dir.$temp['name'];
                $templates[] = $temp;
            }
        }
        return $templates;
    }
    /**
     * render theme option field
     * @param $aField
     */
    public function renderOptionField_APF($field, $aField, $_aAttributes,$_sSlug,$wrapper = true,$repeat = true){
        if(!isset($field['type'])) $field['type'] = 'checkbox';   //default set checkbox option
        //valid field type
        if(!in_array($field['type'], self::allow_field_types())) return ;
        if(!isset($field['name']) ) {
            $field['name'] = '';     //valid field name
            //return ;  //allow for type='string'
        }

        //filter field setting before render field form
        $field = apply_filters('renderOptionField_APF', $field, $aField);

        $tag = '';
        $name = $_aAttributes['name'].'[skin_options]['.$field['name'].']' ; //field name
        $id = $aField['input_id'].'_'.$_sSlug.'_hwtheme_options_'.$field['name'];   //field id
        $title = isset($field['title'])? $field['title'] : $field['name'];
        $desc = isset($field['description'])? $field['description'] : '';


        //field value
        if(isset($aField['attributes']['value']['skin_options'][$field['name']])) {
            $value = esc_attr($aField['attributes']['value']['skin_options'][$field['name']]);
        }
        else {
            $value = isset($field['value'])? esc_attr($field['value']) : '';  //default field value
        }

        if($wrapper) $tag .= '<p style="margin-bottom:14px;">';
        /*$callback = array(
            'name' => ''
        );*/

        self::build_option_field($field, array('id' => $id, 'name' => $name, 'title' => $title, 'desc' => $desc, 'value'=> $value), $tag,
            array(&$this, 'renderOptionField_callback'));
        //special field type
        if($field['type'] == 'template'){   //template
            $field['type'] = 'select';      //change field type

            $tag .= $this->renderOptionField_APF($field, $aField,$_aAttributes,$_sSlug,false,false);   //render select tag
        }

        if($wrapper) $tag .= '</p>';
        //repeat the field
        if($repeat == true && isset($field['repeat'])):
            //repeat current field
            if(isset($field['repeat']) && is_numeric($field['repeat'])) {

                for($i=1;$i < $field['repeat'] ;$i++){  //- first repeat field
                    $save_id = $field['id'];
                    $field['id'] .= '_'.$i;
                    $field['title'] = $field['id']; //sync for title

                    $tag .= $this->renderOptionField_APF($field, $aField, $_aAttributes,$_sSlug,false,false);
                    $field['id'] = $save_id;

                }
            }
        endif;
        return $tag;
    }
    /**
     * render theme option field
     * @param $aField
     */
    public function renderOptionField($field, $_name, $_id, $values = null, $wrapper = true,$repeat = true) {
        if(!isset($field['type'])) $field['type'] = 'text';   //default set checkbox option
        //valid field type
        if(!in_array($field['type'], self::allow_field_types())) return ;
        if(!isset($field['name']) ) {
            $field['name'] = '';     //valid field name
            //return ;  //allow type='string'
        }
        /*if(!isset($field['id'])) {  //set field id attr -> never use
            $field['id'] =  $field['name'];
        }*/
        //filter field setting before render field form
        $field = apply_filters('renderOptionField', $field);

        $tag = '';
        $name = $_name.'['.$field['name'].']' ; //field name
        $id = $_id.'_'.$field['name'];   //field id
        $title = isset($field['title'])? $field['title'] : $field['name'];
        $desc = isset($field['description'])? $field['description'] : '';

        //field value
        if(!empty($values) && isset($values[$field['name']])) {
            $value = esc_attr($values[$field['name']]);
        }
        else {
            $value = isset($field['value'])? esc_attr($field['value']) : '';  //default field value
        }

        $field_atts = array(
            'id' => $id,
            'name' => $name,
            'value' => $value,
            'title' => $title,
            'desc' => $desc
        );
        if($wrapper) $tag .= '<p style="margin-bottom:14px;">';
        //check field avaiable it'belong to widget feature
        if(isset($field['awc_widget_feature']) && is_object($this->widget_ref)
            && ! HW_AWC::check_widget_feature($this->widget_ref, $field['awc_widget_feature']))
        {
            $tag .= '<span>Field (<em>'.$name.'</em>) yêu cầu kích hoạt feature "'. $field['awc_widget_feature'] .'" cho widget này.</span>';
        }
        else self::build_option_field($field, $field_atts, $tag , array(&$this, 'renderOptionField_callback') );
        if($wrapper) $tag .= '</p>';

        //repeat the field
        if($repeat == true && isset($field['repeat'])):
            //repeat current field
            if(isset($field['repeat']) && is_numeric($field['repeat'])) {

                for($i=1;$i < $field['repeat'] ;$i++){  //- first repeat field
                    $save_id = $field['name'];
                    $field['name'] .= '_'.$i;
                    $field['title'] = $field['name']; //sync for title

                    $tag .= $this->renderOptionField($field, $_name, $_id,$values,false,false);
                    $field['name'] = $save_id;

                }
            }
        endif;
        return $tag;
    }
    /**
     * renderOptionField callback (experimental)
     * @param $field: field setting
     */
    public function renderOptionField_callback($field) {
        /*if($field['type'] == 'template') {
            $field['type'] = 'select';
            //if($this->get_from_context() == 'renderOptionField_APF') $this->renderOptionField_APF($field);
        }
        */
        if($field['type'] == 'template'){   //template
            $data = $this->get_list_templates();
            $field['options'] = array(
                self::DEFAULT_TEMPLATE => __('Mặc định')
            );    //override args
            foreach($data as $file){
                $field['options'][base64_encode($file['file_path'])] = $file['name'];
            }
            //$field['type'] = 'select';
            //$tag .= $this->renderOptionField_APF($field, $aField,$_aAttributes,$_sSlug,false,false);   //render select tag
        }
        return $field;
    }
    /**
     * generate attributes for element
     * @param array $aAttributes
     * @return string
     */
    public static function generateAttributes(Array $aAttributes = array()) {
        $_sQuoteCharactor = "'";
        $_aOutput = array();
        foreach ($aAttributes as $sAttribute => $sProperty) {
            if (is_array($sProperty) || is_object($sProperty) ) {
                continue;
            }
            $_aOutput[] = "{$sAttribute}={$_sQuoteCharactor}{$sProperty}{$_sQuoteCharactor}";
        }
        return implode(' ', $_aOutput);
    }

    /**
     * create select tag (from HW_UI_Component class)
     * @param $options
     * @param string $select
     * @param array $atts
     * @return string
     */
    public static function build_select_tag($options, $select = '', $atts = array()) {
        //$atts = self::generateAttributes($atts);

        $tag = vsprintf('<select name="%s" id="%s">', $atts);
        foreach ($options as $value => $text) {
            $selected = ($select == $value)? 'selected="selected"' : '';
            $tag .= sprintf('<option value="%s" %s>%s</option>', $value, $selected, $text) ;
        }
        $tag .= ('</select>');
        return $tag;
    }
    /**
     * return allow field types
     * @return array
     */
    public static function allow_field_types() {
        return array('select','multi_select','checkbox','text','textarea','sidebar','string','template','hidden');

    }
    /**
     * build option field (from HW_UI_Component class)
     * @param $field
     * @param array $field_atts
     * @param $tag
     * @param null $callback
     * @param $param other data
     */
    public static function build_option_field(&$field, $field_atts = array(), &$tag, $callback = null, $params = array()) {
        //init variables
        $id = '';
        $name = '';
        $value = '';
        $title = '';
        $desc = '';
        extract($field_atts);
        //generate attributes for element
        $attrs = array(
            'id' => $id,
            'name' => $name
        );
        if(!empty($field['attributes']) && is_array($field['attributes'])) $attrs = array_merge($field['attributes'], $attrs);

        if($field['type'] == 'checkbox') {  //checkbox
            $attrs['type'] = $field['type'];
            $tag .= '<label for="'.$id.'"><strong>'.$title.'</strong><br/>
                <input '.self::generateAttributes($attrs).' '.($value? 'checked="checked"':'').'/>
                </label><br/><em>'.$desc.'</em>';
        }
        elseif($field['type'] == 'text'){   //text
            $attrs['type'] = $field['type'];
            $attrs['value'] = $value;

            $tag .= '<label for="'.$id.'"><strong>'.$title.'</strong><br/>
                <input '.self::generateAttributes($attrs).'/>
                </label><br/><em>'.$desc.'</em>';
        }
        elseif($field['type'] == 'hidden') {  //hidden field
            $attrs['type'] = $field['type'];
            $attrs['value'] = $value;

            $tag .= '
                <input '.self::generateAttributes($attrs).'/>
                ';
        }
        elseif($field['type'] == 'textarea'){   //textarea
            $tag .= '<label for="'.$id.'"><strong>'.$title.'</strong><br/>
                <textarea '.self::generateAttributes($attrs).' >'.($value).'</textarea>
                </label><br/><em>'.$desc.'</em>';
        }
        elseif($field['type'] == 'select') {    //select
            if(!isset($field['options'])) $field['options'] = array();  //empty options
            if(is_string($field['options'])) $field['options'] = explode(',',$field['options']);

            $tag .= '<label for="'.$id.'"><strong>'.$title.'</strong><br/><select '.self::generateAttributes($attrs).'>';
            if(is_array($field['options']))
                foreach($field['options'] as $key => $option){
                    if(is_numeric($key) && $key >=0) $key = $option;    //accept key-pair value  array
                    $tag .= '<option value="'.esc_attr($key).'" '.($key == $value? 'selected':'').'>'.$option.'</option>';
                }
            $tag .= '</select></label>';
            $tag .= '<br/><em>'.$desc.'</em>';

        }
        elseif($field['type'] == 'multi_select') {  //multi select
            if(!isset($field['options'])) $field['options'] = array();  //empty options
            if(is_string($field['options'])) $field['options'] = explode(',',$field['options']);

            $attrs['name'] .= '[]';

            $tag .= '<div ><strong>'.$title.'</strong><br/>';
            if(is_array($field['options']))
                foreach($field['options'] as $key => $option){
                    if(is_numeric($key) && $key >=0) $key = $option;    //accept key-pair value  array
                    $attrs['id'] .= '_'. $key;

                    $tag .= '<label for="'.$id.'_'.$key.'" class="hw-label-multi_select-field">';
                    $tag .= '<input type="checkbox" '.self::generateAttributes($attrs).' value="'.esc_attr($key).'" '.(in_array($key, (array)$value)? 'checked="checked"':'').'/>';
                    $tag .= $option. '</label>';
                }
            $tag .= '</div>';
            $tag .= '<br/><em>'.$desc.'</em>';
        }

        elseif($field['type'] == 'string'){ //string
            //if(!empty($title)) $tag .= "<h3>{$title}</h3>";
            if(!empty($desc)) $tag .= $desc;
        }
        /*elseif($field['type'] == 'template'){   //template    ->moved it inside callback
            $data = $this->get_list_templates();
            $field['options'] = array(
                self::DEFAULT_TEMPLATE => __('Mặc định')
            );    //override args
            foreach($data as $file){
                $field['options'][base64_encode($file['file_path'])] = $file['name'];
            }
            //$field['type'] = 'select';
            //$tag .= $this->renderOptionField_APF($field, $aField,$_aAttributes,$_sSlug,false,false);   //render select tag
        }*/
        if(is_callable($callback)) $field = call_user_func($callback, $field, array('tag' => &$tag, 'data' => $params));   //callback
        return $field;
    }
    /**
     * build json object for js consuming
     * @param $data
     * @param array|string $except exclude options
     * @param array|string $js_obj_keys_value list keys you want use value as javascript object
     */
    public static function build_json_options($data, $except = array(), $js_obj_keys_value = array()) {
        if(is_string($except)) $except = explode(',', $except);
        if(is_string($js_obj_keys_value)) $js_obj_keys_value = explode(',', $js_obj_keys_value);
        if(is_array($data)) {
            $json = array();
            $value_arr = array();
            $replace_keys = array();

            //excludes options
            if(is_array($except))
            foreach ($except as $key) {
                if(isset($data[$key])) unset($data[$key]);
            }
            foreach ($data as $key => &$value) {
                if(empty($value) && strcmp($value, '0') == -1) continue;
                //valid value
                if( $value == 'on') $value = true;
                if(is_numeric($value)) $value = (int) $value;

                // Look for key that it'value as js object
                if(in_array($key, $js_obj_keys_value) ){
                    // Store function string.
                    $value_arr[] = $value;
                    // Replace function string in $foo with a 'unique' special key.
                    $value = '%' . $key . '%';
                    // Later on, we'll look for the value, and replace it.
                    $replace_keys[] = '"' . $value . '"';
                }

                $json[$key] = $value;
            }
            $json = json_encode($json);
            // Replace the special keys with the original string.
            if(count($value_arr) && count($replace_keys)) {
                $json = str_replace($replace_keys, $value_arr, $json);
            }

            return $json;
        }
    }
}
endif;