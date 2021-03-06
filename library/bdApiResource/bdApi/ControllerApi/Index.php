<?php

class bdApiResource_bdApi_ControllerApi_Index extends XFCP_bdApiResource_bdApi_ControllerApi_Index
{
    public function actionGetIndex()
    {
        $response = parent::actionGetIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $response->params['links']['resource-categories'] = XenForo_Link::buildApiLink('resource-categories');
            $response->params['links']['resources'] = XenForo_Link::buildApiLink('resources');
        }

        return $response;
    }

    protected function _getModules()
    {
        $modules = parent::_getModules();
        $modules['resource'] = 2015042301;

        return $modules;
    }

}
