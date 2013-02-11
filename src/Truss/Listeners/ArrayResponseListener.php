<?php

namespace Truss\Listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ArrayResponseListener implements EventSubscriberInterface {

	public function onView(GetResponseForControllerResultEvent $event) {
		$response = $event->getControllerResult();
		if(is_array($response)) {
			$event->setResponse(new JsonResponse($response));
		}

	}

	public static function getSubscribedEvents() {
		return array('kernel.view' => 'onView');
	}
}

