<?php

class bdApiResource_ControllerApi_Category extends bdApi_ControllerApi_Abstract
{
    public function actionGetIndex()
    {
        $resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
        if (!empty($resourceCategoryId)) {
            return $this->responseReroute(__CLASS__, 'get-single');
        }

        $categories = $this->_getCategoryModel()->getViewableCategories();
        $data = $this->_getCategoryModel()->prepareApiDataForCategories($categories);

        $data = array(
            'categories' => $this->_filterDataMany($data),
        );

        return $this->responseData('bdApiResource_ViewApi_Category_List', $data);
    }

    public function actionGetSingle()
    {
        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');

        $resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
        $category = $resourceHelper->assertCategoryValidAndViewable($resourceCategoryId);

        $data = array(
            'category' => $this->_filterDataSingle($this->_getCategoryModel()->prepareApiDataForCategory($category)),
        );

        return $this->responseData('bdApi_ViewApi_Category_Single', $data);
    }

    /**
     * @return bdApiResource_XenResource_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('XenResource_Model_Category');
    }
}