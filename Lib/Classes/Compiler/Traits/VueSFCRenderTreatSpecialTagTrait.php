<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueSFCRenderTreatSpecialTagTrait.php
// @date: 20230420 10:31:40
// @desc: treat special tags 


namespace igk\js\Vue3\Compiler\Traits;

use IGK\System\Html\Dom\HtmlHostChildren;
use IGK\System\IO\StringBuilder;

trait VueSFCRenderTreatSpecialTagTrait{
    use VueSFCRenderTreatHandlerAttributeTrait;

    protected function isSpecialTagMeaning($tagname, $attrs): bool{
        if (in_array($tagname, ['slot'])){
            return true;
        }
        return false;
    }
    protected function resolvSpecialTag($tagname, $attrs){
       if (method_exists($this, $fc = strtolower('_resolve_special_tag_'.$tagname))){
            return call_user_func_array([$this, $fc], func_get_args());
       }
       return  $this->_resolve_special_tag_slot($tagname, $attrs);
       
    }
    protected function _resolve_special_tag_slot($tagname, $attrs){
        $s = new StringBuilder();
        $n = igk_getv($attrs , 'name', 'default');
        $a = '';
        unset($attrs['name']);
        if ($attrs){
            $ch = '';
            $bs = new StringBuilder;
            $a = $this->handleAttributes($this->node, $attrs, $bs,true,true,false,$directive, $skip, $loop, $conditional, null, $ch, false);
            $a = $bs.'';
        }
        //$this->requestArgs['slots'] = 1;
        self::AddLib($this->m_options, 'useSlots', 'Vue'); 
        $this->m_options->defineArgs['slots'] = 'const slots=useSlots();';

        $node = $this->node;
        $def = null;
        if (!empty($content = $node->getContent()) || ($node->childCount()>0)){
            // render node 
            $children = $node->getChilds()->to_array();
            $p = igk_create_notagnode();
            $p->text($content);
            if ($children){
                $p->add(new HtmlHostChildren($children));
            }
            $visitor = new static($p);
            $visitor->m_options = $this->m_options;            
            $visitor->visit($p);
            if (!empty($rs = $visitor->m_sb.'')){
                $def= trim($rs);
            } 
        }
        // + | append slot conditional with null
        $s->append(sprintf('slots.%s?slots.%s(%s):%s', $n, $n, $a, $def ?? 'null'));         
        return $s.'';
    }

    
  
}