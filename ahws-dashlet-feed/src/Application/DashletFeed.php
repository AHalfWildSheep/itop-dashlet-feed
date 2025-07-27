<?php

use AHalfWildSheep\iTop\Extension\DashletFeed\Controller\CaseLogFeed;
use AHalfWildSheep\iTop\Extension\DashletFeed\Controller\CreateFeed;
use AHalfWildSheep\iTop\Extension\DashletFeed\Controller\DashletFeedView;
use AHalfWildSheep\iTop\Extension\DashletFeed\Controller\EditFeed;
use AHalfWildSheep\iTop\Extension\DashletFeed\Controller\TransitionFeed;

class DashletFeed extends Dashlet
{

	public function __construct($oModelReflection, $sId)
	{
		parent::__construct($oModelReflection, $sId);
		$this->aProperties['title'] = Dict::S('UI:DashletFeed:Prop:Title:Default');
		$this->aProperties['query'] = 'SELECT UserRequest';
		$this->aProperties['limit'] = 100;
		$this->aProperties['show_creations'] = true;
		$this->aProperties['show_changes'] = true;
		$this->aProperties['changes_filter'] = [];
		$this->aProperties['show_transitions'] = true;
		$this->aProperties['transitions_filter'] = [];
		$this->aProperties['caselogs_filter'] = [];
		$this->aProperties['show_log_entries'] = false;
		$this->aProperties['days_back'] = 14;
	}

	/**
	 * @inheritdoc
	 */
	public static function GetInfo()
	{
		return array(
			'label'       => Dict::S('UI:DashletFeed:Label'),
			'icon'        => 'env-'.utils::GetCurrentEnvironment().'/ahws-dashlet-feed/img/icons8-view-headline-96.png',
			'description' => Dict::S('UI:DashletFeed:Description'),
		);
	}

	public function Render($oPage, $bEditMode = false, $aExtraParams = array())
	{
		$sId = $this->sId;
		$sTitle = $this->aProperties['title'];
		$sQuery = $this->aProperties['query'];
		$iLimit = $this->aProperties['limit'];

		$bShowLogEntries = $this->aProperties['show_log_entries'];
		$CaseLogsFilter = $this->aProperties['caselogs_filter'];
		$iDaysBack = $this->aProperties['days_back'];

		$sClass = '';
		try {
			$oQuery = $this->oModelReflection->GetQuery($this->aProperties['query']);
			$sClass = $oQuery->GetClass();
		}
		catch (Exception $e) {
			// Return empty block if the query causes trouble
			return new DashletFeedView('block_'.$sId.($bEditMode ? '_edit' : ''), $sTitle, [], 'UnkownClass');
		}

		// Get the objects matching the OQL
		$oObjectSearch = DBObjectSearch::FromOQL($sQuery);
		$oObjectSet = new DBObjectSet($oObjectSearch);
		$oObjectSet->OptimizeColumnLoad([$oObjectSearch->GetClassAlias() => []]);
		$aObjectIds = array();

		while ($oObject = $oObjectSet->Fetch()) {
			$aObjectIds[] = $oObject->GetKey();
			if (empty($sClass)) {
				$sClass = get_class($oObject);
			}
		}


		// Collect all activities
		$aActivities = array();
		$sDateLimit = date('Y-m-d H:i:s', time() - ($iDaysBack * 24 * 3600));

		$this->CollectCMDBChangeOperations($aActivities, $sClass, $aObjectIds);

		// Get Case Log entries
		if ($bShowLogEntries) {
			$this->CollectLogEntries($aActivities, $sClass, $aObjectIds, $sDateLimit, $CaseLogsFilter);
		}

		// Sort activities by date (newest last)
		usort($aActivities, function ($a, $b) {
			return strcmp($a->GetDate(), $b->GetDate());
		});

		// Limit results
		$aActivities = array_slice($aActivities, 0, $iLimit);

		return new DashletFeedView('block_'.$sId.($bEditMode ? '_edit' : ''), $sTitle, $aActivities, $sClass);
	}

