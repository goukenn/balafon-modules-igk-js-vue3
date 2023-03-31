<?php
// @author: C.A.D. BONDJE DOUE
// @file: RouterMenuBuilder.php
// @date: 20230109 16:24:51
namespace igk\js\Vue3\System\WinUI\Menus;

use IGK\System\Html\Dom\HtmlNode;
use IGK\System\IO\Path;

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
    public function buildItem(HtmlNode $node, string $text, string $u="#", bool $ajx=false, $options=null  ){
        $v_menu_options = igk_getv($options, "vue.routeOptions");
        if ($v_menu_options){
            if (igk_getv($v_menu_options, 'menu_hidden')){
                return null;
            }
        }
        $g = $node->vRouterLink($u, [
            "template"=>'<div>menu _ '.$text.'</div>'
        ])->setAttributes($this->attributes)
        ->Content = $text;
        return $g;
    } 
    /**
     * resolve url
     * @param mixed $uri 
     * @param mixed $ctrl 
     * @return mixed 
     */
    public function resolvUriMenu($uri, $ctrl){
        if (strpos($uri, './') == 0){
            return substr($uri,1);
        }
        if ($ctrl)
            return Path::FlattenPath($ctrl::ruri($uri));
        return $uri;
    }
}