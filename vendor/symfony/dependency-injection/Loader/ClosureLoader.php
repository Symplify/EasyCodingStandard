<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\DependencyInjection\Loader;

use ECSPrefix20210509\Symfony\Component\Config\Loader\Loader;
use ECSPrefix20210509\Symfony\Component\DependencyInjection\ContainerBuilder;
/**
 * ClosureLoader loads service definitions from a PHP closure.
 *
 * The Closure has access to the container as its first argument.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ClosureLoader extends \ECSPrefix20210509\Symfony\Component\Config\Loader\Loader
{
    private $container;
    public function __construct(\ECSPrefix20210509\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        $this->container = $container;
    }
    /**
     * {@inheritdoc}
     * @param string|null $type
     */
    public function load($resource, $type = null)
    {
        $resource($this->container);
    }
    /**
     * {@inheritdoc}
     * @param string $type
     */
    public function supports($resource, $type = null)
    {
        $type = (string) $type;
        return $resource instanceof \Closure;
    }
}