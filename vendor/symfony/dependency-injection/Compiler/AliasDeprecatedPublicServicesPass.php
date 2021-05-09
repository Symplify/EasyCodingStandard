<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class AliasDeprecatedPublicServicesPass extends AbstractRecursivePass
{
    private $tagName;

    private $aliases = [];

    /**
     * @param string $tagName
     */
    public function __construct($tagName = 'container.private')
    {
        $tagName = (string) $tagName;
        $this->tagName = $tagName;
    }

    /**
     * {@inheritdoc}
     * @param bool $isRoot
     */
    protected function processValue($value, $isRoot = false)
    {
        $isRoot = (bool) $isRoot;
        if ($value instanceof Reference && isset($this->aliases[$id = (string) $value])) {
            return new Reference($this->aliases[$id], $value->getInvalidBehavior());
        }

        return parent::processValue($value, $isRoot);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            if (null === $package = isset($tags[0]['package']) ? $tags[0]['package'] : null) {
                throw new InvalidArgumentException(sprintf('The "package" attribute is mandatory for the "%s" tag on the "%s" service.', $this->tagName, $id));
            }

            if (null === $version = isset($tags[0]['version']) ? $tags[0]['version'] : null) {
                throw new InvalidArgumentException(sprintf('The "version" attribute is mandatory for the "%s" tag on the "%s" service.', $this->tagName, $id));
            }

            $definition = $container->getDefinition($id);
            if (!$definition->isPublic() || $definition->isPrivate()) {
                continue;
            }

            $container
                ->setAlias($id, $aliasId = '.'.$this->tagName.'.'.$id)
                ->setPublic(true)
                ->setDeprecated($package, $version, 'Accessing the "%alias_id%" service directly from the container is deprecated, use dependency injection instead.');

            $container->setDefinition($aliasId, $definition);

            $this->aliases[$id] = $aliasId;
        }

        parent::process($container);
    }
}
