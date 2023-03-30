<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCCompiler.php
// @date: 20230301 18:54:08
namespace igk\js\Vue3\Compiler;

use IGK\System\Html\Css\CssParser;
use IGK\System\Html\Dom\HtmlNode;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
class VueSFCCompiler{
    const CSS_STYLE_ATTR = 1;
    const CSS_STYLE_CLASS = 2;
    var $template;
    var $script;
    var $styles;

    var $cssStyle = self::CSS_STYLE_ATTR;
    /**
     * identifier 
     * @var mixed
     */
    var $id = null;
    /**
     * compiler options
     * @var mixed
     */
    private $m_options = null;


    private static function _GetLitteralResult($src)
    {  
        $def = SFCScriptSetup::DetectVarResponse($src);  
        if (!$def) {
            return $src;
        }
        return rtrim($src, ';').'; return {' . implode(",", $def) . '}';
    }
    /**
     * compile file 
     * @param string $file 
     * @param ?array $options compiler options
     * @return null|static 
     * 
     * @throws IGKException 
     */
    public static function Compile(string $file, ?array $options = null)
    {
        if (!($src = file_get_contents($file))) {
            return null;
        }
        $g = igk_create_node('div');
        if (!$g->load($src)) {
            return null;
        }
        $result = new static;
        $result->m_options = $options;
        $result->id = igk_css_str2class_name(basename($file) . '- ' . self::GenClassIdentifier($result, $options));
        array_map([$result, "mapTemplate"], $g->getElementsByTagName('template'));
        array_map([$result, "mapScript"], $g->getElementsByTagName('script'));
        array_map([$result, "mapStyle"], $g->getElementsByTagName('style'));
        return $result;
    }
    public static function GenClassIdentifier($compiler, $options)
    {
        return hash("crc32b", "vue-id-" . time());
    }
    public function mapTemplate($a)
    {
        $id = $this->id;
        if ($id) {
            foreach ($a->getChilds() as $c) {
                if ($c instanceof HtmlNode) {
                    $c["class"] = $id;
                    $c->activate($id);
                }
            }
        }
        $this->template = $a->getInnerHtml();
    }
    public static function ParseCssStyleToCss($src, ?string $scoped_id=null){
        $tab = CssParser::Parse($src);
        $id = $scoped_id;
        if ($id){
            $rtab = $tab->to_array();
            $src = implode("", array_map(function ($i, $key) use ($id) {
                $value = implode("", array_map(function ($s, $k) {
                    return implode(":", [$k, $s]) . ";";
                }, $i, array_keys($i)));

                // + | separator from level
                $id_key = $this->processCssSelector($id, $key);                
                return $id_key. '{' . $value . '}';
            }, $rtab, array_keys($rtab)));
        } else { 
                $src = $tab->render();
          
        }
        return $src;
    }
    public function mapStyle($a)
    {
        $lang = igk_getv($a, "type", "css");
        $scoped = igk_getv($a, "scoped");
        $src = $a->getInnerHtml();
        $id = $scoped ? $this->id : null;
        $src = self::ParseCssStyleToCss($src, $id);        
        if (is_null($this->styles)) {
            $this->styles = "";
        }
        $this->styles .= $src;
    }
    protected function processCssSelector($id, $key){
        $tab = explode(',', $key);
        $ckey = "";
        $sep = '';
        while(count($tab)>0){
            $q = array_shift($tab);
            $d = array_filter(explode(" ", $q));
            $v_tid = '';
            if ($this->cssStyle== self::CSS_STYLE_ATTR){
                $v_tid = ".".$id."[".$id."] ".$d[0];
            }else{
                $v_tid = ".".$id.''.$d[0];
            }
            $ckey .= $sep.implode(" ", array_filter(array_merge([$v_tid], array_slice($d, 1))));
            $sep = ',';
        }
        return $ckey;
    }
    public function mapScript($a)
    {
        $lang = igk_getv($a, "type", "javascript");
        $is_setup = igk_getv($a, "setup");
        $src = $a->getInnerHtml();

        if ($is_setup) {
              $src = self::_GetLitteralResult($src); 
            // + | ---------------------------------------------
            // + | Vue build : direct object return to avoid this in context
            $src = sprintf(<<<'JS'
(function(){ return {setup($props, $ctx){ %s }};}).apply()
JS, $src);
        }

        if (!is_null($this->script)) {
            if (!is_array($this->script)) {
                $this->script = [$this->script];
            }
            $this->script[] = $src;
        } else {
            $this->script = $src;
        }
    }
    public function def()
    {
        $t = [];
        if ($this->template) {
            $t['template'] = $this->template;
        }
        if ($this->script) {
            $t[] = '...' . $this->script;
        }
        return $t;
    }
    public static function GetLitteralSetupScript(string $setup, ?string $inline){
        $src = self::_GetLitteralResult($setup); 
        // + | direct object return to avoid this in context
        if ($inline)
            $inline = ','.$inline;
        $src = sprintf(<<<'JS'
(function(){ return {setup($props, $ctx){ %s }%s};}).apply()
JS, $src, $inline);
    }
}