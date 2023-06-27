<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCFile.php
// @date: 20230418 13:09:40
namespace igk\js\Vue3\System\IO;

use IGK\Helper\IO;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Compiler\VueSFCCompilerOptions;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\System\Html\Dom\VueSFCTemplate;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlContext;
use IGK\System\IO\StringBuilder;

///<summary></summary>
/**
 * represent a single file component data
 * @package igk\js\Vue3\System\IO
 */
class VueSFCFile extends HtmlNode
{
    private $m_template;
    private $m_scripts;
    private $m_styles;
    const ALLOWED_NODES = ['script', 'template', 'style'];
    function getCanRenderTag()
    {
        return false;
    }
    public function __construct()
    {
        parent::__construct();
    }
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * load scf component
     * @param string $file 
     * @return ?static 
     */
    // public static function load(string $file){
    //     return null;
    // }
    public function template()
    {
        if (is_null($this->m_template)) {
            $this->add(new VueSFCTemplate());
        }
        return $this->m_template;
    }
    public function script()
    {
        $n = VueComponent::LoadingNodeCreator('script');
        $this->add($n);
        return $n;
    }
    public function style()
    {
        $n = VueComponent::LoadingNodeCreator('style');
        $this->add($n);
        return $n;
    }
    public function clearChilds()
    {
        $this->m_template = null;
        $this->m_scripts = null;
        $this->m_styles = null;
        parent::clearChilds();
    }
    public function getScriptCount()
    {
        return count($this->m_scripts);
    }
    public function getStyleCount()
    {
        return count($this->m_styles);
    }
    /**
     * get rendergin children
     * @param mixed $options 
     * @return array 
     */
    public function getRenderedChilds($options = null)
    {
        $tab = [];
        if ($this->m_template) {
            $tab[] = $this->m_template;
        }
        if ($this->m_scripts) {
            $tab = array_merge($tab, $this->m_scripts);
        }
        if ($this->m_styles) {
            $tab = array_merge($tab, $this->m_styles);
        }
        return $tab;
    }

    public static function CreateWebNode($n, $attributes = null, $indexOrArgs = null)
    {
        if (in_array($n, self::ALLOWED_NODES)) {
            return parent::CreateWebNode($n, $attributes, $indexOrArgs);
        }
        igk_die(sprintf("[%s] not allowed - ", $n));
    }
    public static function LoadingNodeCreator(string $name, ?array $param = null, $currentNode = null)
    {
        if ($currentNode) {
            return $currentNode::LoadingNodeCreator($name, $param, $currentNode);
        }
        if (in_array($name, self::ALLOWED_NODES)) {
            $fc = '_Create' . ucfirst($name) . 'SFCComponent';
            return call_user_func_array([static::class, $fc], $param ?? []);
        }
        igk_die("not allowed");
    }

    protected static function _CreateTemplateSFCComponent(?array $param = null)
    {
        return new VueSFCTemplate();
    }
    protected static function _CreateStyleSFCComponent()
    {
        return VueComponent::LoadingNodeCreator('style');
    }
    protected static function _CreateScriptSFCComponent()
    {
        return VueComponent::LoadingNodeCreator('script');
    }

    function _add($n, bool $force = false): bool
    {
        if (!($n instanceof VueComponent)) {
            return false;
        }
        $tn = strtolower($n->getTagName());
        if (!in_array($tn, self::ALLOWED_NODES)) {
            igk_die("not allowed node");
        }
        if (parent::_add($n, $force)) {

            switch ($tn) {
                case 'template':
                    $this->m_template = $n;
                    break;
                case 'style':
                    $this->m_styles[] = $n;
                    break;
                case 'script':
                    $this->m_scripts[] = $n;
                    break;
            }
            return true;
        }
        return false;
    }

    /**
     * compile this view and return definition string
     * @return void 
     */
    public function compile(?VueSFCCompilerOptions $options = null)
    {
        $scopedId = '';
        $data = [];
        $sb = new StringBuilder;
        $lib = new StringBuilder;
        $scoped = false;
        $options = $options ?? new VueSFCCompilerOptions;

        if ($this->m_scripts) {
            VueSFCUtility::MergeSetupScript($data, $this->m_scripts, $options);
        }
        if ($this->m_styles) {
            $tdbata = [];
            VueSFCUtility::MergeStyleScript($tdbata, $this->m_styles, $scopedId, $options, $scoped);
            $src = sprintf('(()=>{let s=document.createElement("style");return s.innerHTML=`%s`,document.head.append(s),s;})()', implode("\n", $tdbata));
            $sb->appendLine($src);
        }
        $key =  VueSFCUtility::INIT_JS_KEY;
        $init = igk_getv($data, $key);
        unset($data[$key]);


        $global_import = igk_getv($data, VueSFCUtility::INIT_BS_KEY);
        unset($data[VueSFCUtility::INIT_BS_KEY]);
        if ($options) {
            // $sb->appendLine("/* declare body */");
            //$sb->appendLine("import 'virtual:balafonjs';");
            // $sb->appendLine("const { Vue } = window;");
            // $sb->appendLine("const Vue = window.Vue;");
        }
        if ($init) {
            //$sb->appendLine(implode("\n", $init));
        }
        if ($global_import) {
            $sb->appendLine(implode("\n", $global_import));
        }

        if ($t = $this->m_template) {
            if ($scoped) {
                foreach ($t->getElementsByTagName('*') as $lm) {
                    $lm->activate($scopedId);
                }
            }
            $options->test = true;
            $render = VueSFCCompiler::ConvertToVueRenderMethod($t, $options);
            if ($render) {
                array_unshift($data, $render);
            }
            $lib->appendLine('import * as Vue from \'vue\';');
            if ($options->defineGlobal) {
                $sb->append(implode("", $options->defineGlobal));
            }
        }

        if (!$lib->isEmpty()) {
            $sb->prependLine(trim($lib . ''));
        }
        $sb->appendLine(sprintf("export default {%s}", implode(",", $data)));

        return $sb . '';
    }
    public function loadFile(string $file, $options = null, $args = null)
    {
        if (!is_file($file))
            return false;
        $content = IO::ReadAllText($file);
        if (empty($content)) {
            return $this;
        }
        $op = null;
        if (is_string($options)) {
            $op = ["Context" => $options];
        } else {
            $op = (object)$options;
        }
        $options = igk_create_filterobject($op, ["stripComment" => 0]);
        if ($options->stripComment) {
            $content = igk_html_strip_comment($content);
        }
        if (is_array($args))
            $args = (object)$args;
        else {
            $args = $options;
        }
        $args->Context = HtmlContext::Html;
        $args->noInterpolation = true;
        return $this->load($content, $args);
    }
}
