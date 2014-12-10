<?php

class bdApiResource_XenResource_Model_Resource extends XFCP_bdApiResource_XenResource_Model_Resource
{
    public function getFetchOptionsToPrepareApiData(array $fetchOptions = array())
    {
        if (empty($fetchOptions['join'])) {
            $fetchOptions['join'] = 0;
        }

        $fetchOptions['join'] |= XenResource_Model_Resource::FETCH_DESCRIPTION;
        $fetchOptions['join'] |= XenResource_Model_Resource::FETCH_VERSION;

        return $fetchOptions;
    }

    public function prepareApiDataForResources(array $resources, array $category)
    {
        $data = array();

        $fields = $this->_bdApiResource_getFieldModel()->getResourceFields();

        foreach ($resources as $key => $resource) {
            $data[] = $this->prepareApiDataForResource($resource, $category, $fields);
        }

        return $data;
    }

    public function prepareApiDataForResource(array $resource, array $category, array $fields = null)
    {
        if (!isset($resource['canDownload'])) {
            $resource = $this->prepareResource($resource, $category);
            $resource = $this->prepareResourceCustomFields($resource, $category);
        }

        if (isset($resource['description'])) {
            $resource['message'] = $resource['description'];
            $resource['messageHtml'] = bdApi_Data_Helper_Message::getHtml($resource);
            $resource['messagePlainText'] = bdApi_Data_Helper_Message::getPlainText($resource['message']);
        }

        $publicKeys = array(
            // xf_resource
            'resource_id' => 'resource_id',
            'resource_category_id' => 'resource_category_id',
            'title' => 'resource_title',
            'tag_line' => 'resource_description',
            'version_string' => 'resource_version',
            'user_id' => 'creator_user_id',
            'username' => 'creator_username',
            'resource_date' => 'resource_create_date',
            'last_update' => 'resource_update_date',
            'download_count' => 'resource_download_count',
            'rating' => 'resource_rating', // XenResource_Model_Resource::prepareResource
            'rating_count' => 'resource_rating_count',
            'rating_sum' => 'resource_rating_sum',
            'rating_avg' => 'resource_rating_avg',
            'rating_weighted' => 'resource_rating_weighted',

            // message
            'message' => 'resource_text',
            'messageHtml' => 'resource_text_html',
            'messagePlainText' => 'resource_text_plain_text',
        );
        $data = bdApi_Data_Helper_Core::filter($resource, $publicKeys);

        if (isset($resource['resource_state'])) {
            switch ($resource['resource_state']) {
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

        if (!empty($resource['customFields'])) {
            if ($fields === null) {
                $fields = $this->_bdApiResource_getFieldModel()->getResourceFields();
            }

            $data['resource_custom_fields'] = array();
            foreach ($resource['customFields'] as $fieldId => $fieldValue) {
                if (isset($fields[$fieldId])) {
                    $field = $fields[$fieldId];
                    $fieldData = $this->_bdApiResource_getFieldModel()->prepareApiDataForField($field, $fieldValue);

                    $data['resource_custom_fields'][$fieldId] = $fieldData;
                }
            }
        }

        $data['links'] = array(
            'permalink' => XenForo_Link::buildPublicLink('resources', $resource),
            'detail' => XenForo_Link::buildApiLink('resources', $resource),
            'category' => XenForo_Link::buildApiLink('resource-categories', $resource),
            'ratings' => XenForo_Link::buildApiLink('resources/ratings', $resource),
            'likes' => XenForo_Link::buildApiLink('resources/likes', $resource),
        );

        if (!empty($resource['is_fileless'])) {
            if (!empty($resource['external_purchase_url'])) {
                $data['links']['content'] = $resource['external_purchase_url'];
                $data['resource_price'] = $resource['price'];
                $data['resource_currency'] = $resource['currency'];
            }
        } else {
            if (!empty($resource['download_url'])) {
                $data['links']['content'] = $resource['download_url'];
            } else {
                $data['links']['content'] = XenForo_Link::buildPublicLink('resources/download', $resource, array('version' => $resource['current_version_id'],));
            }
        }

        if (!empty($resource['discussion_thread_id'])) {
            $data['links']['thread'] = XenForo_Link::buildApiLink('threads', array('thread_id' => $resource['discussion_thread_id']));
        }

        $data['permissions'] = array(
            'download' => $resource['canDownload'],
            'edit' => $resource['canEdit'],
            'delete' => $resource['canDelete'],
            'rate' => $resource['canRate'],
        );

        if (is_callable(array(
            'XenResource_ViewPublic_Helper_Resource',
            'getResourceIconUrl'
        ))) {
            $data['links']['icon'] = XenForo_Link::convertUriToAbsoluteUri(XenResource_ViewPublic_Helper_Resource::getResourceIconUrl($resource), true);
            $data['permissions']['add_icon'] = $data['permissions']['edit'];
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
