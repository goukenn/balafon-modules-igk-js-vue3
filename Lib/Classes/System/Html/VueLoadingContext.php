<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueLoadingContext.php
// @date: 20230109 09:35:04
// @desc: 
namespace igk\js\Vue3\System\Html;

use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlLoadingContext;

class VueLoadingContext extends HtmlLoadingContext{
    /**
     * 
     * @var mixed
     */
    private $m_postCreateCallback;
    public function initialize(){
        // init loading context
        parent::initialize();
        // igk_reg_hook(\IGKEvents::HOOK_HTML_PRE_FILTER_ATTRIBUTE, [self::class, "filterAttributeCallack"]);
        // igk_reg_hook(\IGKEvents::FILTER_PRE_CREATE_ELEMENT, function($n){
        //     // igk_wln_e("create element ...");
        // });
        $this->m_postCreateCallback = function($n){
            $args = $n->args;
            $node = $args['node'];
            if ($node instanceof HtmlNode){
                $tagname = $node->getTagName();
                // igk_dev_wln_e("try ".$tagname);
                if ($tagname && (preg_match("/^igk(:|-)/", $tagname) || in_array($tagname, ["igk-img"]))){
                    // igk_wln("try to create an inner element ".$tagname);
                    // add v-pre directive to disable loading ... 
                    $args['node']->vPre();
                }            
            }
        };
        igk_reg_hook(\IGKEvents::FILTER_POST_CREATE_ELEMENT, $this->m_postCreateCallback);
    }
    public function uninitialize(){
        // 
        // igk_unreg_hook(\IGKEvents::HOOK_HTML_PRE_FILTER_ATTRIBUTE, [self::class, "filterAttributeCallack"]);
        igk_unreg_hook(\IGKEvents::FILTER_POST_CREATE_ELEMENT, $this->m_postCreateCallback);
        $this->m_postCreateCallback = null;
        parent::uninitialize();
    }

    function filterAttributeCallack(){
        
    }
    function filterPostCreateCallback(){

    }
}
