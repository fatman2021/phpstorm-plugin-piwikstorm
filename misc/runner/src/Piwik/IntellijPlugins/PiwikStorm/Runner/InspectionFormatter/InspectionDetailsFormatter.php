<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner\InspectionFormatter;

use Piwik\IntellijPlugins\PiwikStorm\Runner\Inspection;
use Piwik\IntellijPlugins\PiwikStorm\Runner\InspectionFormatter;

class InspectionDetailsFormatter implements InspectionFormatter
{
    /**
     * @param string $plugin
     * @param Inspection[] $inspections
     * @return string
     */
    public function format($plugin, $inspections)
    {
        $result = "";

        if (empty($inspections)) {
            $result .= "No problems reported for <info>$plugin</info>.";
        } else {
            $result .= "Inspection Results for <error>$plugin</error>:\n";
            foreach ($inspections as $inspection) {
                $result .= $this->formatInspectionDetails($inspection);
            }
        }

        return $result;
    }

    private function formatInspectionDetails(Inspection $inspection)
    {
        $result = '  ## ' . reset($inspection->problems)->problemClass . "\n";
        foreach ($inspection->problems as $problem) {
            $result .= '    ' . $problem->file . " at line " . $problem->line . " -> " . $problem->description . "\n";
        }
        $result .= "\n";
        return $result;
    }
}