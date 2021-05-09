<?php

namespace Symplify\PackageBuilder\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symplify\PackageBuilder\Exception\DependencyInjection\DefinitionForTypeNotFoundException;
use Throwable;

/**
 * @see \Symplify\PackageBuilder\Tests\DependencyInjection\DefinitionFinderTest
 */
final class DefinitionFinder
{
    /**
     * @return mixed[]
     * @param string $type
     */
    public function findAllByType(ContainerBuilder $containerBuilder, $type)
    {
        $type = (string) $type;
        $definitions = [];
        $containerBuilderDefinitions = $containerBuilder->getDefinitions();
        foreach ($containerBuilderDefinitions as $name => $definition) {
            $class = $definition->getClass() ?: $name;
            if (! $this->doesClassExists($class)) {
                continue;
            }

            if (is_a($class, $type, true)) {
                $definitions[$name] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * @param string $type
     * @return \Symfony\Component\DependencyInjection\Definition
     */
    public function getByType(ContainerBuilder $containerBuilder, $type)
    {
        $type = (string) $type;
        $definition = $this->getByTypeIfExists($containerBuilder, $type);
        if ($definition !== null) {
            return $definition;
        }

        throw new DefinitionForTypeNotFoundException(sprintf('Definition for type "%s" was not found.', $type));
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Definition|null
     * @param string $type
     */
    private function getByTypeIfExists(ContainerBuilder $containerBuilder, $type)
    {
        $type = (string) $type;
        $containerBuilderDefinitions = $containerBuilder->getDefinitions();
        foreach ($containerBuilderDefinitions as $name => $definition) {
            $class = $definition->getClass() ?: $name;
            if (! $this->doesClassExists($class)) {
                continue;
            }

            if (is_a($class, $type, true)) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param string $class
     * @return bool
     */
    private function doesClassExists($class)
    {
        $class = (string) $class;
        try {
            return class_exists($class);
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
