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
class CRM_Fpptareports_Form_Report_Event_FPPTAParticipantListing extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_contribField = FALSE;
  protected $_groupFilter = TRUE;
  protected $_tagFilter = TRUE;
  protected $_balance = FALSE;
  protected $campaigns;

  protected $_customGroupExtends = [
    'Participant',
    'Contact',
    'Individual',
    'Event',
  ];

  public $_drilldownReport = ['event/income' => 'Link to Detail Report'];

  /**
   * Array of group-based yes/no columns. Can't populate this
   * here because we'll use E::ts(), so we populate it in __construct().
   * Format is:
   *  [
   *    85 => [
   *      'label' => E::ts('Is FPPTA Education Commitee?'),
   *      'value' => E::ts('Education'),
   *    ],
   *    [group_id] => [
   *      'label' => [column label]
   *      'value' => [string to display if participant is group member]
   *    ],
   *  ]
   * @var array
   */
  private $groupColumnMetadata = [];

  /**
   * Class constructor.
   */
  public function __construct() {

    // See docblock for this class variable.
    $this->groupColumnMetadata = [
      85 => [
        'label' => E::ts('Is FPPTA Education Commitee?'),
        'value' => E::ts('Education'),
      ],
      87 => [
        'label' => E::ts('Is FPPTA Service Vendor?'),
        'value' => E::ts('Vendor'),
      ],
      86 => [
        'label' => E::ts('Is FPPTA Board Member?'),
        'value' => E::ts('Board'),
      ],
      90 => [
        'label' => E::ts('Is Moderator?'),
        'value' => E::ts('Moderator'),
      ],
      89 => [
        'label' => E::ts('Is Volunteer?'),
        'value' => E::ts('Volunteer'),
      ],
      91 => [
        'label' => E::ts('Is Staff?'),
        'value' => E::ts('Staff'),
      ],
    ];

    $roleOptionIdPerName = $this->_getParticipantRoleOptionIdPerName();

    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array_merge([
          // CRM-17115 - to avoid changing report output at this stage re-instate
          // old field name for sort name
          'sort_name_linked' => [
            'title' => E::ts('Participant Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
            'dbAlias' => 'contact_civireport.sort_name',
          ],
        ],
          $this->getBasicContactFields(),
          [
            'age_at_event' => [
              'title' => E::ts('Age at Event'),
              'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, event_civireport.start_date)',
            ],
          ]
        ),
        'grouping' => 'contact-fields',
        'order_bys' => [
          'sort_name' => [
            'title' => E::ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
          'first_name' => [
            'name' => 'first_name',
            'title' => E::ts('First Name'),
          ],
          'gender_id' => [
            'name' => 'gender_id',
            'title' => E::ts('Gender'),
          ],
          'birth_date' => [
            'name' => 'birth_date',
            'title' => E::ts('Birth Date'),
          ],
          'age_at_event' => [
            'name' => 'age_at_event',
            'title' => E::ts('Age at Event'),
          ],
          'contact_type' => [
            'title' => E::ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => E::ts('Contact Subtype'),
          ],
        ],
        'filters' => CRM_Report_Form::getBasicContactFilters(),
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => E::ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'filters' => [
          'email' => [
            'title' => E::ts('Participant E-mail'),
            'operator' => 'like',
          ],
        ],
      ],
    ];
    $this->_columns += $this->getAddressColumns();
    $this->_columns += [
      'civicrm_participant' => [
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => [
          'participant_id' => ['title' => E::ts('Participant ID')],
          'participant_record' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'event_id' => [
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'status_id' => [
            'title' => E::ts('Status'),
            'default' => TRUE,
          ],
          'role_id' => [
            'title' => E::ts('Role'),
            'default' => TRUE,
          ],
          'fee_currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'registered_by_id' => [
            'title' => E::ts('Registered by Participant ID'),
          ],
          'registered_by_name' => [
            'title' => E::ts('Registered by Participant Name'),
            'name' => 'registered_by_id',
          ],
          'p_source' => [
            'name' => 'source',
            'title' => E::ts('Participant: Source'),
          ],
          'participant_fee_level' => NULL,
          'participant_fee_amount' => ['title' => E::ts('Participant Fee')],
          'participant_register_date' => ['title' => E::ts('Registration Date')],
          'total_paid' => [
            'title' => E::ts('Total Paid'),
            'dbAlias' => 'IFNULL(SUM(ft_civireport.total_amount), 0)',
            'type' => 1024,
          ],
          'balance' => [
            'title' => E::ts('Balance'),
            'dbAlias' => 'participant_civireport.fee_amount - IFNULL(SUM(ft_civireport.total_amount), 0)',
            'type' => 1024,
          ],
          'is_speaker' => [
            'title' => E::ts('Is speaker?'),
            'dbAlias' => "IF(participant_civireport.role_id in ({$roleOptionIdPerName['Speaker']}), 'Speaker', '')",
          ],
        ],
        'grouping' => 'event-fields',
        'filters' => [
          'event_id' => [
            'name' => 'event_id',
            'title' => E::ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => [
              'entity' => 'Event',
              'select' => ['minimumInputLength' => 0],
            ],
          ],
          'sid' => [
            'name' => 'status_id',
            'title' => E::ts('Participant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ],
          'rid' => [
            'name' => 'role_id',
            'title' => E::ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ],
          'participant_register_date' => [
            'title' => E::ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'fee_currency' => [
            'title' => E::ts('Fee Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'registered_by_id' => [
            'title' => E::ts('Registered by Participant ID'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
          'source' => [
            'title' => E::ts('Participant Source'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
        ],
        'order_bys' => [
          'participant_register_date' => [
            'title' => E::ts('Registration Date'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
          'event_id' => [
            'title' => E::ts('Event'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
        ],
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => E::ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_event' => [
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => [
          'event_type_id' => [
            'title' => E::ts('Event Type'),
          ],
          'event_start_date' => [
            'title' => E::ts('Event Start Date'),
          ],
          'event_end_date' => [
            'title' => E::ts('Event End Date'),
          ],
        ],
        'grouping' => 'event-fields',
        'filters' => [
          'eid' => [
            'name' => 'event_type_id',
            'title' => E::ts('Event Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ],
          'event_start_date' => [
            'title' => E::ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'event_end_date' => [
            'title' => E::ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'order_bys' => [
          'event_type_id' => [
            'title' => E::ts('Event Type'),
            'default_weight' => '2',
            'default_order' => 'ASC',
          ],
          'event_start_date' => [
            'title' => E::ts('Event Start Date'),
          ],
        ],
      ],
      'civicrm_note' => [
        'dao' => 'CRM_Core_DAO_Note',
        'fields' => [
          'participant_note' => [
            'name' => 'note',
            'title' => E::ts('Participant Note'),
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contribution_id' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
            'csv_display' => TRUE,
            'title' => E::ts('Contribution ID'),
          ],
          'financial_type_id' => ['title' => E::ts('Financial Type')],
          'receive_date' => ['title' => E::ts('Contribution Date')],
          'contribution_status_id' => ['title' => E::ts('Contribution Status')],
          'ctrb_payment_instrument_id' => [
            'name' => 'payment_instrument_id',
            'title' => E::ts('Contrib: Payment Method'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_source' => [
            'name' => 'source',
            'title' => E::ts('Contribution Source'),
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'trxn_id' => NULL,
          'trxn_id' => ['title' => E::ts('Transaction ID')],
          'invoice_number' => ['title' => E::ts('Invoice Number')],
        ],
        'grouping' => 'contrib-fields',
        'filters' => [
          'receive_date' => [
            'title' => E::ts('Contribution Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'financial_type_id' => [
            'title' => E::ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ],
          'currency' => [
            'title' => E::ts('Contribution Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_status_id' => [
            'title' => E::ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => NULL,
          ],
        ],
      ],
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
        'grouping' => 'priceset-fields',
        'filters' => [
          'price_field_value_id' => [
            'name' => 'price_field_value_id',
            'title' => E::ts('Fee Level'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->getPriceLevels(),
          ],
        ],
      ],
      'civicrm_financial_trxn' => [
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'alias' => 'ft',
        'grouping' => 'trxn-fields',
        'fields' => [
          'trxn_date' => [
            'title' => E::ts('Payment Date'),
            'dbAlias' => 'group_concat(distinct ft_civireport.trxn_date ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
          'trxn_payment_instrument_id' => [
            'name' => 'payment_instrument_id',
            'title' => E::ts('Payment: Payment Method'),
            'dbAlias' => 'group_concat(distinct ft_civireport.payment_instrument_id ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
          'check_number' => [
            'title' => E::ts('Check Number'),
            'dbAlias' => 'group_concat(distinct ft_civireport.check_number ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
          'total_amount' => [
            'title' => E::ts('Payment Amount'),
            'dbAlias' => 'group_concat(distinct ft_civireport.total_amount ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
          'fee_amount' => [
            'title' => E::ts('Payment Fee'),
            'dbAlias' => 'group_concat(distinct ft_civireport.fee_amount ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
          'net_amount' => [
            'title' => E::ts('Payment Net'),
            'dbAlias' => 'group_concat(distinct ft_civireport.net_amount ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
          'pan_truncation' => [
            'title' => E::ts('CC Last 4'),
            'dbAlias' => 'group_concat(distinct ft_civireport.pan_truncation ORDER BY ft_civireport.id ASC SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '")',
          ],
        ],
      ],
      'civicrm_membership_assoc' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'is_member_assoc' => [
            'title' => E::ts('Is Member: Associate?'),
            'dbAlias' => 'if(membership_assoc_status.id IS NOT NULL AND membership_assoc.id IS NOT NULL, "Associate", "")',
          ],
        ],
        'grouping' => 'fppta-member-fields',
      ],
      'civicrm_membership_pens' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'is_member_pens' => [
            'title' => E::ts('Is Member: Pension Board?'),
            'dbAlias' => 'if(membership_pens_status.id IS NOT NULL AND membership_pens.id IS NOT NULL, "Pension Board", "")',
          ],
        ],
        'grouping' => 'fppta-member-fields',
      ],
      'civicrm_line_item_booth' => [
        'dao' => 'CRM_Price_DAO_LineItem',
        'fields' => [
          'is_booth' => [
            'title' => E::ts('Org employee requested exhibitor booth?'),
            'dbAlias' => 'if(group_concat(cw_line_item_booth.id) > "", "Booth", "")',
          ],
        ],
        'grouping' => 'fppta-booth-fields',
      ],

      'event_sponsor_employee' => [
        'fields' => [
          'sponsorship_level_empl' => [
            'title' => E::ts('Employer Sponsorship Level'),
            'dbAlias' => 'group_concat(distinct emplspons.level)',
          ],
        ],
        'grouping' => 'fppta-sponsor-fields',
      ],
      'event_sponsor_registering_for' => [
        'fields' => [
          'sponsorship_level_regfor' => [
            'title' => E::ts("'Registering for' Sponsorship Level"),
            'dbAlias' => 'group_concat(distinct regforspons.level)',
          ],
        ],
        'grouping' => 'fppta-sponsor-fields',
      ],
    ];

    $this->_options = [
      'blank_column_begin' => [
        'title' => E::ts('Blank column at the Begining'),
        'type' => 'checkbox',
      ],
      'blank_column_end' => [
        'title' => E::ts('Blank column at the End'),
        'type' => 'select',
        'options' => [
          '' => E::ts('-select-'),
          1 => E::ts('One'),
          2 => E::ts('Two'),
          3 => E::ts('Three'),
        ],
      ],
    ];

    foreach ($this->groupColumnMetadata as $groupId => $meta) {
      $this->_columns['civicrm_group_contact_' . $groupId] = [
        'fields' => [
          'is_group_' . $groupId => [
            'title' => $meta['label'],
            'dbAlias' => 'if(group_contact_' . $groupId . '_civireport.id, "' . $meta['value'] . '", "")',
          ],
        ],
        'grouping' => 'fppta-group-fields',
      ];
    }

    // CRM-17115 avoid duplication of sort_name - would be better to standardise name
    // & behaviour across reports but trying for no change at this point.
    $this->_columns['civicrm_contact']['fields']['sort_name']['no_display'] = TRUE;

    // If we have campaigns enabled, add those elements to both the fields, filters and sorting
    $this->addCampaignFields('civicrm_participant', FALSE, TRUE);

    $this->_currencyColumn = 'civicrm_participant_fee_currency';
    parent::__construct();
  }

  /**
   * Searches database for priceset values.
   *
   * @return array
   */
  public function getPriceLevels() {
    $query = "
SELECT CONCAT(cv.label, ' (', ps.title, ' - ', cf.label , ')') label, cv.id
FROM civicrm_price_field_value cv
LEFT JOIN civicrm_price_field cf
  ON cv.price_field_id = cf.id
LEFT JOIN civicrm_price_set_entity ce
  ON ce.price_set_id = cf.price_set_id
LEFT JOIN civicrm_price_set ps
  ON ce.price_set_id = ps.id
WHERE ce.entity_table = 'civicrm_event'
ORDER BY  cv.label
";
    $dao = CRM_Core_DAO::executeQuery($query);
    $elements = [];
    while ($dao->fetch()) {
      $elements[$dao->id] = "$dao->label\n";
    }

    return $elements;
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];

    //add blank column at the Start
    if (array_key_exists('options', $this->_params) &&
      !empty($this->_params['options']['blank_column_begin'])
    ) {
      $select[] = " '' as blankColumnBegin";
      $this->_columnHeaders['blankColumnBegin']['title'] = '_ _ _ _';
    }
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_contribution') {
              $this->_contribField = TRUE;
            }
            if ($fieldName == 'total_paid' || $fieldName == 'balance') {
              $this->_balance = TRUE;
              // modify the select if filtered by fee_level as the from clause
              // already selects the total_amount from civicrm_contribution table
              if (!empty($this->_params['price_field_value_id_value'])) {
                $field['dbAlias'] = str_replace('SUM(ft_civireport.total_amount)', 'ft_civireport.total_amount', $field['dbAlias']);
              }
            }
            $alias = "{$tableName}_{$fieldName}";
            $select[] = "{$field['dbAlias']} as $alias";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_selectAliases[] = $alias;
          }
        }
      }
    }
    //add blank column at the end
    $blankcols = CRM_Utils_Array::value('blank_column_end', $this->_params);
    if ($blankcols) {
      for ($i = 1; $i <= $blankcols; $i++) {
        $select[] = " '' as blankColumnEnd_{$i}";
        $this->_columnHeaders["blank_{$i}"]['title'] = "_ _ _ _";
      }
    }

    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    return $errors;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_participant {$this->_aliases['civicrm_participant']}
             LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
                    ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND
                        {$this->_aliases['civicrm_event']}.is_template = 0
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  )
             {$this->_aclFrom}
      ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // Include participant note.
    if ($this->isTableSelected('civicrm_note')) {
      $this->_from .= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                   ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_participant' AND
                   {$this->_aliases['civicrm_participant']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
    }
    if ($this->isTableSelected('civicrm_group_contact')) {
      $this->_from .= "
            LEFT JOIN civicrm_group_contact {$this->_aliases['civicrm_group_contact']}
                  ON {$this->_aliases['civicrm_group_contact']}.contact_id = {$this->_aliases['civicrm_participant']}.contact_id
                     AND {$this->_aliases['civicrm_group_contact']}.status = 'added'
      ";
    }
    if ($this->isTableSelected('civicrm_membership_assoc')) {
      $this->_from .= "
            LEFT JOIN civicrm_membership membership_assoc
                  ON membership_assoc.contact_id = {$this->_aliases['civicrm_participant']}.contact_id
                    AND membership_assoc.membership_type_id = 1
            LEFT JOIN civicrm_membership_status membership_assoc_status
                  ON membership_assoc_status.id = membership_assoc.status_id
                     AND membership_assoc_status.is_current_member
      ";
    }
    if ($this->isTableSelected('civicrm_membership_pens')) {
      $this->_from .= "
            LEFT JOIN civicrm_membership membership_pens
                  ON membership_pens.contact_id = {$this->_aliases['civicrm_participant']}.contact_id
                    AND membership_pens.membership_type_id = 2
            LEFT JOIN civicrm_membership_status membership_pens_status
                  ON membership_pens_status.id = membership_pens.status_id
                     AND membership_pens_status.is_current_member
      ";
    }

    if ($this->_contribField) {
      $this->_from .= "
            LEFT JOIN civicrm_participant_payment pp
                   ON ({$this->_aliases['civicrm_participant']}.id  = pp.participant_id)
            LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                   ON (pp.contribution_id  = {$this->_aliases['civicrm_contribution']}.id)
            LEFT JOIN civicrm_entity_financial_trxn eft
                  ON eft.entity_id = {$this->_aliases['civicrm_contribution']}.id AND eft.entity_table = 'civicrm_contribution'
            LEFT JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
                  ON {$this->_aliases['civicrm_financial_trxn']}.id = eft.financial_trxn_id AND
                     {$this->_aliases['civicrm_financial_trxn']}.is_payment = 1
      ";
    }
    if (!empty($this->_params['price_field_value_id_value'])) {
      $this->_from .= "
            LEFT JOIN civicrm_line_item line_item_civireport
                  ON line_item_civireport.entity_table = 'civicrm_participant' AND
                     line_item_civireport.entity_id = {$this->_aliases['civicrm_participant']}.id AND
                     line_item_civireport.qty > 0
      ";
    }
    if ($this->isTableSelected('event_sponsor_registering_for')) {
      $this->_from .= "
        LEFT JOIN civicrm_value_participant_d_21 pdet
          ON pdet.entity_id = {$this->_aliases['civicrm_participant']}.id
        LEFT JOIN (
          SELECT
            ctrb.contact_id,
            spons.sponsorship_for_event_192 as event_id,
            spons.sponsorship_level_193 as level
          FROM
            `civicrm_value_event_sponsor_34` spons
            inner join civicrm_contribution ctrb ON ctrb.id = spons.entity_id
          where
            spons.sponsorship_for_event_192
            AND ctrb.contribution_status_id in (1,2,5,6,8)
        ) regforspons
          ON regforspons.contact_id = pdet.registering_for_organization_lis_179
            AND regforspons.event_id = {$this->_aliases['civicrm_participant']}.event_id
      ";
    }
    if ($this->isTableSelected('event_sponsor_employee')) {
      $this->_from .= "
        LEFT JOIN (
          SELECT
            ctrb.contact_id,
            spons.sponsorship_for_event_192 as event_id,
            spons.sponsorship_level_193 as level
          FROM
            `civicrm_value_event_sponsor_34` spons
            inner join civicrm_contribution ctrb ON ctrb.id = spons.entity_id
          where
            spons.sponsorship_for_event_192
            AND ctrb.contribution_status_id in (1,2,5,6,8)
        ) emplspons
          ON emplspons.contact_id = {$this->_aliases['civicrm_contact']}.employer_id
            AND emplspons.event_id = {$this->_aliases['civicrm_participant']}.event_id
      ";
    }

    foreach ($this->groupColumnMetadata as $groupId => $meta) {
      $groupTableName = 'civicrm_group_contact_' . $groupId;
      if ($this->isTableSelected($groupTableName)) {
        $this->_from .= "
              LEFT JOIN civicrm_group_contact {$this->_aliases[$groupTableName]}
                    ON {$this->_aliases[$groupTableName]}.contact_id = {$this->_aliases['civicrm_participant']}.contact_id
                       AND {$this->_aliases[$groupTableName]}.status = 'added'
                       AND {$this->_aliases[$groupTableName]}.group_id = $groupId
        ";
      }
    }

    if ($this->isTableSelected('civicrm_line_item_booth')) {
      $this->_from .= "
            LEFT JOIN civicrm_contact cw on contact_civireport.employer_id = cw.employer_id
            LEFT JOIN civicrm_participant cwp on cwp.contact_id = cw.id and cwp.event_id = participant_civireport.event_id
            LEFT JOIN civicrm_value_event_metadat_35 cwemeta
              ON cwemeta.entity_id = cwp.event_id

            LEFT JOIN civicrm_line_item cw_line_item_booth
              ON cw_line_item_booth.entity_table = 'civicrm_participant'
                AND cw_line_item_booth.entity_id = cwp.id
                AND cw_line_item_booth.qty > 0
                AND cw_line_item_booth.price_field_id = cwemeta.booth_selection_price_field_194
      ";
    }

  }

  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_participant']}.id");
  }

  public function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  /**
   * @param $rows
   * @param $entryFound
   * @param $row
   * @param int $rowId
   * @param $rowNum
   * @param $types
   *
   * @return bool
   */
  private function _initBasicRow(&$rows, &$entryFound, $row, $rowId, $rowNum, $types) {
    if (!array_key_exists($rowId, $row)) {
      return FALSE;
    }

    $value = $row[$rowId];
    if ($value) {
      $rows[$rowNum][$rowId] = $types[$value];
    }
    $entryFound = TRUE;
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $eventType = CRM_Core_OptionGroup::values('event_type');
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    $multiSeparator = ';<BR />';
    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_participant_event_id', $row)) {
        $eventId = $row['civicrm_participant_event_id'];
        if ($eventId) {
          $rows[$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($eventId, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('event/income',
            'reset=1&force=1&id_op=in&id_value=' . $eventId,
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]['civicrm_participant_event_id_link'] = $url;
          $rows[$rowNum]['civicrm_participant_event_id_hover'] = E::ts("View Event Income Details for this Event");
        }
        $entryFound = TRUE;
      }

      // handle event type id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_event_event_type_id', $rowNum, $eventType);

      // handle participant status id
      if (array_key_exists('civicrm_participant_status_id', $row)) {
        $statusId = $row['civicrm_participant_status_id'];
        if ($statusId) {
          $rows[$rowNum]['civicrm_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($statusId, FALSE, 'label');
        }
        $entryFound = TRUE;
      }

      // handle participant role id
      if (array_key_exists('civicrm_participant_role_id', $row)) {
        $roleId = $row['civicrm_participant_role_id'];
        if ($roleId) {
          $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $roleId);
          $roleId = [];
          foreach ($roles as $role) {
            $roleId[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
          }
          $rows[$rowNum]['civicrm_participant_role_id'] = implode(', ', $roleId);
        }
        $entryFound = TRUE;
      }

      // Handle registered by name
      if (array_key_exists('civicrm_participant_registered_by_name', $row)) {
        $registeredById = $row['civicrm_participant_registered_by_name'];
        if ($registeredById) {
          $registeredByContactId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant", $registeredById, 'contact_id', 'id');
          $rows[$rowNum]['civicrm_participant_registered_by_name'] = CRM_Contact_BAO_Contact::displayName($registeredByContactId);
          $rows[$rowNum]['civicrm_participant_registered_by_name_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $registeredByContactId, $this->_absoluteUrl);
          $rows[$rowNum]['civicrm_participant_registered_by_name_hover'] = E::ts('View Contact Summary for Contact that registered the participant.');
        }
      }

      // Handle regfor-sponsor-level
      if (array_key_exists('event_sponsor_registering_for_sponsorship_level_regfor', $row)) {
        $rows[$rowNum]['event_sponsor_registering_for_sponsorship_level_regfor'] = $this->_alterDisplaySponsorshipLevels($rows[$rowNum]['event_sponsor_registering_for_sponsorship_level_regfor']);
        $entryFound = TRUE;
      }

      // Handle employer-sponsor-level
      if (array_key_exists('event_sponsor_employee_sponsorship_level_empl', $row)) {
        $rows[$rowNum]['event_sponsor_employee_sponsorship_level_empl'] = $this->_alterDisplaySponsorshipLevels($rows[$rowNum]['event_sponsor_employee_sponsorship_level_empl']);
        $entryFound = TRUE;
      }

      // Handle value seperator in Fee Level
      if (array_key_exists('civicrm_participant_participant_fee_level', $row)) {
        $feeLevel = $row['civicrm_participant_participant_fee_level'];
        if ($feeLevel) {
          CRM_Event_BAO_Participant::fixEventLevel($feeLevel);
          $rows[$rowNum]['civicrm_participant_participant_fee_level'] = $feeLevel;
        }
        $entryFound = TRUE;
      }

      // Handle comma-separated dates
      if (array_key_exists('civicrm_financial_trxn_trxn_date', $row)) {
        $values = [];
        $trxnDates = explode(CRM_Core_DAO::VALUE_SEPARATOR, $row['civicrm_financial_trxn_trxn_date']);
        foreach ($trxnDates as $trxnDate) {
          $values[] = CRM_Utils_Date::customFormat($trxnDate);
        }
        $rows[$rowNum]['civicrm_financial_trxn_trxn_date'] = implode($multiSeparator, $values);
        $entryFound = TRUE;
      }

      // Handle comma-separated payment methods
      if (array_key_exists('civicrm_financial_trxn_trxn_payment_instrument_id', $row)) {
        $values = [];
        $paymentInstrumentIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $row['civicrm_financial_trxn_trxn_payment_instrument_id']);
        foreach ($paymentInstrumentIds as $paymentInstrumentId) {
          $values[] = CRM_Utils_Array::value($paymentInstrumentId, $paymentInstruments);
        }
        $rows[$rowNum]['civicrm_financial_trxn_trxn_payment_instrument_id'] = implode($multiSeparator, $values);
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_contribution_ctrb_payment_instrument_id', $row)) {
        $values = [];
        $paymentInstrumentIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $row['civicrm_contribution_ctrb_payment_instrument_id']);
        foreach ($paymentInstrumentIds as $paymentInstrumentId) {
          $values[] = CRM_Utils_Array::value($paymentInstrumentId, $paymentInstruments);
        }
        $rows[$rowNum]['civicrm_contribution_ctrb_payment_instrument_id'] = implode($multiSeparator, $values);
        $entryFound = TRUE;
      }

      // Handle comma-separated check numbers
      if (array_key_exists('civicrm_financial_trxn_check_number', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_check_number'] = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, $multiSeparator, $rows[$rowNum]['civicrm_financial_trxn_check_number']);
        $entryFound = TRUE;
      }

      // Handle comma-separated "last 4"
      if (array_key_exists('civicrm_financial_trxn_pan_truncation', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_pan_truncation'] = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, $multiSeparator, $rows[$rowNum]['civicrm_financial_trxn_pan_truncation']);
        $entryFound = TRUE;
      }

      // Handle comma-separated "payment amount"
      if (array_key_exists('civicrm_financial_trxn_total_amount', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_total_amount'] = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, $multiSeparator, $rows[$rowNum]['civicrm_financial_trxn_total_amount']);
        $entryFound = TRUE;
      }

      // Handle comma-separated "fee amount"
      if (array_key_exists('civicrm_financial_trxn_fee_amount', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_fee_amount'] = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, $multiSeparator, $rows[$rowNum]['civicrm_financial_trxn_fee_amount']);
        $entryFound = TRUE;
      }

      // Handle comma-separated "net amount"
      if (array_key_exists('civicrm_financial_trxn_net_amount', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_net_amount'] = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, $multiSeparator, $rows[$rowNum]['civicrm_financial_trxn_net_amount']);
        $entryFound = TRUE;
      }

      // Convert display name to link
      $displayName = CRM_Utils_Array::value('civicrm_contact_sort_name_linked', $row);
      $cid = CRM_Utils_Array::value('civicrm_contact_id', $row);
      $id = CRM_Utils_Array::value('civicrm_participant_participant_record', $row);

      if ($displayName && $cid && $id) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid=$cid"
        );

        $viewUrl = CRM_Utils_System::url("civicrm/contact/view/participant",
          "reset=1&id=$id&cid=$cid&action=view&context=participant"
        );

        $contactTitle = E::ts('View Contact Record');
        $participantTitle = E::ts('View Participant Record');

        $rows[$rowNum]['civicrm_contact_sort_name_linked'] = "<a title='$contactTitle' href=$url>$displayName</a>";
        // Add a "View" link to the participant record if this isn't a CSV/PDF/printed document.
        if ($this->_outputMode !== 'csv' && $this->_outputMode !== 'pdf' && $this->_outputMode !== 'print') {
          $rows[$rowNum]['civicrm_contact_sort_name_linked'] .=
            "<span style='float: right;'><a title='$participantTitle' href=$viewUrl>" .
            E::ts('View') . "</a></span>";
        }
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_participant_campaign_id', $rowNum, $this->campaigns);

      // handle contribution status
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_contribution_status_id', $rowNum, $contributionStatus);

      // handle payment instrument
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_payment_instrument_id', $rowNum, $paymentInstruments);

      // handle financial type
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_financial_type_id', $rowNum, $financialTypes);

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'event/participantListing', 'View Event Income Details') ? TRUE : $entryFound;

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'event/ParticipantListing', 'List all participant(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Get a list of participant roleIds, keyed to their system name.
   * @return Array
   */
  public function _getParticipantRoleOptionIdPerName() {
    $optionValues = \Civi\Api4\OptionValue::get(TRUE)
      ->addWhere('option_group_id:name', '=', 'participant_role')
      ->setLimit(0)
      ->execute()
      ->getArrayCopy();
    $optionValues = CRM_Utils_Array::rekey($optionValues, 'value');
    $ret = array_flip(CRM_Utils_Array::collect('name', $optionValues));
    return $ret;
  }

  /**
   * Get a list of option labels for the "sponsorship level" custom field,
   * keyed to their values (also special value -1='unknown').
   *
   * @staticvar type $ret
   * @return type
   */
  public function _getSponsorshipLevelOptions() {
    // Static cache, because this can be called mulitple times per result row.
    static $ret;
    if (!isset($ret)) {
      $ret = CRM_Contribute_DAO_Contribution::buildOptions('custom_193');
      $ret['-1'] = E::ts('(Unspecified level)');
    }
    return $ret;
  }

  /**
   * Given a value for one of the columns reflecting the "Event Sponsorsip Level"
   * field, which is a group_concat(), split by comma, replace individuals values
   * with the labels, and re-join by comma.
   */
  private function _alterDisplaySponsorshipLevels($rowValue) {
    $sponsorshipLevelOptions = $this->_getSponsorshipLevelOptions();
    $sponsorLevels = array_flip(explode(',', $rowValue));
    $sponsorLevelLabels = array_intersect_key($sponsorshipLevelOptions, $sponsorLevels);
    sort($sponsorLevelLabels);
    return implode(', ', $sponsorLevelLabels);
  }

}
