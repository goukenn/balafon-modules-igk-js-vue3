<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteNodeBase.php
// @date: 20230426 08:31:57
namespace igk\js\Vue3\Vite\Html\Dom;

use igk\js\Vue3\Components\VueComponent;
use IGK\System\Html\Dom\HtmlNode;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite\Html\Dom
*/
abstract class ViteNodeBase extends VueComponent{
    public function __construct(?string $tagname=null){
        parent::__construct($tagname);
    }
}