<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueRenderNodeVisitor.php
// @date: 20230330 23:59:21
namespace igk\js\Vue3\Compiler;

use IGK\Helper\Activator;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderBuildInComponentTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderGetKeyValueTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTextTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderVisitTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderResolveComponentTrait;
use igk\js\Vue3\VueConstants;
use IGK\System\ArrayMapKeyValue;
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
    use VueSFCRenderGetKeyValueTrait;
    use VueSFCRenderBuildInComponentTrait;
    use VueSFCRenderResolveComponentTrait;
    

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
     * transform node to render method 
     * @param HtmlItemBase $node 
     * @return ?string
     */
    public  static function GenerateRenderMethod(HtmlItemBase $node, & $options = null):?string{
        $args = $preload = '';
        $visitor = new static($node);
        $is_local = is_null($options);
        $options = Activator::CreateFrom($options, VueSFCRenderNodeVisitorOptions::class);
        $visitor->m_options = $options;
        self::AddLib($options, VueConstants::VUE_METHOD_RENDER, VueConstants::JS_VUE_LIB );

        $options->components['RouterView'] = 1;
        $options->components['RouterLink'] = 1;
        
        $visitor->visit();
        if ($is_local){
            if($options->libraries){
                $preload .= self::_GetLitteralLibrary($options->libraries);
            }
            if ($options->defineGlobal){
                $g = $options->defineGlobal;
                ksort($g);
                $preload.= implode('', $g);
            }
            if ($options->defineArgs){
                $g = $options->defineArgs;
                ksort($g);
                $preload.= implode('', $g);
            }
        }
        return sprintf('render(%s){%sreturn %s}',$args, $preload,$visitor->m_sb.'');
    }

    #region visit
    protected function beginVisit(HtmlItemBase $t, $first_child, $has_childs):?bool{
        $tch = '';
        $ch = '';
        $preserve = count($this->m_preservelist)>0;
        $content = $t->getContent() ?? '';
        $context = null; // context options to get value
        $inner_content = null;
        
        $s = new StringBuilder;
        if (!$first_child){
            $tch = ',';
        }
        if ($t instanceof HtmlTextNode){
            if (!empty($content)){
               
                    self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                    $this->m_sb->append($tch.VueConstants::VUE_METHOD_RENDER.self::GetTextDefinition($content));
               
            }
            return null;
        }

        $tagname = $t->getTagName();
        $canrender = $t->getCanRenderTag();
        $v_slot = false;
        if (empty($tagname) || !$canrender){
            return null;
        }
        $attrs = $t->getAttributes()->to_array();
        $s->append(VueConstants::VUE_METHOD_RENDER."(");    
        
        //treat special tag before rendering
        if ($this->isBuildInComponent($tagname)){
            $s->append($this->resolveBuildInComponent($tagname, $v_slot));            
        } else if ($this->isResolvableComponent($tagname)){
            $s->append($this->resolveComponent($tagname, $v_slot));            
        }
        else{        
            $s->append(igk_str_surround($tagname,"'"));
        }
        $ch = ',';
        if($attrs){
            if (!$preserve && isset($attrs['v-pre'])){
                array_unshift($this->m_preservelist, $t);
                unset($attrs['v-pre']);
            }
            if ($preserve){
                $s->append(sprintf('{%s}', ArrayMapKeyValue::Map([self::class, 'LeaveAttribute'], $attrs)));
            } else {
                $s->append($ch."{");
                $ch = '';
                foreach($attrs as $k=>$v){
                    $s->append($ch.self::_GetKey($k).":".self::_GetValue($v, $context));
                    $ch = ',';
                }
                if ($content){
                    $s->append($ch.'innerHTML:'.self::_GetValue($content, $context));
                    $content= '';
                }
                $s->append("}");
            }
            $ch=',';
        }else {
            if ($has_childs){
                $inner_content = $content;
                $content = '';
            }
        }
        if (!empty(trim($content))){
            $content = self::_GetValue($content);
            $s->append($ch.$content);
            $ch = ',';
        }

        if ($has_childs){
            $s->append($ch."[");
            if ($inner_content){
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                $this->m_sb->append($tch.VueConstants::VUE_METHOD_RENDER.self::GetTextDefinition($inner_content).",");
            }
        }
        $this->m_sb->append($tch.$s);
        return true;
    }
    protected function endVisit(HtmlItemBase $t, bool $has_childs){
        
        if ($this->m_preservelist){
            if ($this->m_preservelist[0] === $t){
                array_shift($this->m_preservelist);
            }
        }
        if ($has_childs){
            $this->m_sb->rtrim(',');
            $this->m_sb->append("]");
        }
        $this->m_sb->append(")");

    }
    #endregion

    protected static function AddLib(VueSFCRenderNodeVisitorOptions $options, string $name, string $lib = VueConstants::JS_VUE_LIB){
        if (!isset($options->libraries[$lib])){
            $options->libraries[$lib] = [];
        }
        $options->libraries[$lib][$name] = 1;
    }
    protected static function GetTextDefinition($content){
        return sprintf('(%s,%s)', VueConstants::VUE_COMPONENT_TEXT, $content);
    }

    protected static function _GetLitteralLibrary($lib, $type='const'){
        $s = "";
        $ch = '';
        $o = '';
        $ln = 0;
        ksort($lib, SORT_FLAG_CASE| SORT_NATURAL);
        foreach($lib as $k=>$v){
            ksort($v, SORT_FLAG_CASE| SORT_NATURAL);
            foreach($v as $m=>$a){                
                if (!empty($s) && $ln){
                    $ch .= ',';
                }
                $s.=$ch;
                $ln = strlen($s);
                (is_numeric($m)) && igk_die("not valid definition");
                if (is_string($a)){
                    $s.= $m.":".$a;
                }
                else 
                    $s .= $m;
                $ln = strlen($s)-$ln;
                $ch = '';                
            }
            if (!empty($s)){
                $s = $type.'{'.$s.'}='.$k.';';
                $o.=$s;
                $s = '';
                $ln = 0;
            }
        }
        return $o;
    }

    protected static function LeaveAttribute($k, $v){

    }
}