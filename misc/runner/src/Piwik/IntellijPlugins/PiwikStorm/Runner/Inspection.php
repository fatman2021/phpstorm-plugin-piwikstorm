<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner;

use DOMDocument;

class Inspection
{
    public $filePath;

    /**
     * @var InspectionProblem[]
     */
    public $problems = array();

    public function __construct($path)
    {
        $this->filePath = $path;

        $dom = new DOMDocument();
        $dom->load($path);

        $problems = $dom->getElementsByTagName('problem');
        for ($i = 0; $i < $problems->length; ++$i) {
            $problem = $problems->item($i);

            $this->problems[] = new InspectionProblem($problem);
        }
    }
}