<?php

class bdApiResource_XenResource_Model_Category extends XFCP_bdApiResource_XenResource_Model_Category
{
    public function prepareApiDataForCategories(array $categories)
    {
        $data = array();

        foreach ($categories as $key => $category) {
            $data[] = $this->prepareApiDataForCategory($category);
        }

        return $data;
    }

    public function prepareApiDataForCategory(array $category)
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
        );

        $data['permissions'] = array(
            'add' => $category['canAdd'],

            'add_file' => !empty($category['allow_local']),
            'add_url' => (!empty($category['allow_external']) || !empty($category['allow_commercial_external'])),
            'add_price' => !empty($category['allow_commercial_external']),
            'add_fileless' => !empty($category['allow_fileless']),
        );

        if (!empty($category['fieldCache'])) {
            $data['resource_custom_fields'] = array();
            foreach ($category['fieldCache'] as $position => $positionFields) {
                foreach ($positionFields as $field) {
                    $data['resource_custom_fields'][$field] = array(
                        'name' => $field,
                    );

                    // TODO: field configuration?
                }
            }
        }

        return $data;
    }
}