<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner;

class InspectionRunner
{
    private $phpStormPath;
    private $piwikPath;
    private $inspectionsOutputPath;
    private $inspectShPath;

    public function __construct($phpStormPath, $piwikPath, $inspectionsOutputPath)
    {
        $this->phpStormPath = realpath($phpStormPath);
        $this->inspectShPath = $this->phpStormPath . '/bin/inspect.sh';
        if (!file_exists($this->inspectShPath)) {
            throw new \InvalidArgumentException("Cannot find inspect.sh file at '" . $this->inspectShPath . "'.");
        }

        $this->piwikPath = realpath($piwikPath);
        if (!is_dir($this->piwikPath)) {
            throw new \InvalidArgumentException("Path to Piwik '" . $piwikPath . "' does not exist.");
        }

        if (!is_dir($inspectionsOutputPath)) {
            $success = @mkdir($inspectionsOutputPath);
            if (!$success) {
                throw new \Exception("Couldn't create inspections output path '" . $inspectionsOutputPath . "'.");
            }
        }
        $this->inspectionsOutputPath = realpath($inspectionsOutputPath);
    }

    public function inspectPlugin($plugin)
    {
        $inspectionsOutputForPlugins = $this->inspectionsOutputPath . '/' . $plugin;
        if (!is_dir($inspectionsOutputForPlugins)) {
            mkdir($inspectionsOutputForPlugins);
        } else {
            // remove existing inspections
            shell_exec("rm '$inspectionsOutputForPlugins'/*.xml");
        }

        $inspectShPath = $this->phpStormPath . '/bin/inspect.sh';
        $inspectionProfilePath = $this->getInspectionProfilePath();
        $pluginPath = $this->piwikPath . '/plugins/' . $plugin . '/';
        $phpStormOutputPath = $inspectionsOutputForPlugins . "/phpstorm.out";

        if (!is_dir($pluginPath)) {
            throw new \Exception("Cannot find plugin directory '$pluginPath'.'");
        }

        $command = $inspectShPath . " '{$this->piwikPath}' '{$inspectionProfilePath}' '{$inspectionsOutputForPlugins}' -d '{$pluginPath}' -v2 > '$phpStormOutputPath' 2>&1";

        shell_exec($command);

        $this->checkForErrorInPhpStormOutput($phpStormOutputPath);

        $inspectionResults = $this->getInspectionResults($inspectionsOutputForPlugins);
        return $inspectionResults;
    }

    private function getInspectionProfilePath()
    {
        $path = __DIR__ . '/../../../../../../Plugin_Quality_Checks.xml';
        return realpath($path);
    }

    private function getInspectionResults($inspectionsOutputForPlugins)
    {
        $inspectionFilesThatMatter = array(
            'PiwikNonApiInspection.xml', 'PhpDeprecationInspection.xml', 'PhpUndefinedMethodInspection.xml'
        );

        $inspections = array();
        foreach ($inspectionFilesThatMatter as $file) {
            $path = $inspectionsOutputForPlugins . '/' . $file;
            if (file_exists($path)) {
                $inspection = new Inspection($path);
                if (!empty($inspection->problems)) {
                    $inspections[] = $inspection;
                }
            }
        }
        return $inspections;
    }

    private function checkForErrorInPhpStormOutput($phpStormOutputPath)
    {
        if (!file_exists($phpStormOutputPath)) {
            throw new \InvalidArgumentException("No output found for PHPStorm execution, probably killed.");
        }

        $phpStormOutputContents = file_get_contents($phpStormOutputPath);

        if (strpos($phpStormOutputContents, "Analyzing code in [piwik]") === false) {
            $message = "Cannot find expected output in PHPStorm execution output, PHPStorm may have failed:\n\n"
                     . "Actual output: '" . $phpStormOutputContents . "'";
            throw new \InvalidArgumentException($message);
        }
    }
}