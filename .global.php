<?php
// @author: C.A.D. BONDJE DOUE
// @file: %modules%/igk/js/Vue3/.global.php
// @date: 20220405 14:40:16

// + module entry file 

use IGK\Controllers\BaseController;
use IGK\Helper\StringUtility;
use igk\js\common\JSExpression;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Components\VueApplicationNode;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Components\VueComponentNode;
use igk\js\Vue3\Components\VueRouterLink;
use igk\js\Vue3\Components\VueTemplateScriptNode;
use IGK\System\Html\Dom\HtmlNoTagNode;

/**
 * bind sfc core application
 * @param BaseController $ctrl 
 * @param string $id 
 * @param string $sfc_file 
 * @return HtmlNoTagNode 
 */
function igk_html_node_vue_sfc_app(BaseController $ctrl, string $id, string $sfc_file)
{

    $compile = VueSFCCompiler::Compile($sfc_file) ?? die("failed to compile : " . $sfc_file);
    $t = igk_html_node_vue_app($id, $compile->def());
    if ($compile->styles) {
        $t->script()->Content = sprintf(<<<'JS'
(function(){
    let style = document.createElement('style');
    style.innerHTML = "%s"; style.setAttribute('id', "%s");
    document.getElementsByTagName('body')[0].appendChild(style);
    igk.getCurrentScript().remove();
})();    
JS, $compile->styles, $compile->id);
    }
    return $t;
}
/**
 * bind manifest 
 * @param mixed $doc HtmlDoc
 * @param mixed $assets assets directory 
 * @return bool 
 * @throws IGKException 
 */
function vue3_bind_manifest($doc, $assets)
{
    if (!file_exists($f = $assets . "/manifest.json")) {
        return false;
    }
    $rp = json_decode(file_get_contents($f));
    if ($vendor = igk_getv($rp, "chunk-vendors.js")) {
        $doc->addTempScript($assets . "/" . $vendor)->activate("defer");
    }
    if ($app = igk_getv($rp, "app.js")) {
        $doc->addTempScript($assets . "/" . $app)->activate("defer");
    }
    $styling = [];
    if ($app_css = igk_getv($rp, 'chunk-vendors.css')) {
        $styling[] = $app_css;
    }
    if ($app_css = igk_getv($rp, 'app.css')) {
        $styling[] = $app_css;
    }
    foreach ($styling as $f) {
        $doc->addTempStyle($assets . "/" . $f);
    }
    return true;
}

/**
 * create a vue3 application node
 * @param string $id
 * @param ?array|string|JSExpression data options definition or JExpression data
 * @return igk\js\Vue3\Components\VueApplicationNode 
 */
function igk_html_node_vue_app(string $id, $data = null)
{
    $n = new VueApplicationNode();
    $n->setAttribute('id', $id);
    if ($data) {
        if (is_string($data)) {
            $data = trim($data);
            // remove {
            $data = StringUtility::RemoveQuote($data, '{', '}');
            $data = [JSExpression::Create($data)];
        }
        $n->setData($data);
    }
    return $n;
}
function igk_html_node_vue_item()
{
    $c = new VueComponentNode('');
    return $c;
}

/**
 * create a vue template node
 */
function igk_html_node_vue_scripttemplate(string $id = null)
{
    $n = new VueTemplateScriptNode();
    $n->setAttribute('id', $id);
    return $n;
}

function igk_html_node_vue_xtemplate(string $id)
{
    $n = igk_create_node('script');
    $n['type'] = 'text/x-template';
    $n['id'] = $id;
    return $n;
}
if (!function_exists('igk_html_node_vue_component')) {
    /**
     * create a vue component
     * @param string $tagname 
     * @return VueComponent 
     */
    function igk_html_node_vue_component(string $tagname = 'div')
    {
        $n = new VueComponent($tagname);
        return $n;
    }
}

if (!function_exists('igk_html_node_vue_router_link')) {
    function igk_html_node_vue_router_link($to = null)
    {
        $n = new VueRouterLink();
        $to && $n->setAttribute('to', $to);
        return $n;
    }
}



if (!function_exists('igk_html_node_vue_clone')) {
    function igk_html_node_vue_clone($to = null)
    {
        $n =  igk_create_node('div');
        $n["igk-data"] = $to;
        return $n;
    }
}