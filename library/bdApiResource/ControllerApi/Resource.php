<?php

class bdApiResource_ControllerApi_Resource extends bdApi_ControllerApi_Abstract
{
    public function actionGetIndex()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);
        if (!empty($resourceId)) {
            return $this->responseReroute(__CLASS__, 'get-single');
        }

        $resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
        if (empty($resourceCategoryId)) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_category_id'), 400);
        }

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        $category = $resourceHelper->assertCategoryValidAndViewable($resourceCategoryId);

        $pageNavParams = array(
            'resource_category_id' => $resourceCategoryId,
        );
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $limit = XenForo_Application::get('options')->discussionsPerPage;

        $inputLimit = $this->_input->filterSingle('limit', XenForo_Input::UINT);
        if (!empty($inputLimit)) {
            $limit = $inputLimit;
            $pageNavParams['limit'] = $inputLimit;
        }

        $conditions = array(
            'deleted' => false,
            'moderated' => false,
            'resource_category_id' => $category['resource_category_id'],
        );
        $fetchOptions = array(
            'limit' => $limit,
            'page' => $page,
        );

        $order = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => 'natural'));
        switch ($order) {
            case 'resource_create_date':
                $fetchOptions['order'] = 'resource_date';
                $fetchOptions['direction'] = 'asc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_create_date_reverse':
                $fetchOptions['order'] = 'resource_date';
                $fetchOptions['direction'] = 'desc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_update_date':
                $fetchOptions['order'] = 'last_update';
                $fetchOptions['direction'] = 'asc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_update_date_reverse':
                $fetchOptions['order'] = 'last_update';
                $fetchOptions['direction'] = 'desc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_download_count':
                $fetchOptions['order'] = 'download_count';
                $fetchOptions['direction'] = 'asc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_download_count_reverse':
                $fetchOptions['order'] = 'download_count';
                $fetchOptions['direction'] = 'desc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_rating_weighted':
                $fetchOptions['order'] = 'rating_weighted';
                $fetchOptions['direction'] = 'asc';
                $pageNavParams['order'] = $order;
                break;
            case 'resource_rating_weighted_reverse':
                $fetchOptions['order'] = 'rating_weighted';
                $fetchOptions['direction'] = 'desc';
                $pageNavParams['order'] = $order;
                break;
        }

        $resources = $this->_getResourceModel()->getResources(
            $conditions,
            $this->_getResourceModel()->getFetchOptionsToPrepareApiData($fetchOptions)
        );
        $data = $this->_getResourceModel()->prepareApiDataForResources($resources, $category);

        $total = $this->_getResourceModel()->countResources($conditions);

        $data = array(
            'resources' => $this->_filterDataMany($data),
            'resources_total' => $total,
        );

        bdApi_Data_Helper_Core::addPageLinks($this->_input, $data, $limit, $total, $page, 'resources', array(), $pageNavParams);

        return $this->responseData('bdApiResource_ViewApi_Resource_List', $data);
    }

    public function actionGetSingle()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        $fetchOptions = $this->_getResourceModel()->getFetchOptionsToPrepareApiData();

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId, $fetchOptions);

        $data = $this->_getResourceModel()->prepareApiDataForResource($resource, $category);

        $data = array(
            'resource' => $this->_filterDataSingle($data),
        );

        return $this->responseData('bdApi_ViewApi_Resource_Single', $data);
    }

    /**
     * @return bdApiResource_XenResource_Model_Resource
     */
    protected function _getResourceModel()
    {
        return $this->getModelFromCache('XenResource_Model_Resource');
    }
}