<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return array(
  0 =>
  array(
    'name' => 'CRM_Fpptareports_Form_Report_Event_FPPTAParticipantListing',
    'entity' => 'ReportTemplate',
    'params' =>
    array(
      'version' => 3,
      'label' => 'FPPTA Event Participant Report (List)',
      'description' => 'Event_FPPTAParticipantListing (com.joineryhq.fpptareports)',
      'class_name' => 'CRM_Fpptareports_Form_Report_Event_FPPTAParticipantListing',
      'report_url' => 'fpptareports/participantlisting',
      'component' => 'CiviEvent',
    ),
  ),
);
