<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * This class will manage all data related to QSM Themes
 * 
 * @since 7.1.17
 */
class QSM_Theme_Settings{

    /**
     * ID of the quiz
     */
    private $quiz_id;

    /**
     * themes_table name
     */
    private $themes_table;

    /**
     * quiz_themes_settings_table name
     */
    private $settings_table;

    public function __construct(){
        $this->themes_table = 'mlw_themes';
        $this->settings_table = 'mlw_quiz_theme_settings';
    }

    /**
     * updates theme status active/inactive when theme plugin is activated/deactivated
     * @param   bool true for activate/ false for deactivate
     * @param   string  theme folder name
     * @param   string  theme name
     * @param   string  serialized data quiz settings.
     * 
     * @return bool true for successful update, false in failure
     * 
     */
    public function update_theme_status($status = TRUE, $path, $name = '', $default_settings = ''){
        global $wpdb;
        $theme_path = isset($path)? $path:'';
        $theme_name = isset($name)? $name:'';
        $theme_default_settings = $default_settings;        

        if($theme_path){
            $theme_id = $this->get_theme_id($theme_path);
            if($theme_id){
                if($status){
                    return $result = $this->update_theme($theme_id, $status, $theme_name, $theme_default_settings);
                } else {
                    return $reuslt = $this->update_theme($theme_id, $status);
                }
            } else {
                if($status){
                    return $result = $this->add_theme_active($theme_path , $theme_name, $theme_default_settings);
                }
            }
        }

    }

    /**
     * 
     * Get theme id from theme_dir_basename
     * 
     * @param string theme_dir_path
     * @return int/null
     */
    private function get_theme_id($theme_dir){
        global $wpdb;
        $theme_dir = isset($theme_dir)?esc_attr( $theme_dir ):'';
        $theme_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}$this->themes_table WHERE theme = %s", $theme_dir
            )
        );
        return $theme_id;
    }

    /**
     * updates and activate existing theme
     * 
     * 
     */
    public function update_theme($theme_id, $status, $theme_name = '', $default_settings = ''){
        global $wpdb;
        $theme_id = (int) $theme_id;

        $data = [
            'theme_active'      => $status
        ];
        $identifier = ['%d'];
        if($theme_name){
            $data['theme_name'] = $theme_name;
            $identifier[] = '%s';
        }
        if($default_settings){
            $data['default_settings'] = $default_settings;
            $identifier[] = '%s';
        }
        return $result = $wpdb->update(
            $wpdb->prefix.$this->themes_table ,
            $data,
            array('id' => $theme_id),
            $identifier,
            array('%d')
        );
    }

    /**
     * 
     * Adds Record of new QSM themes
     * 
     * 
     */
    public function add_theme_active($theme_path , $theme_name, $theme_default_settings){
        global $wpdb;

        $data = [
            'theme_active'      => true,
            'theme'             => $theme_path,
            'theme_name'        => $theme_name,
            'default_settings'  => $theme_default_settings,
        ];

        return $wpdb->insert(
            $wpdb->prefix.$this->themes_table,
            $data,
            array('%d', '%s', '%s', '%s')
        );

    }

    /**
     * Get active themes from database
     * 
     * @param array/string parameter to be retrieved from themes table 
     * 
     * @return array of active themes
     */
    public function get_active_themes($parameter = 'theme'){
        global $wpdb;
        if(is_array($parameter)){
            $parameter = sanitize_text_field(implode(',', $parameter));
        } else if(is_string($parameter)){
            $parameter = sanitize_text_field($parameter);
        } else {
            $parameter = 'theme';
        }

        $query = $wpdb->prepare("SELECT id,%1s FROM {$wpdb->prefix}$this->themes_table WHERE theme_active = 1", $parameter);
        return $results = $wpdb->get_results($query, ARRAY_A);

    }

    /**
     * Activates selected theme for a particular quiz
     */
    public function activate_selected_theme($post_id, $theme_id){
        global $wpdb; 
        $quiz_id = (int) $post_id;
        $theme_id = (int) $theme_id;

        $deactivated = $wpdb->update(
            $wpdb->prefix.$this->settings_table ,
            array('active_theme' => false),
            array('quiz_id' => $quiz_id),
            '%d',
            '%d',
        );

        if($theme_id === 0){
            return;
        }

        $id = $wpdb->get_var($wpdb->prepare("SELECT id from {$wpdb->prefix}$this->settings_table WHERE quiz_id = %d AND theme_id = %d", $quiz_id, $theme_id));

        if($id){
            $wpdb->update(
                $wpdb->prefix.$this->settings_table ,
                array('active_theme' => true),
                array('id' => $id),
                '%d',
                '%d',
            );
        } else {
            $settings = $wpdb->get_var($wpdb->prepare("SELECT default_settings from {$wpdb->prefix}$this->themes_table WHERE id = %d", $theme_id));
            $settings = $settings? $settings:'';
            $data = array(
                'active_theme'          => true,
                'theme_id'              => $theme_id,
                'quiz_id'               => $quiz_id,
                'quiz_theme_settings'   => $settings,
            );
            $wpdb->insert(
                $wpdb->prefix.$this->settings_table ,
                $data,
                array('%d','%d','%d','%s'),
            );
            $wpdb->insert_id;
        }
    }

    
    
    /**
     * This function return Active Quiz Theme id
     */
    public function get_active_quiz_theme($quiz_id){
        global $wpdb;
        $query = $wpdb->prepare("SELECT theme_id FROM {$wpdb->prefix}$this->settings_table WHERE quiz_id = %d AND active_theme = 1", $quiz_id);
        $result = $wpdb->get_var($query);
        return $result?$result:0;
    }

    /**
     * This function return Active Quiz Theme path
     */
    public function get_active_quiz_theme_path($quiz_id){
        global $wpdb;
        $query = $wpdb->prepare("SELECT a.theme FROM {$wpdb->prefix}$this->themes_table AS a, {$wpdb->prefix}$this->settings_table AS b WHERE b.quiz_id = %d AND b.active_theme = 1 AND b.theme_id = a.id", $quiz_id);
        $result = $wpdb->get_var($query);
        return $result?$result:'default';
    }

    /**
     * This function return Active Quiz Theme and Other Active Themes
     */
    public function get_active_theme_settings($quiz_id, $theme_id){
        global $wpdb;
        $query = $wpdb->prepare("SELECT quiz_theme_settings FROM {$wpdb->prefix}$this->settings_table WHERE quiz_id = %d AND theme_id = %d", $quiz_id, $theme_id);
        $result = $wpdb->get_var($query);
        return unserialize($result);
    }

    /**
     * Updates personalizes setting for a particular quiz theme
     */
    public function update_quiz_theme_settings($quiz_id, $theme_id, $settings){
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix.$this->settings_table ,
            array('quiz_theme_settings' => $settings),
            array(
                'quiz_id' => $quiz_id,
                'theme_id'=> $theme_id,
            ),
            '%s',
            array('%d','%d'),
        );
    }



}