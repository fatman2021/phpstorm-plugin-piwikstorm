<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner;

use Piwik\IntellijPlugins\PiwikStorm\Runner\InspectionFormatter\InspectionDetailsFormatter;
use Piwik\IntellijPlugins\PiwikStorm\Runner\InspectionFormatter\InspectionSummaryFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RunInspections extends Command
{
    const NAME = 'inspections:run';

    /**
     * @var BufferedOutput
     */
    private $bufferedOutput;

    public function __construct()
    {
        parent::__construct();

        $this->bufferedOutput = new BufferedOutput();
    }

    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription("Runs PHPStorm inspections on Piwik plugins and optionally uploads the results to builds-artifacts.piwik.org.");
        $this->addArgument("plugins", InputArgument::IS_ARRAY | InputArgument::REQUIRED, "The plugins to run the inspections on.");
        $this->addOption("piwik-path", null, InputOption::VALUE_REQUIRED, "The root of the Piwik install to inspect.");
        $this->addOption("phpstorm-path", null, InputOption::VALUE_REQUIRED, "The root of the PHPStorm installation to use. Must be registsered.");
        $this->addOption("print-summary", null, InputOption::VALUE_NONE,
            "Print only a summary of each plugin's inspection results. By default every problem is printed out, which can create some clutter.");
        $this->addOption("inspections-output-path", null, InputOption::VALUE_REQUIRED,
            "The folder to store inspection output in. By default, set to ./output. Will be created if it doesn't exist.", "./output");
        $this->addOption("upload-artifacts", null, InputOption::VALUE_REQUIRED,
            "If supplied, will attempt to upload the results to the builds-artifacts server. Value must be a unique ID for this run (eg, the"
            . " travis build number if running on travis-ci).");
        $this->addOption('artifacts-pass', null, InputOption::VALUE_REQUIRED, "Artifacts secret token.");
        $this->addOption('unprotected-artifacts', null, InputOption::VALUE_NONE, "If supplied, will not store artifacts in the protected folder.");
        $this->addOption('upload-output-artifact', null, InputOption::VALUE_NONE, "If supplied, the output of the command will be stored as an artifact.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $piwikPath = $input->getOption('piwik-path');
        if (empty($piwikPath)) {
            throw new \InvalidArgumentException("The 'piwik-path' option is required.");
        }

        $phpStormPath = $input->getOption('phpstorm-path');
        if (empty($phpStormPath)) {
            throw new \InvalidArgumentException("The 'phpstorm-path' option is required.");
        }

        $inspectionsOutputPath = $input->getOption('inspections-output-path');
        $printSummary = $input->getOption('print-summary');
        $uploadArtifactsUniqueId = $input->getOption('upload-artifacts');
        $artifactsPass = $input->getOption('artifacts-pass');
        $useUnprotectedArtifacts = $input->getOption('unprotected-artifacts');
        $uploadOutputAsArtifact = $input->getOption('upload-output-artifact');

        if (!empty($uploadArtifactsUniqueId)
            && empty($artifactsPass)
        ) {
            throw new \InvalidArgumentException("Artifacts password not supplied.");
        }

        $inspectionRunner = new InspectionRunner($phpStormPath, $piwikPath, $inspectionsOutputPath);
        $inspectionFormatter = $printSummary ? new InspectionSummaryFormatter() : new InspectionDetailsFormatter();
        $artifactUploader = new ArtifactsUploader($uploadArtifactsUniqueId, $artifactsPass, $useUnprotectedArtifacts);

        $this->writeln($output, "Inspecting plugins...");

        $foundProblems = false;

        $plugins = $input->getArgument("plugins");
        foreach ($plugins as $plugin) {
            $inspectionsOutputForPlugin = $inspectionsOutputPath . '/' . $plugin;

            $inspections = $inspectionRunner->inspectPlugin($plugin);
            $this->filterIrrelevantProblems($inspections);
            if (!empty($inspections)) {
                $foundProblems = true;
            }

            $formattedInspections = $inspectionFormatter->format($plugin, $inspections);

            $this->writeln($output, $formattedInspections);

            if (!empty($uploadArtifactsUniqueId)) {
                $this->write($output, "  Uploading artifacts..");
                $artifactsUrl = $artifactUploader->upload($plugin, $inspectionsOutputForPlugin);
                $this->writeln($output, "Done.");

                $this->writeln($output, "  <comment>Find artifacts at: " . $artifactsUrl . "</comment>");
                if (!$printSummary) {
                    $this->writeln($output, "");
                }
            }
        }

        if ($uploadOutputAsArtifact) {
            file_put_contents("./all_inspections.out", $this->bufferedOutput->fetch());
            $artifactUploader->upload("all", "./all_inspections.out");
        }

        return (int)$foundProblems;
    }

    private function writeln(OutputInterface $output, $message)
    {
        $output->writeln($message);
        $this->bufferedOutput->writeln($message);
    }

    private function write(OutputInterface $output, $message)
    {
        $output->write($message);
        $this->bufferedOutput->write($message);
    }

    /**
     * @param Inspection[] $inspections
     */
    private function filterIrrelevantProblems(&$inspections)
    {
        if (empty($inspections)) {
            return;
        }

        foreach ($inspections as $key => $inspection) {
            foreach ($inspection->problems as $problemKey => $problem) {
                // remove inspection problems that are in a libs/ or vendor/ dir
                if ($this->isIrrelevantInspectionProblem($problem)) {
                    unset($inspection->problems[$problemKey]);
                }
            }

            if (empty($inspection->problems)) {
                unset($inspections[$key]);
            }
        }
    }

    private function isIrrelevantInspectionProblem(InspectionProblem $problem)
    {
        return preg_match("/plugins\\/.*?\\/(libs|vendor)/", $problem->file);
    }
}
