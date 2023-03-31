<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueCommandBase.php
// @date: 20230330 19:02:36
// @desc: 


namespace igk\js\Vue3\System\Console\Commands;
 
use IGK\System\Console\AppExecCommand;

abstract class VueCommandBase extends AppExecCommand{
    const CATEGORY = 'vue3';
    
    var $category = self::CATEGORY;
}