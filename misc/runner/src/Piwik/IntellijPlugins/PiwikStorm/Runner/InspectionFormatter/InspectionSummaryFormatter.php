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

class InspectionSummaryFormatter implements InspectionFormatter
{
    /**
     * @param string $plugin
     * @param Inspect
     * ion[] $inspections
     * @return string
     */
    public function format($plugin, $inspections)
    {
        if (empty($inspections)) {
            $result = "<info>No problems reported for $plugin.</info>";
        } else {
            $problemSummary = $this->getProblemSummary($inspections);

            $result = "<error>Found problems in $plugin:</error>";
            if (!empty($problemSummary['non-api'])) {
                $result .= " " . $problemSummary['non-api'] . ' uses of non-@api symbols';
            }
            if (!empty($problemSummary['deprecated'])) {
                $result .= ', ' . $problemSummary['deprecated'] . ' uses of @deprecated symbols';
            }
            if (!empty($problemSummary['undefinedMethods'])) {
                $result .= ', ' . $problemSummary['undefinedMethods'] . " undefined methods.";
            }
        }

        return $result;
    }

    /**
     * @param Inspection[] $inspections
     * @return array
     */
    private function getProblemSummary($inspections)
    {
        $result = array();
        foreach ($inspections as $inspection) {
            if ($this->inspectionIs($inspection, 'PiwikNonApiInspection.xml')) {
                $key = 'non-api';
            } else if ($this->inspectionIs($inspection, 'PhpDeprecationInspection.xml')) {
                $key = 'deprecated';
            } else if ($this->inspectionIs($inspection, 'PhpUndefinedMethodInspection.xml')) {
                $key = 'undefinedMethods';
            } else {
                continue;
            }

            $result[$key] = count($inspection->problems);
        }
        return $result;
    }

    private function inspectionIs(Inspection $inspection, $fileName)
    {
        return strpos($inspection->filePath, $fileName) !== false;
    }
}