<?php

class bdApiResource_ControllerHelper_Category extends XenForo_ControllerHelper_Abstract
{
	public function actionGetIndex($controllerName)
	{
		$resourceCategoryId = $this->_controller->getInput()->filterSingle('resource_category_id', XenForo_Input::UINT);
		if (!empty($resourceCategoryId))
		{
			return $this->_controller->responseReroute($controllerName, 'get-single');
		}

		$categoryModel = $this->_getCategoryModel();

		$categories = $categoryModel->getViewableCategories();

		$data = array(
				'categories' => $this->_controller->bdApiResource_filterDataMany($categoryModel->prepareApiDataForCategories($categories)),
		);

		return $this->_controller->responseData('bdApiResource_ViewApi_Category_List', $data);
	}

	public function actionGetSingle()
	{
		$category = $this->_controller->getHelper('XenResource_ControllerHelper_Resource')->assertCategoryValidAndViewable();

		$data = array(
				'category' => $this->_controller->bdApiResource_filterDataSingle($this->_getCategoryModel()->prepareApiDataForCategory($category)),
		);

		return $this->_controller->responseData('bdApi_ViewApi_Category_Single', $data);
	}

	/**
	 * @return XenResource_Model_Category
	 */
	protected function _getCategoryModel()
	{
		return $this->_controller->getModelFromCache('XenResource_Model_Category');
	}
}