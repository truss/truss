<?php

namespace Truss;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;



class Application extends HttpKernel\HttpKernel {


	protected $started;



	public function attach($routes=null, $plugins=null, $config=false) {

	}

	public function run(Request $request=null) {
		if($request == null) {
			$request = Request::createFromGlobals();
		}

		$response = $this->handle($request);
		$response->send();
		$this->shutdown($request, $response);
	}

	protected function startup(Request $request=null) {
		$this->started = true;
	}

	protected function shutdown(Request $request, Response $response) {

	}

	public function dispatch(Request $request) {
		try {
			$request->attributes->add($this->matcher->match($request->getPathInfo()));
			$controller = $this->resolver->getController($request);
			$arguments = $this->resolver->getArguments($request, $controller);
			$response = call_user_func_array($controller, $arguments);

			// $attributes = $matcher->match($request->getPathInfo());
			// $response = new Response('test');
		} catch (ResourceNotFoundException $e) {
		    return new Response('Not Found', 404);
		} catch (Exception $e) {
		    return new Response('An error occurred', 500);
		}

		$this->dispatcher->dispatch('response', new ResponseEvent($request, $response));
		return $response;
 
	}

	// public function handle(Request $request, $type=HttpKernelInterface::MASTER_REQUEST, $catch=true) {
	// 	if(!$this->started) {
	// 		$this->startup();
	// 	}

	// 	// return parent::handle($request, $type, $catch);
	// }

}