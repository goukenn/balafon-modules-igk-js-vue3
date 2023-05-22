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
use igk\js\Vue3\Helpers\JSUtility;
use igk\js\Vue3\System\Html\Dom\VueSFCTemplate;
use igk\js\Vue3\VueConstants;
use IGK\System\ArrayMapKeyValue;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlCommentNode;
use IGK\System\Html\Dom\HtmlHostChildren;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlNoTagNode;
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
    private $m_func_depth = 0; // no close function on end call 
    protected $skip = false; // skip flag for v-html and v-text
    private $m_state_info = [];
    private $m_start_render = false; // flag to detect node start render for array detection
    private $m_globalStart_Array = false; // flag to detect global array start;
    private $m_globalChildCounter = 0; // counting global childs;
    private $m_globalDepth;

    private $m_child_state = [];

    private $m_last_text = null;

    /**
     * request extra arguments for render function (props, /[{}]/);
     * @var array
     */
    protected $requestArgs = [];

    private function __construct(HtmlItemBase $node)
    {
        parent::__construct($node);
        $this->m_sb = new StringBuilder;
        $this->startVisitorListener = [$this, 'beginVisit'];
        $this->endVisitorListener = [$this, 'endVisit'];
    }
    private function _pushState()
    {
        $info = (object)[
            'start_child' => false,
        ];
        array_unshift($this->m_state_info, $info);
        return $info;
    }
    private function _popState()
    {
        $q = array_shift($this->m_state_info);
        return $q;
    }
    private function _state()
    {
        return count($this->m_state_info) > 0 ? $this->m_state_info[0] : null;
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
        if ($node instanceof VueSFCTemplate) {
            $children = $node->getChilds()->to_array();
            $node = new HtmlHostChildren($children);
        }
        $is_local = is_null($options) || $options->test;
        $visitor = self::_HandleVisit($node, $options);

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
        if ($visitor->requestArgs) {
            $args = sprintf('props, {%s}', implode(",", array_keys($visitor->requestArgs)));
        }

        if ($visitor->m_conditional_group) {
            $visitor->endConditional(true);
            igk_ilog('warn end global conditional');
        }
        if (!empty($res = $visitor->m_sb . '')) {
            $res = 'return ' . $res;
        }
        return sprintf('render(%s){%s%s}', $args, $preload, $res);
    }
    private static function _HandleVisit($node, &$options)
    {
        $visitor = new static($node);
        $options = Activator::CreateFrom($options, VueSFCRenderNodeVisitorOptions::class);
        $visitor->m_options = $options;
        self::AddLib($options, VueConstants::VUE_METHOD_RENDER, VueConstants::JS_VUE_LIB);
        /**
         * router component
         */
        $options->components['RouterView'] = 1;
        $options->components['RouterLink'] = 1;
        $visitor->visit();
        return $visitor;
    }

    private function _update_conditionLevel()
    {
        if ($this->m_conditional_group) {
            $g = $this->m_conditional_group[0][0];
            if ($g->depth == $this->m_globalDepth) {
                $g->childCount++;
            }
        }
    }

    private function _updateGlobalChildCounter()
    {
        if ($this->m_globalStart_Array) {
            if ($this->m_globalDepth == 0) {
                $this->m_globalChildCounter++;
            }
        }
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
        igk_debug_wln('begin visit : ');
        // + | debug counting
        //  $count = igk_env_count(__METHOD__);

        $tch = '';
        $ch = '';
        $preserve = count($this->m_preservelist) > 0;
        $content = $t->getContent() ?? '';
        $context = null; // context options to get value
        $inner_content = null;
        $this->node = $t;

        $s = new StringBuilder;
        if ($this->m_child_state) {
            $tch = $this->m_child_state[0]->sep;
        }
        if ($t instanceof HtmlTextNode) {
            $this->m_child_detect = false;
            $_content_is_empty = empty(trim($content));
            if ($this->m_last_text) {
                // check if end with space
                if (substr($this->m_last_text, -1) == ' ') {
                    if ($_content_is_empty) {
                        return null;
                    }
                }
            }
            if ($this->m_conditional_group && $_content_is_empty) {
                return null;
            }
            if (!empty($content)) {
                if (empty(trim($content)) && ($first_child || $last_child)) {
                    return null;
                }
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                $this->m_last_text = $content;
                $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . self::getTextDefinition($content, null, $this->preserveContent()));
                $this->skip = true;
                $this->skip_end = true;
                if ($this->m_child_state)
                    $this->m_child_state[0]->sep = ',';
                $this->_update_conditionLevel();
                return true;
            }
            return null;
        }
        if ($t instanceof HtmlCommentNode) {
            self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_COMMENT);
            $v_tc = self::_GetValue($content, null, true);
            $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . sprintf(
                '(%s,%s)',
                VueConstants::VUE_COMPONENT_COMMENT,
                $v_tc
            ));
            $this->skip = true;
            $this->skip_end = true;
            if ($this->m_child_state)
                $this->m_child_state[0]->sep = ',';
            return true;
        }

        $tagname = $t->getTagName();
        $canrender = $t->getCanRenderTag();
        $v_slot = false;
        $v_conditional = false;
        $v_loop = false;
        $v_directives = [];
        $v_skip = false;
        $v_start_child = false;
        if (empty($tagname) || !$canrender) {
            //  $this->m_sb->append($tch);
            $this->m_child_detect = $this->m_child_detect || $has_childs;
            if (!empty($content)) {
                // "hredering 
                $n = new HtmlNoTagNode();
                if ($n->load($content)) {
                    $refoptions = [];
                    $visitor = self::_HandleVisit($n, $refoptions);
                    $g = $visitor->m_sb . '';
                    if (empty($tch)) {
                        //start child rendering
                        $tch = ',';
                    }
                    $gv = sprintf('%s', trim($g, '[]'));
                    $this->m_sb->append($gv);
                    $this->m_child_detect = false;
                    if ($this->m_child_state) {
                        $this->m_child_state[0]->sep = ',';
                    }
                }
                $this->skip = true;
                $this->skip_end = true;
            }
            return null;
        }



        $attrs = $t->getAttributes()->to_array();
        // + | special tag meaning , slot - for example
        if ($this->isSpecialTagMeaning($tagname, $attrs)) {

            $s->append($tch . $this->resolvSpecialTag($tagname, $attrs, $v_slot, $has_childs));
            $this->m_sb->append($s);
            $this->skip = true;
            $this->skip_end = true;
            // $this->m_func_depth = true;
            $this->_updateGlobalChildCounter();
            return true;
        }

        // child detected by skipping
        if ($tch) {
            $this->m_sb->append($tch);
            $tch = '';
        }
        if ($this->m_child_detect) {
            if (!$this->m_close_childs) {
                if (!($q = $this->_state()) || !$q->start_child) {
                    if (!($this->m_start_render)) {
                        $s->append('[');
                        $this->m_globalStart_Array = true;
                        $this->_startChildVisit();
                    }
                }
            }
            $this->m_child_detect = false;
            $this->m_close_childs = !$this->m_start_render;
            $v_start_child = true;
        }



        $this->m_start_render = true;
        if ($this->m_conditional_group) {
            // detect if need to stop;
            $this->checkEndCondition($t, $attrs, $first_child, $last_child);
        }

        $this->_updateGlobalChildCounter();
        $s->append(VueConstants::VUE_METHOD_RENDER . "(");
        $this->m_func_depth++;
        $v_info = $this->_pushState();
        $v_info->start_child = $v_start_child;
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
            if (!empty(trim($content))) {
                if ($has_childs) {
                    $inner_content = $content;
                    $content = '';
                }
            }
            $this->handleAttributes(
                $this->node,
                $attrs,
                $s,
                $first_child,
                $last_child,
                $has_childs,
                $v_directives,
                $v_skip,
                $v_loop,
                $v_conditional,
                $context,
                $ch,
                $preserve,
                $content
            );
        } else {
            if (!empty(trim($content))) {
                if ($has_childs) {

                    $inner_content = $content;
                    $content = '';
                } else {
                    $this->m_last_text = $content;
                    $content = self::_GetValue($content, $this->m_options, $preserve);
                    if (self::DetectHtmlSupport($content))
                        $s->append($ch . '{innerHTML:' . $content . '}');
                    else {
                        $s->append($ch . $content);
                    }
                    $content = '';
                    $ch = ',';
                }
            }
        }
        if (!empty(trim($content))) {
            $this->m_last_text = $content;
            $content = self::_GetValue($content, $this->m_options, $preserve);
            $s->append($ch . $content);
            $ch = ',';
        }
        if ($this->m_child_state) {
            $this->m_child_state[0]->sep = $ch;
        }
        if ($has_childs) {
            $s->append($ch);
            // detect slot render 
            if ($v_slot) {
                $s->append(sprintf('(%s)=>', is_string($v_slot) ? $v_slot : ''));
            }
            // start child rendering  
            // if (!$v_start_child) {
            $s->append('[');
            $v_info->start_child = true;
            $this->_startChildVisit();
            // }
            if ($inner_content) {
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                $s->append($tch . VueConstants::VUE_METHOD_RENDER . self::getTextDefinition($inner_content, null, $this->preserveContent()));
                $ch = ',';
                $this->m_child_state[0]->sep = $ch;
            }
        }
        if ($v_conditional || $v_loop || $v_directives) {
            if ($v_conditional) {
                // backup current buffer 
                $this->m_conditionals[0]->sb = $this->m_sb;
            }
            if ($v_loop) {
                // backgup buffer to current 
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
            if ($this->m_child_state) {
                $this->m_child_state[0]->sep = ',';
            }
            return null;
        }
        return true;
    }
    protected function _close($t, $v_info, $has_childs, bool $shift){
        if ($this->m_preservelist && $shift) {
            if ($this->m_preservelist[0] === $t) {
                array_shift($this->m_preservelist);
            }
        }
        $v_childs_container = (!$v_info || $v_info->start_child);
        if ($has_childs) {
            $this->m_sb->rtrim(',');
            if ($this->m_single_item || $v_childs_container) {
                //if (!$v_info || $v_info->start_child) {
                $this->m_sb->append("]");
                if ($shift){
                    array_shift($this->m_child_state);
                }
                //     }
                // } else {
                //     $this->m_sb->append("]");
                //     array_shift($this->m_child_state);
            }
            $this->m_single_item = false;
            if (!is_null($this->m_globalDepth)) {
                // decrement global depth - for end
                $this->m_globalDepth--;
            }
        }
        if ($this->m_func_depth) {
            $this->m_sb->append(")");
            $this->m_func_depth--;
        }

    }
    /**
     * end visit
     * @param HtmlItemBase $t 
     * @param bool $has_childs 
     * @param bool $last 
     * @param mixed $end_visit 
     * @return void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected function endVisit(HtmlItemBase $t, bool $has_childs, bool $last, bool $end_visit)
    {
        igk_debug_wln('end visit::' . $this->m_globalDepth);
        $v_info = $this->_popState();
        $close_array = $has_childs && $this->m_single_item || (!$v_info || $v_info->start_child);
        $closed = false;

        // + | --------------------------------------------------------------------
        // + | preserved list stop render 
        // + |   
       
        // + | pre condition end

        if ($this->m_conditional_group) {
            $cond_group = $this->m_conditional_group[0];
            if ($end_visit || $close_array ||  ($cond_group[0]->depth > $this->m_globalDepth)) {
                if ($this->m_conditionals) {
                    // else conditional can't be marked 
                   // $this->_endOrCreateConditionalGroup($t);
                   $cond = $this->m_conditionals[0];
                   if ($cond_group[0]->depth == $cond->depth){                       
                        $this->_endOrCreateConditionalGroup($t);
                   }
                }
               // $closed = true;
                $this->_close($t, $v_info, $has_childs, false);   
                $this->endConditional();
               // $this->_close($t, $v_info, $has_childs, false);      
                $this->m_sb->append('/* close */');
            }
        }
        if ($this->m_conditionals) {
            // mark or visit end condtion 
            $this->_endOrCreateConditionalGroup($t);
        }
        if (!$closed)
            $this->_close($t, $v_info, $has_childs, true);   
       

        if ($this->m_loop_group) {
            $g = $this->m_loop_group[0];
            if ($g->t === $t) {
                $q = array_shift($this->m_loop_group);
                $src = self::_GetLoopScript($q->v, $this->m_sb);
                //   $q->sb->set($src);
                $q->sb->append($src);
                $this->m_sb = $q->sb;
                if ($this->m_options->contextVars) {
                    array_shift($this->m_options->contextVars);
                }
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


        if ($end_visit) {
            if ($this->m_globalStart_Array || ($this->m_close_childs > 0)) {
                $this->m_close_childs = 0;
                $this->m_sb->append("]");
            }
            if ($this->m_globalChildCounter <= 1) {
                $this->m_sb->set(trim($this->m_sb . '', '[]'));
            }
        }
    }
    private function _startChildVisit()
    {
        $s = false;
        if (is_null($this->m_globalDepth)) {
            $this->m_globalDepth = 0;
            $s = true;
        }
        array_unshift($this->m_child_state, (object)['sep' => '', 'depth' => $this->m_globalDepth]);
        if (!$s) {
            $this->m_globalDepth++;
        }
    }
    function checkEndCondition($t, $attr, $first_child, $last_child)
    {
        if (!$this->isConditionnal($t, $attr, $first_child, $last_child, true)) {
            if ($r = $this->m_conditional_group) {
                if ($r[0][0]->depth >= $this->m_globalDepth) {
                    // end conditional 
                    $this->endConditional();
                    $this->m_sb->append(",");
                }
            }
        }
    }
    /**
     * simple detect entities
     * @param mixed $string 
     * @return bool 
     */
    public static function DetectHtmlSupport($string): bool
    {
        return (strpos($string, '<') !== false) || (html_entity_decode($string) != $string);
    }

    private function _endOrCreateConditionalGroup($t)
    {
        // check to close conditional 
        if ($this->m_conditionals) {
            $tcond = &$this->m_conditionals;
            if ($tcond[0]->t === $t) {
                $c = $tcond[0];
                array_shift($tcond);
                if (in_array($c->i, ['v-else', 'v-else-if']) && !$this->m_conditional_group) {
                    igk_die("miss conditional_group configuration", $c->i);
                }
                if ($c->i == 'v-else') {
                    // close conditional group
                    array_push($this->m_conditional_group[0], $c);
                    $this->endConditional(false);
                }
                if ($c->i == 'v-if') {
                    // + | start conditional group                    
                    array_unshift($this->m_conditional_group, [$c]);
                    // array_unshift($tcond, $c);
                } else {
                    if ($c->i == 'v-else-if') {
                        array_push($this->m_conditional_group[0], $c);
                    }
                }
            }
        } else if ($this->m_conditional_group) {
            //detect conditional group
            if ($this->m_conditional_group[0][0]->t === $t) {
                $this->endConditional(false);
            }
            if ($this->m_conditional_group) {
                igk_dev_wln("need to close ... conditional group not empty conditional", $this->m_conditional_group);
            }
        }
    }
    #endregion

    protected static function _GetAttributeStringDefinition($node, $attrs, $content, $context, $options, &$directives, bool $preserve)
    {
        $s = '';
        $ch = '';
        $ln = 0;
        if (isset($attrs['v-model'])) {
        }
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
            if (preg_match("/^v-model/", $k)) {
                $s .= self::TreatModelAttribute($node, $options, $k, $v, $context);
                continue;
            }
            if (preg_match("/^v-([^:]+):/", $k)) {
                $s .= self::TreatDirectiveAttribute($directives, $options, $k, $v,  $context);
                continue;
            }
            $s .= (self::_GetKey($k) . ":" . self::_GetValue($v, $options, $preserve));
        }
        if ($ln != strlen($s)) {
            $ch = ',';
        }
        if ($content) {
            $s .= ($ch . 'innerHTML:' . self::_GetValue($content, $options, $preserve));
            $content = '';
        }
        return $s;
    }
    public static function TreatModelAttribute($node, $options, $k, $v, $context)
    {
        $s = '';
        $type = $node['type'] ?? 'text';
        $r = JSUtility::TreatExpression(self::_GetBindingExpressionValue($v, $context));
        switch ($type) {
            default:
                $s .= "value:" . $r . ",";
                $s .= "onInput:(e)=>" . $r . "= e.target.Value";
                break;
        }
        return $s;
    }


    #region conditional traitment
    /**
     * 
     * @param bool $top 
     * @return void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected function endConditional($top = false)
    {
        // $c = array_pop($this->m_conditional_group); 
        $c = array_shift($this->m_conditional_group);
        if (!$c) {
            return;
        }
        $r = $this->m_sb; // current string build 
        $cond = '';

        $sep = 0;
        $else_block = null;
        $g_is_root = null;

        $stop = false;
        $baseDepth = $c[0]->depth;
        $conditions = [];
        while (!$stop) {

            $n_build = new StringBuilder();
            $else = 'null';
            while (count($c) > 0) {
                $q = array_pop($c);
                if (is_null($g_is_root)) {
                    $g_is_root = $q->sb->isEmpty();
                }
                if ($q->i == 'v-else') {
                    $else = $r . ""; //self::_GetExpression($q->v,true);      
                    $else_block = $q;
                    $stop = true;
                } else {
                    $cond = self::_GetExpression($q->v, true);
                    if (($q->first) && (($q->last) || ($else_block && $else_block->last))) {
                        // $q->sb->rtrim('[');
                        $this->m_single_item = true;
                    }
                    $tr = $r . '';
                    $tr = trim($tr, '[],');
                    $express = $tr . '';
                    if ($q->childCount > 0) {
                        $express = '[' . $express . ']';
                    }
                    $express = sprintf("%s%s%s?%s", '', $sep ? '' : '', $cond, $express); 
                    $n_build->append($express); 
                    $sep++;
                }
                //restore backg up
                $r = $q->sb;
            }
            $m = '';

            if ($top) {
                $m = sprintf("[%s:%s]", $n_build . '', $else);
            } else {
                $m = sprintf("%s:%s%s", $n_build . '', $else, $sep > 1 ? ',' : '');
                if ($g_is_root === true) {
                    $m = '[' . $m;
                }
            }
            array_unshift($conditions, $m);
            if ($this->m_conditional_group) {
                $group = $this->m_conditional_group[0];
                if ($baseDepth == $group[0]->depth) {
                    $this->m_sb->set($m);
                    $c = array_shift($this->m_conditional_group);
                    $stop = false;
                } else {
                    $stop = true;
                }
            } else {
                $stop = true;
            }
        }
        $m = ltrim(implode('', $conditions), '(');
        if (count($this->m_conditional_group) == 0) {
            // $r->set($m);
            $r->append($m);
            $this->m_sb = $r;
        } else {
            $this->m_sb->set($m);
        }
        //array_shift($this->m_conditional_group);
    }
    /**
     * check that node contain a conditional tag 
     * @param mixed $t 
     * @param mixed $attr 
     * @param bool $first_child 
     * @param bool $last_child 
     * @return bool 
     * @throws IGKException 
     */
    protected function isConditionnal($t, &$attr, bool $first_child, bool $last_child, $check = false)
    {
        $r = false;
        $rgx = '/^(' . VueConstants::BUILTIN_DIRECTIVE_CONDITIONAL . ')$/';
        $groups = null;
        foreach (array_keys($attr) as $k) {
            if (preg_match($rgx, $k)) {
                $r = true;
                if ($check) {
                    return $r;
                }
                $v = igk_getv($attr, $k);
                unset($attr[$k]);
                // + | add conditionals tag
                array_unshift($this->m_conditionals, (object)[
                    'sb' => null, // stringbuilder
                    't' => $t,
                    'i' => $k,
                    'v' => $v,
                    'first' => $first_child,
                    'last' => $last_child,
                    'sep' => '',
                    'ch' => '',
                    'depth' => $this->m_globalDepth,
                    'childCount' => 0,
                    'group' => $groups
                ]);
            }
        }
        return $r;
    }

    #endregion 

    #region LOOP Traitement
    public function isLoop($t, &$attrs, $options)
    {
        if ($loop = igk_getv($attrs, $k = 'v-for')) {
            unset($attrs[$k]);
            array_unshift($this->m_loop_group, (object)[
                't' => $t,
                'v' => $loop,
                'sb' => null
            ]);
            array_unshift($options->contextVars, array_merge(['key'], self::_GetContextArgs($loop, $options)));
            return true;
        }
        return false;
    }
    #endregion
    protected static function _GetContextArgs($loop, $options)
    {
        $args = $options->contextVars ? $options->contextVars[0] : [];
        preg_match('/^\s*(?P<cond>.+)\s+(?P<op>in|of)\s+(?P<exp>.+)\s*$/', $loop, $tab) ?? igk_die("not a valid expression");
        $src = trim($tab['cond'], '{}()');
        $tj = array_map('trim', array_filter(explode(',', $src)));
        $args = array_merge($args, $tj);

        return $args;
    }

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
    protected function getTextDefinition($content, $context = null, bool $preserve = false, $interpolate = true)
    {
        if ($preserve && preg_match("/" . $this->interpolateStart . "/", $content)) {

            $v = VueSFCUtility::InterpolateValue($content, '{{',  '}}', true, []);
            $v = igk_str_surround(trim($v, '`'), "`");
        } else {
            $v =  self::_GetValue($content, $context, $preserve);
        }
        return sprintf('(%s,%s)', VueConstants::VUE_COMPONENT_TEXT, $v);
    }

    private function preserveContent(): bool
    {
        return count($this->m_loop_group) > 0;
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
        preg_match('/^\s*(?P<cond>.+)\s+(?P<op>in|of)\s+(?P<exp>.+)\s*$/', $cond, $tab) ?? igk_die("not a valid expression");
        $src = "";
        $cond = $tab['cond'];
        $op = $tab['op'];
        $mode = preg_match('/^\{.+\}$/', $tab['cond']) ? 1 : (preg_match('/^\(.+\)$/', $tab['cond']) ? 2 : 0);
        $exp = JSUtility::TreatExpression($tab['exp']);
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
                    '(function(l,key){for(key %s l){((%s)=>this.push(%s))(l[key])}return this}).apply([],[%s])',
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
