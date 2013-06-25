<?php

class bdApiResource_XenResource_Model_Category extends XFCP_bdApiResource_XenResource_Model_Category
{
	public function prepareApiDataForCategories(array $categories)
	{
		$data = array();

		foreach ($categories as $key => $category)
		{
			$data[] = $this->prepareApiDataForCategory($category);
		}

		return $data;
	}

	public function prepareApiDataForCategory(array $category)
	{
		$category = $this->prepareCategory($category);

		$publicKeys = array(
				// xf_resource_category
				'resource_category_id'		=> 'resource_category_id',
				'category_title' 			=> 'category_title',
				'category_description'		=> 'category_description',
				'parent_category_id'		=> 'parent_category_id',
				'resource_count'			=> 'category_resource_count',
		);

		$data = bdApi_Data_Helper_Core::filter($category, $publicKeys);

		if (XenForo_Application::isRegistered('_Appforo_fc'))
		{
			$data['links'] = array(
					'permalink' => Appforo_Link::buildPublicLink('resources/categories', $category),
					'detail' => Appforo_Link::buildAppforoLink('resource-categories', $category),
					'resources' => Appforo_Link::buildAppforoLink('resources', null, array(
							'resource_category_id' => $category['resource_category_id']
					)),
			);
		}
		else
		{
			$data['links'] = array(
					'permalink' => bdApi_Link::buildPublicLink('resources/categories', $category),
					'detail' => bdApi_Link::buildApiLink('resource-categories', $category),
					'resources' => bdApi_Link::buildApiLink('resources', null, array(
							'resource_category_id' => $category['resource_category_id']
					)),
			);
		}

		$data['permissions'] = array(
				'add'						=> $category['canAdd'],
		);

		return $data;
	}
}