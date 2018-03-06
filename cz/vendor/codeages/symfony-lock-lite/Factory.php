<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

/**
 * Factory provides method to create locks.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class Factory implements LoggerAwareInterface
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    private $store;

    public function __construct(StoreInterface $store)
    {
        $this->store = $store;

        $this->logger = new NullLogger();
    }

    /**
     * Creates a lock for the given resource.
     *
     * @param string $resource The resource to lock
     * @param float  $ttl      maximum expected lock duration
     *
     * @return Lock
     */
    public function createLock($resource, $ttl = 300.0)
    {
        $lock = new Lock(new Key($resource), $this->store, $ttl);
        $lock->setLogger($this->logger);

        return $lock;
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
