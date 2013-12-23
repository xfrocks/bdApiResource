<?php

class bdApiResource_Listener
{
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'Appforo_ControllerAppforo_Index',

			'bdApi_ControllerApi_Index',

			'XenResource_Model_Category',
			'XenResource_Model_Resource',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'bdApiResource_' . $class;
		}
	}

	public static function bdapi_setup_routes(array &$routes)
	{
		bdApi_Route_PrefixApi::addRoute($routes, 'resources', 'bdApiResource_Route_PrefixApi_Resources', 'data_only');
		bdApi_Route_PrefixApi::addRoute($routes, 'resource-categories', 'bdApiResource_Route_PrefixApi_Categories', 'data_only');
	}

	public static function appforo_setup_routes(array &$routes)
	{
		Appforo_Route_PrefixAppforo::addRoute($routes, 'resources', 'bdApiResource_Route_PrefixAppforo_Resources', 'data_only');
		Appforo_Route_PrefixAppforo::addRoute($routes, 'resource-categories', 'bdApiResource_Route_PrefixAppforo_Categories', 'data_only');
	}

}
