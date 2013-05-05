<?php

class bdApiResource_ControllerApi_Resource extends bdApi_ControllerApi_Abstract
{
	public function actionGetIndex()
	{
		$resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);
		if (!empty($resourceId))
		{
			return $this->responseReroute(__CLASS__, 'get-single');
		}

		$resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
		if (empty($resourceCategoryId))
		{
			return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_category_id'), 400);
		}

		$category = $this->getHelper('XenResource_ControllerHelper_Resource')->assertCategoryValidAndViewable();

		$resourceModel = $this->_getResourceModel();

		$pageNavParams = array(
				'resource_category_id' => $resourceCategoryId,
		);
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$limit = XenForo_Application::get('options')->discussionsPerPage;

		$inputLimit = $this->_input->filterSingle('limit', XenForo_Input::UINT);
		if (!empty($inputLimit))
		{
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
		switch ($order)
		{
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

		$resources = $resourceModel->getResources(
				$conditions,
				$resourceModel->getFetchOptionsToPrepareApiData($fetchOptions)
		);

		$total = $resourceModel->countResources($conditions);

		$data = array(
				'resources' => $this->_filterDataMany($resourceModel->prepareApiDataForResources($resources, $category)),
				'resources_total' => $total,
		);

		bdApi_Data_Helper_Core::addPageLinks($data, $limit, $total, $page, 'resources', array(), $pageNavParams);

		return $this->responseData('bdApiResource_ViewApi_Resource_List', $data);
	}

	public function actionGetSingle()
	{
		$resourceModel = $this->_getResourceModel();

		list($resource, $category) = $this->getHelper('XenResource_ControllerHelper_Resource')->assertResourceValidAndViewable(
				null,
				$resourceModel->getFetchOptionsToPrepareApiData()
		);

		$data = array(
				'resource' => $this->_filterDataSingle($resourceModel->prepareApiDataForResource($resource, $category)),
		);

		return $this->responseData('bdApi_ViewApi_Resource_Single', $data);
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}
}