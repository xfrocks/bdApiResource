<?php

class bdApiResource_ControllerApi_Resource extends bdApi_ControllerApi_Abstract
{
    public function actionGetIndex()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);
        if (!empty($resourceId)) {
            return $this->responseReroute(__CLASS__, 'get-single');
        }

        $resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
        if (empty($resourceCategoryId)) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_category_id'), 400);
        }

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        $category = $resourceHelper->assertCategoryValidAndViewable($resourceCategoryId);

        $pageNavParams = array(
            'resource_category_id' => $resourceCategoryId,
        );
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $limit = XenForo_Application::get('options')->discussionsPerPage;

        $inputLimit = $this->_input->filterSingle('limit', XenForo_Input::UINT);
        if (!empty($inputLimit)) {
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

            'watchUserId' => XenForo_Visitor::getUserId(),
        );

        $order = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => 'natural'));
        switch ($order) {
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

        $resources = $this->_getResourceModel()->getResources(
            $conditions,
            $this->_getResourceModel()->getFetchOptionsToPrepareApiData($fetchOptions)
        );
        $data = $this->_getResourceModel()->prepareApiDataForResources($resources, $category);

        $total = $this->_getResourceModel()->countResources($conditions);

        $data = array(
            'resources' => $this->_filterDataMany($data),
            'resources_total' => $total,
        );

        bdApi_Data_Helper_Core::addPageLinks($this->_input, $data, $limit, $total, $page, 'resources', array(), $pageNavParams);

        return $this->responseData('bdApiResource_ViewApi_Resource_List', $data);
    }

    public function actionGetSingle()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        $fetchOptions = $this->_getResourceModel()->getFetchOptionsToPrepareApiData(array(
            'watchUserId' => XenForo_Visitor::getUserId(),
        ));

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId, $fetchOptions);

        $data = $this->_getResourceModel()->prepareApiDataForResource($resource, $category);

        $data = array(
            'resource' => $this->_filterDataSingle($data),
        );

        return $this->responseData('bdApi_ViewApi_Resource_Single', $data);
    }

    public function actionPostIndex()
    {
        $resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
        if (empty($resourceCategoryId)) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_category_id'), 400);
        }

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        $category = $resourceHelper->assertCategoryValidAndViewable($resourceCategoryId);
        if (!$this->_getCategoryModel()->canAddResource($category)) {
            return $this->responseNoPermission();
        }

        $visitor = XenForo_Visitor::getInstance();
        $input = $this->_input->filter(array(
            'resource_title' => XenForo_Input::STRING,
            'resource_description' => XenForo_Input::STRING,
        ));
        /* @var $editorHelper XenForo_ControllerHelper_Editor */
        $editorHelper = $this->getHelper('Editor');
        $input['resource_text'] = $editorHelper->getMessageText('resource_text', $this->_input);
        $input['resource_text'] = XenForo_Helper_String::autoLinkBbCode($input['resource_text']);

        if (empty($input['resource_title'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_title'), 400);
        }
        if (empty($input['resource_description'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_description'), 400);
        }
        if (empty($input['resource_text'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_text'), 400);
        }

        /* @var $dw bdApiResource_XenResource_DataWriter_Resource */
        $dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');
        $dw->set('resource_category_id', $category['resource_category_id']);

        $dw->set('user_id', $visitor['user_id']);
        $dw->set('username', $visitor['username']);

        $dw->set('title', $input['resource_title']);
        $dw->set('tag_line', $input['resource_description']);

        $descriptionDw = $dw->getDescriptionDw();
        $descriptionDw->set('message', $input['resource_text']);

        $versionDw = $dw->getVersionDw();
        $dataInput = $this->_input->filter(array(
            'resource_url' => XenForo_Input::STRING,
            'resource_price' => XenForo_Input::UNUM,
            'resource_currency' => XenForo_Input::STRING,
            'resource_version' => XenForo_Input::STRING,
        ));
        $dataFile = XenForo_Upload::getUploadedFile('resource_file');

        if (!empty($dataFile)) {
            if (empty($category['allow_local'])) {
                return $this->responseNoPermission();
            }

            /** @var bdApi_ControllerHelper_Attachment $attachmentHelper */
            $attachmentHelper = $this->getHelper('bdApi_ControllerHelper_Attachment');

            $fileHash = md5($category['resource_category_id'] . $visitor['user_id'] . XenForo_Application::$time);
            $attachmentHelper->doUpload('resource_file', $fileHash, 'resource_version', array(
                'resource_category_id' => $category['resource_category_id'],
            ));

            $versionDw->setExtraData(XenResource_DataWriter_Version::DATA_ATTACHMENT_HASH, $fileHash);
        } elseif (!empty($dataInput['resource_url'])) {
            if (!empty($dataInput['resource_price']) AND !empty($dataInput['resource_currency'])) {
                if (empty($category['allow_commercial_external'])) {
                    return $this->responseNoPermission();
                }

                $dw->bulkSet(array(
                    'is_fileless' => 1,
                    'price' => $dataInput['resource_price'],
                    'currency' => $dataInput['resource_currency'],
                    'external_purchase_url' => $dataInput['resource_url'],
                ));
                $versionDw->setOption(XenResource_DataWriter_Version::OPTION_IS_FILELESS, true);
            } else {
                if (empty($category['allow_external'])) {
                    return $this->responseNoPermission();
                }

                $versionDw->set('download_url', $dataInput['resource_url']);
            }
        } else {
            if (empty($category['allow_fileless'])) {
                return $this->responseNoPermission();
            }

            $dw->set('is_fileless', 1);
            $versionDw->setOption(XenResource_DataWriter_Version::OPTION_IS_FILELESS, true);
        }

        if ($dataInput['resource_version'] === '') {
            $dataInput['resource_version'] = XenForo_Locale::date(XenForo_Application::$time, 'Y-m-d');
        }
        $versionDw->set('version_string', $dataInput['resource_version']);

        $fieldValues = $this->_input->filterSingle('resource_custom_fields', XenForo_Input::ARRAY_SIMPLE);
        $dw->setCustomFields($fieldValues);

        $dw->bdApiResource_onControllerSave($category, $visitor);

        $dw->preSave();

        if (!$dw->hasErrors()) {
            $this->assertNotFlooding('post');
        }

        $dw->save();
        $resource = $dw->getMergedData();

        $this->_request->setParam('resource_id', $resource['resource_id']);
        return $this->responseReroute(__CLASS__, 'get-single');
    }

    public function actionPutIndex()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        if (!$this->_getResourceModel()->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $visitor = XenForo_Visitor::getInstance();
        $input = $this->_input->filter(array(
            'resource_title' => XenForo_Input::STRING,
            'resource_description' => XenForo_Input::STRING,
        ));
        /* @var $editorHelper XenForo_ControllerHelper_Editor */
        $editorHelper = $this->getHelper('Editor');
        $input['resource_text'] = $editorHelper->getMessageText('resource_text', $this->_input);
        $input['resource_text'] = XenForo_Helper_String::autoLinkBbCode($input['resource_text']);

        if (empty($input['resource_title'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_title'), 400);
        }
        if (empty($input['resource_description'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_description'), 400);
        }
        if (empty($input['resource_text'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_text'), 400);
        }

        /* @var $dw bdApiResource_XenResource_DataWriter_Resource */
        $dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');
        $dw->setExistingData($resource, true);

        $dw->set('title', $input['resource_title']);
        $dw->set('tag_line', $input['resource_description']);

        $descriptionDw = $dw->getDescriptionDw();
        $descriptionDw->set('message', $input['resource_text']);

        if (!empty($resource['external_purchase_url'])) {
            // already an external purchase
            $versionDw = $dw->getVersionDw();
            $dataInput = $this->_input->filter(array(
                'resource_url' => XenForo_Input::STRING,
                'resource_price' => XenForo_Input::UNUM,
                'resource_currency' => XenForo_Input::STRING,
                'resource_version' => XenForo_Input::STRING,
            ));

            if (empty($dataInput['resource_price'])) {
                return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_price'), 400);
            }
            if (empty($dataInput['resource_currency'])) {
                return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_currency'), 400);
            }
            if (empty($dataInput['resource_url'])) {
                return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_url'), 400);
            }

            $dw->bulkSet(array(
                'price' => $dataInput['resource_price'],
                'currency' => $dataInput['resource_currency'],
                'external_purchase_url' => $dataInput['resource_url']
            ));
            $versionDw->setOption(XenResource_DataWriter_Version::OPTION_IS_FILELESS, true);

            if ($dataInput['resource_version'] === '') {
                $dataInput['resource_version'] = XenForo_Locale::date(XenForo_Application::$time, 'Y-m-d');
            }
            $versionDw->set('version_string', $dataInput['resource_version']);
        }

        $fieldValues = $this->_input->filterSingle('resource_custom_fields', XenForo_Input::ARRAY_SIMPLE);
        $dw->setCustomFields($fieldValues);

        $dw->bdApiResource_onControllerSave($category, $visitor);

        $dw->save();
        $resource = $dw->getMergedData();

        XenForo_Model_Log::logModeratorAction('resource', $resource, 'edit', array_merge($input, array(
            'reason' => __METHOD__,
        )));

        return $this->responseReroute(__CLASS__, 'get-single');
    }

    public function actionDeleteIndex()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        if (!$this->_getResourceModel()->canDeleteResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        /* @var $dw bdApiResource_XenResource_DataWriter_Resource */
        $dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');
        $dw->setExistingData($resource, true);
        $dw->set('resource_state', 'deleted');
        $dw->save();

        XenForo_Model_Log::logModeratorAction('resource', $resource, 'delete_soft', array(
            'reason' => __METHOD__,
        ));

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionPostIcon()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        if (!$this->_getResourceModel()->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $icon = XenForo_Upload::getUploadedFile('file');
        if (empty($icon)) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_icon_requires_upload_file'), 400);
        }

        $this->_getResourceModel()->uploadResourceIcon($icon, $resource['resource_id']);

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionDeleteIcon()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        if (!$this->_getResourceModel()->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $this->_getResourceModel()->deleteResourceIcon($resource['resource_id']);

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionGetRatings()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        $pageNavParams = array();
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $limit = XenForo_Application::get('options')->discussionsPerPage;

        $inputLimit = $this->_input->filterSingle('limit', XenForo_Input::UINT);
        if (!empty($inputLimit)) {
            $limit = $inputLimit;
            $pageNavParams['limit'] = $inputLimit;
        }

        $conditions = array(
                'resource_id' => $resource['resource_id'],
            ) + $this->_getCategoryModel()->getPermissionBasedFetchConditions($category);
        $fetchOptions = array(
            'limit' => $limit,
            'page' => $page,
        );

        $ratings = $this->_getRatingModel()->getRatings(
            $conditions,
            $this->_getRatingModel()->getFetchOptionsToPrepareApiData($fetchOptions)
        );
        $data = $this->_getRatingModel()->prepareApiDataForRatings($ratings, $resource, $category);

        $total = $this->_getRatingModel()->countRatings($conditions);

        $data = array(
            'ratings' => $this->_filterDataMany($data),
            'ratings_total' => $total,
        );

        bdApi_Data_Helper_Core::addPageLinks($this->_input, $data, $limit, $total, $page, 'resources/ratings', $resource, $pageNavParams);

        return $this->responseData('bdApiResource_ViewApi_Resource_Ratings', $data);
    }

    public function actionPostRatings()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        if (!$this->_getResourceModel()->canRateResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $visitor = XenForo_Visitor::getInstance();
        $input = $this->_input->filter(array(
            'rating_value' => XenForo_Input::UINT,
        ));
        /* @var $editorHelper XenForo_ControllerHelper_Editor */
        $editorHelper = $this->getHelper('Editor');
        $input['rating_text'] = $editorHelper->getMessageText('rating_text', $this->_input);
        $input['rating_text'] = XenForo_Helper_String::autoLinkBbCode($input['rating_text']);

        $existing = $this->_getRatingModel()->getRatingByVersionAndUserId($resource['current_version_id'], $visitor['user_id']);
        if ($existing && !$this->_getRatingModel()->canUpdateRating($existing, $resource, $category, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        if (XenForo_Application::getOptions()->get('resourceReviewRequired') && empty($input['rating_text'])) {
            return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_ratings_requires_rating_text'), 400);
        }

        /** @var XenResource_DataWriter_Rating $ratingDw */
        $ratingDw = XenForo_DataWriter::create('XenResource_DataWriter_Rating');

        $fields = $ratingDw->getFields();
        foreach ($fields as $table => $tableFields) {
            foreach ($tableFields as $field => $fieldConfig) {
                if ($field == 'rating' && !empty($fieldConfig['min']) && !empty($fieldConfig['max'])) {
                    if ($input['rating_value'] < $fieldConfig['min'] || $input['rating_value'] > $fieldConfig['max']) {
                        return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_ratings_requires_rating_value_in_range', $fieldConfig), 400);
                    }
                }
            }
        }

        $ratingDw->set('resource_version_id', $resource['current_version_id']);
        $ratingDw->set('version_string', $resource['version_string']);
        $ratingDw->set('resource_id', $resource['resource_id']);
        $ratingDw->set('user_id', $visitor['user_id']);
        $ratingDw->set('rating', $input['rating_value']);
        $ratingDw->set('message', $input['rating_text']);

        if ($existing) {
            $deleteDw = XenForo_DataWriter::create('XenResource_DataWriter_Rating');
            $deleteDw->setExistingData($existing, true);
            $deleteDw->delete();
        }

        $ratingDw->save();

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionGetLikes()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource,) = $resourceHelper->assertResourceValidAndViewable($resourceId);
        $update = $resourceHelper->getUpdateOrError($resource['description_update_id']);

        $likes = $this->_getLikeModel()->getContentLikes('resource_update', $update['resource_update_id']);
        $users = array();

        if (!empty($likes)) {
            foreach ($likes as $like) {
                $users[] = array(
                    'user_id' => $like['like_user_id'],
                    'username' => $like['username'],
                );
            }
        }

        $data = array('users' => $users);

        return $this->responseData('bdApiResource_ViewApi_Resource_Likes', $data);
    }

    public function actionPostLikes()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);
        $update = $resourceHelper->getUpdateOrError($resource['description_update_id']);

        if (!$this->_getUpdateModel()->canLikeUpdate($update, $resource, $category, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        $likeModel = $this->_getLikeModel();

        $existingLike = $likeModel->getContentLikeByLikeUser('resource_update', $update['resource_update_id'], XenForo_Visitor::getUserId());
        if (empty($existingLike)) {
            $latestUsers = $likeModel->likeContent('resource_update', $update['resource_update_id'], $resource['user_id']);

            if ($latestUsers === false) {
                return $this->responseNoPermission();
            }
        }

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionDeleteLikes()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);
        $update = $resourceHelper->getUpdateOrError($resource['description_update_id']);

        if (!$this->_getUpdateModel()->canLikeUpdate($update, $resource, $category, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        $likeModel = $this->_getLikeModel();

        $existingLike = $likeModel->getContentLikeByLikeUser('resource_update', $update['resource_update_id'], XenForo_Visitor::getUserId());
        if (!empty($existingLike)) {
            $latestUsers = $likeModel->unlikeContent($existingLike);

            if ($latestUsers === false) {
                return $this->responseNoPermission();
            }
        }

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    /**
     * @return bdApiResource_XenResource_Model_Resource
     */
    protected function _getResourceModel()
    {
        return $this->getModelFromCache('XenResource_Model_Resource');
    }

    /**
     * @return bdApiResource_XenResource_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('XenResource_Model_Category');
    }

    /**
     * @return bdApiResource_XenResource_Model_Rating
     */
    protected function _getRatingModel()
    {
        return $this->getModelFromCache('XenResource_Model_Rating');
    }

    /**
     * @return XenResource_Model_Update
     */
    protected function _getUpdateModel()
    {
        return $this->getModelFromCache('XenResource_Model_Update');
    }

    /**
     * @return XenForo_Model_Like
     */
    protected function _getLikeModel()
    {
        return $this->getModelFromCache('XenForo_Model_Like');
    }
}