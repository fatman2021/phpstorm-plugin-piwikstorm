<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\IntellijPlugins\PiwikStorm\Runner;

class ArtifactsUploader
{
    private $uploadArtifactsUniqueId;
    private $artifactsPass;
    private $unprotectedArtifacts;

    public function __construct($uploadArtifactsUniqueId, $artifactsPass, $unprotectedArtifacts)
    {
        $this->uploadArtifactsUniqueId = $uploadArtifactsUniqueId;
        $this->artifactsPass = $artifactsPass;
        $this->unprotectedArtifacts = $unprotectedArtifacts;
    }

    /**
     * @param $folderToUpload
     * @return string
     */
    public function upload($plugin, $folderToUpload)
    {
        $tarArchivePath = $this->createTarArchive($folderToUpload);

        $artifactsUploadUrl = $this->getArtifactsUrlPostUrl($plugin);
        $this->post($artifactsUploadUrl, $tarArchivePath);

        return $this->getArtifactsViewUrl($plugin);
    }

    private function createTarArchive($folderToUpload)
    {
        $archivePath = "./inspections.tar.bz2";
        $this->executeCommand("tar -cjf $archivePath '" . $folderToUpload . "' --exclude=.gitkeep");
        return $archivePath;
    }

    private function getArtifactsUrlPostUrl($plugin)
    {
        $url = "https://builds-artifacts.piwik.org/upload.php";
        $url .= "?auth_key=" . $this->artifactsPass;
        $url .= "&build_id=" . $this->uploadArtifactsUniqueId;
        $url .= "&branch=inspections.$plugin";

        if (!$this->unprotectedArtifacts) {
            $url .= "&protected=1";
        }

        $url .= "&artifact_name=inspections";
        return $url;
    }

    private function post($artifactsUploadUrl, $tarArchivePath)
    {
        $this->executeCommand("curl -X POST --data-binary @$tarArchivePath '$artifactsUploadUrl'");
    }

    private function executeCommand($command)
    {
        $command = $command . ' 2>&1';

        exec($command, $output, $returnCode);

        if ($returnCode != 0) {
            $command = preg_replace("/auth_key=[^&]+/", "", $command);
            throw new \Exception("Command '" . $command . "' failed: " . implode("\n", $output));
        }
    }

    private function getArtifactsViewUrl($plugin)
    {
        $url = "http://builds-artifacts.piwik.org/";
        if (!$this->unprotectedArtifacts) {
            $url .= "protected/";
        }
        $url .= "inspections.$plugin/";
        $url .= $this->uploadArtifactsUniqueId . "/";
        return $url;
    }
}
