<?php

namespace AHalfWildSheep\iTop\Extension\DashletFeed\Controller;

use MetaModel;

class CaseLogFeed extends AbstractFeed {
	public const BLOCK_CODE = 'ahws-dashlet-feed--case-log-entry';
	public const DEFAULT_HTML_TEMPLATE_REL_PATH = 'ahws-dashlet-feed/view/CaseLogFeed';

	public const DEFAULT_ICON = 'fas fa-fw fa-quote-left';
	protected string $sLastLog;
	protected string $sLogAttCode;

	public function __construct($oTargetObject, $sUserLogin, $sDate, $sLogAttCode, $sLastLog)
	{
		parent::__construct($oTargetObject, $sUserLogin, $sDate);
		$this->sLogAttCode = $sLogAttCode;
		$this->sLastLog = $sLastLog;
	}

	public function GetLastLog(): string
	{
		return $this->sLastLog;
	}

	public function GetLogAttCode(): string {
		return $this->sLogAttCode;
	}

	public function GetLogAttribute(): string {
		return MetaModel::GetLabel(get_class($this->oTargetObject), $this->sLogAttCode);
	}
}