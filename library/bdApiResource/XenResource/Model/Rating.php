<?php

class bdApiResource_XenResource_Model_Rating extends XFCP_bdApiResource_XenResource_Model_Rating
{
    public function getFetchOptionsToPrepareApiData(array $fetchOptions = array())
    {
        if (empty($fetchOptions['join'])) {
            $fetchOptions['join'] = 0;
        }

        $fetchOptions['join'] |= XenResource_Model_Rating::FETCH_USER;

        return $fetchOptions;
    }

    public function prepareApiDataForRatings(array $ratings, array $resource, array $category)
    {
        $data = array();

        foreach ($ratings as $key => $rating) {
            $data[] = $this->prepareApiDataForRating($rating, $resource, $category);
        }

        return $data;
    }

    public function prepareApiDataForRating(array $rating, array $resource, array $category)
    {
        if (empty($rating['messageHtml'])) {
            $rating['messageHtml'] = bdApi_Data_Helper_Message::getHtml($rating);
        }

        if (empty($rating['messagePlainText'])) {
            $rating['messagePlainText'] = bdApi_Data_Helper_Message::getPlainText($rating['message']);
        }

        $publicKeys = array(
            // xf_resource_rating
            'resource_rating_id' => 'rating_id',
            'resource_id' => 'resource_id',
            'user_id' => 'creator_user_id',
            'username' => 'creator_username',

            'rating' => 'rating_value',
            'rating_date' => 'rating_create_date',

            // message
            'message' => 'rating_text',
            'messageHtml' => 'rating_text_html',
            'messagePlainText' => 'rating_text_plain_text',
        );
        $data = bdApi_Data_Helper_Core::filter($rating, $publicKeys);

        if (isset($resource['rating_date'])) {
            switch ($resource['rating_date']) {
                case 'visible':
                    $data['rating_is_published'] = true;
                    $data['rating_is_deleted'] = false;
                    break;
                case 'moderated':
                    $data['rating_is_published'] = false;
                    $data['rating_is_deleted'] = false;
                    break;
                case 'deleted':
                    $data['rating_is_published'] = false;
                    $data['rating_is_deleted'] = true;
                    break;
            }
        }

        $data['links'] = array(
            'resource' => XenForo_Link::buildApiLink('resources', $resource),
            'category' => XenForo_Link::buildApiLink('resource-categories', $category),
        );

        return $data;
    }
}