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
class CRM_Fpptareports_Form_Report_Member_FPPTAMembersEnding extends CRM_Fpptareports_Form_Report_Member_FPPTAMemberStartEnd {

  public function __construct() {
    $this->yearFilterLabel = E::ts('Membership ending in year');
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

    $endingMinActivityDateTime = "{$year}-01-01 00:00:00";
    $endingMaxActivityDateTime = "{$year}-12-12 23:59:59";

    // build a list of "ending max id" per membership (max id of activities in this year with 'ending' subject)
    // build a list of "starting max id" per membership (max id of activities in subsequent years with 'starting' subject)
    // compare those lists, identifying contacts having an ending_max_id and NOT having a max(activity_id) > ending max(activity_id)
    // (we could go by activity_date_time, but activity.id is serial, and that seems reliable enough, since it's very hard to do
    // something that would remove and re-create an activity out-of-sequence.)

    $startingParams = [];
    $startingPlaceholders = [];
    $i = 1;
    foreach ($endingSubjects as $endingSubject) {
      $endingParams[$i] = [$endingSubject, 'String'];
      $endingPlaceholders[] = '%'. $i++;
    }
    $endingPlaceholderString = implode(', ', $endingPlaceholders);
    $endingMaxSql = "
      select a.source_record_id, c.id as contact_id, max(a.id) as activity_id
      from civicrm_activity a
        inner join civicrm_membership m on m.id = a.source_record_id
        inner join civicrm_contact c on c.id = m.contact_id
      where 
        1
        and a.activity_type_id = " . self::MEMBERSHIP_STATUS_ACTIVITY_TYPE_ID . "
        and a.subject in ($endingPlaceholderString)
        and activity_date_time between '$endingMinActivityDateTime' and '$endingMaxActivityDateTime'
        and m.owner_membership_id is null
        and m.membership_type_id in (". self::MEMBERSHIP_TYPE_IDS_IN . ")
      group by source_record_id
    ";
    $endingMaxSql = CRM_Core_DAO::composeQuery($endingMaxSql, $endingParams);

    $startingParams = [];
    $startingPlaceholders = [];
    $i = 1;
    foreach ($startingSubjects as $startingSubject) {
      $startingParams[$i] = [$startingSubject, 'String'];
      $startingPlaceholders[] = '%'. $i++;
    }
    $startingPlaceholderString = implode(', ', $startingPlaceholders);
    $startingMaxSql = "
      select source_record_id, c.id as contact_id, max(a.id) as activity_id
      from civicrm_activity a
        inner join civicrm_membership m on m.id = a.source_record_id
        inner join civicrm_contact c on c.id = m.contact_id
      where
        1
        AND (
          (
            a.activity_type_id = " . self::MEMBERSHIP_STATUS_ACTIVITY_TYPE_ID . "
            and a.subject in ($startingPlaceholderString)
          )
          OR
          (
            a.activity_type_id IN (" . self::MEMBERSHIP_SIGNUP_ACTIVITY_TYPE_ID . ", " . self::MEMBERSHIP_RENEWAL_ACTIVITY_TYPE_ID . ")
          )
        )
        and m.owner_membership_id is null
        and m.membership_type_id in (". self::MEMBERSHIP_TYPE_IDS_IN . ")
      group by source_record_id
    ";
    $startingMaxSql = CRM_Core_DAO::composeQuery($startingMaxSql, $startingParams);

    $query = "
      select e.contact_id, e.activity_id, e.source_record_id as membership_id
      from ($endingMaxSql) e
        left join ($startingMaxSql) s on s.contact_id = e.contact_id
          and s.activity_id > e.activity_id
        inner join civicrm_membership m on m.id = e.source_record_id
        inner join civicrm_contact c on c.id = e.contact_id
      where
        1
        and s.contact_id is null
        and not c.is_deleted
    ";
    return $query;
  }
  
}
