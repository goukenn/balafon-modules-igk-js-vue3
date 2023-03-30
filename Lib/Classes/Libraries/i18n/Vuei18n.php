<?php

// @author: C.A.D. BONDJE DOUE
// @filename: Vuei18n.php
// @date: 20230303 10:20:32
// @desc: 
namespace igk\js\Vue3\Libraries\i18n;

 
use IGK\Controllers\BaseController;
use igk\js\common\JSExpression;
use igk\js\common\IJSExpressionOptions;
use igk\js\Vue3\Libraries\VueLibraryVar;
use IGK\Resources\R;
use IGK\System\IO\StringBuilder;
use igk\js\Vue3_i18n\Helpers\Locale as I18nLocaleHelper;

class Vuei18n
{
    public static function InitDoc($doc, ?BaseController $ctrl, bool $useglobal_resource = false , string $varName="i18n")
    {
        $mod = igk_require_module(\igk\js\Vue3_i18n::class);
        $mod->initDoc($doc);
        $i18n = new VueLibraryVar($varName, "createI18n", "VueI18n");
        $i18n->setDeclarationListener(function ($n, $method, $options = null) use ($ctrl, $useglobal_resource): ?string {
            return self::VueRenderI18nLocaleSetting($n, $method, $ctrl, $useglobal_resource, $options);    
            //         /**
            //     * @var IJSExpressionOptions $obj
            //     */
            // $obj = igk_createobj();  
            // $obj->detectMethod = false;
            // $obj->useObjectNotation = true;
            // $default_lang = igk_getv($options, "default_lang", igk_configs()->default_lang);
            // $msg = JSExpression::Litteral(JSExpression::Stringify((object)I18nLocaleHelper::LoadLocale($ctrl, $useglobal_resource, $default_lang), $obj));

            // $sb = new StringBuilder;
            // $sb->appendLine(sprintf('let %s = %s(', $n, $method));
            // $sb->appendLine(JSExpression::Stringify((object)[
            //     "locale" => R::GetCurrentLang(),
            //     "fallbackLocale" => igk_configs()->default_lang, 
            //     "messages" => $msg,
            // ], (object)['objectNotation' => true]));
            // $sb->appendLine(");");
            // return $sb . '';
        });
        return $i18n;
    }

    public static function VueRenderI18nLocaleSetting(string $n, string $method, BaseController $ctrl, $useglobal_resource, $options ){
        /**
        * @var IJSExpressionOptions $obj
        */
       $obj = igk_createobj();  
       $obj->detectMethod = false;
       $obj->useObjectNotation = true;
       $default_lang = igk_getv($options, "default_lang", igk_configs()->default_lang);
       $sb = new StringBuilder;
       $msg = JSExpression::Stringify((object)I18nLocaleHelper::LoadLocale($ctrl, $useglobal_resource, $default_lang), $obj);

       $sb->appendLine(sprintf('let %s = %s(', $n, $method));
       $sb->appendLine(JSExpression::Stringify((object)[
           "locale" => R::GetCurrentLang(),
           "fallbackLocale" => igk_configs()->default_lang, 
           "messages" => JSExpression::Litteral($msg),
       ], (object)['objectNotation' => true]));
       $sb->appendLine(");");
       return $sb . '';
   }
}

 /*

// @author: C.A.D. BONDJE DOUE
// @filename: Vuei18n.php
// @date: 20230303 10:20:32
// @desc: 
namespace igk\js\Vue3\Libraries\i18n;

 
use IGK\Controllers\BaseController;
use igk\js\common\JSExpression;
use igk\js\common\IJSExpressionOptions;
use igk\js\Vue3\Libraries\VueLibraryVar;
use IGK\Resources\R;
use IGK\System\IO\StringBuilder;
use igk\js\Vue3_i18n\Helpers\Locale as I18nLocaleHelper;

class Vuei18n
{
    public static function InitDoc($doc, ?BaseController $ctrl, bool $useglobal_resource = false , string $varName="i18n")
    {
        $mod = igk_require_module(\igk\js\Vue3_i18n::class);
        $mod->initDoc($doc);
        $i18n = new VueLibraryVar($varName, "createI18n", "VueI18n");
        $i18n->setDeclarationListener(function ($n, $method, $option = null) use ($ctrl, $useglobal_resource): ?string {
            return self::VueRenderI18nLocaleSetting($n, $method, $ctrl, $useglobal_resource, $option);            
        });
        return $i18n;
    }
  
}

*/