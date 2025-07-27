<?php

namespace AHalfWildSheep\iTop\Extension\DashletFeed\Controller;

class CreateFeed extends AbstractFeed {
	public const BLOCK_CODE = 'ahws-dashlet-feed--create-entry';
	public const DEFAULT_HTML_TEMPLATE_REL_PATH = 'ahws-dashlet-feed/view/CreateFeed';

	public const DEFAULT_ICON = 'fas fa-fw fa-seedling';

	public function __construct($oTargetObject, $sUserLogin, $sDate)
	{
		parent::__construct($oTargetObject, $sUserLogin, $sDate);
	}
}