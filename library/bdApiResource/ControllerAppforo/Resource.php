<?php

class bdApiResource_ControllerAppforo_Resource extends Appforo_ControllerAppforo_Abstract
{
	public function actionGetIndex()
	{
		/* @var $helper bdApiResource_ControllerHelper_Resource */
		$helper = $this->getHelper('bdApiResource_ControllerHelper_Resource');
		
		return $helper->actionGetIndex(__CLASS__);
	}
	
	public function actionGetSingle()
	{
		/* @var $helper bdApiResource_ControllerHelper_Resource */
		$helper = $this->getHelper('bdApiResource_ControllerHelper_Resource');
		
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