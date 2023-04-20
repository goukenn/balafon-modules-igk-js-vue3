<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueApp.php
// @date: 20230419 07:18:08
namespace igk\js\Vue3;

use IGK\Controllers\BaseController;
use IGK\Helper\ViewHelper;
use igk\js\Vue3\Libraries\i18n\Vuei18n;
use igk\js\Vue3\Libraries\VueRouter;
use IGKHtmlDoc;
use IGKResourceUriResolver;

///<summary></summary>
/**
* 
* @package igk\js\Vue3
*/
class VueApp{
    /**
     * init view application
     * @param BaseController $controller 
     * @param null|IGKHtmlDoc $doc 
     * @param null|VueAppOptions $options 
     * @return void 
     */
    public static function Init(BaseController $controller, ?IGKHtmlDoc $doc=null, ?VueAppOptions $options=null){
        $doc = $doc ?? ViewHelper::CurrentDocument();
        $options = $options ?? new VueAppOptions();
        $controller->exposeAssets();
        if ($options->useRouter){
            $router = VueRouter::InitDoc($doc, $controller, $options->routerName);
            $options->addLibrary($router);
        }
        if ($options->useI18n){
            $i18n = Vuei18n::InitDoc($doc, $controller, $options->i18nGlobal, $options->i18nVarName);
            $options->addLibrary($i18n);
        }
        $doc->getHead()->addScript()->Content = 'igk.system.createNS("igk.js.vue3.options",{}); console.log("ok");';
        if ($options->manifest){
            $dist = dirname($options->manifest);
            $data = json_decode(file_get_contents($options->manifest));
            foreach($data as $v){
                if (igk_getv($v, 'isEntry')){
                    $file = $v->file;
                    $uri = $dist."/".$file;
                    $script = IGKResourceUriResolver::getInstance()->resolveOnly($uri);
//                     $doc->getHead()->addScript()
//                     ->setAttribute('type','module')
//                     ->Content = <<<JS
// import * as App from '{$script}'; 
// App.default.App.mount('#app-core'); 
// console.log(App);
// JS;

                    $doc->addTempScript($uri)
                    ->setAttribute('type','module')
                    ->activate('defer');
                    if ($css = igk_conf_get($v, "css")){
                        foreach($css as $l){
                            $doc->addTempStyle($dist."/".$l);
                        }
                    }
                }
            }
        }

        return $options;
    }
}