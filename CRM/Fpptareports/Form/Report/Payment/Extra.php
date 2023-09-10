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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Fpptareports_Form_Report_Payment_Extra extends CRM_Report_Form {

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
  ];

  /**
   * This report has been optimised for group filtering.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = array_merge(
      $this->getColumns('Contact', [
        'order_bys_defaults' => ['sort_name' => 'ASC '],
        'fields_defaults' => ['sort_name'],
        'fields_excluded' => ['id'],
        'fields_required' => ['id'],
        'filters_defaults' => ['is_deleted' => 0],
        'no_field_disambiguation' => TRUE,
      ]),
      [
        'civicrm_email' => [
          'dao' => 'CRM_Core_DAO_Email',
          'fields' => [
            'email' => [
              'title' => ts('Donor Email'),
              'default' => TRUE,
            ],
          ],
          'grouping' => 'contact-fields',
        ],
        'civicrm_phone' => [
          'dao' => 'CRM_Core_DAO_Phone',
          'fields' => [
            'phone' => [
              'title' => ts('Donor Phone'),
              'default' => TRUE,
            ],
          ],
          'grouping' => 'contact-fields',
        ],
        'civicrm_contribution' => [
          'dao' => 'CRM_Contribute_DAO_Contribution',
          'fields' => [
            'contribution_id' => [
              'name' => 'id',
              'no_display' => TRUE,
              'required' => TRUE,
            ],
            'list_contri_id' => [
              'name' => 'id',
              'title' => ts('Contrib: ID'),
              'default' => TRUE,
            ],
            'financial_type_id' => [
              'title' => ts('Contrib: Financial Type'),
              'default' => TRUE,
            ],
            'contribution_status_id' => [
              'title' => ts('Contrib: Status'),
            ],
            'contribution_page_id' => [
              'title' => ts('Contrib: Contribution Page'),
            ],
            'source' => [
              'title' => ts('Contrib: Source'),
            ],
            'payment_instrument_id' => [
              'title' => ts('Contrib: Payment Type'),
            ],
            'check_number' => [
              'title' => ts('Contrib: Check Number'),
            ],
            'invoice_number' => [
              'title' => ts('Contrib: Invoice Number'),
            ],
            'currency' => [
              'required' => TRUE,
              'no_display' => TRUE,
            ],
            'trxn_id' => NULL,
            'receive_date' => [
              'title' => ts('Contrib: Date Received'),
              // Change format of this field to text, because:
              // a) it's a date or date/time field
              // b) we're cutomizing the date/time format in alterDisplay()
              // c) if it retains a Date or DateTime type, the template will force its own format, undoing our formatting.
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'receipt_date' => [
              // Change format of this field to text, because:
              // a) it's a date or date/time field
              // b) we're cutomizing the date/time format in alterDisplay()
              // c) if it retains a Date or DateTime type, the template will force its own format, undoing our formatting.
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'thankyou_date' => [
              // Change format of this field to text, because:
              // a) it's a date or date/time field
              // b) we're cutomizing the date/time format in alterDisplay()
              // c) if it retains a Date or DateTime type, the template will force its own format, undoing our formatting.
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'total_amount' => [
              'title' => ts('Contrib: Amount'),
            ],
            'non_deductible_amount' => [
              'title' => ts('Contrib: Non-deductible Amount'),
            ],
            'fee_amount' => NULL,
            'net_amount' => NULL,
            'cancel_date' => [
              'title' => ts('Contrib: Cancelled / Refunded Date'),
              'name' => 'contribution_cancel_date',
              // Change format of this field to text, because:
              // a) it's a date or date/time field
              // b) we're cutomizing the date/time format in alterDisplay()
              // c) if it retains a Date or DateTime type, the template will force its own format, undoing our formatting.
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'cancel_reason' => [
              'title' => ts('Contrib: Cancellation / Refund Reason'),
            ],
          ],
          'filters' => [
            'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
            'receipt_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
            'thankyou_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
            'contribution_source' => [
              'title' => ts('Source'),
              'name' => 'source',
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'currency' => [
              'title' => ts('Currency'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
              'default' => NULL,
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'non_deductible_amount' => [
              'title' => ts('Non-deductible Amount'),
            ],
            'financial_type_id' => [
              'title' => ts('Financial Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
              'type' => CRM_Utils_Type::T_INT,
            ],
            'contribution_page_id' => [
              'title' => ts('Contrib: Contribution Page'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_PseudoConstant::contributionPage(),
              'type' => CRM_Utils_Type::T_INT,
            ],
            'payment_instrument_id' => [
              'title' => ts('Payment Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
              'type' => CRM_Utils_Type::T_INT,
            ],
            'contribution_status_id' => [
              'title' => ts('Contrib: Status'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
              'default' => [1],
              'type' => CRM_Utils_Type::T_INT,
            ],
            'total_amount' => ['title' => ts('Contrib: Amount')],
            'cancel_date' => [
              'title' => ts('Cancelled / Refunded Date'),
              'operatorType' => CRM_Report_Form::OP_DATE,
              'name' => 'contribution_cancel_date',
            ],
            'cancel_reason' => [
              'title' => ts('Cancellation / Refund Reason'),
            ],
          ],
          'order_bys' => [
            'financial_type_id' => ['title' => ts('Financial Type')],
            'contribution_status_id' => ['title' => ts('Contrib: Status')],
            'payment_instrument_id' => ['title' => ts('Payment Method')],
            'receive_date' => ['title' => ts('Date Received')],
            'receipt_date' => ['title' => ts('Receipt Date')],
            'thankyou_date' => ['title' => ts('Thank-you Date')],
          ],
          'grouping' => 'contri-fields',
        ],
        'civicrm_entity_financial_trxn' => [
          'dao' => 'CRM_Financial_DAO_EntityFinancialTrxn',
          'fields' => [
          ],
        ],
        'civicrm_financial_trxn' => [
          'dao' => 'CRM_Financial_DAO_FinancialTrxn',
          'fields' => [
            'trxn_status_id' => [
              'name' => 'status_id',
              'title' => ts('Payment: Status'),
            ],
            'trxn_payment_instrument_id' => [
              'name' => 'payment_instrument_id',
              'title' => ts('Payment: Payment Type'),
            ],
            'trxn_check_number' => [
              'name' => 'check_number',
              'title' => ts('Payment: Check Number'),
            ],
            'trxn_total_amount' => [
              'name' => 'total_amount',
              'title' => ts('Payment: Amount'),
              'default' => TRUE,
            ],
            'card_type_id' => [
              'title' => ts('Credit Card Type'),
            ],
            'pan_truncation' => [
              'title' => ts('Credit Card Last-4'),
            ],
            'trxn_date' => [
              'title' => ts('Payment: Received Date'),
              'default' => TRUE,
              // Change format of this field to text, because:
              // a) it's a date or date/time field
              // b) we're cutomizing the date/time format in alterDisplay()
              // c) if it retains a Date or DateTime type, the template will force its own format, undoing our formatting.
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'financial_trxn_id' => [
              'name' => 'id',
              'title' => ts('Payment: ID'),
            ],
          ],
          'filters' => [
            'card_type_id' => [
              'title' => ts('Credit Card Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
              'default' => NULL,
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'trxn_date' => [
              'title' => ts('Payment Received Date'),
              'operatorType' => CRM_Report_Form::OP_DATETIME,
              'default' => NULL,
              'type' => CRM_Utils_Type::T_DATE,
            ],
            'is_payment' => [
              'required' => TRUE,
              'no_display' => TRUE,
              'operatorType' => CRM_Report_Form::OP_INT,
              'default_op' => 'eq',
              'default' => 1,
              'type' => CRM_Utils_Type::T_INT,
            ],
          ],
          'group_bys' => [
            'trxn_id' => [
              'name' => 'id',
              'required' => TRUE,
              'default' => TRUE,
              'title' => ts('Payment'),
            ],
          ],
        ],
        'civicrm_note' => [
          'dao' => 'CRM_Core_DAO_Note',
          'fields' => [
            'contribution_note' => [
              'name' => 'note',
              'title' => ts('Contrib: Note'),
            ],
          ],
          'filters' => [
            'note' => [
              'name' => 'note',
              'title' => ts('Contrib: Note'),
              'operator' => 'like',
              'type' => CRM_Utils_Type::T_STRING,
            ],
          ],
        ],
      ],
      $this->getColumns('Address'),
      [
        'contributor_org' => [
          'alias' => 'contributor_org',
          'fields' => [
            'contributor_org_display_name' => [
              'title' => ts('Contributor: related organizations'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT contributor_org_civireport.display_name ORDER BY contributor_org_civireport.display_name SEPARATOR "<BR>")',
            ],
          ],
          'grouping' => 'extra-fields',
        ],
        'soft_credit_contact' => [
          'alias' => 'soft_credit_contact',
          'fields' => [
            'soft_display_name' => [
              'title' => ts('Soft-credited contacts'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT soft_credit_contact_civireport.display_name ORDER BY soft_credit_contact_civireport.display_name SEPARATOR "<BR>")',
            ],
          ],
          'grouping' => 'extra-fields',
        ],
        'attendee_contact' => [
          'alias' => 'attendee_contact',
          'fields' => [
            'attendee_display_name' => [
              'title' => ts('Other attendees'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT attendee_contact_civireport.display_name ORDER BY attendee_contact_civireport.display_name SEPARATOR "<BR>")',
            ],
          ],
          'grouping' => 'extra-fields',
        ],
        'attendee_contact_org' => [
          'alias' => 'attendee_contact_org',
          'fields' => [
            'attendee_org_display_name' => [
              'title' => ts('Other attendees: related organizations'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT attendee_contact_org_civireport.display_name ORDER BY attendee_contact_org_civireport.display_name SEPARATOR "<BR>")',
            ],
          ],
          'grouping' => 'extra-fields',
        ],
        'softcredit_contact_org' => [
          'alias' => 'softcredit_contact_org',
          'fields' => [
            'softcredit_org_display_name' => [
              'title' => ts('Soft credits: related organizations'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT softcredit_contact_org_civireport.display_name ORDER BY softcredit_contact_org_civireport.display_name SEPARATOR "<BR>")',
            ],
          ],
          'grouping' => 'extra-fields',
        ],
        'civicrm_line_item' => [
          'alias' => 'line_item',
          'fields' => [
            'line_items' => [
              'title' => ts('Line Items'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT price_field.label, " &ndash; ", line_item_civireport.label ORDER BY line_item_civireport.label SEPARATOR "<BR>")',
            ],
            'line_item_financial_types' => [
              'title' => ts('Line Items: Financial Types'),
              'dbAlias' => 'GROUP_CONCAT(DISTINCT line_item_civireport.financial_type_id ORDER BY line_item_civireport.financial_type_id SEPARATOR ",")',
            ],
          ],
          'grouping' => 'extra-fields',
        ],
        // Separate line_item table for filters -- this table will be INNER JOINed to limit the contributions
        // that appear in rows, while the 'civicrm_line_item' table will be LEFT JOINed to include all line
        // items on each of those contribution rows.
        'li' => [
          'dao' => 'CRM_Price_DAO_LineItem',
          'alias' => 'li',
          'filters' => [
            'line_item_financial_type_id' => [
              'name' => 'financial_type_id',
              'title' => ts('Line Items: Financial Types'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
              'type' => CRM_Utils_Type::T_INT,
            ],
          ],
          'grouping' => 'line-item-fields',
        ],
        'civicrm_value_participant_d_21' => [
          'alias' => 'participant_details',
          'fields' => [
            'registered_for_organization_143' => [
              'title' => ts('Registering For Organization'),
            ],
          ],
          'grouping' => 'extra-fields',
        ],
      ]

    );

    // Change format of this field to text, because:
    // a) it's a date or date/time field
    // b) we're cutomizing the date/time format in alterDisplay()
    // c) if it retains a Date or DateTime type, the template will force its own format, undoing our formatting.
    $this->_columns['civicrm_contact']['fields']['birth_date']['type'] = CRM_Utils_Type::T_STRING;

    // The tests test for this variation of the sort_name field. Don't argue with the tests :-).
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    // If we have campaigns enabled, add those elements to both the fields, filters and sorting
    $this->addCampaignFields('civicrm_contribution', FALSE, TRUE);

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();

  }

  /**
   * Validate incompatible report settings.
   *
   * @return bool
   *   true if no error found
   */
  public function validate() {
    return parent::validate();
  }

  /**
   * Set the FROM clause for the report.
   */
  public function from() {
    $this->setFromBase('civicrm_contact');
    $this->_from .= "
      INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
        AND {$this->_aliases['civicrm_contribution']}.is_test = 0
      INNER JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}
        ON {$this->_aliases['civicrm_entity_financial_trxn']}.entity_table = 'civicrm_contribution'
        AND {$this->_aliases['civicrm_entity_financial_trxn']}.entity_id = {$this->_aliases['civicrm_contribution']}.id
      INNER JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
        ON {$this->_aliases['civicrm_entity_financial_trxn']}.financial_trxn_id = {$this->_aliases['civicrm_financial_trxn']}.id
    ";
    if ($this->isTableSelected('civicrm_note')) {
      $this->_from .= "
        LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
          ON {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_contribution'
          AND {$this->_aliases['civicrm_note']}.entity_id = {$this->_aliases['civicrm_contribution']}.id
      ";
    }
    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
        LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
          ON {$this->_aliases['civicrm_email']}.contact_id = {$this->_aliases['civicrm_contact']}.id
          AND {$this->_aliases['civicrm_email']}.is_primary
      ";
    }
    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
        LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
          ON {$this->_aliases['civicrm_phone']}.contact_id = {$this->_aliases['civicrm_contact']}.id
          AND {$this->_aliases['civicrm_phone']}.is_primary
      ";
    }
    if ($this->isTableSelected('civicrm_address')) {
      $this->_from .= "
        LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
          ON {$this->_aliases['civicrm_address']}.contact_id = {$this->_aliases['civicrm_contact']}.id
          AND {$this->_aliases['civicrm_address']}.is_primary
      ";
    }
    if ($this->isTableSelected('contributor_org')) {
      $this->_from .= "
        LEFT JOIN civicrm_relationship r_contributor_org
          ON {$this->_aliases['civicrm_contact']}.id IN (r_contributor_org.contact_id_a, r_contributor_org.contact_id_b)
          AND r_contributor_org.is_active
        LEFT JOIN civicrm_contact {$this->_aliases['contributor_org']}
          ON {$this->_aliases['contributor_org']}.id =
            if({$this->_aliases['civicrm_contact']}.id = r_contributor_org.contact_id_a, r_contributor_org.contact_id_b, r_contributor_org.contact_id_a)
          AND {$this->_aliases['contributor_org']}.contact_type = 'organization'
          AND NOT {$this->_aliases['contributor_org']}.is_deleted
      ";
    }
    if ($this->isTableSelected('soft_credit_contact') || $this->isTableSelected('softcredit_contact_org')) {
      $this->_from .= "
        LEFT JOIN civicrm_contribution_soft soft
          ON {$this->_aliases['civicrm_contribution']}.id = soft.contribution_id
        LEFT JOIN civicrm_contact {$this->_aliases['soft_credit_contact']}
          ON {$this->_aliases['soft_credit_contact']}.id = soft.contact_id
          AND NOT {$this->_aliases['soft_credit_contact']}.is_deleted
      ";
    }
    if ($this->isTableSelected('attendee_contact') || $this->isTableSelected('civicrm_value_participant_d_21') || $this->isTableSelected('attendee_contact_org')) {
      $this->_from .= "
        LEFT JOIN civicrm_participant_payment partpay
          ON {$this->_aliases['civicrm_contribution']}.id = partpay.contribution_id
      ";
    }
    if ($this->isTableSelected('attendee_contact') || $this->isTableSelected('attendee_contact_org')) {
      $this->_from .= "
        LEFT JOIN civicrm_participant otherpart
          ON otherpart.registered_by_id = partpay.participant_id
        LEFT JOIN civicrm_contact {$this->_aliases['attendee_contact']}
          ON {$this->_aliases['attendee_contact']}.id = otherpart.contact_id
          AND NOT {$this->_aliases['attendee_contact']}.is_deleted
      ";
    }
    if ($this->isTableSelected('attendee_contact_org')) {
      $this->_from .= "
        LEFT JOIN civicrm_relationship r_attendee_contact_org
          ON otherpart.contact_id IN (r_attendee_contact_org.contact_id_a, r_attendee_contact_org.contact_id_b)
          AND r_attendee_contact_org.is_active
        LEFT JOIN civicrm_contact {$this->_aliases['attendee_contact_org']}
          ON {$this->_aliases['attendee_contact_org']}.id =
            if(otherpart.contact_id = r_attendee_contact_org.contact_id_a, r_attendee_contact_org.contact_id_b, r_attendee_contact_org.contact_id_a)
          AND {$this->_aliases['attendee_contact_org']}.contact_type = 'organization'
          AND NOT {$this->_aliases['attendee_contact_org']}.is_deleted
      ";
    }
    if ($this->isTableSelected('softcredit_contact_org')) {
      $this->_from .= "
        LEFT JOIN civicrm_relationship r_softcredit_contact_org
          ON soft.contact_id IN (r_softcredit_contact_org.contact_id_a, r_softcredit_contact_org.contact_id_b)
          AND r_softcredit_contact_org.is_active
        LEFT JOIN civicrm_contact {$this->_aliases['softcredit_contact_org']}
          ON {$this->_aliases['softcredit_contact_org']}.id =
            if(soft.contact_id = r_softcredit_contact_org.contact_id_a, r_softcredit_contact_org.contact_id_b, r_softcredit_contact_org.contact_id_a)
          AND {$this->_aliases['softcredit_contact_org']}.contact_type = 'organization'
          AND NOT {$this->_aliases['softcredit_contact_org']}.is_deleted
      ";
    }
    if ($this->isTableSelected('civicrm_line_item')) {
      $this->_from .= "
        LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
          ON {$this->_aliases['civicrm_line_item']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id
        LEFT JOIN civicrm_price_field price_field
          ON price_field.id = {$this->_aliases['civicrm_line_item']}.price_field_id
      ";
    }
    if ($this->isTableSelected('li')) {
      // Separate line_item table for filters -- this table will be INNER JOINed to limit the contributions
      // that appear in rows, while the 'civicrm_line_item' table will be LEFT JOINed to include all line
      // items on each of those contribution rows.
      $this->_from .= "
        INNER JOIN civicrm_line_item {$this->_aliases['li']}
          ON {$this->_aliases['li']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id
      ";
    }
    if ($this->isTableSelected('civicrm_value_participant_d_21')) {
      $this->_from .= "
        LEFT JOIN civicrm_participant primarypart
          ON primarypart.id = partpay.participant_id
        LEFT JOIN civicrm_value_participant_d_21 {$this->_aliases['civicrm_value_participant_d_21']}
          ON {$this->_aliases['civicrm_value_participant_d_21']}.entity_id = primarypart.id
      ";
    }
  }

  /**
   * TODO: This method was copied from CRM_Fpptareports_Form_Report_Contribute_Extra but
   * probably needs review and modification to make sense in this report.
   * Therefore we're commenting it out.
   *
   * @param $rows
   *
   * @return array
   */
//  public function statistics(&$rows) {
//    $statistics = parent::statistics($rows);
//
//    $totalAmount = $average = $fees = $net = [];
//    $count = 0;
//    $select = "
//        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
//               SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount,
//               ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg,
//               {$this->_aliases['civicrm_contribution']}.currency as currency,
//               SUM( {$this->_aliases['civicrm_contribution']}.fee_amount ) as fees,
//               SUM( {$this->_aliases['civicrm_contribution']}.net_amount ) as net
//        ";
//
//    $group = "\nGROUP BY {$this->_aliases['civicrm_contribution']}.currency";
//    $sql = "{$select} {$this->_from} {$this->_where} {$group}";
//    $dao = CRM_Core_DAO::executeQuery($sql);
//    $this->addToDeveloperTab($sql);
//
//    while ($dao->fetch()) {
//      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency) . " (" . $dao->count . ")";
//      $fees[] = CRM_Utils_Money::format($dao->fees, $dao->currency);
//      $net[] = CRM_Utils_Money::format($dao->net, $dao->currency);
//      $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
//      $count += $dao->count;
//    }
//    $statistics['counts']['amount'] = [
//      'title' => ts('Total Amount (Contributions)'),
//      'value' => implode(',  ', $totalAmount),
//      'type' => CRM_Utils_Type::T_STRING,
//    ];
//    $statistics['counts']['count'] = [
//      'title' => ts('Total Contributions'),
//      'value' => $count,
//    ];
//    $statistics['counts']['fees'] = [
//      'title' => ts('Fees'),
//      'value' => implode(',  ', $fees),
//      'type' => CRM_Utils_Type::T_STRING,
//    ];
//    $statistics['counts']['net'] = [
//      'title' => ts('Net'),
//      'value' => implode(',  ', $net),
//      'type' => CRM_Utils_Type::T_STRING,
//    ];
//    $statistics['counts']['avg'] = [
//      'title' => ts('Average'),
//      'value' => implode(',  ', $average),
//      'type' => CRM_Utils_Type::T_STRING,
//    ];
//
//    return $statistics;
//  }

  /**
   * Shared function for preliminary processing.
   *
   * This is called by the api / unit tests and the form layer and is
   * the right place to do 'initial analysis of input'.
   */
  public function beginPostProcessCommon() {
    parent::beginPostProcessCommon(); return;
  }

  /**
   * Store group bys into array - so we can check elsewhere what is grouped.
   *
   */
  protected function storeGroupByArray() {
    parent::storeGroupByArray();
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
    $display_flag = $prev_cid = $cid = 0;
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    // We pass in TRUE as 2nd param so that even disabled contribution page titles are returned and replaced in the report
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
    $batches = CRM_Batch_BAO_Batch::getBatches();

    $formatStringDateTime = Civi::Settings()->get('dateformatshortdate') . ' ' . Civi::Settings()->get('dateformatTime');
    $formatStringDate = Civi::Settings()->get('dateformatshortdate');

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                // Hide repeats in no-repeat columns, but not if the field's a section header
                if (in_array($colName, $this->_noRepeats) &&
                  !array_key_exists($colName, $this->_sections)
                ) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contribution/detail', ts('View Contribution Details')) ? TRUE : $entryFound;
      // convert donor sort name to link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_line_item_line_item_financial_types', $row)) {
        $ftIds = array_flip(explode(',', $value));
        $ftLabels = array_intersect_key($contributionTypes, $ftIds);
        $rows[$rowNum]['civicrm_line_item_line_item_financial_types'] = implode('<br>', $ftLabels);
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_financial_trxn_trxn_status_id', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_trxn_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_page_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_page_id'] = $contributionPages[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_financial_trxn_trxn_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_trxn_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_batch_batch_id'])) {
        $rows[$rowNum]['civicrm_batch_batch_id'] = $batches[$row['civicrm_batch_batch_id']] ?? NULL;
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // Contribution amount links to viewing contribution
      if (CRM_Utils_Array::value('civicrm_contribution_list_contri_id', $row)) {
        if (CRM_Core_Permission::check('access CiviContribute')) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/contribution",
            [
              'reset' => 1,
              'id' => $row['civicrm_contribution_contribution_id'],
              'cid' => $row['civicrm_contact_id'],
              'action' => 'view',
              'context' => 'contribution',
              'selectedChild' => 'contribute',
            ],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_contribution_list_contri_id_link'] = $url;
          $rows[$rowNum]['civicrm_contribution_list_contri_id_hover'] = ts("View Details of this Contrib.");
        }
        $entryFound = TRUE;
      }

      // Contribution ID links to viewing contribution
      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }

      // Contribution amount links to viewing contribution
      if ($value = CRM_Utils_Array::value('civicrm_pledge_payment_pledge_id', $row)) {
        if (CRM_Core_Permission::check('access CiviContribute')) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/pledge",
            [
              'reset' => 1,
              'id' => $row['civicrm_pledge_payment_pledge_id'],
              'cid' => $row['civicrm_contact_id'],
              'action' => 'view',
              'context' => 'pledge',
              'selectedChild' => 'pledge',
            ],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_pledge_payment_pledge_id_link'] = $url;
          $rows[$rowNum]['civicrm_pledge_payment_pledge_id_hover'] = ts("View Details of this Pledge.");
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribs(s) for this ') ? TRUE : $entryFound;

      if (array_key_exists('civicrm_contact_birth_date', $row)) {
        $entryFound = TRUE;
        $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($rows[$rowNum]['civicrm_contact_birth_date'], $formatStringDate);
      }
      $dateTimeColumnNames = [
        'civicrm_contribution_cancel_date',
        'civicrm_contribution_receipt_date',
        'civicrm_contribution_receive_date',
        'civicrm_contribution_thankyou_date',
        'civicrm_financial_trxn_trxn_date',
      ];
      foreach ($dateTimeColumnNames as $dateTimeColumnName) {
        if (array_key_exists($dateTimeColumnName, $row)) {
          $entryFound = TRUE;
          $rows[$rowNum][$dateTimeColumnName] = CRM_Utils_Date::customFormat($rows[$rowNum][$dateTimeColumnName], $formatStringDateTime);
        }
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }

}
