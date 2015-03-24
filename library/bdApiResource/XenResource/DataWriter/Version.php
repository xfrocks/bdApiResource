<?php

class bdApiResource_XenResource_DataWriter_Version extends XFCP_bdApiResource_XenResource_DataWriter_Version
{
    public function bdApiResource_onControllerSave(array $category, XenForo_Visitor $visitor)
    {
        if ($this->isInsert()
            && $category['always_moderate_update']
            && !$visitor->hasPermission('resource', 'approveUnapprove')
        ) {
            $this->set('version_state', 'moderated');
        }
    }
}