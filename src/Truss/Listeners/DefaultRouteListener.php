<?php

namespace Truss\Listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultRouteListener implements EventSubscriberInterface {

	public static function getSubscribedEvents() {
		return array('kernel.request' => array('resolve', -240));
	}

	public function resolve(ResponseEvent $event) {
		$response = $event->getResponse();
		$request = $event->get('request');

		if($request->attributes->has('_controller')) {
			return;
		}

        $url = $request->getPathInfo();

        $parts = explode('/', $url);
        if (count($parts) < 4) {
            return;
        }

        $controllerName = sprintf(
            'App\\%sBundle\\Controller\\%sController::%sAction',
            $parts[1],
            $parts[2],
            $parts[3]
        );

        $request->attributes->add(array('_controller'=> $controllerName));
	}
}