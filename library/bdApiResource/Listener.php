<?php

class bdApiResource_Listener
{
    public static function load_class($class, array &$extend)
    {
        if (strpos($class, '_ControllerApi_Index')) {
            $extend[] = 'bdApiResource_ControllerApi_Index';
        }

        static $classes = array(
            'XenForo_Model_Search',

            'XenResource_DataWriter_Resource',
            'XenResource_DataWriter_Version',

            'XenResource_Model_Category',
            'XenResource_Model_Rating',
            'XenResource_Model_Resource',
            'XenResource_Model_ResourceField',
            'XenResource_Model_ResourceWatch',
        );

        if (in_array($class, $classes)) {
            $extend[] = 'bdApiResource_' . $class;
        }
    }

    public static function bdapi_setup_routes(array &$routes)
    {
        bdApi_Route_PrefixApi::addRoute($routes, 'resources', 'bdApiResource_Route_PrefixApi_Resources', 'data_only');
        bdApi_Route_PrefixApi::addRoute($routes, 'resource-categories', 'bdApiResource_Route_PrefixApi_Categories', 'data_only');
    }

    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += bdApiResource_FileSums::getHashes();
    }


}
