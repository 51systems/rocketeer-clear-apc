<?php
/**
 * Created by PhpStorm.
 * User: dustin
 * Date: 25/10/14
 * Time: 5:30 PM
 */

namespace ClearApc\Rocketeer;


use Rocketeer\Abstracts\AbstractTask;

class ClearApcTask extends AbstractTask
{
    /**
     * The hostname of the server
     * @var string
     */
    private $hostname;

    /**
     * The public_html directory on the server.
     *
     * @var string
     */
    private $webDir;

    /**
     * If true the user cache is cleared.
     * @var boolean
     */
    private $clearUserCache;

    /**
     * If true the apc cache is cleared.
     * @var boolean
     */
    private $clearApcCache;

    /**
     * Run the task
     *
     * @return string
     */
    public function execute()
    {
        $this->hostname = $this->config->get(sprintf('remote.connections.%s.%s.host',
            $this->connections->getConnection(),
            $this->connections->getServer()
        ));

        $file = $this->createApcFile();

        if ($file === false)
            return false;

        return $this->callApcFile($file);
    }

    private function callApcFile($filename)
    {
        $url = 'http://' . $this->hostname . '/' . $filename;

        $this->command->info(sprintf('Calling URL "%s" to clear apc cache', $url));
        if ($this->command->option('pretend')) {
            return true;
        }

        $result = false;

        //Try 5 times to get the file.
        for($i=0; $i<5; ++$i) {
            if ($result == @file_get_contents($url, false, null)) {
                break;
            } else {
                sleep(1);
            }
        }

        $this->removeApcFile($filename);
        if (!$result) {
            return $this->halt(sprintf('Unable to read %s, does the host resove?', $url));
        }

        if ($result['success']) {
            $this->command->info('APC Cache Plugin: ' . $result['message']);
            return true;
        } else {
            $this->command->error('Could not clear APC cache: ' . $result['message']);
            return false;
        }
    }

    private function removeApcFile($filename)
    {
        unlink($this->getWebPath() . $filename);
    }

    /**
     * Creates the APC file and configures it.
     *
     * @return string The filename of the apc file
     */
    private function createApcFile()
    {
        $webPath = $this->getWebPath();
        if (!$this->command->option('pretend') && !is_dir($webPath)) {
            return $this->halt(sprintf('Web dir does not exist "%s"', $webPath));
        }

        if (!$this->command->option('pretend') && !is_writable($webPath)) {
            return $this->halt(sprintf('Web dir is not writeable "%s"', $webPath));
        }

        $template = file_get_contents(__DIR__ . '/../Resources/clear_apc.php.tpl');
        $code = strtr($template, array(
            '%user%' => var_export($this->getClearUserCache(), true),
            '%opcode%' => var_export($this->getClearApcCache(), true)
        ));

        $filename = 'apc-' . md5(uniqid().php_uname()) . '.php';
        $path = $webPath.$filename;

        $this->command->info(sprintf('writing apc clearing file to "%s"', $path));

        if ($this->command->option('pretend')) {
            return $filename;
        }

        if (false === @file_put_contents($path, $code)) {
            return $this->halt(sprintf('Unable to write to file "%s"', $path));
        }

        return $filename;
    }

    /**
     * @return string
     */
    public function getWebDir()
    {
        $webDir = $this->webDir;

        if ($webDir == null)
            $webDir = $this->config->get('rocketeer-clear-apc::web_dir');

        return rtrim($webDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the full path to the web directory.
     * @return string
     */
    public function getWebPath()
    {
        return $this->releasesManager->getCurrentReleasePath() . DIRECTORY_SEPARATOR
        . $this->getWebDir();
    }

    /**
     * @param string $webDir
     */
    public function setWebDir($webDir)
    {
        $this->webDir = $webDir;
    }

    /**
     * @return boolean
     */
    public function getClearApcCache()
    {
        if (isset($this->clearApcCache))
            return $this->clearApcCache;

        return $this->config->get('rocketeer-clear-apc::clear_apc_cache');
    }

    /**
     * @param boolean $clearApcCache
     */
    public function setClearApcCache($clearApcCache)
    {
        $this->clearApcCache = $clearApcCache;
    }

    /**
     * @return boolean
     */
    public function getClearUserCache()
    {
        if (isset($this->clearUserCache))
            return $this->clearUserCache;

        return $this->config->get('rocketeer-clear-apc::clear_user_cache');
    }

    /**
     * @param boolean $clearUserCache
     */
    public function setClearUserCache($clearUserCache)
    {
        $this->clearUserCache = $clearUserCache;
    }
}