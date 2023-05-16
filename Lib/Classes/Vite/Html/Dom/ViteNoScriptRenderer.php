<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteNoScriptRenderer.php
// @date: 20230426 08:31:24
namespace igk\js\Vue3\Vite\Html\Dom;
use function igk_resources_gets as __;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite\Html\Dom
*/
class ViteNoScriptRenderer extends ViteNodeBase{
    protected $tagname ='noscript';
    private $m_host;
    public function getCanAddChilds()
    {
        return false;
    }
    
    public function render($options=null){
        return sprintf('<%s>', $this->tagname). 
               sprintf(__('no script application available')).
               sprintf('</%s>', $this->tagname);
    }
    public function __construct(ViteApplicationNode $host){
        $this->m_host = $host;
        parent::__construct();
    }
}