	public function GetPropertiesFields(DesignerForm $oForm)
	{
		$oField = new DesignerTextField('title', Dict::S('UI:DashletFeed:Prop:Title'), $this->aProperties['title']);
		$oForm->AddField($oField);

		$oField = new DesignerLongTextField('query', Dict::S('UI:DashletFeed:Prop:Query'), $this->aProperties['query']);
		$oField->SetMandatory();
		$oField->AddCSSClass("ibo-query-oql");
		$oField->AddCSSClass("ibo-is-code");
		$oForm->AddField($oField);

		$oField = new DesignerIntegerField('limit', Dict::S('UI:DashletFeed:Prop:Limit'), $this->aProperties['limit']);
		$oField->SetMandatory();
		$oForm->AddField($oField);

		$oField = new DesignerBooleanField('show_creations', Dict::S('UI:DashletFeed:Prop:ShowCreations'), $this->aProperties['show_creations']);
		$oField->SetMandatory();
		$oForm->AddField($oField);

		$oField = new DesignerBooleanField('show_changes', Dict::S('UI:DashletFeed:Prop:ShowChanges'), $this->aProperties['show_changes']);
		$oField->SetMandatory();
		$oForm->AddField($oField);
		try {
			$oQuery = $this->oModelReflection->GetQuery($this->aProperties['query']);
			$sClass = $oQuery->GetClass();
		}
		catch (Exception $e) {
		}

		$oField = new DesignerComboField('changes_filter', Dict::S('UI:DashletFeed:Prop:ChangesFilter'), $this->aProperties['changes_filter']);
		$oField->MultipleSelection(true);

		if (isset($sClass)) {
			$aAllAttributes = $this->GetAllAttributes($this->aProperties['query']);
			$oField->SetAllowedValues($aAllAttributes);
		} else {
			$oField->SetReadOnly();
		}

		$oForm->AddField($oField);

		$oField = new DesignerBooleanField('show_transitions', Dict::S('UI:DashletFeed:Prop:ShowTransitions'), $this->aProperties['show_transitions']);
		$oField->SetMandatory();
		$oForm->AddField($oField);

		$oField = new DesignerComboField('transitions_filter', Dict::S('UI:DashletFeed:Prop:TransitionsFilter'), $this->aProperties['transitions_filter']);
		$oField->MultipleSelection(true);
		if (isset($sClass)) {
			$aAllAttributes = $this->GetAllTransitions($this->aProperties['query']);
			$oField->SetAllowedValues($aAllAttributes);
		} else {
			$oField->SetReadOnly();
		}
		$oForm->AddField($oField);

		$oField = new DesignerBooleanField('show_log_entries', Dict::S('UI:DashletFeed:Prop:ShowLogEntries'), $this->aProperties['show_log_entries']);
		$oField->SetMandatory();
		$oForm->AddField($oField);

		$oField = new DesignerComboField('caselogs_filter', Dict::S('UI:DashletFeed:Prop:CaseLogFilter'), $this->aProperties['caselogs_filter']);
		$oField->MultipleSelection(true);
		if (isset($sClass)) {
			$aAllAttributes = $this->GetAllCaseLogs($this->aProperties['query']);
			$oField->SetAllowedValues($aAllAttributes);
		} else {
			$oField->SetReadOnly();
		}
		$oForm->AddField($oField);

		$oField = new DesignerIntegerField('days_back', Dict::S('UI:DashletFeed:Prop:DaysBack'), $this->aProperties['days_back']);
		$oField->SetMandatory();
		$oForm->AddField($oField);
	}


	private function GetAllAttributes($sQuery)
	{
		$aTrackedAttributes = [];
		try {
			$sClass = DBSearch::FromOQL($sQuery)->GetClass();
			$aAllAttributes = MetaModel::GetAttributesList($sClass);
			$aStateAttribute = MetaModel::GetStateAttributeCode($sClass);
			foreach ($aAllAttributes as $sAttribute) {
				$oAttDef = MetaModel::GetAttributeDef($sClass, $sAttribute);
				if ($oAttDef->GetTrackingLevel() === ATTRIBUTE_TRACKING_ALL && $aStateAttribute !== $sAttribute && !$oAttDef instanceof AttributeCaseLog && !$oAttDef->IsMagic() && !$oAttDef instanceof AttributeExternalField) {
					$aTrackedAttributes[$sAttribute] = MetaModel::GetLabel($sClass, $sAttribute);
				}
			}
		}
		catch (Exception $e) {
			$aTrackedAttributes = [];
		}

		return $aTrackedAttributes;
	}

	private function GetAllTransitions($sQuery)
	{
		$aStateAttributeValues = [];
		try {
			$sClass = DBSearch::FromOQL($sQuery)->GetClass();
			$sStateAttributeCode = MetaModel::GetStateAttributeCode($sClass);
			$aStateAttributeValues = MetaModel::GetAllowedValues_att($sClass, $sStateAttributeCode);
		}
		catch (Exception $e) {
			$aStateAttributeValues = [];
		}

		return $aStateAttributeValues;
	}

