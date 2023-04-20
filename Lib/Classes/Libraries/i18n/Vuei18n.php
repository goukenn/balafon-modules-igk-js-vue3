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
use IGKException;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use ReflectionException;
use IGK\System\Exceptions\EnvironmentArrayException;

class Vuei18n
{
    /**
     * init document helper 
     * @param mixed $doc 
     * @param null|BaseController $ctrl 
     * @param bool $useglobal_resource 
     * @param string $varName 
     * @return VueLibraryVar 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     * @throws EnvironmentArrayException 
     */
    public static function InitDoc($doc, ?BaseController $ctrl, bool $useglobal_resource = false , string $varName="i18n")
    {
        $mod = igk_require_module(\igk\js\Vue3_i18n::class);
        $mod->initDoc($doc);
        $i18n = new VueLibraryVar($varName, "createI18n", "VueI18n");
        $i18n->setDeclarationListener(function ($n, $method, $options = null) use ($ctrl, $useglobal_resource): ?string {
            return self::VueRenderI18nLocaleSetting($n, $method, $ctrl, $useglobal_resource, $options);    
        });
        return $i18n;
    }

    /**
     * load render 18nlocale 
     * @param string $n 
     * @param string $method 
     * @param BaseController $ctrl 
     * @param mixed $useglobal_resource 
     * @param mixed $options 
     * @return string 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public static function VueRenderI18nLocaleSetting(string $n, string $method, BaseController $ctrl, bool $useglobal_resource, $options ){
        /**
        * @var IJSExpressionOptions $obj
        */
       $obj = igk_createobj();  
       $obj->detectMethod = false;
       $obj->useObjectNotation = true;
       // $default_lang = igk_getv($options, "default_lang", igk_configs()->default_lang);
       $fallback_lang = igk_getv($options, "fallback_lang", 'en');
       $current_lang = R::GetCurrentLang();
       $sb = new StringBuilder;
       $msg = JSExpression::Stringify((object)I18nLocaleHelper::LoadLocale($ctrl, $useglobal_resource, $fallback_lang), $obj);
 

       $sb->appendLine(sprintf('let %s = %s(', $n, $method));
       $sb->appendLine(JSExpression::Stringify((object)[
           "legacy"=>false, // + |  to support composition api - avoid error - 24
           "locale" => $current_lang,
           "fallbackLocale" => $fallback_lang, 
           "messages" => JSExpression::Litteral($msg),
       ], (object)['objectNotation' => true]));
       $sb->appendLine(");");
       return $sb . '';
   }
}