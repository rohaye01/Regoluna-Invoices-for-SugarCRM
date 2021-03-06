<?php
$module_name = 'reg_invoices';
$_object_name = 'reg_invoices';
$viewdefs [$module_name] =
array (
  'EditView' =>
  array (
    'templateMeta' =>
    array (
      'maxColumns' => '2',
      'widths' =>
      array (
        0 =>
        array (
          'label' => '10',
          'field' => '30',
        ),
        1 =>
        array (
          'label' => '10',
          'field' => '30',
        ),
      ),
      'javascript' => '{$PROBABILITY_SCRIPT}',
      'includes' => array (
        array ( 'file' => 'include/javascript/tiny_mce/tiny_mce.js' ),
      ),
    ),
    'panels' =>
    array (
      'lbl_sale_information' =>
      array (
        0 =>
        array (
          0 =>
          array (
            'name' => 'name',
            'label' => 'LBL_NAME',
          ),
          1 =>
          array (
            'name' => 'date_closed',
            'label' => 'LBL_FECHA_EMISION',
          ),
        ),
        1 =>
        array (
          0 =>
          array (
            'name' => 'number',
            'type' => 'NumFactura',
            'label' => 'LBL_NUMERO',
          ),
          1 => null,
/// Temporalmente deshabilitado hasta ver que hacemos con el descuento general.
//          array (
//            'name' => 'discount',
//            'label' => 'LBL_DESCUENTO',
//          ),
        ),
        2 =>
        array (
          0 =>
          array (
            'name' => 'account_name',
          ),
          1 =>
          array (
            'name' => 'tax_type',
            'label' => 'LBL_TIPO_IMPUESTO',
          ),
        ),
        3 =>
        array (
          0 =>
          array (
            'name' => 'reg_invoices_type',
            'label' => 'LBL_TYPE',
          ),
          1 =>
          array (
            'name' => 'output_tax',
            'label' => 'LBL_REPERCUTIDO',
          ),
        ),
        4 =>
        array (
          0 =>
          array (
            'name' => 'state',
            'label' => 'LBL_ESTADO',
          ),
          1 =>
          array (
            'name' => 'retention',
            'label' => 'LBL_RETENCION',
          ),
        ),
        5 =>
        array (
          0 =>
          array (
            'name' => 'description',
            'label' => 'LBL_DESCRIPTION',
            'type' => 'Htmledit',
            'displayParams' =>
            array (
              'cols' => 95,
              'rows' => 13,
            ),
          ),
        ),
        6 =>
        array (
          0 =>
          array (
            'name' => 'conditions',
            'label' => 'LBL_CONDICIONES',
            'type' => 'Htmledit',
            'displayParams' =>
            array (
              'cols' => 95,
              'rows' => 10,
            ),
          ),
        ),
      ),
    ),
  ),
);
?>
