<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupPhpStorm extends Command
{
    const NAME = 'inspections:setup-phpstorm';
    const PHPSTORM_URL = 'https://www.dropbox.com/s/w9naqsmgmul1gua/PhpStorm-8.0.3.tar.gz?dl=0';

    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription("Downloads and sets up PHPStorm for running inspections in a CI server.");
        $this->addOption('piwik-path', null, InputOption::VALUE_REQUIRED, "Path to the Piwik root directory. For installing .idea files.");
        $this->addOption('phpstorm-output-path', null, InputOption::VALUE_REQUIRED,
            "Folder to extract PHPStorm files to. Defaults to current directory.", ".");
        $this->addOption('phpstorm-license-file', null, InputOption::VALUE_REQUIRED,  "Path to the PHPStorm license to use. Required.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $piwikPath = $input->getOption('piwik-path');
        if (empty($piwikPath)) {
            throw new \InvalidArgumentException("--piwik-path option is required.");
        }
        if (!is_dir($piwikPath)) {
            throw new \InvalidArgumentException("'$piwikPath' is not a directory.");
        }

        $phpstormLicensePath = $input->getOption('phpstorm-license-file');
        if (empty($phpstormLicensePath)) {
            throw new \InvalidArgumentException("--phpstorm-license-file is required.");
        }
        if (!is_file($phpstormLicensePath)) {
            throw new \InvalidArgumentException("'$phpstormLicensePath' is not a valid file.");
        }

        $phpstormOutputPath = $input->getOption('phpstorm-output-path');

        $output->writeln("Downloading PHPStorm...");
        $phpstormArchivePath = $this->downloadPhpStorm();

        $output->writeln("Extracting PHPStorm...");
        $this->extractPhpStorm($phpstormArchivePath, $phpstormOutputPath);

        $output->writeln("Setting up PHPStorm...");
        $this->installPhpStormLicense($phpstormLicensePath);
        $this->installPiwikStormPluginJar();
        $this->copyDefaultPhpStormVmOptions($phpstormOutputPath);

        $output->writeln("Setting up Piwik IDEA project...");
        $this->copyDefaultIdeaProject($piwikPath);

        $output->writeln("Done!");
    }

    private function downloadPhpStorm()
    {
        $dest = "./PhpStorm.tar.gz";

        $this->exec('wget -q "' . self::PHPSTORM_URL . '" -O ' . $dest);

        return $dest;
    }

    private function extractPhpStorm($phpstormArchivePath, $phpstormOutputPath)
    {
        if (!is_dir($phpstormOutputPath)) {
            $this->exec("mkdir -p '$phpstormOutputPath'");
        }

        $this->exec("tar -xvf '$phpstormArchivePath' -C '$phpstormOutputPath'");
        $this->exec("mv '$phpstormOutputPath'/PhpStorm*/* '$phpstormOutputPath'");
    }

    private function installPhpStormLicense($phpstormLicensePath)
    {
        $configDir = $this->getPhpStormConfigDirectory();

        $this->exec("mkdir -p " . $configDir);
        $this->exec("cp '" . $phpstormLicensePath . "' '$configDir/phpstorm80.key'");
    }

    private function installPiwikStormPluginJar()
    {
        $configDir = $this->getPhpStormConfigDirectory();
        $pluginsDir = $configDir . "/plugins";

        $latestVersion = $this->getLatestVersion();
        $piwikStormJarUrl = "https://github.com/piwik/phpstorm-plugin-piwikstorm/releases/download/$latestVersion/PiwikStorm.jar";

        $this->exec("mkdir -p " . $pluginsDir);
        $this->exec("wget -q '$piwikStormJarUrl' -O '$pluginsDir/PiwikStorm.jar'");
    }

    private function getLatestVersion()
    {
        return $this->exec('cd \'' . __DIR__ . '\' && git describe --abbrev=0 --tags');
    }

    private function copyDefaultIdeaProject($piwikPath)
    {
        $basePath = $this->getBasePath();
        $this->exec("cp -R '$basePath/misc/runner/resources/piwik.idea' '$piwikPath/.idea'");
    }

    private function copyDefaultPhpStormVmOptions($phpstormOutputPath)
    {
        $basePath = $this->getBasePath();
        $this->exec("cp '$basePath/misc/runner/resources/phpstorm64.vmoptions' '$phpstormOutputPath/bin/phpstorm64.vmoptions'");
    }

    private function exec($command) // TODO: code redundancy w/ ArtifactsUploader
    {
        $command = $command . ' 2>&1';

        exec($command, $output, $returnCode);

        if ($returnCode != 0) {
            throw new \Exception("Command '" . $command . "' failed: " . implode("\n", $output));
        }

        return implode("\n", $output);
    }

    private function getPhpStormConfigDirectory()
    {
        return $this->getHomeDirectory() . "/.WebIde80/config";
    }

    private function getHomeDirectory()
    {
        return getenv('HOME');
    }

    private function getBasePath()
    {
        return __DIR__ . '/../../../../../../..';
    }
}