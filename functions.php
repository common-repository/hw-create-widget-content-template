<?php
/**
 * re-order actived plugins list
 * @param $plugin: plugin path
 * @param $pos: specific position or other plugin path to find position of that plugin (default 0)
 */
function hwskin_reorder_actived_plugins($plugin, $pos = 0){
    if(!is_admin()) return;
    // ensure path to this file is via main wp plugin path
    $active_plugins = get_option('active_plugins');
    $first_plugin_key = array_search($plugin, $active_plugins);
    if(!is_numeric($pos)) $second_plugin_key = array_search($pos, $active_plugins);
    else $second_plugin_key = $pos;
    if(!is_numeric($pos) && $second_plugin_key == 0) $second_plugin_key++; //don't you should learn index start from 0
    array_splice($active_plugins, $first_plugin_key, 1);    //remove first plugin from list
    if(is_numeric($second_plugin_key))
    {
        $active_plugins = array_merge(array_slice($active_plugins, 0, $second_plugin_key, true)
            , array($plugin)
            , array_slice($active_plugins, $second_plugin_key, count($active_plugins) , true)); //tobe sure get all plugin num ->don't -1

    }

    update_option('active_plugins', $active_plugins);
    /*if ($first_plugin_key) { // if it's 0 it's the first plugin already, no need to continue
        array_splice($active_plugins, $this_plugin_key, 1);
        array_unshift($active_plugins, $this_plugin);
        update_option('active_plugins', $active_plugins);
    }*/
}
/**
order plugins to be loaded before all other plugins
 */
function _hwskin_move_at_first_when_activation(){
    if(!is_admin()) return;

    hwskin_reorder_actived_plugins('hw-create-widget-content-template/hw-skin.php');   //move this plugin at first list
}
add_action( 'activated_plugin', '_hwskin_move_at_first_when_activation');

//load skin selector Field type for AdminPageFramework
/**
 * load APF filetype for hw_skin
 * @param string $type: skin type
 */
function hwskin_load_APF_Fieldtype($type = HW_SKIN::SKIN_FILES){
    if(!class_exists('AdminPageFramework_Registry')) include_once('lib/admin-page-framework.min.php'); //load admin page framework
    if($type == HW_SKIN::SKIN_FILES){
        if(!class_exists('APF_hw_skin_Selector_hwskin')) include_once('APF_Fields/hw_skin_FieldType.php');
    }
    if($type == HW_SKIN::SKIN_LINKS){
        if(!class_exists('APF_imageSelector_hwskin')) include_once('APF_Fields/hw_skin_link_FieldType.php');
    }
}
/**
 * register skin
 * @param unknown $name: skin name
 * @param unknown $instance: skin instance
 */
function hwskin_register_skin($name,$instance){
    global $HW_Skins;
    if(!$HW_Skins) $HW_Skins = array();
    if(!isset($HW_Skins[$name])){
        $HW_Skins[$name] = $instance;
    }
    
}

/**
 * parse $theme_options in file skin option : options.php
 * @param array $options
 */
function hwskin_parse_theme_options($options = array()){
    if(is_array($options)){
        foreach($options as $f => $arr){
            if(is_numeric($f)){
                if(isset($arr['name'])) {
                    $options[$arr['name']] = $arr;
                    unset($options[$f]);
                }
            }
        }
    }
    return $options;
}