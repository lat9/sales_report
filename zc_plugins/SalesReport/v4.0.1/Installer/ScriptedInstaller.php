<?php
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        zen_deregister_admin_pages([
                                       'statsSalesReport',
                                       'stats_sales_report',
                                   ]);
        zen_register_admin_page(
            'statsSalesReport', 'BOX_REPORTS_SALES_REPORT2', 'FILENAME_STATS_SALES_REPORT2', '', 'reports', 'Y');
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(['statsSalesReport']);
    }
}
