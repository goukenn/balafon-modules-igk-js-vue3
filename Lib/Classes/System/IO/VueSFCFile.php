<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCFile.php
// @date: 20230418 13:09:40
namespace igk\js\Vue3\System\IO;

use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\System\Html\Dom\VueSFCTemplate;
use IGK\System\Html\Dom\HtmlNode;

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
        $n = new VueComponent('script');
        $this->add($n);
        return $n;
    }
    public function style()
    {
        $n = new VueComponent('style');
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
    protected static function _CreateScriptSFCComponent(?array $param = null)
    {
        return new VueComponent('script');
    }
    protected static function _CreateStyleSFCComponent(?array $param = null)
    {
        return new VueComponent('style');
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
}
