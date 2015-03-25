<?php

class bdApiResource_XenResource_Model_Resource extends XFCP_bdApiResource_XenResource_Model_Resource
{
    public function getFetchOptionsToPrepareApiData(array $fetchOptions = array(), array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (isset($fetchOptions['watchUserId'])) {
            $fetchOptions['bdApiResource_likeUserId'] = $fetchOptions['watchUserId'];
        } else {
            $fetchOptions['watchUserId'] = $viewingUser['user_id'];
            $fetchOptions['bdApiResource_likeUserId'] = $viewingUser['user_id'];
        }

        if (empty($fetchOptions['join'])) {
            $fetchOptions['join'] = 0;
        }

        $fetchOptions['join'] |= XenResource_Model_Resource::FETCH_DESCRIPTION;
        $fetchOptions['join'] |= XenResource_Model_Resource::FETCH_VERSION;
        $fetchOptions['bdApiResource_joinDescriptionUpdate'] = true;

        return $fetchOptions;
    }

    public function prepareApiDataForResources(array $resources, array $category)
    {
        $data = array();

        foreach ($resources as $key => $resource) {
            $data[] = $this->prepareApiDataForResource($resource, $category);
        }

        return $data;
    }

    public function prepareApiDataForResource(array $resource, array $category)
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

        $descriptionUpdate = $this->_bdApiResource_getDescriptionUpdateFromResource($resource);

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

