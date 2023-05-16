<?php

namespace igk\js\Vue3\Vite;

use IGK\Controllers\BaseController;
use IGK\System\Shell\OsShell;

class ViteAppManagement{
    private $m_app_dir;
    private $m_ctrl;
    private $m_yarn;
    public function __construct(BaseController $ctrl, string $app_dir)
    {
        $this->m_app_dir = $app_dir;
        $this->m_ctrl = $ctrl;
        $this->m_yarn = OsShell::Where('yarn');
    }

}