<?php

namespace AppBundle\Service\Util;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class AbstractContainerAware
 * @package AppBundle\Service
 * @mixin ContainerAwareTrait
 */
abstract class AbstractContainerAware implements ContainerAwareInterface
{
    use ContainerAwareTrait;
}
