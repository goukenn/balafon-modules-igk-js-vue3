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
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTreatBindingAttributeTraitTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTreatEventAttributeTrait;
use igk\js\Vue3\VueConstants;
use IGK\System\ArrayMapKeyValue;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlTextNode;
use IGK\System\Html\HtmlVisitor;
use IGK\System\IO\StringBuilder;
use IGKException;

///<summary></summary>
/**
 * use to render on 
 * @package igk\js\Vue3\Compiler
 */
class VueSFCRenderNodeVisitor extends HtmlVisitor
{
    use VueSFCRenderVisitTrait;
    use VueSFCRenderTextTrait;
    use VueSFCRenderGetKeyValueTrait;
    use VueSFCRenderBuildInComponentTrait;
    use VueSFCRenderResolveComponentTrait;
    use VueSFCRenderTreatEventAttributeTrait;
    use VueSFCRenderTreatBindingAttributeTraitTrait;


    private $m_sb;
    private $m_preservelist = [];
    private $m_options;
    private $m_conditionals = []; // store conditionals - on detection 
    private $m_conditional_group = []; //store conditional group
    private $m_loop_group = [];
    private $m_single_item; // single item flags - to skip [...]
    private function __construct(HtmlItemBase $node)
    {
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
    public  static function GenerateRenderMethod(HtmlItemBase $node, &$options = null): ?string
    {
        $args = $preload = '';
        $visitor = new static($node);
        $is_local = is_null($options) || $options->test;
        $options = Activator::CreateFrom($options, VueSFCRenderNodeVisitorOptions::class);
        $visitor->m_options = $options;
        self::AddLib($options, VueConstants::VUE_METHOD_RENDER, VueConstants::JS_VUE_LIB);

        $options->components['RouterView'] = 1;
        $options->components['RouterLink'] = 1;

        $visitor->visit();
        if ($is_local) {
            if ($options->libraries) {
                $preload .= self::_GetLitteralLibrary($options->libraries);
            }
            if ($options->defineGlobal) {
                $g = $options->defineGlobal;
                ksort($g);
                $preload .= implode('', $g);
            }
            if ($options->defineArgs) {
                $g = $options->defineArgs;
                ksort($g);
                $preload .= implode('', $g);
            }
        }
        if($visitor->m_conditional_group){
            $visitor->endConditional(true);
        }
        return sprintf('render(%s){%sreturn %s}', $args, $preload, $visitor->m_sb . '');
    }
   

    #region visit
    /**
     * 
     * @param HtmlItemBase $t 
     * @param bool $first_child 
     * @param bool $has_childs 
     * @param bool $last_child 
     * @return null|bool 
     * @throws IGKException 
     */
    protected function beginVisit(HtmlItemBase $t, bool $first_child, bool $has_childs, bool $last_child): ?bool
    {
        $tch = '';
        $ch = '';
        $preserve = count($this->m_preservelist) > 0;
        $content = $t->getContent() ?? '';
        $context = null; // context options to get value
        $inner_content = null;

        $s = new StringBuilder;
        if (!$first_child) {
            $tch = ',';
        }
        if ($t instanceof HtmlTextNode) {
            if (!empty($content)) {

                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . self::GetTextDefinition($content));
            }
            return null;
        }

        $tagname = $t->getTagName();
        $canrender = $t->getCanRenderTag();
        $v_slot = false;
        $v_conditional = false;
        $v_loop = false;
        if (empty($tagname) || !$canrender) {
            return null;
        }
        $attrs = $t->getAttributes()->to_array();
        $s->append(VueConstants::VUE_METHOD_RENDER . "(");

        //treat special tag before rendering
        if ($this->isBuildInComponent($tagname)) {
            $s->append($this->resolveBuildInComponent($tagname, $attrs, $v_slot, $has_childs));
        } else if ($this->isResolvableComponent($tagname)) {
            $s->append($this->resolveComponent($tagname, $attrs, $v_slot, $has_childs));
        } else {
            $s->append(igk_str_surround($tagname, "'"));
        }
        $ch = ',';
        if ($attrs) {
            if (!$preserve && isset($attrs[$tk = 'v-pre'])) {
                array_unshift($this->m_preservelist, $t);
                unset($attrs[$tk]);
            }
            if ($preserve) {
                $s->append(sprintf('{%s}', ArrayMapKeyValue::Map([self::class, 'LeaveAttribute'], $attrs)));
            } else {               
                // + | pre-treat directive attribute  
                if ($this->isConditionnal($t, $attrs, $first_child, $last_child)) {
                    $v_conditional = true;
                }
                if ($this->isLoop($t, $attrs)){
                  $v_loop= true;
                }   
                // + | treat event - and binding                
                if ($g_attr = self::_GetAttributeStringDefinition($attrs, $content, $context)){
                    $s->append($ch . "{".$g_attr."}");                    
                    $ch = ',';
                    $content = '';
                }                
            }
        } else {
            if ($has_childs) {
                $inner_content = $content;
                $content = '';
            }
        }
        if (!empty(trim($content))) {
            $content = self::_GetValue($content);
            $s->append($ch . $content);
            $ch = ',';
        }

        if ($has_childs) {
            $s->append($ch);
            if ($v_slot) {
                $s->append(sprintf('(%s)=>', is_string($v_slot) ? $v_slot : ''));
            }
            $s->append("[");
            if ($inner_content) {
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . self::GetTextDefinition($inner_content) . ",");
            }
        }
        if ($v_conditional || $v_loop) {
            if ($v_conditional){
                // backup current buffer
                $this->m_conditionals[0]->sb = $this->m_sb;            
            }
            if ($v_loop){
                $this->m_loop_group[0]->sb = $this->m_sb;
            }
            // start a new buffer
            $this->m_sb = new StringBuilder();
        }        
        else {
            $this->m_sb->append($tch);
        }
        $this->m_sb->append($s);
        return true;
    }
    protected function endVisit(HtmlItemBase $t, bool $has_childs, bool $last)
    {

        if ($this->m_preservelist) {
            if ($this->m_preservelist[0] === $t) {
                array_shift($this->m_preservelist);
            }
        }

        if ($has_childs) {
            $this->m_sb->rtrim(',');
            if (!$this->m_single_item){
                $this->m_sb->append("]");
            }
            $this->m_single_item = false;
        }
        $this->m_sb->append(")");

        if ($this->m_loop_group){
            $g = $this->m_loop_group[0];
            if ($g->t === $t){
                $q = array_shift($this->m_loop_group);

                $src = self::_GetLoopScript($q->v, $this->m_sb);
                $q->sb->set($src);
                $this->m_sb = $q->sb;
                //transform to 
            }
        }

        // check to close conditional 
        if ($this->m_conditionals) {
            if ($this->m_conditionals[0]->t === $t) {
                $c = $this->m_conditionals[0];
                array_shift($this->m_conditionals);
                if (in_array($c->i, ['v-else', 'v-else-if']) && !$this->m_conditional_group) {
                    igk_die("miss conditional configuration", $c->i);
                }
                if ($c->i == 'v-else') {
                    // close conditional group
                    array_push($this->m_conditional_group[0], $c);
                    $this->endConditional(false); 
                }
                if ($c->i == 'v-if') {
                    // start conditional group
                    array_unshift($this->m_conditional_group, [$c]);
                } else {
                    if ($c->i == 'v-else-if') {
                        array_push($this->m_conditional_group[0], $c);
                    }
                }
            }
        } else if ($this->m_conditional_group) {
            //detect conditional group
            igk_wln_e("need to close ... ");
        }
    }
    #endregion

    protected static function _GetAttributeStringDefinition($attrs, $content, $context){
        $s = '';
        $ch = '';
        foreach ($attrs as $k => $v) {
            if (preg_match("/^(v-on:|@)/", $k)){
                $s.= self::TreatEventAttribute($k, $v, $ch,$context);
                $ch = ',';
                continue;
            }
            if (preg_match("/^v-bind?:/", $k)){
                $s.= self::TreatBindingAttribute($k, $v, $ch, $context);
                $ch = ',';
                continue;
            }
            $s .= ($ch . self::_GetKey($k) . ":" . self::_GetValue($v, $context));
            $ch = ',';
        }
        if ($content) {
            $s .= ($ch . 'innerHTML:' . self::_GetValue($content, $context));
            $content = '';
        }
        return $s;
    }


    #region conditional traitment
    protected function endConditional($top=false){
        $c = $this->m_conditional_group[0];  
        $r = $this->m_sb;
        $cond = '';
        $else = 'null';        
        $sep = 0;
        $else_block = null;
        while(count($c)>0){
            $q = array_pop($c);           
            if($q->i == 'v-else'){
                $else = $r."";//self::_GetExpression($q->v,true);      
                $else_block = $q;          
            }else{
                $cond = self::_GetExpression($q->v,true);
                if (($q->first) && (($q->last) || ($else_block && $else_block->last))){
                    $q->sb->rtrim('[');
                    $this->m_single_item = true;
                }
                $q->sb->set(sprintf("%s%s%s?%s" , $q->sb, $sep?'(':'', $cond, $r));
                $sep++;
            }
            $r = $q->sb;
        }
        if ($top){
            $r->set(sprintf("[%s:%s]",$r.'',$else));
        } else {
            $r->set(sprintf("%s:%s%s",$r.'',$else,$sep>1?')':''));
        }
        $this->m_sb = $r;
        // $old = $this->m_conditionals[0]->sb;
        // $old->append($this->m_sb . '');
        // $this->m_sb = $old;
        array_shift($this->m_conditional_group);
    }
    protected function isConditionnal($t, &$attr, bool $first_child, bool $last_child)
    {
        $r = false;
        $rgx = '/^(' . VueConstants::BUILTIN_DIRECTIVE_CONDITIONAL . ')$/';
        foreach (array_keys($attr) as $k) {
            if (preg_match($rgx, $k)) {
                $r = true;
                $v = igk_getv($attr, $k);
                unset($attr[$k]);
                array_unshift($this->m_conditionals, (object)[
                    'sb' => null,
                    't' => $t,
                    'i' => $k,
                    'v' => $v,
                    'first'=>$first_child,
                    'last'=>$last_child,
                ]);
            }
        }
        return $r;
    }

    #endregion 
   
    #region LOOP Traitement
    public function isLoop($t, & $attrs){
        if ($loop = igk_getv($attrs, $k = 'v-for')){
            unset($attrs[$k]);
            array_unshift($this->m_loop_group, (object)[
                't' => $t,
                'v' => $loop,
                'sb'=>null
            ]);
            return true;
        }
        return false;
    }
    #endregion
   
    public static function _GetExpression(string $v, $resolve_this = false){
        $def = $v;
        if ($resolve_this){
            $def = SFCScriptSetup::TransformToThisContext($v);
        }
        return $def;

    }

    protected static function AddLib(VueSFCRenderNodeVisitorOptions $options, string $name, string $lib = VueConstants::JS_VUE_LIB)
    {
        VueSFCUtility::AddLib($options, $name, $lib);
    }
    protected static function GetTextDefinition($content, $context = null)
    {
        return sprintf('(%s,%s)', VueConstants::VUE_COMPONENT_TEXT, self::_GetValue($content, $context));
    }

    protected static function _GetLitteralLibrary($lib, $type = 'const')
    {
        $s = "";
        $ch = '';
        $o = '';
        $ln = 0;
        ksort($lib, SORT_FLAG_CASE | SORT_NATURAL);
        foreach ($lib as $k => $v) {
            ksort($v, SORT_FLAG_CASE | SORT_NATURAL);
            foreach ($v as $m => $a) {
                if (!empty($s) && $ln) {
                    $ch .= ',';
                }
                $s .= $ch;
                $ln = strlen($s);
                (is_numeric($m)) && igk_die("not valid definition");
                if (is_string($a)) {
                    $s .= $m . ":" . $a;
                } else
                    $s .= $m;
                $ln = strlen($s) - $ln;
                $ch = '';
            }
            if (!empty($s)) {
                $s = $type . '{' . $s . '}=' . $k . ';';
                $o .= $s;
                $s = '';
                $ln = 0;
            }
        }
        return $o;
    }

    protected static function LeaveAttribute($k, $v)
    {
    }

    public static function _GetLoopScript($cond, $content)
    {
        preg_match('/^\s*(?P<cond>.+)\s+(?P<op>in|of)\s+(?P<exp>.+)\s*$/', $cond, $tab) ?? igk_die("not a valid expression");;
        $src = "";
        $cond = $tab['cond'];
        $op = $tab['op'];
        $mode = preg_match('/^\{.+\}$/', $tab['cond']) ? 1 : (preg_match('/^\(.+\)$/', $tab['cond']) ? 2 : 0);
        $exp = $tab['exp']; 
        switch ($mode) {
            case 1:
                $firstkey = trim(explode(",", substr($cond, 1, -1))[0]);
                $src = sprintf(<<<'JS'
(function(l,key){for(key %s l){((%s)=>this.push(%s))(l[key])} return this}).apply([],[%s])
JS, $op, $cond, $content, $exp);
            break;
            case 2:
            $firstkey = trim(explode(",", substr($cond, 1, -1))[0]);
            $src = sprintf('(function(l,key){for(key %op l){((%s)=>this.push(%s))(l[key])}return this}).apply([],[%s])',
            $op, $firstkey, $content, $exp);
            break;

            default:
                $firstkey = trim($cond);
                $src = sprintf('(function(l,key){for(key in l){((%s)=>this.push(%s))(l[key])}return this}).apply([],[%s])',
                $firstkey, $content, $exp);
                break;
        }
        return $src;
    }
}
