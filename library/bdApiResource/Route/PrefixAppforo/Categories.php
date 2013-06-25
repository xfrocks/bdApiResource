<?php

class bdApiResource_Route_PrefixAppforo_Categories extends Appforo_Route_PrefixAppforo_Abstract
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'resource_category_id');
		return $router->getRouteMatch('bdApiResource_ControllerAppforo_Category', $action);
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'resource_category_id');
	}
}