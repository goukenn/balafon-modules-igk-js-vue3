<?php
namespace igk\js\Vue3\Components;
 
use igk\js\Vue3\System\Html\VueLoadingContext;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlLoadingContext;
use IGK\System\Html\IHtmlContextContainer;
use IGKException;

/**
 * base vue component node
 * @package igk\js\Vue3\Components 
 * */
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
    /**
     * 
     * @param mixed $n 
     * @param mixed $attributes 
     * @param mixed $indexOrArgs 
     * @return mixed 
     * @throws IGKException 
     */
    public static function CreateWebNode($n, $attributes = null, $indexOrArgs = null)
    {
        if ($n =  parent::CreateWebNode($n, $attributes, $indexOrArgs)){
            if (!($n instanceof static)){
                $n = new VueComponentHost($n);
            }
        } 
        return $n; 
    }
    /**
     * create a node on loader 
     * @param string $name 
     * @param null|array $param 
     * @return mixed 
     */
    public static function LoadingNodeCreator(string $name, ?array $param = null)
    {
        $n = new self($name);
        if ($param) $n->setAttributes($param);
        return $n;        
    }   
}