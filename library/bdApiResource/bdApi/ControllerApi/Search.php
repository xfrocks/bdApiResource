<?php

class bdApiResource_bdApi_ControllerApi_Search extends XFCP_bdApiResource_bdApi_ControllerApi_Search
{
    public function actionGetIndex()
    {
        $response = parent::actionGetIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $response->params['links']['resources'] = XenForo_Link::buildApiLink('search/resources');
        }

        return $response;
    }

    public function actionGetResources()
    {
        return $this->responseError(new XenForo_Phrase('appforo_slash_search_only_accepts_post_requests'), 400);
    }

    public function actionPostResources()
    {
        $search = $this->_doSearch('resource_update');

        $this->_request->setParam('search_id', $search['search_id']);
        return $this->responseReroute(__CLASS__, 'get-results');
    }
}