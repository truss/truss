<?php

namespace Truss\Listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;

class JsonRequestListener implements EventSubscriberInterface {

	public function onRequest(GetResponseForControllerResultEvent $event) {
		$request = $event->get('request');

	    if (strpos($request->headers->get('Content-Type'), 'application/json') === 0) {
	        $data = json_decode($request->getContent(), true);
	        $request->request->replace(is_array($data) ? $data : array());
	    }

	}

	public static function getSubscribedEvents() {
		return array('kernel.request' => 'onRequest');
	}
}
