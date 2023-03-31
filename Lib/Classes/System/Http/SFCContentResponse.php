<?php
// @author: C.A.D. BONDJE DOUE
// @file: SFCContentResponse.php
// @date: 20230111 14:43:28
namespace igk\js\Vue3\System\Http;

use IGK\System\Http\WebResponse;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\Http
*/
class SFCContentResponse extends WebResponse{
    /**
     * .ctr construct a Single file component response
     */
    public function __construct($n){
        parent::__construct($n, 200, [
            "Content-Type: text/javascript"
        ]);
    }
}