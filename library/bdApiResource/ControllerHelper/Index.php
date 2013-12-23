<?php

class bdApiResource_ControllerHelper_Index extends XenForo_ControllerHelper_Abstract
{
	public function actionGetIndex(XenForo_ControllerResponse_Abstract $response)
	{
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			if (XenForo_Application::isRegistered('_Appforo_fc'))
			{
				$response->params['links']['resource-categories'] = Appforo_Link::buildAppforoLink('resource-categories');
				$response->params['links']['resources'] = Appforo_Link::buildAppforoLink('resources');
			}
			else
			{
				$response->params['links']['resource-categories'] = bdApi_Link::buildApiLink('resource-categories');
				$response->params['links']['resources'] = bdApi_Link::buildApiLink('resources');
			}
		}

		return $response;
	}

	public function getModules(array $modules)
	{
		$modules['resource'] = 2013122301;

		return $modules;
	}

}
