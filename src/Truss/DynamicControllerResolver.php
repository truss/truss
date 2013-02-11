<?php

/*
 * 
 *
 */

namespace Truss;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;

class DynamicControllerResolver extends BaseControllerResolver {

	protected $defaultNamespace = "App\Controllers";

	public function __construct($namespace=null, $logger=null) {
		if($namespace) {
			$this->defaultNamespace = $namespace;
		}
		parent::__construct($logger);
	}

	public function getController(Request $request) {

		if(false !== $controller = parent::getController($request)) {
			return $controller;	
		}

		if(!$namespace = $request->attributes->get('_namespace')) {
			$namespace = $this->defaultNamespace;
		}

		if(!$object = $request->attributes->get('_object')) {
			$object = 'index';
			// echo ($object = $request->attributes->get('_object'));
		}

		if(!$method = $request->attributes->get('_method')) {
			$method = $request->getMethod();
		}

		$request->attributes->remove('_namespace');
		$request->attributes->remove('_object');
		$request->attributes->remove('_method');

		// if($request->attributes->has('object')) {
		// 	$controller .= '\\' . ucfirst($request->attributes->get('object')) . 'Controller';
		// } else {

		// }

		// if($request->attributes->has('method')) {
		// 	$controller .= '::' . $request->attributes->get('method'); 
		// } else {
		// 	$controller .= '::' . strtolower($request->getMethod());
		// }

		$controllerName = sprintf("%s\\%sController::%s", $namespace, ucfirst($object), strtolower($method));

		// echo '<pre>';
		// var_dump($request->attributes);
		// echo $controllerName . '<br>';

        list($controller, $method) = $this->createController($controllerName);

        if (!method_exists($controller, $method)) {
            throw new \InvalidArgumentException(sprintf('Method "%s::%s" does not exist.', get_class($controller), $method));
        }

        return array($controller, $method);
		// var_dump($request->attributes);
		// die();
  //       $url = $request->getPathInfo();

  //       $parts = explode('/', $url);
  //       if (count($parts) < 4) {
  //           return;
  //       }

  //       $controllerName = sprintf(
  //           'App\\%sBundle\\Controller\\%sController::%sAction',
  //           $parts[1],
  //           $parts[2],
  //           $parts[3]
  //       );

        // $request->attributes->add(array('_controller'=> 'Webwall\Controllers\IndexController::get'));
	}
}
