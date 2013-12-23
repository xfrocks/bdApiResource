<?php

class bdApiResource_Appforo_ControllerAppforo_Index extends XFCP_bdApiResource_Appforo_ControllerAppforo_Index
{
	public function actionGetIndex()
	{
		$response = parent::actionGetIndex();

		/* @var $helper bdApiResource_ControllerHelper_Index */
		$helper = $this->getHelper('bdApiResource_ControllerHelper_Index');

		return $helper->actionGetIndex($response);
	}

	protected function _getModules()
	{
		$modules = parent::_getModules();

		/* @var $helper bdApiResource_ControllerHelper_Index */
		$helper = $this->getHelper('bdApiResource_ControllerHelper_Index');

		return $helper->getModedules($modules);
	}

}
