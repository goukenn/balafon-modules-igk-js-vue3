//. menus for production 
import { getNS } from "@/igk/system";
function menus(key){
    const _menus = getNS(import.meta.env.VITE_IGK_APP_NAMESPACE+".configs.dashboardMenus") || {}; 
    return key ? _menus[key] : _menus;
}
export {
    menus
}