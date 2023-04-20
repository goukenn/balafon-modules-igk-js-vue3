<?php
// @author: C.A.D. BONDJE DOUE
// @file: ProjectViteSettingConfigurationValidator.php
// @date: 20230420 12:58:56
namespace igk\js\Vue3\Vite\Configuration;

use IGK\System\TamTam\ProjectSettingValidationData;
use IGK\System\WinUI\Forms\FormData;

///<summary></summary>
/**
* project vite validation extensions 
* @package IGK
*/
class ProjectViteSettingConfigurationValidator extends ProjectSettingValidationData{
    /**
     * controller vite project . [project-name=>vite-configs-options]
     * @var ?array
     */
    var $viteProjects;
    protected function getValidationClassReference(){
        return ProjectViteSettingConfiguration::class;
    }
    /**
     * extends not required data
     * @return null|array 
     */
    function getNotRequired(): ?array
    {
        $tab =parent::getNotRequired();
        $ta[] = 'viteProjects';
        return $tab;
    }
}