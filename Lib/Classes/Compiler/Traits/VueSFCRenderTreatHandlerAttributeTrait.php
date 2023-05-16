<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueSFCRenderTreatHandlerAttributeTrait.php
// @date: 20230420 11:05:35
// @desc: 

namespace igk\js\Vue3\Compiler\Traits;

use IGK\System\ArrayMapKeyValue;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\IO\Configuration\ConfigurationEncoder;
use IGK\System\IO\StringBuilder;

/**
 * handle attribute traits 
 */
trait VueSFCRenderTreatHandlerAttributeTrait{
    /**
     * visitor attributes 
     * @param mixed $attrs 
     * @return mixed array|string|null 
     */
    protected function handleAttributes(HtmlItemBase $t, $attrs, StringBuilder $s,
        $first_child,
        $last_child,
        $has_childs,
        & $directives, 
        & $skip,
        & $v_loop,
        & $v_conditional,
        $context,
        & $ch='', $preserve=false, ?string & $content = ''){        
        if (!$preserve && isset($attrs[$tk = 'v-pre'])) {
            $content = $t->getInnerHtml();
            array_unshift($this->m_preservelist, $t);
            unset($attrs[$tk]);
            $skip = true;
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
                $skip = true;
                $content = igk_getv($attrs, $ck);
                unset($attrs[$ck]);
                if (empty(igk_getv($attrs,'innerHTML')) && self::DetectHtmlSupport($content) ){
                    $attrs['innerHTML'] = $content;
                    $content = '';
                }
            }
            if (key_exists($ck = 'v-text', $attrs)) {
                $skip = true;
                $content = igk_getv($attrs, $ck);
                $attrs['innerText'] = $content;
                $content = '';
                unset($attrs[$ck]);
            }
            // + | pre-treat directive attribute  
            if ($this->isConditionnal($t, $attrs, $first_child, $last_child)) {
                $v_conditional = true;
            }
            if ($this->isLoop($t, $attrs, $this->m_options)) {
                $v_loop = true;
            }
            // mark content as empty to avoid innerHTML setting
            if ($has_childs && $content){
                if (empty(trim($content))){                        
                    $content = '';
                }
            }

            // + | treat event - and binding       
            if ($attrs || $has_childs|| (strpos($content, '<') !== false) || (strpos($content, '&')!== false)) {
                if ($g_attr = self::_GetAttributeStringDefinition($t, $attrs, $content, $context, $this->m_options, $directives, $preserve)) {
                    $s->append($ch . "{" . $g_attr . "}");
                    $ch = ',';
                    $content = '';
                }
            }
        }
    }
}