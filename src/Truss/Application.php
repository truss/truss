<?php

namespace Truss;

use Truss\Lang\Container;
use Truss\DynamicControllerResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;

use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Truss;

class Application extends Container implements HttpKernelInterface {

	const DEV = 1;

	protected $started;
	protected $env;
	protected $routes;

	protected $request;
	protected $dispatcher;
	protected $resolver;
	protected $matcher;
	protected $context;

	public function __construct($env, $routes=null) {
		$this->env = $env;
		$this->routes = $routes;
	}


	public function attach($routes=null, $plugins=null, $config=false) {

	}

	public function run(Request $request=null) {
		if($request == null) {
			$this->request = $request = Request::createFromGlobals();
		}

		$this->context = new RequestContext();
		$this->context->fromRequest($request);

		$this->matcher = new UrlMatcher($this->routes, $this->context);

		$this->resolver = new DynamicControllerResolver("App\Controllers");

		$this->dispatcher = new EventDispatcher();
		$this->dispatcher->addSubscriber(new HttpKernel\EventListener\RouterListener($this->matcher));
		$this->dispatcher->addSubscriber(new ExceptionListener('Webwall\\Controllers\\ErrorController::exceptionAction'));
		// $this->dispatcher->addSubscriber(new Truss\Listeners\ContentLengthListener());
		$this->dispatcher->addSubscriber(new Truss\Listeners\StringResponseListener());
		$this->dispatcher->addSubscriber(new Truss\Listeners\ArrayResponseListener());


		$this->response = $response = $this->handle($this->request);
		$response->send();
		$this->shutdown($this->request, $response);
	}

	protected function startup(Request $request=null) {
		$this->started = true;
	}

	protected function shutdown(Request $request, Response $response) {

	}

	public function handle(Request $request=null, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {

        $event = new GetResponseEvent($this, $this->request, $type);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $request, $type);
        }

		try {
			$request->attributes->add($this->matcher->match($request->getPathInfo()));
			$controller = $this->resolver->getController($request);

	        $event = new FilterControllerEvent($this, $controller, $this->request, $type);
	        $this->dispatcher->dispatch(KernelEvents::CONTROLLER, $event);
        	$controller = $event->getController();

			$arguments = $this->resolver->getArguments($request, $controller);
			$response = call_user_func_array($controller, $arguments);

			// $attributes = $matcher->match($request->getPathInfo());
			// $response = new Response('test');
	        if (!$response instanceof Response) {
	        	$event = new GetResponseForControllerResultEvent($this, $this->request, $type, $response);
	            $this->dispatcher->dispatch(KernelEvents::VIEW, $event);

	            if ($event->hasResponse()) {
	                $response = $event->getResponse();
	            }

		        if (!$response instanceof Response) {
	                $msg = sprintf('The controller must return a response (%s given).', $this->varToString($response));

	                // the user may have forgotten to return something
	                if (null === $response) {
	                    $msg .= ' Did you forget to add a return statement somewhere in your controller?';
	                }
	                throw new \LogicException($msg);
	            }
	        }
		} catch (ResourceNotFoundException $e) {
		    return new Response('Not Found', 404);
		} catch (Exception $e) {
		    return new Response('An error occurred', 500);
		}

		$this->dispatcher->dispatch('response', new ResponseEvent($request, $response));
		return $response;
 
	}

    private function handleException(\Exception $e, $request, $type)
    {
        $event = new GetResponseForExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch(KernelEvents::EXCEPTION, $event);

        // a listener might have replaced the exception
        $e = $event->getException();

        if (!$event->hasResponse()) {
            throw $e;
        }

        $response = $event->getResponse();

        // the developer asked for a specific status code
        if ($response->headers->has('X-Status-Code')) {
            $response->setStatusCode($response->headers->get('X-Status-Code'));

            $response->headers->remove('X-Status-Code');
        } elseif (!$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            // ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } else {
                $response->setStatusCode(500);
            }
        }

        try {
            return $this->filterResponse($response, $request, $type);
        } catch (\Exception $e) {
            return $response;
        }
    }

    private function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = array();
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, $this->varToString($v));
            }

            return sprintf("Array(%s)", implode(', ', $a));
        }

        if (is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'false';
        }

        if (true === $var) {
            return 'true';
        }

        return (string) $var;
    }
	// public function handle(Request $request, $type=HttpKernelInterface::MASTER_REQUEST, $catch=true) {
	// 	if(!$this->started) {
	// 		$this->startup();
	// 	}

	// 	// return parent::handle($request, $type, $catch);
	// }

}