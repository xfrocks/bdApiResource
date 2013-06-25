<?php

class bdApiResource_ControllerApi_Category extends bdApi_ControllerApi_Abstract
{
	public function actionGetIndex()
	{
		/* @var $helper bdApiResource_ControllerHelper_Category */
		$helper = $this->getHelper('bdApiResource_ControllerHelper_Category');

		return $helper->actionGetIndex(__CLASS__);
	}

	public function actionGetSingle()
	{
		/* @var $helper bdApiResource_ControllerHelper_Category */
		$helper = $this->getHelper('bdApiResource_ControllerHelper_Category');

		return $helper->actionGetSingle();
	}

	public function bdApiResource_filterDataMany(array $resourcesData)
	{
		return $this->_filterDataMany($resourcesData);
	}
	
	public function bdApiResource_filterDataSingle(array $resourceData, array $prefixes = array())
	{
		return $this->_filterDataSingle($resourceData, $prefixes);
	}
}