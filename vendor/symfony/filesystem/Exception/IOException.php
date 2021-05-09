<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\Filesystem\Exception;

/**
 * Exception class thrown when a filesystem operation failure happens.
 *
 * @author Romain Neutron <imprec@gmail.com>
 * @author Christian Gärtner <christiangaertner.film@googlemail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IOException extends \RuntimeException implements \ECSPrefix20210509\Symfony\Component\Filesystem\Exception\IOExceptionInterface
{
    private $path;
    /**
     * @param string $message
     * @param int $code
     * @param string $path
     */
    public function __construct($message, $code = 0, \Throwable $previous = null, $path = null)
    {
        $message = (string) $message;
        $code = (int) $code;
        $this->path = $path;
        parent::__construct($message, $code, $previous);
    }
    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }
}