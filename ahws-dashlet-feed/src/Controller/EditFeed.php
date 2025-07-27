<?php

namespace AHalfWildSheep\iTop\Extension\DashletFeed\Controller;

use MetaModel;

class EditFeed extends AbstractFeed {
	public const BLOCK_CODE = 'ahws-dashlet-feed--edit-entry';
	public const DEFAULT_HTML_TEMPLATE_REL_PATH = 'ahws-dashlet-feed/view/EditFeed';

	public const DEFAULT_ICON = 'fas fa-fw fa-pen';
	protected array $aModifiedAttributes;

	public function __construct($oTargetObject, $sUserLogin, $sDate, $aModifiedAttributesAttCodes)
	{
		parent::__construct($oTargetObject, $sUserLogin, $sDate);

		$this->aModifiedAttributes = [];

		foreach ($aModifiedAttributesAttCodes as $sModifieAttributeAttCode) {
			$this->aModifiedAttributes[] = MetaModel::GetLabel(get_class($oTargetObject), $sModifieAttributeAttCode);
		}
	}

	public function GetModifiedAttributes() {
		return $this->aModifiedAttributes;
	}
}