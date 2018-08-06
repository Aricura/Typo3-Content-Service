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

namespace Typo3ContentService;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Typo3ContentService\Symfony\EventListener;

class Typo3ContentServiceBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $containerBuilder)
    {
        $containerBuilder
            ->register('event_dispatcher', EventDispatcher::class);

        $containerBuilder
            ->register('typo3_content_service_event_listener', EventListener::class)
            ->addArgument('event_dispatcher')
            ->addTag('kernel.event_listener', [
                'method' => 'add',
                'event' => 'kernel.request',
            ]);
    }
}