            // description update
            'description_update_likes' => 'resource_like_count',
            'description_update_attach_count' => 'resource_attachment_count',
        );
        $data = bdApi_Data_Helper_Core::filter($resource, $publicKeys);

        if (!empty($resource['review_count'])) {
            $data['resource_rating_count'] += $resource['review_count'];
        }

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
            $fields = $this->_bdApiResource_getFieldModel()->bdApiResource_getResourceFields();

            $data['resource_custom_fields'] = array();
            foreach ($resource['customFields'] as $fieldId => $fieldValue) {
                if (!empty($fields[$fieldId])) {
                    $field = $fields[$fieldId];
                    $fieldData = $this->_bdApiResource_getFieldModel()->prepareApiDataForField($field, $fieldValue);

                    $data['resource_custom_fields'][$fieldId] = $fieldData;
                }
            }
        }

        $resourceKeys = array_keys($resource);
        if (in_array('like_date', $resourceKeys, true)) {
            $data['resource_is_liked'] = !empty($resource['like_date']);
        }
        if (in_array('is_watched', $resourceKeys, true)) {
            $data['resource_is_followed'] = !empty($resource['is_watched']);
        }

        if (!empty($resource['attachments'])) {
            $data['attachments'] = $this->prepareApiDataForAttachments($resource['attachments'], $descriptionUpdate, $resource, $category);
        }

        $data['links'] = array(
            'permalink' => XenForo_Link::buildPublicLink('resources', $resource),
            'detail' => XenForo_Link::buildApiLink('resources', $resource),
            'category' => XenForo_Link::buildApiLink('resource-categories', $resource),
            'ratings' => XenForo_Link::buildApiLink('resources/ratings', $resource),
            'likes' => XenForo_Link::buildApiLink('resources/likes', $resource),
            'report' => XenForo_Link::buildApiLink('resources/report', $resource),
            'followers' => XenForo_Link::buildApiLink('resources/followers', $resource),
        );

        if (!empty($descriptionUpdate['attach_count'])) {
            $data['links']['attachments'] = XenForo_Link::buildApiLink('resources/attachments', $resource);
        }

        $data['resource_has_url'] = false;
        $data['resource_has_file'] = false;
        $data['resource_price'] = null;
        $data['resource_currency'] = null;
        if (!empty($resource['is_fileless'])) {
            if (!empty($resource['external_purchase_url'])) {
                $data['resource_has_url'] = true;
                $data['links']['content'] = $resource['external_purchase_url'];
                $data['resource_price'] = $resource['price'];
                $data['resource_currency'] = $resource['currency'];
            }
        } else {
            if (!empty($resource['download_url'])) {
                $data['resource_has_url'] = true;
                $data['links']['content'] = $resource['download_url'];
            } else {
                $data['resource_has_file'] = true;
                $data['links']['content'] = XenForo_Link::buildApiLink('resources/file', $resource);
            }
        }

        if (!empty($resource['discussion_thread_id'])) {
            $data['links']['thread'] = XenForo_Link::buildApiLink('threads', array('thread_id' => $resource['discussion_thread_id']));
        }

        $data['permissions'] = array(
            'download' => $resource['canDownload'],
            'edit' => $resource['canEdit'],
            'edit_file' => $resource['canEdit'] && $data['resource_has_file'],
            'edit_url' => $resource['canEdit'] && $data['resource_has_url'],
            'edit_price' => $resource['canEdit'] && !empty($data['resource_price']) && !empty($data['resource_currency']),
            'delete' => $resource['canDelete'],
            'rate' => $resource['canRate'],
            'like' => $this->_getUpdateModel()->canLikeUpdate($descriptionUpdate, $resource, $category),
            'report' => $this->_getUpdateModel()->canReportUpdate($descriptionUpdate, $resource, $category),
            'follow' => $resource['canWatch'],
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

    public function prepareResourceFetchOptions(array $fetchOptions)
    {
        list($selectFields, $joinTables) = array_values(parent::prepareResourceFetchOptions($fetchOptions));

        if (isset($fetchOptions['bdApiResource_likeUserId'])) {
            if (empty($fetchOptions['bdApiResource_likeUserId'])) {
                $selectFields .= ',
					0 AS like_date';
            } else {
                $selectFields .= ',
					liked_content.like_date';
                $joinTables .= '
					LEFT JOIN xf_liked_content AS liked_content
						ON (liked_content.content_type = \'resource_update\'
							AND liked_content.content_id = resource.description_update_id
							AND liked_content.like_user_id = ' . $this->_getDb()->quote($fetchOptions['bdApiResource_likeUserId']) . ')';
            }
        }

        if (!empty($fetchOptions['bdApiResource_joinDescriptionUpdate'])) {
            if (!empty($fetchOptions['join'])
                && $fetchOptions['join'] & XenResource_Model_Resource::FETCH_DESCRIPTION
            ) {
                $selectFields .= ',
                    1 AS bdApiResource_joinDescriptionUpdate,
					resource_update.attach_count AS description_update_attach_count,
                    resource_update.message_state AS description_update_message_state,
                    resource_update.likes AS description_update_likes';
            }
        }

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables,
        );
    }

    public function bdApiResource_getAndMergeAttachmentsIntoResources(array $resources)
    {
        $updates = array();

        foreach ($resources as $resource) {
            $update = $this->_bdApiResource_getDescriptionUpdateFromResource($resource);
            if (!empty($update)) {
                $updates[$update['resource_update_id']] = $update;
            }
        }

        $updates = $this->_getUpdateModel()->getAndMergeAttachmentsIntoUpdates($updates);

        foreach ($updates as $update) {
            if (!empty($update['attachments']) && isset($resources[$update['resource_id']])) {
                $resources[$update['resource_id']]['attachments'] = $update['attachments'];
            }
        }

        return $resources;
    }

    public function prepareApiDataForAttachments(array $attachments, array $update, array $resource, array $category, $tempHash = '')
    {
        $data = array();

        foreach ($attachments as $key => $attachment) {
            $data[] = $this->prepareApiDataForAttachment($attachment, $update, $resource, $category, $tempHash);
        }

        return $data;
    }

    public function prepareApiDataForAttachment(array $attachment, array $update, array $resource, array $category, $tempHash = '')
    {
        /* @var $attachmentModel XenForo_Model_Attachment */
        $attachmentModel = $this->getModelFromCache('XenForo_Model_Attachment');
        $attachment = $attachmentModel->prepareAttachment($attachment);

        $publicKeys = array(
            // xf_attachment
            'attachment_id' => 'attachment_id',
            'view_count' => 'attachment_download_count',
            // xf_attachment_data
            'filename' => 'filename',
        );

        $data = bdApi_Data_Helper_Core::filter($attachment, $publicKeys);
        $data['resource_id'] = !empty($resource['resource_id']) ? $resource['resource_id'] : 0;

        $paths = XenForo_Application::get('requestPaths');
        $paths['fullBasePath'] = XenForo_Application::getOptions()->get('boardUrl') . '/';

        $data['links'] = array('permalink' => XenForo_Link::buildPublicLink('attachments', $attachment));

        if (!empty($attachment['thumbnailUrl'])) {
            $data['links']['thumbnail'] = XenForo_Link::convertUriToAbsoluteUri($attachment['thumbnailUrl'], true, $paths);
        }

        if (!empty($resource['resource_id'])) {
            $data['links'] += array(
                'data' => XenForo_Link::buildApiLink('resources/attachments', $resource, array('attachment_id' => $attachment['attachment_id'])),
                'resource' => XenForo_Link::buildApiLink('resources', $resource),
            );
        } else {
            $data['links']['data'] = XenForo_Link::buildApiLink('resources/attachments', null, array(
                'attachment_hash' => $tempHash,
                'attachment_id' => $attachment['attachment_id'],
            ));
        }

        $data['permissions'] = array(
            'view' => !empty($tempHash)
                ? $attachmentModel->canViewAttachment($attachment, $tempHash)
                : $this->_getUpdateModel()->canViewUpdateImages($resource, $category),
            'delete' => !empty($tempHash)
                ? $attachmentModel->canDeleteAttachment($attachment, $tempHash)
                : $this->_getUpdateModel()->canUploadAndManageUpdateAttachment(),
        );

        return $data;
    }

    protected function _bdApiResource_getDescriptionUpdateFromResource(array $resource)
    {
        $descriptionUpdate = array();

        if (!empty($resource['bdApiResource_joinDescriptionUpdate'])) {
            $descriptionUpdate = array(
                'resource_update_id' => $resource['description_update_id'],
                'resource_id' => $resource['resource_id'],
                'attach_count' => $resource['description_update_attach_count'],
                'message_state' => $resource['description_update_message_state'],
            );
        }

        return $descriptionUpdate;
    }

    /**
     * @return bdApiResource_XenResource_Model_ResourceField
     */
    protected function _bdApiResource_getFieldModel()
    {
        return $this->getModelFromCache('XenResource_Model_ResourceField');
    }
}
