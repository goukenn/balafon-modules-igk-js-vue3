<?php
// @author: C.A.D. BONDJE DOUE
// @file: ProjectViteSettingConfiguration.php
// @date: 20230420 12:04:14
namespace igk\js\Vue3\Vite\Configuration;

use IGK\Helper\Activator;
use IGK\System\Configuration\ProjectConfiguration;
use IGK\System\Traits\JSonFileConfigurationTrait;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite\Configuration
*/
class ProjectViteSettingConfiguration extends ProjectConfiguration{
    use JSonFileConfigurationTrait;
    
    var $viteProjects;
    /**
     * create from configuration data 
     * @param mixed $data 
     * @return ?static 
     */
    public static function CreateFromConfigData($data){
        $i = null;
        if ($i = ProjectViteSettingConfigurationValidator::ValidateData($data, null, $errors)){
            return Activator::CreateNewInstance( static::class, $i);
        }     
        igk_environment()->last_error = $errors;
        return $i;
    }    
}