<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner;

use DOMNode;

class InspectionProblem
{
    public $file;
    public $line;
    public $description;
    public $problemClass;

    public function __construct(DOMNode $node)
    {
        $this->file = $this->getRelativeFilePath($this->getChildNodeValue($node, 'file'));
        $this->line = $this->getChildNodeValue($node, 'line');
        $this->description = $this->getChildNodeValue($node, 'description');
        $this->problemClass = $this->getChildNodeValue($node, 'problem_class');
    }

    private function getRelativeFilePath($path)
    {
        return str_replace('file://$PROJECT_DIR$/', '', $path);
    }

    public function getChildNodeValue(DOMNode $node, $nodeName)
    {
        for ($i = 0; $i < $node->childNodes->length; ++$i) {
            $childNode = $node->childNodes->item($i);
            if ($childNode->nodeName == $nodeName) {
                return $childNode->textContent;
            }
        }
    }
}
