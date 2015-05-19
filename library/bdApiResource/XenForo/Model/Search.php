<?php

class bdApiResource_XenForo_Model_Search extends XFCP_bdApiResource_XenForo_Model_Search
{
    public function checkApiSupportsContentType($contentType)
    {
        switch ($contentType) {
            case 'resource_update':
                return true;
        }

        return parent::checkApiSupportsContentType($contentType);
    }

    public function prepareApiContentDataForSearch(array $preparedResults)
    {
        $updateIds = array();
        $data = parent::prepareApiContentDataForSearch($preparedResults);

        foreach ($preparedResults as $key => $preparedResult) {
            switch ($preparedResult['content_type']) {
                case 'resource_update':
                    $updateIds[$preparedResult['content_id']] = $key;
                    break;
            }
        }

        if (!empty($updateIds)) {
            /** @var XenResource_Model_Update $updateModel */
            $updateModel = $this->getModelFromCache('XenResource_Model_Update');
            $updates = $updateModel->getUpdatesByIds(array_keys($updateIds));

            $resourceIds = array();
            foreach ($updates as $update) {
                if (isset($updateIds[$update['resource_update_id']])) {
                    $resourceIds[$update['resource_id']] = $updateIds[$update['resource_update_id']];
                }
            }

            $dataJobParams = array();
            $dataJobParams['resource_ids'] = implode(',', array_keys($resourceIds));
            $dataJob = Appforo_Data_Helper_Batch::doJob('GET', 'resources', $dataJobParams);
            $resources = null;

            if (isset($dataJob['resources'])) {
                // legacy support
                $resources =& $dataJob['resources'];
            } elseif (isset($dataJob['_job_response'])
                && !empty($dataJob['_job_response']->params['resources'])
            ) {
                // new version of batch api
                $resources =& $dataJob['_job_response']->params['resources'];
            }

            if ($resources !== null) {
                foreach ($resources as $resource) {
                    if (empty($resource['resource_id'])
                        || !isset($resourceIds[$resource['resource_id']])
                    ) {
                        // key not found?!
                        continue;
                    }

                    $key = $resourceIds[$resource['resource_id']];
                    $data[$key] = array_merge($preparedResults[$key], $resource, array(
                        'content_type' => 'resource',
                        'content_id' => $resource['resource_id'],
                    ));
                }
            }

            ksort($data);
        }

        return $data;
    }
}