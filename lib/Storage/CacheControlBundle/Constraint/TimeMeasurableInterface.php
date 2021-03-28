<?php

namespace Storage\CacheControlBundle\Constraint;

/**
 * Interface TimeMeasurableInterface
 *
 * @author Nomenjanahry Randriamahefa <rasmuchacho@gmail.com>
 */
interface TimeMeasurableInterface
{

    /**
     * getLastModified
     *
     * @return \DateTimeInterface|null
     */
    public function getLastModified(): ?\DateTimeInterface;
}
