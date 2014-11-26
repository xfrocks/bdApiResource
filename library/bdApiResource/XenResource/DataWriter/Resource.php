<?php

class bdApiResource_XenResource_DataWriter_Resource extends XFCP_bdApiResource_XenResource_DataWriter_Resource
{
    public function bdApiResource_onControllerSave(array $category, XenForo_Visitor $visitor)
    {
        $resourceId = $this->getExisting('resource_id');

        if (!empty($category['always_moderate_create'])
            && ($this->get('resource_state') == 'visible' || !$resourceId)
            && !$visitor->hasPermission('resource', 'approveUnapprove')
        ) {
            $this->set('resource_state', 'moderated');
        }

        if (!$resourceId) {
            $watch = XenForo_Visitor::getInstance()->get('default_watch_state');
            if (!$watch)
            {
                $watch = 'watch_no_email';
            }

            $this->setExtraData(XenResource_DataWriter_Resource::DATA_THREAD_WATCH_DEFAULT, $watch);
        }
    }
}