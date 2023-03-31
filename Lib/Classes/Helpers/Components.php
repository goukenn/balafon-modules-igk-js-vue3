<?php
// @author: C.A.D. BONDJE DOUE
// @file: Components.php
// @date: 20230331 00:25:19
namespace igk\js\Vue3\Helpers;

use IGK\System\Exceptions\ResourceNotFoundException;
use IGK\System\Http\NotAllowedRequestException;
use IGK\System\Http\WebFileResponse;
use IGK\System\IO\Path;
use IGKException;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Helpers
*/
class Components{
    /**
     * helper: output component file
     * @param string $dir 
     * @param mixed $params 
     * @return never 
     * @throws IGKException 
     * @throws ResourceNotFoundException 
     */
    public static function OuputComponent(string $dir, ...$params){
        if ($params){
            if (file_exists($file = Path::Combine($dir, ...$params))){
                $rep = new WebFileResponse($file);
                igk_do_response($rep);
            }
            throw new ResourceNotFoundException('not found',$file);    
        }
        throw new NotAllowedRequestException();
    }
}