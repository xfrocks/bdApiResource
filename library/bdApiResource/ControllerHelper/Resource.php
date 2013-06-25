<?php

class bdApiResource_ControllerHelper_Resource extends XenForo_ControllerHelper_Abstract
{
	public function actionGetIndex($controllerName)
	{
		$resourceId = $this->_controller->getInput()->filterSingle('resource_id', XenForo_Input::UINT);
		if (!empty($resourceId))
		{
			return $this->_controller->responseReroute($controllerName, 'get-single');
		}
	
		$resourceCategoryId = $this->_controller->getInput()->filterSingle('resource_category_id', XenForo_Input::UINT);
		if (empty($resourceCategoryId))
		{
			return $this->_controller->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_category_id'), 400);
		}
	
		$category = $this->_controller->getHelper('XenResource_ControllerHelper_Resource')->assertCategoryValidAndViewable();
	
		$resourceModel = $this->_getResourceModel();
	
		$pageNavParams = array(
				'resource_category_id' => $resourceCategoryId,
		);
		$page = $this->_controller->getInput()->filterSingle('page', XenForo_Input::UINT);
		$limit = XenForo_Application::get('options')->discussionsPerPage;
	
		$inputLimit = $this->_controller->getInput()->filterSingle('limit', XenForo_Input::UINT);
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
	
		$order = $this->_controller->getInput()->filterSingle('order', XenForo_Input::STRING, array('default' => 'natural'));
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
				'resources' => $this->_controller->bdApiResource_filterDataMany($resourceModel->prepareApiDataForResources($resources, $category)),
				'resources_total' => $total,
		);
	
		if (class_exists('bdApi_ControllerApi_Abstract'))
		{
			if ($this->_controller instanceof bdApi_ControllerApi_Abstract)
			{
				bdApi_Data_Helper_Core::addPageLinks($data, $limit, $total, $page, 'resources', array(), $pageNavParams);
			}
		}
		elseif (class_exists('Appforo_ControllerAppforo_Abstract'))
		{
			if ($this->_controller instanceof Appforo_ControllerAppforo_Abstract)
			{
				Appforo_Data_Helper_Core::addPageLinks($this->_controller->getInput(), $data, $limit, $total, $page, 'resources', array(), $pageNavParams);
			}
		}
		
		return $this->_controller->responseData('bdApiResource_ViewApi_Resource_List', $data);
	}
	
	public function actionGetSingle()
	{
		$resourceModel = $this->_getResourceModel();
	
		list($resource, $category) = $this->_controller->getHelper('XenResource_ControllerHelper_Resource')->assertResourceValidAndViewable(
				null,
				$resourceModel->getFetchOptionsToPrepareApiData()
		);
	
		$data = array(
				'resource' => $this->_controller->bdApiResource_filterDataSingle($resourceModel->prepareApiDataForResource($resource, $category)),
		);
	
		return $this->_controller->responseData('bdApi_ViewApi_Resource_Single', $data);
	}
	
	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		return $this->_controller->getModelFromCache('XenResource_Model_Resource');
	}
}