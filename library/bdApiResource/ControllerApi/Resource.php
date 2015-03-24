<?php

class bdApiResource_ControllerApi_Resource extends bdApi_ControllerApi_Abstract
{
    public function actionGetIndex()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);
        if (!empty($resourceId)) {
            return $this->responseReroute(__CLASS__, 'get-single');
        }

        $pageNavParams = array();
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
        );
        $fetchOptions = array(
            'limit' => $limit,
            'page' => $page,
        );

        $resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
        $category = null;
        $categories = array();
        if (!empty($resourceCategoryId)) {
            /** @var XenResource_ControllerHelper_Resource $resourceHelper */
            $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
            $category = $resourceHelper->assertCategoryValidAndViewable($resourceCategoryId, $this->_getCategoryModel()->getFetchOptionsToPrepareApiData());

            $conditions['resource_category_id'] = $category['resource_category_id'];
            $pageNavParams['resource_category_id'] = $category['resource_category_id'];
            $categories[$category['resource_category_id']] = $category;
        } else {
            $categories = $this->_getCategoryModel()->getViewableCategories($this->_getCategoryModel()->getFetchOptionsToPrepareApiData());
        }

        $inSub = $this->_input->filterSingle('in_sub', XenForo_Input::UINT);
        if (!empty($category) && !empty($inSub)) {
            $categories = $this->_getCategoryModel()->getViewableCategories($this->_getCategoryModel()->getFetchOptionsToPrepareApiData());
            $categoryList = $this->_getCategoryModel()->groupCategoriesByParent($categories);

            $childCategories = (isset($categoryList[$category['resource_category_id']])
                ? $categoryList[$category['resource_category_id']]
                : array()
            );
            if ($childCategories) {
                $searchCategoryIds = $this->_getCategoryModel()->getDescendantCategoryIdsFromGrouped($categoryList, $category['resource_category_id']);
                $searchCategoryIds[] = $category['resource_category_id'];
            } else {
                $searchCategoryIds = array($category['resource_category_id']);
            }
            $conditions['resource_category_id'] = $searchCategoryIds;
            $pageNavParams['in_sub'] = 1;
        }

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

        $resourcesData = $this->_prepareResources($resources, $categories, array(
            'includeCategory' => empty($conditions['resource_category_id']) OR is_array($conditions['resource_category_id']),
        ));

        $total = $this->_getResourceModel()->countResources($conditions);

        $data = array(
            'resources' => $this->_filterDataMany($resourcesData),
            'resources_total' => $total,
        );

        if (!$this->_isFieldExcluded('category') && !empty($category) && empty($pageNavParams['in_sub'])) {
            $data['category'] = $this->_filterDataSingle($this->_getCategoryModel()->prepareApiDataForCategory($category), array('category'));
        }

        bdApi_Data_Helper_Core::addPageLinks($this->_input, $data, $limit, $total, $page, 'resources', array(), $pageNavParams);

        return $this->responseData('bdApiResource_ViewApi_Resource_List', $data);
    }

    public function actionGetSingle()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        $fetchOptions = $this->_getResourceModel()->getFetchOptionsToPrepareApiData();

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
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);
        if (!empty($resourceId)) {
            return $this->actionPutIndex();
        }

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

        list(, $attachmentTempHash) = $this->getAttachmentTempHash();
        $descriptionDw->setExtraData(XenResource_DataWriter_Update::DATA_ATTACHMENT_HASH, $attachmentTempHash);

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

    protected function actionPutIndex()
    {
        // temporary switched to use POST request because we can't parse multipart in PUT
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

        list(, $attachmentTempHash) = $this->getAttachmentTempHash();
        $descriptionDw->setExtraData(XenResource_DataWriter_Update::DATA_ATTACHMENT_HASH, $attachmentTempHash);

        $dataInput = $this->_input->filter(array(
            'resource_url' => XenForo_Input::STRING,
            'resource_price' => XenForo_Input::UNUM,
            'resource_currency' => XenForo_Input::STRING,
            'resource_version' => array(
                XenForo_Input::STRING,
                'default' => XenForo_Locale::date(XenForo_Application::$time, 'Y-m-d'),
            ),
        ));
        $versionDw = $dw->getVersionDw();

        if (!empty($resource['external_purchase_url'])) {
            // an external purchase resource
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

            $versionDw->set('version_string', $dataInput['resource_version']);
        } else {
            $downloadUrl = $versionDw->get('download_url');
            $newVersionIsNeeded = false;

            if (!empty($downloadUrl)) {
                // an external url resource
                if (empty($dataInput['resource_url'])) {
                    return $this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_requires_resource_url'), 400);
                }

                if ($dataInput['resource_url'] != $downloadUrl) {
                    $newVersionIsNeeded = 'url';
                }
            } elseif (empty($resource['is_fileless'])) {
                // a local file resource
                $dataFile = XenForo_Upload::getUploadedFile('resource_file');

                if (!empty($dataFile)) {
                    $newVersionIsNeeded = 'file';
                }
            }

            if (!empty($newVersionIsNeeded)) {
                /** @var bdApiResource_XenResource_DataWriter_Version $newVersionDw */
                $newVersionDw = XenForo_DataWriter::create('XenResource_DataWriter_Version');
                $newVersionDw->bulkSet(array(
                    'resource_id' => $resource['resource_id'],
                    'version_string' => $dataInput['resource_version'],
                ));

                switch ($newVersionIsNeeded) {
                    case 'file':
                        /** @var bdApi_ControllerHelper_Attachment $attachmentHelper */
                        $attachmentHelper = $this->getHelper('bdApi_ControllerHelper_Attachment');

                        $fileHash = md5($resource['resource_id'] . $visitor['user_id'] . XenForo_Application::$time);
                        $attachmentHelper->doUpload('resource_file', $fileHash, 'resource_version', array(
                            'resource_id' => $resource['resource_id'],
                        ));

                        $newVersionDw->setExtraData(XenResource_DataWriter_Version::DATA_ATTACHMENT_HASH, $fileHash);
                        break;

                    case 'url':
                        $newVersionDw->set('download_url', $dataInput['resource_url']);
                        break;
                }

                $newVersionDw->bdApiResource_onControllerSave($category, $visitor);

                // it will be saved together with resource data writer below
            }
        }

        $fieldValues = $this->_input->filterSingle('resource_custom_fields', XenForo_Input::ARRAY_SIMPLE);
        $dw->setCustomFields($fieldValues);

        $dw->bdApiResource_onControllerSave($category, $visitor);

        XenForo_Db::beginTransaction();
        try {
            $dw->save();

            if (!empty($newVersionDw)) {
                $newVersionDw->save();
            }
        } catch (Exception $e) {
            XenForo_Db::rollback();
            throw new $e;
        }

        XenForo_Db::commit();

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

    public function actionGetFollowed()
    {
        $resourceWatches = $this->_getResourceWatchModel()->getResourcesWatchedByUser(XenForo_Visitor::getUserId());
        $resourcesData = array();

        if (!empty($resourceWatches)) {
            $resourceIds = array();
            foreach ($resourceWatches as $resourceWatch) {
                $resourceIds[] = $resourceWatch['resource_id'];
            }

            if (!empty($resourceIds)) {
                $fetchOptions = $this->_getResourceModel()->getFetchOptionsToPrepareApiData(array(
                    'join' => XenResource_Model_Resource::FETCH_CATEGORY
                ));
                $resources = $this->_getResourceModel()->getResourcesByIds($resourceIds, $fetchOptions);

                foreach ($resources as $key => $resource) {
                    $resourcesData[] = $this->_getResourceModel()->prepareApiDataForResource($resource, $resource);
                }
            }
        }

        foreach ($resourceWatches as $resourceWatch) {
            foreach ($resourcesData as &$resourceDataRef) {
                if ($resourceWatch['resource_id'] == $resourceDataRef['resource_id']) {
                    $resourceDataRef = $this->_getResourceWatchModel()->prepareApiDataForResourceWatch($resourceDataRef, $resourceWatch);
                }
            }
        }

        $data = array('resources' => $this->_filterDataMany($resourcesData));

        return $this->responseData('bdApiResource_ViewApi_Resource_Followed', $data);
    }

    public function actionGetFollowers()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        $users = array();

        if ($this->_getResourceModel()->canWatchResource($resource, $category)) {
            $visitor = XenForo_Visitor::getInstance();

            $resourceWatch = $this->_getResourceWatchModel()
                ->getUserResourceWatchByResourceId($visitor['user_id'], $resource['resource_id']);

            if (!empty($resourceWatch)) {
                $user = array(
                    'user_id' => $visitor['user_id'],
                    'username' => $visitor['username'],
                );

                $user = $this->_getResourceWatchModel()->prepareApiDataForResourceWatch($user, $resourceWatch);

                $users[] = $user;
            }
        }

        $data = array('users' => $this->_filterDataMany($users));

        return $this->responseData('bdApiResource_ViewApi_Resource_Followers', $data);
    }

    public function actionPostFollowers()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        $email = $this->_input->filterSingle('email', XenForo_Input::UINT);

        if (!$this->_getResourceModel()->canWatchResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $state = ($email > 0 ? 'watch_email' : 'watch_no_email');
        $this->_getResourceWatchModel()->setResourceWatchState(XenForo_Visitor::getUserId(), $resource['resource_id'], $state);

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionDeleteFollowers()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        $this->_getResourceWatchModel()->setResourceWatchState(XenForo_Visitor::getUserId(), $resourceId, '');

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionGetAttachments()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        $attachmentId = $this->_input->filterSingle('attachment_id', XenForo_Input::UINT);

        $updates = $this->_getUpdateModel()->getUpdatesByIds(array($resource['description_update_id']));
        $updates = $this->_getUpdateModel()->getAndMergeAttachmentsIntoUpdates($updates);
        $update = reset($updates);

        if (empty($update)) {
            return $this->responseNoPermission();
        }
        if (empty($update['attachments'])) {
            $update['attachments'] = array();
        }

        if (empty($attachmentId)) {
            $attachments = $this->_getResourceModel()->prepareApiDataForAttachments($update['attachments'], $update, $resource, $category);

            $data = array('attachments' => $this->_filterDataMany($attachments));
        } else {
            $attachment = false;

            foreach ($update['attachments'] as $_attachment) {
                if ($_attachment['attachment_id'] == $attachmentId) {
                    $attachment = $_attachment;
                }
            }

            if (!empty($attachment)) {
                return $this->_getAttachmentHelper()->doData($attachment);
            } else {
                return $this->responseError(new XenForo_Phrase('requested_attachment_not_found'), 404);
            }
        }

        return $this->responseData('bdApiResource_ViewApi_Resource_Attachments', $data);
    }

    public function actionPostAttachments()
    {
        list($contentData, $hash) = $this->getAttachmentTempHash();
        $attachmentHelper = $this->_getAttachmentHelper();
        $response = $attachmentHelper->doUpload('file', $hash, 'resource_update', $contentData);

        if ($response instanceof XenForo_ControllerResponse_Abstract) {
            return $response;
        }

        $contentData['resource_update_id'] = 0;
        $attachmentData = $this->_getResourceModel()->prepareApiDataForAttachment($response, $contentData, $contentData, $contentData, $hash);

        $data = array('attachment' => $this->_filterDataSingle($attachmentData));

        return $this->responseData('bdApiResource_ViewApi_Resource_Attachments', $data);
    }

    public function actionDeleteAttachments()
    {
        $hash = $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING);
        $attachmentId = $this->_input->filterSingle('attachment_id', XenForo_Input::UINT);

        $attachmentHelper = $this->_getAttachmentHelper();
        return $attachmentHelper->doDelete($hash, $attachmentId);
    }

    public function actionGetFile()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);

        $version = $this->_getVersionModel()->getVersionById($resource['current_version_id'], array(
            'join' => XenResource_Model_Version::FETCH_FILE
        ));
        if (empty($version)) {
            return $this->responseNoPermission();
        }

        if (!$this->_getVersionModel()->canDownloadVersion($version, $resource, $category, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        $this->_getVersionModel()->logVersionDownload($version, XenForo_Visitor::getUserId());

        if ($version['download_url']) {
            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
                $version['download_url']
            );
        } else {
            /** @var XenForo_Model_Attachment $attachmentModel */
            $attachmentModel = $this->getModelFromCache('XenForo_Model_Attachment');
            $attachment = $attachmentModel->getAttachmentById($version['attachment_id']);

            return $this->_getAttachmentHelper()->doData($attachment);
        }
    }

    public function actionPostReport()
    {
        $resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT);

        /** @var XenResource_ControllerHelper_Resource $resourceHelper */
        $resourceHelper = $this->getHelper('XenResource_ControllerHelper_Resource');
        list($resource, $category) = $resourceHelper->assertResourceValidAndViewable($resourceId);
        $update = $resourceHelper->getUpdateOrError($resource['description_update_id']);

        if (!$this->_getUpdateModel()->canReportUpdate($update, $resource, $category, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        $message = $this->_input->filterSingle('message', XenForo_Input::STRING);
        if (!$message) {
            return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'), 400);
        }

        $this->assertNotFlooding('report');

        $update['resource'] = $resource;
        $update['category'] = $category;

        /* @var $reportModel XenForo_Model_Report */
        $reportModel = XenForo_Model::create('XenForo_Model_Report');
        $reportModel->reportContent('resource_update', $update, $message);

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    protected function _prepareResources(array $resources, array $categories, array $options = array())
    {
        if (!$this->_isFieldExcluded('attachments')) {
            $resources = $this->_getResourceModel()->bdApiResource_getAndMergeAttachmentsIntoResources($resources);
        }

        $resourcesData = array();
        foreach ($resources as $resource) {
            if (!isset($categories[$resource['resource_category_id']])) {
                continue;
            }
            $categoryRef =& $categories[$resource['resource_category_id']];

            $resourceData = $this->_getResourceModel()->prepareApiDataForResource($resource, $categoryRef);

            if (!empty($options['includeCategory'])) {
                $resourceData['category'] = $this->_getCategoryModel()->prepareApiDataForCategory($categoryRef);
            }

            $resourcesData[] = $resourceData;
        }

        return $resourcesData;
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

    /**
     * @return bdApiResource_XenResource_Model_ResourceField
     */
    protected function _getResourceFieldModel()
    {
        return $this->getModelFromCache('XenResource_Model_ResourceField');
    }

    /**
     * @return bdApiResource_XenResource_Model_ResourceWatch
     */
    protected function _getResourceWatchModel()
    {
        return $this->getModelFromCache('XenResource_Model_ResourceWatch');
    }

    /**
     * @return XenResource_Model_Version
     */
    protected function _getVersionModel()
    {
        return $this->getModelFromCache('XenResource_Model_Version');
    }

    /**
     * @return bdApi_ControllerHelper_Attachment
     */
    protected function _getAttachmentHelper()
    {
        return $this->getHelper('bdApi_ControllerHelper_Attachment');
    }

    protected function getAttachmentTempHash()
    {
        $contentData = $this->_input->filter(array(
            'resource_id' => XenForo_Input::UINT,
            'resource_category_id' => XenForo_Input::UINT,
            'attachment_hash' => XenForo_Input::STRING,
        ));

        if (empty($contentData['attachment_hash'])) {
            if (empty($contentData['resource_id']) AND empty($contentData['resource_category_id'])) {
                throw $this->responseException($this->responseError(new XenForo_Phrase('bdapi_resource_slash_resources_attachments_requires_ids'), 400));
            }

            $hash = md5(serialize($contentData));
            $this->getRequest()->setParam('attachment_hash', $hash);
        }

        return array($contentData, $this->_getAttachmentHelper()->getAttachmentTempHash($contentData));
    }
}