<?php

namespace AHalfWildSheep\iTop\Extension\DashletFeed\Controller;

use MetaModel;

class TransitionFeed extends AbstractFeed {
	public const BLOCK_CODE = 'ahws-dashlet-feed--transition-entry';
	public const DEFAULT_HTML_TEMPLATE_REL_PATH = 'ahws-dashlet-feed/view/TransitionFeed';

	public const DEFAULT_ICON = 'fas fa-fw fa-map-signs';
	protected string $sPreviousState;
	protected string $sCurrentState;
	protected string $sPreviousStateHTML;
	protected string $sCurrentStateHTML;

	public function __construct($oTargetObject, $sUserLogin, $sDate, $sPreviousState, $sCurrentState)
	{
		parent::__construct($oTargetObject, $sUserLogin, $sDate);
		$this->sPreviousState = $sPreviousState;
		$this->sCurrentState = $sCurrentState;
		$sAttributeState = MetaModel::GetStateAttributeCode(get_class($oTargetObject));
		$oAttDef = MetaModel::GetAttributeDef(get_class($oTargetObject), $sAttributeState);
		$this->sPreviousStateHTML = $oAttDef->GetAsHTML($this->sPreviousState);
		$this->sCurrentStateHTML = $oAttDef->GetAsHTML($this->sCurrentState);
	}


	public function GetPreviousState() {
		return $this->sPreviousState;
	}

	public function GetCurrentState() {
		return $this->sCurrentState;
	}

	public function GetPreviousStateHTML() {
		return $this->sPreviousStateHTML;
	}

	public function GetCurrentStateHTML() {
		return $this->sCurrentStateHTML;
	}
}