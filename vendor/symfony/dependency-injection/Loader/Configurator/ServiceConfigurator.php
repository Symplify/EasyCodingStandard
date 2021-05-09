<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\DependencyInjection\Loader\Configurator;

use ECSPrefix20210509\Symfony\Component\DependencyInjection\ContainerBuilder;
use ECSPrefix20210509\Symfony\Component\DependencyInjection\Definition;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ServiceConfigurator extends \ECSPrefix20210509\Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator
{
    const FACTORY = 'services';
    use Traits\AbstractTrait;
    use Traits\ArgumentTrait;
    use Traits\AutoconfigureTrait;
    use Traits\AutowireTrait;
    use Traits\BindTrait;
    use Traits\CallTrait;
    use Traits\ClassTrait;
    use Traits\ConfiguratorTrait;
    use Traits\DecorateTrait;
    use Traits\DeprecateTrait;
    use Traits\FactoryTrait;
    use Traits\FileTrait;
    use Traits\LazyTrait;
    use Traits\ParentTrait;
    use Traits\PropertyTrait;
    use Traits\PublicTrait;
    use Traits\ShareTrait;
    use Traits\SyntheticTrait;
    use Traits\TagTrait;
    private $container;
    private $instanceof;
    private $allowParent;
    private $path;
    /**
     * @param bool $allowParent
     * @param string $path
     */
    public function __construct(\ECSPrefix20210509\Symfony\Component\DependencyInjection\ContainerBuilder $container, array $instanceof, $allowParent, \ECSPrefix20210509\Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator $parent, \ECSPrefix20210509\Symfony\Component\DependencyInjection\Definition $definition, $id, array $defaultTags, $path = null)
    {
        $allowParent = (bool) $allowParent;
        $this->container = $container;
        $this->instanceof = $instanceof;
        $this->allowParent = $allowParent;
        $this->path = $path;
        parent::__construct($parent, $definition, $id, $defaultTags);
    }
    public function __destruct()
    {
        parent::__destruct();
        $this->container->removeBindings($this->id);
        $this->container->setDefinition($this->id, $this->definition->setInstanceofConditionals($this->instanceof));
    }
}