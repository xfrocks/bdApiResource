<?php

class bdApiResource_XenResource_Model_Resource extends XFCP_bdApiResource_XenResource_Model_Resource
{
	public function getFetchOptionsToPrepareApiData(array $fetchOptions = array())
	{
		return $fetchOptions;
	}

	public function prepareApiDataForResources(array $resources, array $category)
	{
		$data = array();

		foreach ($resources as $key => $resource)
		{
			$data[] = $this->prepareApiDataForResource($resource, $category);
		}

		return $data;
	}

	public function prepareApiDataForResource(array $resource, array $category)
	{
		$resource = $this->prepareResource($resource, $category);

		$publicKeys = array(
				// xf_resource
				'resource_id'		=> 'resource_id',
				'resource_category_id' => 'resource_category_id',
				'title'				=> 'resource_title',
				'tag_lie'			=> 'resource_description',
				'user_id'			=> 'creator_user_id',
				'username'			=> 'creator_username',
				'price'				=> 'resource_price',
				'currency'			=> 'resource_currency',
				'resource_date'		=> 'resource_create_date',
				'last_update'		=> 'resource_update_date',
				'download_count'	=> 'resource_download_count',
				'rating'			=> 'resource_rating', // XenResource_Model_Resource::prepareResource
				'rating_count'		=> 'resource_rating_count',
				'rating_sum'		=> 'resource_rating_sum',
				'rating_avg'		=> 'resource_rating_avg',
				'rating_weighted'	=> 'resource_rating_weighted',
		);

		if (isset($resource['resource_state']))
		{
			switch ($resource['resource_state'])
			{
				case 'visible':
					$data['resource_is_published'] = true;
					$data['resource_is_deleted'] = false;
					break;
				case 'moderated':
					$data['resource_is_published'] = false;
					$data['resource_is_deleted'] = false;
					break;
				case 'deleted':
					$data['resource_is_published'] = false;
					$data['resource_is_deleted'] = true;
					break;
			}
		}

		if (XenForo_Application::isRegistered('_Appforo_fc'))
		{
			$data = Appforo_Data_Helper_Core::filter($resource, $publicKeys);

			$data['links'] = array(
					'permalink' => Appforo_Link::buildPublicLink('resources', $resource),
					'detail' => Appforo_Link::buildAppforoLink('resources', $resource),
					'category' => Appforo_Link::buildAppforoLink('resource-categories', $resource),
			);
		}
		else
		{
			$data = bdApi_Data_Helper_Core::filter($resource, $publicKeys);

			$data['links'] = array(
					'permalink' => bdApi_Link::buildPublicLink('resources', $resource),
					'detail' => bdApi_Link::buildApiLink('resources', $resource),
					'category' => bdApi_Link::buildApiLink('resource-categories', $resource),
			);
		}

		if (!$resource['is_fileless'])
		{
			if (!empty($resource['external_url']))
			{
				$data['links']['content'] = $resource['external_url'];
			}
			elseif (!empty($resource['external_purchase_url']))
			{
				$data['links']['content'] = $resource['external_purchase_url'];
			}
			else
			{
				if (XenForo_Application::isRegistered('_Appforo_fc'))
				{
					$data['links']['content'] = Appforo_Link::buildPublicLink('resources/download', $resource, array(
							'version' => $resource['current_version_id'],
					));
				}
				else
				{
					$data['links']['content'] = bdApi_Link::buildPublicLink('resources/download', $resource, array(
							'version' => $resource['current_version_id'],
					));
				}
			}
		}

		if (!empty($resource['discussion_thread_id']))
		{
			if (XenForo_Application::isRegistered('_Appforo_fc'))
			{
				$data['links']['thread'] = Appforo_Link::buildAppforoLink('threads', array('thread_id' => $resource['discussion_thread_id']));
			}
			else
			{
				$data['links']['thread'] = bdApi_Link::buildApiLink('threads', array('thread_id' => $resource['discussion_thread_id']));
			}
		}

		$data['permissions'] = array(
				'download'			=> $resource['canDownload'],
				'edit'				=> $resource['canEdit'],
				'delete'			=> $resource['canDelete'],
				'rate'				=> $resource['canRate'],
		);

		return $data;
	}
}