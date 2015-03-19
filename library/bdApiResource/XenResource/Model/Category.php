<?php

class bdApiResource_XenResource_Model_Category extends XFCP_bdApiResource_XenResource_Model_Category
{
    public function getFetchOptionsToPrepareApiData(array $fetchOptions = array(), array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        return $fetchOptions;
    }

    public function prepareApiDataForCategories(array $categories)
    {
        $data = array();

        $fields = $this->_bdApiResource_getFieldModel()->getResourceFields();

        foreach ($categories as $key => $category) {
            $data[] = $this->prepareApiDataForCategory($category, $fields);
        }

        return $data;
    }

    public function prepareApiDataForCategory(array $category, array $fields = null)
    {
        $category = $this->prepareCategory($category);

        $publicKeys = array(
            // xf_resource_category
            'resource_category_id' => 'resource_category_id',
            'category_title' => 'category_title',
            'category_description' => 'category_description',
            'parent_category_id' => 'parent_category_id',
            'resource_count' => 'category_resource_count',
        );

        $data = bdApi_Data_Helper_Core::filter($category, $publicKeys);

        $data['links'] = array(
            'permalink' => XenForo_Link::buildPublicLink('resources/categories', $category),
            'detail' => XenForo_Link::buildApiLink('resource-categories', $category),
            'resources' => XenForo_Link::buildApiLink('resources', null, array(
                'resource_category_id' => $category['resource_category_id']
            )),
            'resources_in_sub' => XenForo_Link::buildApiLink('resources', null, array(
                'resource_category_id' => $category['resource_category_id'],
                'in_sub' => 1,
            )),
        );

        $data['permissions'] = array(
            'add' => $category['canAdd'],

            'add_file' => !empty($category['allow_local']),
            'add_url' => (!empty($category['allow_external']) || !empty($category['allow_commercial_external'])),
            'add_price' => !empty($category['allow_commercial_external']),
            'add_fileless' => !empty($category['allow_fileless']),
        );

        if (!empty($category['fieldCache'])) {
            if ($fields === null) {
                $fields = $this->_bdApiResource_getFieldModel()->getResourceFields();
            }

            $data['resource_custom_fields'] = array();
            foreach ($category['fieldCache'] as $position => $positionFields) {
                foreach ($positionFields as $fieldId) {
                    if (isset($fields[$fieldId])) {
                        $field = $fields[$fieldId];
                        $fieldData = $this->_bdApiResource_getFieldModel()->prepareApiDataForField($field);

                        $data['resource_custom_fields'][$fieldId] = $fieldData;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return bdApiResource_XenResource_Model_ResourceField
     */
    protected function _bdApiResource_getFieldModel()
    {
        return $this->getModelFromCache('XenResource_Model_ResourceField');
    }
}