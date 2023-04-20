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
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTreatDirectiveAttributeTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTreatEventAttributeTrait;
use igk\js\Vue3\Compiler\Traits\VueSFCRenderTreatSpecialTagTrait;
use igk\js\Vue3\System\Html\Dom\VueSFCTemplate;
use igk\js\Vue3\VueConstants;
use IGK\System\ArrayMapKeyValue;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlHostChildren;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlTextNode;
use IGK\System\Html\HtmlVisitor;
use IGK\System\IO\Configuration\ConfigurationEncoder;
use IGK\System\IO\StringBuilder;
use IGKException;
use ReflectionException;

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
    use VueSFCRenderTreatDirectiveAttributeTrait;
    use VueSFCRenderTreatSpecialTagTrait;

    /**
     * stored current node
     * @var ?HtmlItemBase 
     */
    protected $node; // current node;

    private $m_sb;
    
    private $m_preservelist = [];
    private $m_options;
    private $m_conditionals = []; // store conditionals - on detection 
    private $m_conditional_group = []; //store conditional group
    private $m_loop_group = [];
    private $m_directives = []; // chain directive
    private $m_single_item; // single item flags - to skip [...]
    private $m_child_detect; // detect that an item required child block;
    private $m_close_childs = 0;
    private $m_no_close_function = false; // no close function on end call 
    protected $skip = false; // skip flag for v-html and v-text

    /**
     * request extra arguments for render function (props, /[{}]/);
     * @var array
     */
    protected $requestArgs= []; 

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
     * @param ?VueSFCRenderNodeVisitorOptions $options 
     * @return ?string
     */
    public static function GenerateRenderMethod(HtmlItemBase $node, &$options = null): ?string
    {
        $args = $preload = '';
        if ($node instanceof VueSFCTemplate){
            $children = $node->getChilds()->to_array();
            $node = new HtmlHostChildren($children);
        }

        $visitor = new static($node);
        $is_local = is_null($options) || $options->test;
        $options = Activator::CreateFrom($options, VueSFCRenderNodeVisitorOptions::class);
        $visitor->m_options = $options;
        self::AddLib($options, VueConstants::VUE_METHOD_RENDER, VueConstants::JS_VUE_LIB);
        /**
         * router component
         */
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
        if ($visitor->requestArgs){
            $args = sprintf('props, {%s}', implode(",", array_keys($visitor->requestArgs)));
        }

        if ($visitor->m_conditional_group) {
            $visitor->endConditional(true);
        }
        if (!empty($res = $visitor->m_sb . '')) {
            $res = 'return ' . $res;
        }
        return sprintf('render(%s){%s%s}', $args, $preload, $res);
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
        $this->node = $t; 

        $s = new StringBuilder;
        if (!$first_child) {
            $tch = ',';
        }
        if ($t instanceof HtmlTextNode) {
            if (!empty($content)) {
                if (empty(trim($content))&& ($first_child || $last_child)){
                    return null;
                }
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
               //  $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . rtrim(self::GetTextDefinition($content), ')'));
                $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . self::GetTextDefinition($content));
                $this->skip = true;
                $this->skip_end = true;
                return true;
            }
            return null;
        }

        $tagname = $t->getTagName();
        $canrender = $t->getCanRenderTag();
        $v_slot = false;
        $v_conditional = false;
        $v_loop = false;
        $v_directives = [];
        $v_skip = false;
        if (empty($tagname) || !$canrender) {
            $this->m_sb->append($tch);
            $this->m_child_detect = $this->m_child_detect || $has_childs;
            return null;
        }
        ///child detected by skipping
        if ($this->m_child_detect){
                if (!$this->m_close_childs)
                $s->append('[');
                $tch = "";
                $this->m_child_detect = false;
                $this->m_close_childs= 1;
            
        }
        $attrs = $t->getAttributes()->to_array();

        // special tag meaning , slot - 
        if ($this->isSpecialTagMeaning($tagname, $attrs)){
            $s->append($tch.$this->resolvSpecialTag($tagname, $attrs, $v_slot, $has_childs));
            // $s->trim((')'));
            $this->m_sb->append($s);
            $this->skip = true;
            $this->skip_end = false;
            $this->m_no_close_function = true;
            return null;

        }



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
        if (!$preserve && empty($attrs) && !empty($content) && (strpos($content, '<') !== false)) {
            $attrs['innerHTML'] = $content;
            $content = '';
        }
        if ($attrs) {
            if (!$preserve && isset($attrs[$tk = 'v-pre'])) {
                $content = $t->getInnerHtml();
                array_unshift($this->m_preservelist, $t);
                unset($attrs[$tk]);
                $v_skip = true;
                $preserve = true;
            }
            if ($preserve) {
                $self = $this;
                $attrs['innerHTML']  = $content;
                $data = ArrayMapKeyValue::Map(function ($k, $v) use ($self) {
                    return $self->LeaveAttribute($k, $v);
                }, $attrs);
                $c = new ConfigurationEncoder;
                $c->delimiter = ',';
                $c->separator = ':';
                $data = $c->encode($data);
                $s->append($ch . sprintf('{%s}', $data));
                $content='';
            } else {
                if (key_exists($ck = 'v-html', $attrs)) {
                    $v_skip = true;
                    $content = igk_getv($attrs, $ck);
                    unset($attrs[$ck]);
                }
                if (key_exists($ck = 'v-text', $attrs)) {
                    $v_skip = true;
                    $content = igk_getv($attrs, $ck);
                    $attrs['innerText'] = $content;
                    $content = '';
                    unset($attrs[$ck]);
                }
                // + | pre-treat directive attribute  
                if ($this->isConditionnal($t, $attrs, $first_child, $last_child)) {
                    $v_conditional = true;
                }
                if ($this->isLoop($t, $attrs)) {
                    $v_loop = true;
                }
                // mark content as empty to avoid innerHTML setting
                if ($has_childs && $content){
                    if (empty(trim($content))){                        
                        $content = '';
                    }
                }

                // + | treat event - and binding       
                if ($attrs || $has_childs|| (strpos($content, '<') !== false)) {
                    if ($g_attr = self::_GetAttributeStringDefinition($attrs, $content, $context, $this->m_options, $v_directives, $preserve)) {
                        $s->append($ch . "{" . $g_attr . "}");
                        $ch = ',';
                        $content = '';
                    }
                }
            }
        } else {
            if ($has_childs) {
                if (!empty(trim($content))){
                    $inner_content = $content;
                    $content = '';
                }
            }
        }
        if (!empty(trim($content))) {
            $content = self::_GetValue($content, null, $preserve);
            $s->append($ch . $content);
            $ch = ',';
        }

        if ($has_childs) {
            $s->append($ch);
            // detect slot render 
            if ($v_slot) {
                $s->append(sprintf('(%s)=>', is_string($v_slot) ? $v_slot : ''));
            }
            // start child rendering 
            $s->append('[');
            if ($inner_content) {
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                // $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . self::GetTextDefinition($inner_content) . ",");
                $s->append($tch . VueConstants::VUE_METHOD_RENDER . self::GetTextDefinition($inner_content) . ",");
            }
        }
        if ($v_conditional || $v_loop || $v_directives) {
            if ($v_conditional) {
                // backup current buffer
                $this->m_conditionals[0]->sb = $this->m_sb;
            }
            if ($v_loop) {
                $this->m_loop_group[0]->sb = $this->m_sb;
            }
            if ($v_directives) {
                array_unshift($this->m_directives, (object)['t' => $t, 'd' => $v_directives]);
            }
            // start a new buffer
            $this->m_sb = new StringBuilder();
        } else {
            $this->m_sb->append($tch);
        }
        $this->m_sb->append($s);
        if ($v_skip) {
            $this->m_sb->rtrim('[,');
            $this->skip = true;
            return null;
        }
        return true;
    }
    /**
     * 
     * @param HtmlItemBase $t 
     * @param bool $has_childs 
     * @param bool $last 
     * @param mixed $end_visit 
     * @return void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected function endVisit(HtmlItemBase $t, bool $has_childs, bool $last, $end_visit)
    {

        if ($this->m_preservelist) {
            if ($this->m_preservelist[0] === $t) {
                array_shift($this->m_preservelist);
            }
        } 
        if ($has_childs) {
            $this->m_sb->rtrim(',');
            if (!$this->m_single_item) {
                $this->m_sb->append("]");
            }
            $this->m_single_item = false;
        }
        if (!$this->m_no_close_function)
            $this->m_sb->append(")");
        $this->m_no_close_function = false;

        if ($this->m_loop_group) {
            $g = $this->m_loop_group[0];
            if ($g->t === $t) {
                $q = array_shift($this->m_loop_group); 
                $src = self::_GetLoopScript($q->v, $this->m_sb);
                $q->sb->set($src);
                $this->m_sb = $q->sb; 
            }
        }

        if ($this->m_directives) {
            // surround with directive declaration 
            $q = $this->m_directives[0];
            $vs =  '';
            $dch = '';
            while (count($q->d) > 0) {
                $tq = array_shift($q->d);
                $vs = $dch . '[' . implode(',', $tq) . ']';
                $dch = ',';
            }
            $this->m_sb->set(sprintf(VueConstants::VUE_METHOD_WITH_DIRECTIVES . '(%s,[%s])', $this->m_sb, $vs));
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
            igk_dev_wln_e("need to close ... ");
        }
        if ($end_visit){
            if ($this->m_close_childs>0){
                $this->m_close_childs = 0;
                   $this->m_sb->append("]");
            }
        }
    }
    #endregion

    protected static function _GetAttributeStringDefinition($attrs, $content, $context, $options, &$directives, bool $preserve)
    {
        $s = '';
        $ch = '';
        $ln = 0;
        foreach ($attrs as $k => $v) {
            if ($ln != strlen($s)) {
                $ch = ',';
            }
            $s .= $ch;
            $ln = strlen($s);

            if (preg_match("/^(v-on:|@)/", $k)) {
                $s .= self::TreatEventAttribute($options, $k, $v, $context);
                continue;
            }
            if (preg_match("/^(v-bind)?:/", $k)) {
                $s .= self::TreatBindingAttribute($k, $v, $context);
                continue;
            }
            if (preg_match("/^v-([^:]+):/", $k)) {
                $s .= self::TreatDirectiveAttribute($directives, $options, $k, $v,  $context);
                continue;
            }
            $s .= (self::_GetKey($k) . ":" . self::_GetValue($v, $context));
        }
        if ($ln != strlen($s)) {
            $ch = ',';
        }
        if ($content) {
            $s .= ($ch . 'innerHTML:' . self::_GetValue($content, $context, $preserve));
            $content = '';
        }
        return $s;
    }


    #region conditional traitment
    protected function endConditional($top = false)
    {
        $c = $this->m_conditional_group[0];
        $r = $this->m_sb;
        $cond = '';
        $else = 'null';
        $sep = 0;
        $else_block = null;
        while (count($c) > 0) {
            $q = array_pop($c);
            if ($q->i == 'v-else') {
                $else = $r . ""; //self::_GetExpression($q->v,true);      
                $else_block = $q;
            } else {
                $cond = self::_GetExpression($q->v, true);
                if (($q->first) && (($q->last) || ($else_block && $else_block->last))) {
                    $q->sb->rtrim('[');
                    $this->m_single_item = true;
                }
                $q->sb->set(sprintf("%s%s%s?%s", $q->sb, $sep ? '(' : '', $cond, $r));
                $sep++;
            }
            $r = $q->sb;
        }
        if ($top) {
            $r->set(sprintf("[%s:%s]", $r . '', $else));
        } else {
            $r->set(sprintf("%s:%s%s", $r . '', $else, $sep > 1 ? ')' : ''));
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
                    'first' => $first_child,
                    'last' => $last_child,
                ]);
            }
        }
        return $r;
    }

    #endregion 

    #region LOOP Traitement
    public function isLoop($t, &$attrs)
    {
        if ($loop = igk_getv($attrs, $k = 'v-for')) {
            unset($attrs[$k]);
            array_unshift($this->m_loop_group, (object)[
                't' => $t,
                'v' => $loop,
                'sb' => null
            ]);
            return true;
        }
        return false;
    }
    #endregion

    public static function _GetExpression(string $v, $resolve_this = false)
    {
        $def = $v;
        if ($resolve_this) {
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

    public function LeaveAttribute($k, $v)
    {
        return [self::_GetKey($k), self::_GetValue($v, null, true)];
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
                $src = sprintf(
                    '(function(l,key){for(key %op l){((%s)=>this.push(%s))(l[key])}return this}).apply([],[%s])',
                    $op,
                    $firstkey,
                    $content,
                    $exp
                );
                break;

            default:
                $firstkey = trim($cond);
                $src = sprintf(
                    '(function(l,key){for(key in l){((%s)=>this.push(%s))(l[key])}return this}).apply([],[%s])',
                    $firstkey,
                    $content,
                    $exp
                );
                break;
        }
        return $src;
    }
}
