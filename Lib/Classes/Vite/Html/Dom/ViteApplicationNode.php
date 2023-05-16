<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteApplicationNode.php
// @date: 20230426 08:27:53
namespace igk\js\Vue3\Vite\Html\Dom;

use IGK\Controllers\BaseController;
use IGK\Helper\ViewHelper;
use igk\js\Vue3\Libraries\i18n\Vuei18n;
use igk\js\Vue3\Libraries\VueRouter;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\IO\Path;
use IGKException;
use IGKHtmlDoc;
use ReflectionException;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite\Html\Dom
*/
class ViteApplicationNode extends ViteNodeBase{
   

    private $m_options;

    /**
     * store application shared options
     * @var mixed
     */
    private static $sm_shared_options;
    private static $sm_topNode;
    /**
     * 
     * @param mixed $id 
     * @param mixed $ctrl 
     * @param string $tag 
     * @return void 
     */
    public function __construct(string $id, ?ViteApplicationOptions $options=null, $tag='div'){
        parent::__construct($tag);
        $this['id'] = $id; 
        $this->m_options = $options ?? self::$sm_shared_options ?? 
        ((($ctrl = ViewHelper::CurrentCtrl()) && ($doc = ViewHelper::CurrentDocument())) ?
        new ViteApplicationOptions($ctrl, $doc) : igk_die("can't initialize application"));


        $this->m_options->targetListener = function(){
            return '#'.$this['id'];
        };
    }
    public function getRenderedChilds($options = null)
    {
        $tab = parent::getRenderedChilds($options);
        $tab[] = new ViteScriptRenderer($this);
        $tab[] = new ViteNoScriptRenderer($this);
        return $tab;
    }
    /**
     * get host controller
     * @return mixed 
     */
    public function getController(){
        return $this->m_controller;
    }
    /**
     * get application options
     * @return ViteApplicationOptions 
     */
    public function getAppOptions(){
        return $this->m_options;
    }
    /**
     * init core vite application entry point. must be call on the view 
     * @param ViteApplicationOptions $options setting options
     * @param HtmlNode $targetNode view node
     * @return bool 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     * @throws EnvironmentArrayException 
     */
    public static function InitDoc(ViteApplicationOptions $options, HtmlNode $targetNode):bool{
        if (self::$sm_shared_options)
        {
            return false;
        }
        $doc = $options->getDocument();
        $ctrl = $options->getController();
        $ctrl->exposeAssets();
        if ($options->useControllerRouter){
            $router = VueRouter::InitDoc($doc, $ctrl, $options->routerConfigFile );
            $options->libraries[] = $router;
            $router->options = $options;
        }
        if ($options->useControllerLang){
            $i18n = Vuei18n::InitDoc($doc, $ctrl, $options->useGlobalLangResource, $options->langVarName);
            $options->libraries[] = $i18n;
            $i18n->options = $options;
        } 
        self::$sm_topNode = $targetNode;
        self::$sm_shared_options = $options;         

        $targetNode->jsscript_options($options->entryNamespace, function($renderOptions)use($options){ 
            return $options->scriptOptionDefinition($renderOptions);
        });
        $asset = $ctrl->getAssetsDir();
        if (!vue3_bind_manifest($doc, Path::Combine($asset, $options->dist), 'module', true)){
            igk_die("no manifest provided");
        }
        return true;
    }
    /**
     * use in single view
     * @return mixed 
     */
    public static function GetSharedTargetNode(){
        return self::$sm_topNode;
    }
}