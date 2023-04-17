<?php
// @author: C.A.D. BONDJE DOUE
// @file: RouterMenuBuilder.php
// @date: 20230109 16:24:51
namespace igk\js\Vue3\System\WinUI\Menus;

use IGK\Controllers\BaseController;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlLoadingContext;
use IGK\System\IO\Path;
use IGKException;
use IGKValidator;
use Validator;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\WinUI\Menus
*/
class RouterMenuBuilder extends \IGK\System\WinUI\Menus\Engine{
    
    var $attributes = [];
    public function __construct($attributes=null){
        $this->attributes = $attributes;
    }
    /**
     * build items 
     * @param HtmlNode $node 
     * @param string $text 
     * @param string $u 
     * @param bool $ajx 
     * @param mixed $options 
     * @return null|HtmlItemBase 
     * @throws IGKException 
     */
    public function buildItem(HtmlNode $node, string $text, string $u="#", bool $ajx=false, $options=null): ?HtmlItemBase{
        $v_menu_options = igk_getv($options, "vue.routeOptions");
        if ($v_menu_options){
            if (igk_getv($v_menu_options, 'menu_hidden')){
                return null;
            }
        } else {
            // no a router definition 
            // if (($u=='#') || IGKValidator::IsUri($u)){
                $g = $node->add('a')->setAttribute('href', $u)->setContent($text);
                return $g;
            //}
        }
        $g = $node->vRouterLink($u, [
            "template"=>'<div>menu _ '.$text.'</div>'
        ])->setAttributes($this->attributes);
        $g->Content = $text;
        return $g;
    } 
    /**
     * resolve url
     * @param mixed $uri 
     * @param mixed $ctrl 
     * @return mixed 
     */
    public function resolvUriMenu($uri, ?BaseController $ctrl = null){
        if (strpos($uri, './') === 0){
            return substr($uri,1);
        } else {
            if (!IGKValidator::IsUri($uri)){
                $uri= '/'.ltrim($uri, '/');
            } else {
                return $uri;
            }
        }
        if ($ctrl)
            return Path::FlattenPath($ctrl::ruri($uri));
        return $uri;
    }
}