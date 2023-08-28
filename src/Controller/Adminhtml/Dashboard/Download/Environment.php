<?php
namespace Synerise\Integration\Controller\Adminhtml\Dashboard\Download;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\ModuleList;
use Synerise\Integration\Helper\Version;

class Environment extends System
{
    const ADMIN_RESOURCE = 'Synerise_Integration::dashboard';

    const FILENAME = 'environment.log';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var Version
     */
    private $version;

    public function __construct(
        Context $context,
        ProductMetadataInterface $productMetadata,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        Filesystem\DirectoryList $directoryList,
        ModuleList $moduleList,
        Version $version
    ) {
        $this->productMetadata = $productMetadata;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->moduleList = $moduleList;
        $this->directoryList = $directoryList;
        $this->version = $version;

        parent::__construct($context);
    }

    public function execute()
    {
        $moduleList = $this->moduleList->getAll();
        foreach($moduleList as &$module) {
            $codeVersion = $this->version->getMagentoModuleVersion($module['name']);
            $module['code_version'] = $codeVersion;
        }

        $log = [
            'php_version' => phpversion(),
            'magento_version' => $this->productMetadata->getVersion(),
            'magento_edition' => $this->productMetadata->getEdition(),
            'module_version' => $this->version->getMagentoModuleVersion('Synerise_Integration'),
            'module_list' => $moduleList
        ];

        $tmpDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);
        $tmpDirectoryWrite->writeFile(self::FILENAME, json_encode($log));

        try {
            return $this->fileFactory->create(
                self::FILENAME,
                [
                    'type'  => 'filename',
                    'value' => $this->getFilePath()
                ]
            );
        } catch (\Exception $e) {
            throw new NotFoundException(__($e->getMessage()));
        }
    }

    protected function getFilePath()
    {
        return $this->directoryList->getPath(DirectoryList::TMP) . DIRECTORY_SEPARATOR . self::FILENAME ;
    }
}