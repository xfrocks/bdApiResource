<?php

class bdApiResource_XenResource_Model_ResourceWatch extends XFCP_bdApiResource_XenResource_Model_ResourceWatch
{
    public function prepareApiDataForResourceWatch(array $data, array $resourceWatch)
    {
        $data['follow']['alert'] = true;
        $data['follow']['email'] = !empty($resourceWatch['email_subscribe']);

        return $data;
    }
}