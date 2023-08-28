<?php
namespace Synerise\Integration\Helper;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;

class Version
{
    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var ReadFactory
     */
    private $readFactory;

    public function __construct(
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory
    ) {

        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
    }

    public function getMagentoModuleVersion(string $moduleName): string
    {
        try {
            $path = $this->componentRegistrar->getPath(
                ComponentRegistrar::MODULE,
                $moduleName
            );
            $path = preg_replace('|/src$|', '', $path);
            $directoryRead = $this->readFactory->create($path);
            $composerJsonData = '';
            if ($directoryRead->isFile('composer.json')) {
                $composerJsonData = $directoryRead->readFile('composer.json');
            }
            $data = json_decode($composerJsonData);
            return !empty($data->version) ? $data->version : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}