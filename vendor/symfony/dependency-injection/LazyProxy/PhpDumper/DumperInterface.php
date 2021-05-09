<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\DependencyInjection\LazyProxy\PhpDumper;

use ECSPrefix20210509\Symfony\Component\DependencyInjection\Definition;
/**
 * Lazy proxy dumper capable of generating the instantiation logic PHP code for proxied services.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
interface DumperInterface
{
    /**
     * Inspects whether the given definitions should produce proxy instantiation logic in the dumped container.
     *
     * @return bool
     */
    public function isProxyCandidate(\ECSPrefix20210509\Symfony\Component\DependencyInjection\Definition $definition);
    /**
     * Generates the code to be used to instantiate a proxy in the dumped factory code.
     *
     * @return string
     * @param string $id
     * @param string $factoryCode
     */
    public function getProxyFactoryCode(\ECSPrefix20210509\Symfony\Component\DependencyInjection\Definition $definition, $id, $factoryCode);
    /**
     * Generates the code for the lazy proxy.
     *
     * @return string
     */
    public function getProxyCode(\ECSPrefix20210509\Symfony\Component\DependencyInjection\Definition $definition);
}