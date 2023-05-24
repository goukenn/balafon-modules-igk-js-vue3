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
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Components\VueNoTagNode;
use igk\js\Vue3\Helpers\JSUtility;
use igk\js\Vue3\System\Html\Dom\VueSFCTemplate;
use igk\js\Vue3\VueConstants;
use IGK\System\ArrayMapKeyValue;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlCommentNode;
use IGK\System\Html\Dom\HtmlHostChildren;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\Dom\HtmlNoTagNode;
use IGK\System\Html\Dom\HtmlTextNode;
use IGK\System\Html\HtmlVisitor;
use IGK\System\IO\Configuration\ConfigurationEncoder;
use IGK\System\IO\StringBuilder;
use IGKException;
use ReflectionException; 

igk_require_module(\igk\js\Vue3::class);


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
    private $m_globalDepth = 0;

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
            igk_environment()->isDev() && igk_die('warn : conditial group is not empty');
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
            if ($this->m_globalDepth <= 1) { // contional skip
                $this->m_globalChildCounter++;
            }
        }
    }

    protected function _downgradeGlobalChildCount(){ 
        if ($this->m_globalStart_Array){
            if ($this->m_globalDepth <= 1) { // conditionnal skip 
                $this->m_globalChildCounter--; 
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
        igk_debug_wln('begin visit : '. $t->getTagName());
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
                $this->m_sb->append($tch . VueConstants::VUE_METHOD_RENDER . self::getTextDefinition($content, null, $this->_preserveContent()));
                $this->skip = true;
                $this->skip_end = true;
                if ($this->m_child_state){ 
                    $this->m_child_state[0]->sep = ',';
                    $this->m_child_state[0]->has_childs = true;
                }
                $this->_update_conditionLevel();
                return true;
            }
            return null;
        }
        if ($t instanceof HtmlCommentNode) {
            self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_COMMENT);
            $v_tc = self::_GetValue($content, null, true);
            if (strpos($v_tc,"\n")!==false){
                $v_tc = igk_str_surround(stripslashes($v_tc),'`');
            }
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
        $manual_child  = null;
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

        if (strtolower($tagname)=='script'){
            $this->skip = true;
            $this->skip_end = true;
            return null;
        }
        $attrs = $t->getAttributes()->to_array();

        // TODO : MANAGE TEMPLATE transform to slot or explode to content .
        if (0 && strtolower($tagname)=='template'){
            // igk_wln_e("found template.....");
            // isset($attrs['v-else']) && 
            igk_die('v-else not allowed supported in template');
            if ($r = $this->m_conditional_group) {
                if ($r[0][0]->depth >= $this->m_globalDepth) {
                    // end conditional 
                    $this->endConditional();
                    $this->m_sb->append(",");
                    $tch = '';
                }
            }
            // special meaning in vue
            $v_n = new VueComponent('dummy-template');
            $childs = $t->getRenderedChilds();
            $v_n->addRange($childs);
            $v_n->setAttributes($t->getAttributes()->to_array());
            // $v_n->Load($t->render());
            $sf = new static($v_n);
            $sf->m_options = $this->m_options;
        
            $sf->visit();
            $this->m_last_text = $sf->m_last_text;
            $content = $sf->m_sb.'';
            $content = str_replace("h('dummy-template',[", '',$content);
            $content = preg_replace("/\]\):null$/", ':null',$content);
            $this->m_sb->append($tch .$content);
            $this->skip = true;
            $this->skip_end = true;
            if ($this->m_child_state) {
                $this->m_child_state[0]->sep = ',';
            }
            return null;
        }
 
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
                        $this->_startChildVisit($t);
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
            $this->_checkEndCondition($t, $attrs, $first_child, $last_child);
        }

        $this->_updateGlobalChildCounter();
        $s->append(VueConstants::VUE_METHOD_RENDER . "(");
        $this->m_func_depth++;
        $v_info = $this->_pushState(); 
        //treat special tag before rendering
        if ($this->isBuildInComponent($tagname)) {
            $s->append($this->resolveBuildInComponent($tagname, $attrs, $v_slot, $has_childs));
            if (strtolower($tagname)=='teleport'){
                if (!$has_childs && !empty($content)){
                    $inner_content = $content;
                    $content = '';                    
                    $node = new VueNoTagNode;
                    $node->load($inner_content);
                    $mvisitor = new self($node);
                    $mvisitor->m_options = $this->m_options;
                    $mvisitor->visit();
                    $info = $mvisitor->m_sb."";
                    $manual_child = sprintf('[%s]',trim($info,'[]'));
                }
            }


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
            $this->_startChildVisit($t);
            // }
            if ($inner_content) {
                self::AddLib($this->m_options, VueConstants::VUE_COMPONENT_TEXT);
                $s->append($tch . VueConstants::VUE_METHOD_RENDER . self::getTextDefinition($inner_content, null, $this->_preserveContent()));
                $ch = ',';
                $this->m_child_state[0]->sep = $ch;
            }
        }
        else {
            if ($manual_child){
                $s->append($ch);
                $s->append($manual_child);
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
    protected function _close($t, $v_info, $has_childs, bool $shift)
    {
      
        $v_childs_container = (!$v_info || $v_info->start_child);
        if ($has_childs) {
            $this->m_sb->rtrim(',');
            if ($this->m_single_item || $v_childs_container) {
                //if (!$v_info || $v_info->start_child) {
                $this->m_sb->append("]");
                if ($shift) {
                    array_shift($this->m_child_state);
                }
                //     }
                // } else {
                //     $this->m_sb->append("]");
                //     array_shift($this->m_child_state);
            }
            $this->m_single_item = false;          
        }
       $this->_closeFunction();
    }
    private function _closeFunction(){
        if ($this->m_func_depth) {
            $this->m_sb->append(")");
            $this->m_func_depth--;
        }
    }
    private function _closeArray($v_info){
        if ($v_info && $v_info->start_child){
            // close array childs 
            $this->m_sb->append("]"); 
        }
    }
    private function _closePreservation($t){
        if ($this->m_preservelist) {
            if ($this->m_preservelist[0] === $t) {
                array_shift($this->m_preservelist);
            }
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
        // igk_debug_wln('end visit::  ' . $this->m_globalDepth);
        $v_info = $this->_popState();
        // $close_array = $has_childs && $this->m_single_item || (!$v_info || $v_info->start_child);
        $closed = false;
        if ($v_info && $v_info->start_child && !is_null($this->m_globalDepth)) {
            // decrement global depth - for end
            $this->m_globalDepth--;
        }
        $this->_closePreservation($t);

        // condition group marker - 
        // ------------------------
        // merge all condiontinal with A?B:C?D:E
        // v-if A v-else-if B v-else C

        // + | --------------------------------------------------------------------
        // + | preserved list stop render 
        // + | 
      
        // every time with read a conditionals we must update the conditional list before closing the node 
        if ($this->m_conditionals || $this->m_conditional_group){
            $cond = null; 
            // igk_debug_wln('end conditional.....');           
            if ($this->m_conditionals){   
                if ($this->m_conditional_group){
                    if ($this->m_conditional_group[0][0]->depth > $this->m_globalDepth){ 
                        $this->endConditional();
                        // if ($v_info && $v_info->start_child){
                        //     // close array childs 
                        //     $this->m_sb->append("]"); 
                        // }
                    }
                } 
                $cond = $this->m_conditionals[0];
                if ($cond->t === $t){
                    $this->_closeArray($v_info);
                    $this->_closeFunction();
                    $this->_endOrCreateConditionalGroup($t);
                   
                } else {
                    // $this->_closeFunction();                    
                    // $this->_closeArray($v_info);
                    $this->_closeArray($v_info);
                    $this->_closeFunction();
                } 
            } else {
                // $group = $this->m_conditional_group[0];  
                $this->endConditional(); 
                $this->_closeArray($v_info);
                $this->_closeFunction();
            }


            // if($this->m_conditional_group && !$this->m_conditionals){
            //     // no conditional next found . so end conditional 
            //     $this->endConditional();
            //     igk_dev_wln('no end found**************************************');
            // } else if ($this->m_conditional_group && $this->m_conditionals){
            //     if ($this->m_conditional_group[0][0]->group){
            //         $this->endConditional();
            //         igk_dev_wln("end first condition ..... ");
            //     }

            //     // check for new conditional start
            //     $cond = $this->m_conditionals[0];
            //     if ($cond->i =='v-if'){
            //         $this->_endOrCreateConditionalGroup($t); 
            //     }
            // }
            // if ($v_info && $v_info->start_child){
            //     // close array childs 
            //     $this->m_sb->append("]"); 
            // }
            // // close function list
    
            // // close function 
            // if ($this->m_func_depth){
            //     $this->m_sb->append(")"); 
            //     $this->m_func_depth--;
            // }
            // if ($this->m_conditionals ){
            //     $cond =  $this->m_conditionals[0];
            //     $skip_conditions = true;
            //     if($cond->depth > $this->m_globalDepth){
            //         // force end condition 
            //         igk_dev_wln('force end condition');
            //     } else {
            //         if ($cond->t === $t){
            //             $this->_endOrCreateConditionalGroup($t);
            //             $skip_conditions = false;
            //         }
            //     }
            // }
            // // wait for condition next marker
            // if (!$skip_conditions && $this->m_conditional_group){ 
            //     $group = $this->m_conditional_group[0];
            //     if (($group[0]->group) || ($group[0]->i=='v-else')){ 
            //         $this->endConditional();
            //     } else {
            //         $group[0]->group = true;
            //         if ($cond && ($cond->i  == 'v-else')){
            //             $this->endConditional();
            //         }
            //     }
            // } 
            $closed = true;
        } 

        !$closed && $this->_close($t, $v_info, $has_childs, true); 

      

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
           
            if ($this->m_conditional_group){
                $this->endConditional();
            }
            if ($this->m_globalStart_Array || ($this->m_close_childs > 0)) {
                $this->m_close_childs = 0;
                $this->m_sb->append("]");
            }
            if ($this->m_globalChildCounter <= 1) {
                $this->m_sb->set(trim($this->m_sb . '', '[]'));
            }
        }
    }
    private function _startChildVisit(HtmlNode $t)
    {
        $s = false;
        if (is_null($this->m_globalDepth)) {
            $this->m_globalDepth = 0;
            $s = true;
        }
        // store child state in used
        array_unshift($this->m_child_state, (object)[
            'sep' => '', 
            'depth' => $this->m_globalDepth,
            'target'=>$t]);
        if (!$s) {
            $this->m_globalDepth++;
        }
    }
    /**
     * check end conditional
     * @param mixed $t 
     * @param mixed $attr 
     * @param mixed $first_child 
     * @param mixed $last_child 
     * @return void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected function _checkEndCondition($t, $attr, $first_child, $last_child)
    {
        if (!$this->isConditionnal($t, $attr, $first_child, $last_child, true)) {
            // $this->_stopeEndConditionalGroup();
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
               
                    $this->_downgradeGlobalChildCount();

                    array_push($this->m_conditional_group[0], $c);
                    $this->endConditional();
                }
                if ($c->i == 'v-if') {
                    // + | start conditional group                    
                    array_unshift($this->m_conditional_group, [$c]);
                    $c->buffer = $this->m_sb.'';
                } else {
                    if ($c->i == 'v-else-if') {
                        $this->_downgradeGlobalChildCount();
                        
                        array_push($this->m_conditional_group[0], $c);
                        $this->m_conditional_group[0][0]->group = false;
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
        $is_inloop = $this->_preserveContent(); 

        $sep = 0;
        $else_block = null; 

        $stop = false;
        $baseDepth = $c[0]->depth;
        $conditions = [];
        while (!$stop) {

            $n_build = new StringBuilder();
            $else = 'null';
            $lsep = '';
            $sep = 0;
            while (count($c) > 0) {
                $q = array_pop($c);              
                if ($q->i == 'v-else') {
                    $else = $r . ""; //self::_GetExpression($q->v,true);      
                    $else_block = $q;
                    $stop = true;
                } else {
                    $cond = self::_GetExpression($q->v, !$is_inloop);
                    if (($q->first) && (($q->last) || ($else_block && $else_block->last))) {
                        // $q->sb->rtrim('[');
                        $this->m_single_item = true;
                    }
                    $tr = trim($r . '');
                    $is_array = strpos($tr, '[') === 0; // start with array
                    $tr = trim($tr, '[],');
                    $express = $tr . '';
                    if ($q->childCount > 0) {
                        $express = '[' . $express . ']';
                    }
                    $express = sprintf("%s%s%s?%s", '', $sep ? '' : '', $cond, $express);
                    if ($is_array){
                        $express = '['.$express;
                    }
                    // $n_build->append($express);
                    $n_build->set($express. $lsep.$n_build);
                    $sep++;
                    //$lsep = '/*:*/:(';
                    $lsep = ':(';
                }
                //restore backg up
                $r = $q->sb;
            }
            $m = '';

            if ($top) {
                $m = sprintf("[%s:%s]", $n_build . '', $else);
            } else {

                $lf = str_repeat(')', $sep-1);

                // $n_build->append(str_repeat(')', $sep-1));

                $m = sprintf("%s:%s%s", $n_build . '', $else.$lf, $sep > 1 ? '' : '');
                
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
        $m = ltrim(implode(',', $conditions), '(');
        if (count($this->m_conditional_group) == 0) {
            // $r->set($m);
            $r->append($m);
            $this->m_sb = $r;
        } else {
            $r->append($m);
            $this->m_sb = $r;
            //$this->m_sb->set($m);
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
                igk_debug_wln("add conditional : ".$k." : ".$this->m_globalDepth);
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
                    'group' => $groups,
                    'buffer' =>null
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

    private function _preserveContent(): bool
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
                // + | push on array list items
                $src = sprintf(
                    '(function(l,ctx,key){for(key %s l){((%s)=>this.push((function(){ return %s}).apply(ctx)))(l[key])}return this}).apply([],[%s, /*  context - contex=== */ this])',
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
