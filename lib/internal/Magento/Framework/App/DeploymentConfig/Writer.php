<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\App\DeploymentConfig;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Config\File\ConfigFilePool;

/**
 * Deployment configuration writer
 */
class Writer
{
    /**
     * Deployment config reader
     *
     * @var Reader
     */
    private $reader;

    /**
     * Application filesystem
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Formatter
     *
     * @var Writer\FormatterInterface
     */
    private $formatter;

    /**
     * @var ConfigFilePool
     */
    private $configFilePool;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * Constructor
     *
     * @param Reader $reader
     * @param Filesystem $filesystem
     * @param ConfigFilePool $configFilePool
     * @param DeploymentConfig $deploymentConfig
     * @param Writer\FormatterInterface $formatter
     */
    public function __construct(
        Reader $reader,
        Filesystem $filesystem,
        ConfigFilePool $configFilePool,
        DeploymentConfig $deploymentConfig,
        Writer\FormatterInterface $formatter = null
    ) {
        $this->reader = $reader;
        $this->filesystem = $filesystem;
        $this->formatter = $formatter ?: new Writer\PhpFormatter();
        $this->configFilePool = $configFilePool;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Check if configuration file is writable
     *
     * @return bool
     */
    public function checkIfWritable()
    {
        $configDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG);
        if ($configDirectory->isWritable($this->reader->getFile())) {
            return true;
        }
        return false;
    }

    /**
     * Saves config
     *
     * @param array $data
     * @return void
     */
    public function saveConfig(array $data)
    {
        $paths = $this->configFilePool->getPaths();

        foreach ($data as $fileKey => $config) {
            if (isset($paths[$fileKey])) {

                if ($this->filesystem->getDirectoryWrite(DirectoryList::CONFIG)->isExist($paths[$fileKey])) {
                    $currentData = $this->reader->load($paths[$fileKey]);
                    $config = array_replace_recursive($currentData, $config);
                }

                $contents = $this->formatter->format($config);
                $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG)->writeFile($paths[$fileKey], $contents);
            }
        }
        $this->deploymentConfig->resetData();
    }
}
