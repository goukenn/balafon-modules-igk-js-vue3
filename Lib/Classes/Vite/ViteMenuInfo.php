<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteMenuInfo.php
// @date: 20230504 19:33:03
namespace igk\js\Vue3\Vite;


///<summary></summary>
/**
* menu information 
* @package igk\js\Vue3\Vite
*/
class ViteMenuInfo
{
    const MENU_ROUTER_LINK = 0;
    const MENU_REF = 1;
    private $m_type = self::MENU_ROUTER_LINK;
    var $key;
    /**
     * js identifier 
     * @var string 
     */
    var $id;

    var $title;
    /**
     * svg presentation icons
     * @var mixed
     */
    var $svg;
    var $alt;
    var $target;
    /**
     * 
     * @var ?bool|string profile auth_required for display
     */
    var $auth;
    /**
     * do ajx request if href
     * @var ?bool
     */
    var $ajx; 
    /**
     * array|match()location that match
     * @var ?array 
     */
    var $locations;

    var $route;

    /**
     * prefered index position
     * @var mixed
     */
    var $index;
    /**
     * child items
     * @var mixed
     */
    var $items;

    /**
     * fallback href in case route not found
     * @var mixed
     */
    var $href;
}