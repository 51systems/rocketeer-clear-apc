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
        $webDir = $this->rocketeer['config']->get('rocketeer-clear-apc::web_dir');
        $this->webDir = $this->releasesManager->getCurrentReleasePath()
            . rtrim($webDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->clearUserCache = $this->rocketeer['config']->get('rocketeer-clear-apc::clear_user_cache');
        $this->clearApcCache = $this->rocketeer['config']->get('rocketeer-clear-apc::clear_apc_cache');
        $this->connections->getOption('host');

        $file = $this->createApcFile();
        $this->callApcFile($file);
    }

    private function callApcFile($filename)
    {
        $url = 'http://' . $this->hostname . '/' . $filename;

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
            throw new \RuntimeException(sprintf('Unable to read %s, does the host resove?', $url));
        }

        if ($result['success']) {
            $this->command->info('APC Cache Plugin: ' . $result['message']);
        } else {
            $this->command->error('Could not clear APC cache: ' . $result['message']);
        }
    }

    private function removeApcFile($filename)
    {
        unlink($this->webDir . $filename);
    }

    /**
     * Creates the APC file and configures it.
     *
     * @return string The filename of the apc file
     */
    private function createApcFile()
    {
        if (!is_dir($this->webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir does not exist "%s"', $this->webDir));
        }

        if (!is_writable($this->webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir is not writeable "%s"', $this->webDir));
        }

        $template = file_get_contents(__DIR__ . '/../Resources/clear_apc.php.tpl');
        $code = strstr($template, array(
            '%user%' => var_export($this->clearUserCache, true),
            '%opcode%' => var_export($this->clearApcCache, true)
        ));

        $filename = 'apc-' . md5(uniqid().php_uname()) . '.php';
        $path = $this->webDir.$filename;


        if (false === @file_put_contents($path, $code)) {
            throw new \RuntimeException(sprintf('Unable to write to file "%s"', $path));
        }

        return $filename;
    }
}