	private function GetAllCaseLogs($sQuery)
	{
		$aCaseLogs = [];
		try {
			$sClass = DBSearch::FromOQL($sQuery)->GetClass();
			$aAllAttributes = MetaModel::GetAttributesList($sClass);
			foreach ($aAllAttributes as $sAttribute) {
				$oAttDef = MetaModel::GetAttributeDef($sClass, $sAttribute);
				if ($oAttDef instanceof AttributeCaseLog) {
					$aCaseLogs[$sAttribute] = MetaModel::GetLabel($sClass, $sAttribute);
				}
			}
		}
		catch (Exception $e) {
			$aCaseLogs = [];
		}

		return $aCaseLogs;
	}

	public function Update($aValues, $aUpdatedFields)
	{
		if (in_array('query', $aUpdatedFields)) {
			try {
				$sCurrQuery = $aValues['query'];
				$oCurrSearch = $this->oModelReflection->GetQuery($sCurrQuery);
				$sCurrClass = $oCurrSearch->GetClass();

				$sPrevQuery = $this->aProperties['query'];
				$oPrevSearch = $this->oModelReflection->GetQuery($sPrevQuery);
				$sPrevClass = $oPrevSearch->GetClass();

				if ($sCurrClass !== $sPrevClass) {
					$this->bFormRedrawNeeded = true;
					$this->aProperties['changes_filter'] = [];
					$this->aProperties['transitions_filter'] = [];
					$this->aProperties['caselogs_filter'] = [];
				}
			}
			catch (Exception $e) {
				$this->bFormRedrawNeeded = true;
			}
		}

		return parent::Update($aValues, $aUpdatedFields);
	}

