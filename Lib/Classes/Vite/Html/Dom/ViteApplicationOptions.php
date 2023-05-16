<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteApplicationOptions.php
// @date: 20230426 11:57:42
namespace igk\js\Vue3\Vite\Html\Dom;

use IGK\Controllers\BaseController;
use igk\js\Vue3\Libraries\i18n\Vuei18n;
use IGK\Resources\R;
use IGKHtmlDoc;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite\Html\Dom
*/
class ViteApplicationOptions{
    /**
     * application entry namespace
     * @var string
     */
    var $entryNamespace = 'igk.js.vue3.app';

    var $useControllerRouter = true;

    var $useControllerLang = true;

    var $useGlobalLangResource = false;

    var $routerConfigFile = "vue-router";

    var $langVarName = Vuei18n::VAR_NAME;

    var $target;

    var $locale;

    var $targetListener;

    /**
     * application dist folder relative to controller asset directory
     * @var string
     */
    var $dist = '/dist';

    /**
     * libraries
     * @var array
     */
    var $libraries = [];

    /**
     * core document
     * @var ?IGKHtmlDoc
     */
    private $m_document;
    /**
     * option controller
     * @var BaseController
     */
    private $m_controller;

    public function __construct(BaseController $controller, IGKHtmlDoc $doc)
    {
        $this->m_document = $doc;
        $this->m_controller = $controller;
    }
    public function getController(){ return $this->m_controller; }
    public function getDocument(){ return $this->m_document; }

    function __debugInfo()
    {
        return [];
    }

    /**
     * 
     * @param mixed $options render options
     * @return null|array 
     */
    public function scriptOptionDefinition($options=null):?array{
        $t = [];
        if ($this->target){
            $t['target'] = $this->target;
        } else if ($this->targetListener){
            $fc = $this->targetListener;
            $t['target'] = $fc();
        }
        $t['locale'] = $this->locale ?? R::GetCurrentLang();       

        return $t;    
    } 
}