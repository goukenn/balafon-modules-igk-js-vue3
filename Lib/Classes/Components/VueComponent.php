<?php
namespace igk\js\Vue3\Components;
 
use igk\js\Vue3\Html\VueLoadingContext;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlLoadingContext;
use IGK\System\Html\IHtmlContextContainer;


/** @package igk\js\Vue3\Components */
class VueComponent extends HtmlNode implements IHtmlContextContainer{
    /**
     * get component loading context 
     * @return ?HtmlLoadingContext  
     * */
    public function getContext():?HtmlLoadingContext{
        static $context =null;
        if (is_null($context)){
            $context = new VueLoadingContext; 
            $context->name = VueConstants::WEB_CONTEXT;
            $context->load_content = false;
            $context->load_expression = false;
            $context->node = $this;
            $context->ignore_tags = ['script', 'style'];
        }
        return $context;
    }
    public static function CreateWebNode($n, $attributes = null, $indexOrArgs = null)
    {
        return parent::CreateWebNode($n, $attributes, $indexOrArgs); 
    }
   
}