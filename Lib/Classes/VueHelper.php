<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueHelper.php
// @date: 20220813 14:28:40
// @desc: represent vue helper

namespace igk\js\Vue3;

use IGK\Controllers\BaseController;
use IGK\Helper\IO;
use IGK\Helper\StringUtility;
use IGK\Helper\ViewHelper;
use igk\js\common\JSExpression;
use igk\js\common\JSExpressionObjectResult;
use igk\js\Vue3\Controllers\MacrosExtensions;
use igk\js\Vue3\JS\VueLazyImportExpression;
use igk\js\Vue3\JS\VueLazyLoadExpression;
use igk\js\Vue3\Libraries\VueFileLibrary;
use igk\js\Vue3\Libraries\VueLibrary;
use igk\js\Vue3\Libraries\VueLibraryBase;
use igk\js\Vue3\Libraries\VueRouter;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlContext;
use IGK\System\Html\HtmlReader;
use IGK\System\Html\HtmlUtils;
use IGK\System\IO\Path;
use IGK\System\IO\StringBuilder;
use IGK\System\Regex\Replacement;
use IGKException;
use ReflectionException;

/**
 * vue class helper 
 * @package igk\js\Vue3
 */
abstract class VueHelper
{
    /**
     * 
     * @param string $content 
     * @param null|array $args 
     * @return never 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public static function LoadContentsAsTemplate(string $content, ?array $args=null): ?string{
        igk_die(__METHOD__ . ". not implement : ".$content);
    }
    /**
     * resolve components 
     * @return array list of detected component in directory 
     */
    public static function GetComponents(string $component_dir, string $supports="vue|js"){
        $tab = [];
        $g = Replacement::RegexExpressionFromString('('.$supports.')$');
        $dirln = strlen($component_dir);
        foreach(IO::GetFiles($component_dir, $g, true) as $file){
            $ext = igk_io_path_ext($file);
            $key = igk_io_basenamewithoutext($file);
            $dirname = dirname($file);
            if ($s = substr($dirname, $dirln+1)){
                $key = "./".$s."/".$key;
            }
            $tab[] = $key.'|'. $ext;
        }
        return $tab;
    }

   
        
    public static function InitRoute($doc, BaseController $ctrl=null, ?string $name=null){
        return VueRouter::InitDoc($doc, $ctrl, $name);
    }
    /**
     * use script file as library to inject to view app core definition before createAppMethod 
     * @param string $file 
     * @return null|VueLibraryBase 
     */
    public static function UseScriptLibrary(string $file):?VueLibraryBase{
        return new VueFileLibrary($file); 
    }
    public static function IncRouteOptions(string $path, $args=[], $routeOptions =[],  BaseController $ctrl=null){
        $defs = [];
        $defs['template']= ViewHelper::Article($path, $args);
        if ($src = self::IncControllerArticleSetupScript($path. VueConstants::VUE_JS_SETUP_EXT)){
            $defs[] = $src;
        }
        if ($routeOptions){
            $defs = array_merge($defs, $routeOptions); 
        }
        return $defs;
    }
    /**
     * include controller article vue3 inline setup script
     * @param string $path default is a .vue3-setup.js file
     * @param BaseController|null $ctrl 
     * @return string|void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public static function IncControllerArticleSetupScript(string $path, BaseController $ctrl = null ){
        if (is_null($ctrl) && is_null($ctrl = ViewHelper::CurrentCtrl())) {
            igk_die("controller required.");
        }
        $cf = Path::Combine( $ctrl->getArticlesDir(), $path);
        foreach(['', VueConstants::VUE_JS_SETUP_EXT,'.vue.b', '.vue', '.jsx', '.js'] as $c){
            if (file_exists($f = $cf.$c)){
                return VueScript::Include($f, $c? trim($c) : 'js');   
            }
        }
    }
    public static function LayoutImport(string $file, BaseController $ctrl = null)
    {
        if (is_null($ctrl) && is_null($ctrl = ViewHelper::CurrentCtrl())) {
            igk_die("controller required.");
        }
        return MacrosExtensions::vueLayoutImport($ctrl, $file);
    }
    /**
     * load vue js
     * @param string $file 
     * @return string 
     */
    public function IncludeVue(string $file)
    {
    }
    /**
     * create a lazy load expression with uri
     * @param $uri uri to get the definied view
     * @return VueLazyLoadExpression 
     */
    public static function LazyLoad(string $uri): VueLazyLoadExpression
    {
        $exp = new VueLazyLoadExpression;
        $exp->module = $uri;
        return $exp;
    }
    /**
     * create a vue file lazy import
     * @param string $path path to the view file
     * @return VueLazyImportExpression 
     * @exemple use in route : $route->addRoute('/', VueHelper::LazyVueImport(path_to_vue_file))
     */
    public static function LazyVueImport(string $path, array $options = null)
    {
        if (!is_file($path)) {
            if (!is_file($path .= VueConstants::FILE_EXT)) {
                igk_die(sprintf("path [%s] not found", $path));
            }
        }
        $src = file_get_contents($path);
        $exp = new VueLazyImportExpression;
        $exp->data = $src;
        $exp->options = $options;
        return $exp;
    }
    public static function LoadScript(string $jsfile)
    {

        $src = file_get_contents($jsfile);
        // dectect function expression
        // \s*\((.)+;\s*$
        if (preg_match("#\s*\(\s*function\s*\(.+((;)?)\s*$#m", $src)) {
            $r = new JSExpressionObjectResult();
            $r->data = rtrim($src, "; ");
            return $r;
        }
        return $src;
    }

