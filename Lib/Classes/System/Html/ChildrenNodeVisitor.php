<?php

// @author: C.A.D. BONDJE DOUE
// @filename: ChildrenNodeVisitor.php
// @date: 20230627 11:43:17
// @desc: children visitor
namespace igk\js\Vue3\System\Html;

use IGK\System\Html\Dom\HtmlNode;

final class ChildrenNodeVisitor extends HtmlNode{
    private $m_children;
    protected $tagname = 'vue3:child-visitor';
    
    function getCanRenderTag(){
        return false;
    }
    function getRenderedChilds($options = null)
    {
        return $this->m_children;
    }
    function __construct(array $children)
    {
        parent::__construct();
        $this->m_children = $children;
    }
    
}