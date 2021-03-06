<?php

declare (strict_types=1);
namespace ECSPrefix20210715\Symplify\SymplifyKernel\HttpKernel;

use ECSPrefix20210715\Symfony\Component\Config\Loader\LoaderInterface;
use ECSPrefix20210715\Symfony\Component\HttpKernel\Bundle\BundleInterface;
use ECSPrefix20210715\Symfony\Component\HttpKernel\Kernel;
use ECSPrefix20210715\Symplify\PackageBuilder\Contract\HttpKernel\ExtraConfigAwareKernelInterface;
use ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo;
use ECSPrefix20210715\Symplify\SymplifyKernel\Bundle\SymplifyKernelBundle;
use ECSPrefix20210715\Symplify\SymplifyKernel\Strings\KernelUniqueHasher;
abstract class AbstractSymplifyKernel extends \ECSPrefix20210715\Symfony\Component\HttpKernel\Kernel implements \ECSPrefix20210715\Symplify\PackageBuilder\Contract\HttpKernel\ExtraConfigAwareKernelInterface
{
    /**
     * @var string[]
     */
    private $configs = [];
    public function getCacheDir() : string
    {
        return \sys_get_temp_dir() . '/' . $this->getUniqueKernelHash();
    }
    public function getLogDir() : string
    {
        return \sys_get_temp_dir() . '/' . $this->getUniqueKernelHash() . '_log';
    }
    /**
     * @return mixed[]
     */
    public function registerBundles()
    {
        return [new \ECSPrefix20210715\Symplify\SymplifyKernel\Bundle\SymplifyKernelBundle()];
    }
    /**
     * @param string[]|SmartFileInfo[] $configs
     * @return void
     */
    public function setConfigs($configs)
    {
        foreach ($configs as $config) {
            if ($config instanceof \ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo) {
                $config = $config->getRealPath();
            }
            $this->configs[] = $config;
        }
    }
    /**
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     * @return void
     */
    public function registerContainerConfiguration($loader)
    {
        foreach ($this->configs as $config) {
            $loader->load($config);
        }
    }
    private function getUniqueKernelHash() : string
    {
        $kernelUniqueHasher = new \ECSPrefix20210715\Symplify\SymplifyKernel\Strings\KernelUniqueHasher();
        return $kernelUniqueHasher->hashKernelClass(static::class);
    }
}
