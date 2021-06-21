<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Fpptareports_Form_Report_Payment_Extra',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'FPPTA: Payment Extra',
      'description' => 'Custom for FPPTA: Payment details with extra data for soft credits, related orgs, and participant details',
      'class_name' => 'CRM_Fpptareports_Form_Report_Payment_Extra',
      'report_url' => 'com.joineryhq.fpptareports/payment_extra',
      'component' => 'CiviContribute',
    ],
  ],
];