    /**
     * include server .vue file
     * @param string $file .vue file that implement SFC
     * @return null|string js expression string
     * @throws IGKException 
     */
    public static function IncludeSFC(string $file): ?string
    {
        if (!is_file($file)) {
            //resolv file 
            if (!is_file($file .= VueConstants::FILE_EXT)) {
                return null;
            }
        }
        $data = HtmlReader::LoadFile($file, HtmlContext::XML);
        $template = igk_getv($data->getElementsByTagName("template"), 0);
        $script = igk_getv($data->getElementsByTagName("script"), 0);
        $style = igk_getv($data->getElementsByTagName("style"), 0);
        $src = $script ? $script->getInnerHtml() : null;
        if (!empty($_temp = $template ? $template->getInnerHtml() : "")) {
            $_temp = JSExpression::Create("[\"" . implode("\",\"", array_filter(explode("\n", $_temp))) . "\"].join('')");
        }
        if (!empty($src)) {
            $src = self::GetArrayExpression($src);
        }
        $istyle = null;
        if ($style){
            $istyle = 'ns_igk.system.module.vue3.inject_style(`'.$style.'`);';
        }
        return ":()=> { let p = new Promise((resolve,reject) => { ns_igk.system.modules.import(" . $src .
            ").then((d)=>{ resolve({template:" .
            $_temp . ", ...d.default});".$istyle."}); } ); return p;}";
    }
    public static function GetArrayExpression(string $data): string
    {
        $src = "[\"" . implode("\",\"", array_filter(array_map(function ($n) {
            return trim(addslashes($n));
        }, explode("\n", $data)))) . "\"].join('')";
        return $src;
    }
    /**
     * 
     * @param string $file 
     * @return callable
     * @throws IGKException 
     */
    public static function Import(string $file, $options = [], $document = null): callable
    {
        if (!is_file($file)) {
            //resolv file 
            if (!is_file($file .= VueConstants::FILE_EXT)) {
                return null;
            }
        }
        $data = HtmlReader::LoadFile($file, HtmlContext::XML);
        $template = igk_getv($data->getElementsByTagName("template"), 0);
        $scripts =  $data->getElementsByTagName("script");
        $styles =  $data->getElementsByTagName("style");
        return function ($a) use ($template, $scripts, $styles, $options) {
            $project_dir = $options["project_dir"];
            $resolv_uri = $options["resolv_uri"];
            $a->loop($template->getChilds()->to_array(), function ($a, $m) {
                $a->add($m);
            });

            if ($styles) {
                $doc = $document ?? ViewHelper::CurrentDocument();
                $doc->body()->add($styles[0]); 
            }
            if ($scripts) {
                $src = $scripts[0]->getInnerHtml();
                $ln = preg_match_all("/import (?P<expression>(.)+)( as (?P<alias>(.)+))? from\s*(?P<path>('|\"))/i", $src, $tab, PREG_OFFSET_CAPTURE);
                //igk_html_pre($tab);
                $lstr = "";
                $e_pos = 0;
                $sb = new StringBuilder;
                $v_texpr = [];
                for ($i = 0; $i < $ln; $i++) {
                    $s_pos = $tab[$i][0][1];
                    $r =  $tab["path"][$i];
                    $expr =  $tab["expression"][$i][0];
                    $sep = $r[0];
                    $offset = $r[1];
                    $g = igk_str_read_brank($src, $offset, $sep, $sep,null, 0,1);
                    $lstr .= substr($src, $e_pos, $s_pos);
                    $e_pos = $r[1] + strlen($g);

                    if (!empty($expr)){                       
                        $tg = trim($g, $sep);
                        $sb->appendLine("const ".$expr." = await igk.js.vue3.loadScript(\"".$resolv_uri."/$tg\");");
                        $v_texpr[] = $expr;
                    }
                }
                $src = ltrim(substr($src, $e_pos), "; ");
                if ($v_texpr){
                    foreach ($v_texpr as $v_v) { 
                        if (($lw = strtolower($v_v)) && ($lw != $v_v)){
                            $src = str_replace($v_v, $lw.": ".$v_v, $src); 
                        }
                    }
                }
                $lstr = $sb.$lstr.$src;                
                $defs = "const _d = await igk.js.vue3.import(" . self::GetArrayExpression($lstr) . ");"; 
                $a->setDefs($defs);
                $a->setData([":..._d.default"]);
            }
        };
    }
}
