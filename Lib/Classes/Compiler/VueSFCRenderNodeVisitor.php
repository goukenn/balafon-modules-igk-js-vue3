<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueRenderNodeVisitor.php
// @date: 20230330 23:59:21
namespace igk\js\Vue3\Compiler;

use IGK\Helper\Activator;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTextTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderVisitTrait;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlTextNode;
use IGK\System\Html\HtmlVisitor;
use IGK\System\IO\StringBuilder;

///<summary></summary>
/**
* use to render on 
* @package igk\js\Vue3\Compiler
*/
class VueSFCRenderNodeVisitor extends HtmlVisitor{
    use VueSFCRenderVisitTrait;
    use VueSFCRenderTextTrait;

    private $m_sb;
    private $m_preservelist = [];
    private $m_options;

    private function __construct(HtmlItemBase $node){
        parent::__construct($node);
        $this->m_sb = new StringBuilder;
        $this->startVisitorListener = [$this, 'beginVisit'];
        $this->endVisitorListener = [$this, 'endVisit'];
    }
    /**
     * generate 
     * @param HtmlItemBase $node 
     * @return ?string
     */
    public  static function GenerateRenderMethod(HtmlItemBase $node, & $options = null):?string{
        $args = $preload = '';
        $visitor = new static($node);
        $options = Activator::CreateFrom($options, VueSFCRenderNodeVisitorOptions::class);
        $visitor->m_options = $options;
        $visitor->visit();
        return sprintf('render(%s){%s return %s}',$args, $preload,$visitor->m_sb.'');
    }

    #region visit
    protected function beginVisit(HtmlItemBase $t, $first_child, $has_childs):?bool{
        $tch = '';
        $ch = '';
        $preserve = count($this->m_preservelist)>0;
        
        $s = new StringBuilder;
        if (!$first_child){
            $tch = ',';
        }
        if ($t instanceof HtmlTextNode){
            $content = $t->getContent();
            if (!empty($content)){
                self::AddLib($this->m_options , VueConstants::JS_VUE_LIB, VueConstants::VUE_COMPONENT_TEXT);
                $this->m_sb->append($tch.VueConstants::VUE_METHOD_RENDER.self::GetTextDefinition($content));
            }
            return null;
        }

        $tagname = $t->getTagName();
        $canrender = $t->getCanRenderTag();
        if (empty($tagname) || !$canrender){
            return null;
        }
        $attrs = $t->getAttributes()->to_array();
        $s->append(VueConstants::VUE_METHOD_RENDER."(");        
        $s->append(igk_str_surround($tagname,"'"));
        $ch = ',';
        if($attrs){
            if ($preserve){

            } else {

            }
            $ch=',';
        }

        if ($has_childs){
            $s->append($ch."[");
        }
        $this->m_sb->append($tch.$s);
        return true;
    }
    protected function endVisit(HtmlItemBase $t, bool $has_childs){
        
        if ($this->m_preservelist){

        }
        if ($has_childs){
            $this->m_sb->rtrim(',');
            $this->m_sb->append("]");
        }
        $this->m_sb->append(")");

    }
    #endregion

    protected static function AddLib(VueSFCRenderNodeVisitorOptions $options, $name, $lib){
        if (!isset($options->libraries[$name])){
            $options->libraries[$name] = [];
        }
        $options->libraries[$name][$lib] = 1;
    }
    protected static function GetTextDefinition($content){
        return sprintf('(%s,%s)', VueConstants::VUE_COMPONENT_TEXT, $content);
    }
}