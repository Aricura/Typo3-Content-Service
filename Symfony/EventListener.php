<?php

declare(strict_types=1);

/*
 * The Typo3-Content-Service is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The Typo3-Content-Service is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the Typo3-Content-Service. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Typo3ContentService\Symfony;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Typo3ContentService\Models\AbstractModel;

class EventListener
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Add the event listener to inject the content service.
     */
    public function addListener()
    {
        $this->eventDispatcher->addListener(KernelEvents::CONTROLLER, [$this, 'inject']);
    }

    /**
     * Inject the content service as argument to the controller's method.
     *
     * @param FilterControllerEvent $event
     */
    public function inject(FilterControllerEvent $event)
    {
        $controller = (array) $event->getController();
        $data = (array) $event->getRequest()->attributes->get('data');

        $reflectionMethod = new \ReflectionMethod(\get_class($controller[0]), $controller[1]);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getType();
            if (!$parameterType) {
                continue;
            }

            $className = $parameterType->getName();
            if (!\method_exists($className, 'inject')) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($className);
            if (!$reflectionClass->isSubclassOf(AbstractModel::class)) {
                continue;
            }

            $event->getRequest()->attributes->set('element', $className::inject($data));
            return;
        }
    }
}
