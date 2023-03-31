<?php
// @author: C.A.D. BONDJE DOUE
// @file: Vuex.php
// @date: 20230330 23:51:10
namespace igk\js\Vue3\Libraries\vuex;

use igk\js\Vue3\Libraries\VueLibraryBase;
use IGK\System\Html\Dom\HtmlDocumentNode;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Libraries\vuex
*/
class Vuex extends VueLibraryBase{

    public static function InitDoc(HtmlDocumentNode $doc){

    }
    public function useLibrary($option = null): array {
        return ['VueEx'];
    }

    public function render($option = null): ?string { 
        return null;
    }

}