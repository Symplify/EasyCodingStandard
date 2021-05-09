<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\VarDumper\Caster;

use ECSPrefix20210509\ProxyManager\Proxy\ProxyInterface;
use ECSPrefix20210509\Symfony\Component\VarDumper\Cloner\Stub;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @final
 */
class ProxyManagerCaster
{
    /**
     * @param bool $isNested
     */
    public static function castProxy(\ECSPrefix20210509\ProxyManager\Proxy\ProxyInterface $c, array $a, \ECSPrefix20210509\Symfony\Component\VarDumper\Cloner\Stub $stub, $isNested)
    {
        $isNested = (bool) $isNested;
        if ($parent = \get_parent_class($c)) {
            $stub->class .= ' - ' . $parent;
        }
        $stub->class .= '@proxy';
        return $a;
    }
}