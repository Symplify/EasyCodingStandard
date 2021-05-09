<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

final class ResponseStatusCodeSame extends Constraint
{
    private $statusCode;

    /**
     * @param int $statusCode
     */
    public function __construct($statusCode)
    {
        $statusCode = (int) $statusCode;
        $this->statusCode = $statusCode;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function toString()
    {
        return 'status code is '.$this->statusCode;
    }

    /**
     * @param Response $response
     *
     * {@inheritdoc}
     * @return bool
     */
    protected function matches($response)
    {
        return $this->statusCode === $response->getStatusCode();
    }

    /**
     * @param Response $response
     *
     * {@inheritdoc}
     * @return string
     */
    protected function failureDescription($response)
    {
        return 'the Response '.$this->toString();
    }

    /**
     * @param Response $response
     *
     * {@inheritdoc}
     * @return string
     */
    protected function additionalFailureDescription($response)
    {
        return (string) $response;
    }
}