	private function CollectCMDBChangeOperations(&$aActivities, $sClass, $aObjectIds) {
		$bShowCreations = $this->aProperties['show_creations'];
		$bShowChanges = $this->aProperties['show_changes'];
		$aChangesFilter = is_array($this->aProperties['changes_filter']) ? $this->aProperties['changes_filter']: [$this->aProperties['changes_filter']];
		$bShowTransitions = $this->aProperties['show_transitions'];
		$aTransitionsFilter = is_array($this->aProperties['transitions_filter']) ? $this->aProperties['transitions_filter'] : [$this->aProperties['transitions_filter']];
		$iDaysBack = $this->aProperties['days_back'];
		$iLimit = $this->aProperties['limit'];


		$sDateLimit = date('Y-m-d H:i:s', time() - ($iDaysBack * 24 * 3600));

		if($bShowCreations === true) {
			// Query for CMDBChangeOp records
			$sCreateOQL = "SELECT CMDBChangeOpCreate WHERE objclass = :obj_class AND objkey IN (:obj_ids) AND date >= :date_limit";
			$oCreateSet = new DBObjectSet(DBObjectSearch::FromOQL($sCreateOQL, [
				'obj_class' => $sClass,
				'obj_ids' => $aObjectIds,
				'date_limit' => $sDateLimit
			]));

			$oCreateSet->SetLimit($iLimit);

			while ($oCreate = $oCreateSet->Fetch()) {
				$iUserId = $oCreate->Get('user_id');
				$oUser = (empty($oCreate->Get('user_id')) ? null : MetaModel::GetObject('User', $oCreate->Get('user_id'), false, true));
				$sUserLogin = ($oUser === null) ? $oCreate->Get('userinfo') : $oUser->Get('login');
				
				$oObject = MetaModel::GetObject($oCreate->Get('objclass'), $oCreate->Get('objkey'), false);
				if($oObject !== null) {
					$aActivities[] = new CreateFeed($oObject, $sUserLogin, $oCreate->Get('date'));
				}
			}

		}

		if($bShowChanges || $bShowTransitions) {
			$aChangesFilter = $bShowChanges ? $aChangesFilter: [];
			$aChangesFilter = empty($aChangesFilter) ? [''] : $aChangesFilter;

			$aTransitionsFilter = $bShowTransitions ? $aTransitionsFilter: [];
			$aTransitionsFilter = empty($aTransitionsFilter) ? [''] : $aTransitionsFilter;


			$sStateAttCode = $bShowTransitions ? MetaModel::GetStateAttributeCode($sClass) : '';

			$sChangeOQL = "SELECT CMDBChangeOpSetAttribute WHERE objclass = :obj_class AND objkey IN (:obj_ids) AND date >= :date_limit AND (attcode IN (:change_attcodes) OR attcode = :state_attcode)";

			$oChangeSet = new DBObjectSet(DBObjectSearch::FromOQL($sChangeOQL, [
				'obj_class'       => $sClass,
				'obj_ids'         => $aObjectIds,
				'date_limit'      => $sDateLimit,
				'change_attcodes' => $aChangesFilter,
				'state_attcode' => $sStateAttCode,
			]));

			$oChangeSet->SetLimit($iLimit);

			$aGroupedChanges = [];
			while ($oChange = $oChangeSet->Fetch()) {
				$sAttCode = $oChange->Get('attcode');
				$iUserId = $oChange->Get('user_id');
				$sUserLogin = $oChange->Get('userinfo');
				// Try to find user login
				$oUser = (empty($oChange->Get('user_id')) ? null :MetaModel::GetObject('User', $oChange->Get('user_id'), false, true));
				$sUserLogin = ($oUser === null) ? $oChange->Get('userinfo') : $oUser->Get('login');
				
				$sDate = $oChange->Get('date');

				if ($bShowChanges && !$oChange instanceof CMDBChangeOpSetAttributeCaseLog && in_array($sAttCode, $aChangesFilter, true)) {
					$sKey = "$sUserLogin|$sDate|".$oChange->Get('objkey').'|'.$oChange->Get('objclass');
					if (!isset($aGroupedChanges[$sKey])) {
						$aGroupedChanges[$sKey] = array(
							'date'         => $sDate,
							'attcodes'     => [],
							'user'         => $sUserLogin,
							'object_id'    => $oChange->Get('objkey'),
							'object_class' => $oChange->Get('objclass')
						);
					}
					$aGroupedChanges[$sKey]['attcodes'][] = $sAttCode;
				} else if($bShowTransitions && $sAttCode === $sStateAttCode && in_array($oChange->Get('newvalue'), $aTransitionsFilter, true)) {
					$sOldState = $oChange->Get('oldvalue');
					$sNewValue = $oChange->Get('newvalue');

					$oObject = MetaModel::GetObject($oChange->Get('objclass'), $oChange->Get('objkey'), false);

					$aActivities[] = new TransitionFeed($oObject, $sUserLogin, $oChange->Get('date'), $sOldState, $sNewValue);
				}
			}
		}

		// Merge Changes together
		foreach ($aGroupedChanges as $aGroup) {
			$oObject = MetaModel::GetObject($aGroup['object_class'], $aGroup['object_id'], false);

			if ($oObject) {
				$aActivities[] = new EditFeed($oObject, $aGroup['user'], $aGroup['date'], $aGroup['attcodes']);
			}

		}
	}
	private function CollectLogEntries(&$aActivities, $sClass, $aObjectIds)
	{
		$aCaseLogsFilter = is_array($this->aProperties['caselogs_filter']) ? $this->aProperties['caselogs_filter'] : [$this->aProperties['caselogs_filter']];
		$iDaysBack = $this->aProperties['days_back'];

		$sDateLimit = date('Y-m-d H:i:s', time() - ($iDaysBack * 24 * 3600));

		// This is a simplified approach - you might need to adapt based on your specific log structure
		foreach ($aObjectIds as $iObjectId) {
			try {
				$oObject = MetaModel::GetObject($sClass, $iObjectId);

				// Check for case log attributes
				foreach (MetaModel::ListAttributeDefs($sClass) as $sAttCode => $oAttDef) {
					if ($oAttDef instanceof AttributeCaseLog && in_array($sAttCode, $aCaseLogsFilter, true)) {
						$oCaseLog = $oObject->Get($sAttCode);
						if ($oCaseLog instanceof ormCaseLog) {
							$aEntries = $oCaseLog->GetAsArray();
							foreach ($aEntries as $aEntry) {
								if ($aEntry['date'] >= $sDateLimit) {
									$oObject = MetaModel::GetObject($sClass, $iObjectId, false);
									$oUser = MetaModel::GetObject('User', $aEntry['user_id'], false, true);
									$sUserLogin = ($oUser === null) ? '' : $oUser->Get('login');
									
									$aActivities[] = new CaseLogFeed($oObject, $sUserLogin, $aEntry['date'], $sAttCode, $aEntry['message_html']);
								}
							}
						}
					}
				}
			} catch (Exception $e) {
				// Skip this object if there's an error
				continue;
			}
		}
	}
}