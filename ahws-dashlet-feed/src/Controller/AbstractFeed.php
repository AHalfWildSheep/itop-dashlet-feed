<?php

namespace AHalfWildSheep\iTop\Extension\DashletFeed\Controller;

use AttributeDateTime;
use Combodo\iTop\Application\UI\Base\UIBlock;
use MetaModel;
use User;
use UserRights;
use utils;

class AbstractFeed extends UIBlock {
	public const BLOCK_CODE = 'ahws-dashlet-feed--abstract-entry';
	public const DEFAULT_HTML_TEMPLATE_REL_PATH = 'ahws-dashlet-feed/view/AbstractFeed';
	public const DEFAULT_JS_TEMPLATE_REL_PATH = 'ahws-dashlet-feed/view/AbstractFeed';

	public const DEFAULT_ICON = '';
	protected \DBObject $oTargetObject;
	protected string $sUserLogin;
	protected string $sDate;
	protected $iDatetimesReformatLimit;
	

	public function __construct($oTargetObject, $sUserLogin, $sDate)
	{
		parent::__construct();
		$this->oTargetObject = $oTargetObject;
		$this->sUserLogin = $sUserLogin;
		$this->sDate = $sDate;

		$oConfig = MetaModel::GetConfig();
		// Use activity panel parameter as it's pretty similar
		$this->iDatetimesReformatLimit = $oConfig->Get('activity_panel.datetimes_reformat_limit');

	}

	public function GetDate(): string
	{
		return $this->sDate;
	}

	public function SetDate(string $sDate): void
	{
		$this->sDate = $sDate;
	}

	public function GetFormattedDateTime(): string
	{
		$oDateTimeFormat = AttributeDateTime::GetFormat();
		return $oDateTimeFormat->Format($this->sDate);
	}

	public function GetDateTimeFormatForJSWidget(): string
	{
		$oDateTimeFormat = AttributeDateTime::GetFormat();

		return $oDateTimeFormat->ToMomentJS();
	}

	/**
	 * @return int
	 */
	public function GetDatetimesReformatLimit(): int
	{
		return $this->iDatetimesReformatLimit;
	}

	public function GetUserLogin()
	{
		return $this->sUserLogin;
	}
	public function GetContactHTML(): string
	{
		$sContactHTML = '';

		$sContactId = UserRights::GetContactId($this->sUserLogin);
		$iUserId = UserRights::GetUserId($this->sUserLogin);
		if (!empty($sContactId))
		{
			// Picture if generally for Person, so try it first
			if (MetaModel::IsValidClass('Contact'))
			{
				$oContact = MetaModel::GetObject('Person',  $sContactId, false, true);
				$sContactHTML = $oContact->GetHyperLink();
			}
		} elseif (!empty($iUserId)) {
			$sContactHTML = utils::HtmlEntities(UserRights::GetUserFriendlyName($this->sUserLogin));
		}
		
		if(empty($sContactHTML)) {
			return Dict::S('UI:DashletFeed:UnknownUser');
		}

		return $sContactHTML;
	}



	public function SetUserLogin(string $sUserLogin): void
	{
		$this->sUserLogin = $sUserLogin;
	}

	public function GetTargetObjectFriendlyname() {
		return $this->oTargetObject->GetName();
	}

	public function GetTargetObjectHTML() {
		return $this->oTargetObject->GetHyperLink();
	}
	
	public function GetDecorationClasses() {
		return static::DEFAULT_ICON;
	}
}