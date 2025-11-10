<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

 use CRM_Fpptareports_ExtensionUtil as E;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Fpptareports_Form_Report_Member_FPPTAMembersStarting extends CRM_Fpptareports_Form_Report_Member_FPPTAMemberStartEnd {
  
  public function __construct() {
    $this->yearFilterLabel = E::ts('Membership new or returning in year');
    parent::__construct();
  }

  protected function _generateTempTableQuery() {
    $year = $this->_params['year_value'];

    $positiveStatuses = [];
    $negativeStatuses = [];
    $membershipStatuses = \Civi\Api4\MembershipStatus::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('id', 'name', 'is_current_member')
      ->addWhere('is_active', '=', TRUE)
      ->execute();
    foreach ($membershipStatuses as $membershipStatus) {
      if ($membershipStatus['is_current_member']) {
        $positiveStatuses[] = $membershipStatus['name'];
      }
      else {
        $negativeStatuses[] = $membershipStatus['name'];
      }
    }


    $startingSubjects = [];
    $endingSubjects = [];
    foreach ($positiveStatuses as $positiveStatus) {
      foreach ($negativeStatuses as $negativeStatus) {
        $startingSubjects[] = "Status changed from $negativeStatus to $positiveStatus";
        $endingSubjects[] = "Status changed from $positiveStatus to $negativeStatus";
      }
    }

    $prevYear = $year - 1;
    $startingMinActivityDateTime = "{$prevYear}-10-01 00:00:00";
    $startingMaxActivityDateTime = "{$year}-12-12 23:59:59";

    // build a list of "starting max id" per membership (max id of activities in this year with 'starting' subject)
    // compare those lists, identifying memberships having a starting_max_id and NOT having an ending_max_id > starting_max_id.

    $startingParams = [];
    $startingPlaceholders = [];
    $i = 1;
    foreach ($startingSubjects as $startingSubject) {
      $startingParams[$i] = [$startingSubject, 'String'];
      $startingPlaceholders[] = '%'. $i++;
    }
    $startingPlaceholderString = implode(', ', $startingPlaceholders);
    $startingMaxSql = "
      select source_record_id, max(id) as activity_id
      from civicrm_activity a
      where
        1
        and (
          (
            a.activity_type_id = " . self::MEMBERSHIP_STATUS_ACTIVITY_TYPE_ID . "
            and a.subject in ($startingPlaceholderString)
          )
          OR
          (
            a.activity_type_id IN (" . self::MEMBERSHIP_SIGNUP_ACTIVITY_TYPE_ID . ")
          )
        )
        and activity_date_time between '$startingMinActivityDateTime' and '$startingMaxActivityDateTime'
      group by source_record_id
    ";
    $startingMaxSql = CRM_Core_DAO::composeQuery($startingMaxSql, $startingParams);

    $query = "
      select s.source_record_id as membership_id, s.activity_id, m.contact_id
      from ($startingMaxSql) s
        inner join civicrm_membership m on m.id = s.source_record_id
        inner join civicrm_contact c on c.id = m.contact_id
      where
        1
        and not c.is_deleted
        and m.owner_membership_id is null
        and m.membership_type_id in (". self::MEMBERSHIP_TYPE_IDS_IN . ")
    ";
    return $query;
  }

}
