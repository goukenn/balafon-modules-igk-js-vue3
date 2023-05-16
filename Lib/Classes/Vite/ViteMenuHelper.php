<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteMenuHelper.php
// @date: 20230504 19:33:41
namespace igk\js\Vue3\Vite;

use IGK\Controllers\BaseController;
use IGK\Helper\Activator;
use IGK\Helper\ViewHelper;
use igk\js\common\JSExpression;
use igk\js\Vue3\Vite\Helper\RoutedMenusHelper;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Regex\Replacement;
use IGKException;
use ReflectionException;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite
*/
class ViteMenuHelper{
    var $source;
    private $m_idcounter = 0;
    const MENU_NAME = 'vue-vite-menus.pinc';

    /**
     * load menu with 
     * @param BaseController $ctrl 
     * @param string|null $menuName 
     * @return mixed 
     * @throws IGKException 
     */
    public static function LoadMenu(BaseController $ctrl, string $menuName=null){ 
        $m = $menuName ?? self::MENU_NAME;
        $file = $ctrl->configFile($m);
        $helper = new static;
        $menu = new RoutedMenusHelper($ctrl, $helper);        
        $tab = ViewHelper::Inc($file, [
            'ctrl'=>$ctrl,
            'user'=>$ctrl->getUser(),
            'helper'=>$helper,
            'menu'=>$menu
        ]);  
        if ($binding = $menu->getMenus() ){
            $tab = array_merge($binding, $tab);
        }
        return $tab;
    }

    /**
     * build array an return a parsed array
     * @param array $tab 
     * @param mixed $source 
     * @return array 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public static function Build(array $tab, $source = null)
    {
        $builder = new static;
        $builder->source = $source;
        return $builder->parse($tab);
    }
    /**
     * tranform array to menu specification 
     * @param mixed $tab 
     * @return array
     */
    public function parse(array $tab)
    {
        $root = $this->source ?? [];
        $count = & $this->m_idcounter;
        $t_tab = [(object)['keys' => array_keys($tab), 'data' => $tab, 'p' => null]];
        $rp =  new Replacement;
        $rp->add('/\\s+/','');
        $rp->add('/[^a-z0-9_]+/i','_');
        

        while (count($t_tab) > 0) {
            $q = array_shift($t_tab);
            sort($q->keys);
            while (count($q->keys) > 0) {


                $key = array_shift($q->keys);
                $data = $q->data[$key];

                if (is_numeric($key)) {
                    if (is_string($data)) {
                        if ($q->p) {
                            $q->p->title = $data;
                        }
                        continue;
                    }
                } else {
                    if ($data instanceof ViteMenuInfo) {
                        $info = $data;
                    } else {
                        $info = Activator::CreateNewInstance(ViteMenuInfo::class, $data);
                    }
                    $info->key = $key;
                    if (empty($info->id)){
                        // + | convert key to js identifier 
                        $info->id = $rp->replace($key); 
                        if (empty($info->id)){
                            // + | generate an identifier 
                            $info->id = hash("crc32b", "id-".$count+rand(1-100) . time());
                            $count++;
                        }
                    }
                }
               
                $pkey = substr($info->key,0, strrpos($info->key,'.')); 
                $to_root = true;
                if ($pkey){
                    $plist = null;
                    $fplist = null;
                    while($pkey && !isset($root[$pkey]) && (($cpos = strrpos($pkey, '.'))!==false)){
                         
                        $npkey = substr($pkey, 0, $cpos);
                        $olist = $plist ;
                        $plist = new ViteMenuInfo;
                        $plist->key = $pkey;
                        $plist->title = igk_io_path_ext($pkey);
                        $plist->id = $rp->replace($pkey);
                        if (!$fplist){
                            $fplist = $plist;
                        }
                        if ($olist){
                            $plist->items = [$olist];
                        }
                        $pkey = $npkey;                    
                    }
                    if ($plist && $pkey){
                        if (!isset($root[$plist->key])){
                            $root[$plist->key] = $plist;
                        }else{
                            igk_die("not implelement");
                        }
                        //$root[$pkey]->items[] = $plist;
                    }

                    if ($fplist || isset($root[$pkey])){
                        $tp = $fplist ?? $root[$pkey];
                        if (!$tp->items){ 
                            $tp->items = [];
                        }
                        $tp->items[$info->key] = $info; 
                        $to_root =false;
                    }
                }
                if ($to_root){
                    $root[$info->key] = $info;
                }

                if ($info->items){ 
                    $tab_i = [];
                    foreach($info->items as $k=>$v){
                        if(is_numeric($k)){
                            $tab_i[] = $v;
                            continue;
                        }
                        if (strpos($k, $info->key.".")!==0){
                            $k = $info->key.".".$k;
                        }
                        $tab_i[$k]=$v;

                    }
                    $t_tab[] = (object)['keys' => array_keys($tab_i), 'data' => $tab_i, 'p' => $info];
                    $info->items = null;
                }
            }
        }

        return $root;
    }
    private function _addLib($lib, $import){

    }
    public function litteral($expression){
        return JSExpression::Litteral($expression);
    }
    public function useIonIcon(string $name){
        $this->_addLib('IonIcon', '@/lib/IonIcon/Icon.vue');
        return $this->litteral(sprintf('h(IonIcon,{name:"%s"})', $name));
    }
}