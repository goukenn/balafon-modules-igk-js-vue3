<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueAppOptions.php
// @date: 20230419 07:18:04
namespace igk\js\Vue3;

use igk\js\Vue3\Libraries\VueLibraryBase;

///<summary></summary>
/**
* 
* @package igk\js\Vue3
*/
class VueAppOptions{
    var $name = 'igk.js.vue3.application';

    var $routerName = 'vue-router.pinc';
    /**
     * get or set the manifest for loading production application
     * @var mixed
     */
    var $manifest;

    var $useRouter = true;
    var $useVueEx = true;
    var $useI18n = true;

    /**
     * for i18n use global resources lang
     * @var true
     */
    var $i18nGlobal = false;
    /**
     * 
     * @var string
     */
    var $i18nVarName = 'i18n';

    private $m_libraries = [];

    public function libraries(){
        return $this->m_libraries;
    }
    /**
     * 
     * @param string|VueLibraryBase $library 
     * @return void 
     */
    public function addLibrary($library){
        (is_null($library) || !$library || !($library instanceof VueLibraryBase))  && igk_die("library not valid");
        $this->m_libraries[] = $library;
    }
    /**
     * clear library
     * @return void 
     */
    public function clearLibrary(){
        $this->m_libraries = [];        
    }
}