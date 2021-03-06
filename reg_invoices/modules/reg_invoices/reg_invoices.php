<?PHP
/*********************************************************************************
 *
 * Copyright (C) 2008 Rodrigo Saiz Camarero (http://www.regoluna.com)
 *
 * This file is part of "Regoluna® Spanish Invoices" module.
 *
 * "Regoluna® Spanish Invoices" is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3 of the License.
 *
 ********************************************************************************/
require_once('modules/reg_invoices/reg_invoices_sugar.php');
class reg_invoices extends reg_invoices_sugar {

  function reg_invoices() {
    parent::reg_invoices_sugar();
  }

  function unformat_all_fields(){
    // Corregimos el comportamiento erroneo de SugarCRM en algunos casos.
    if ($this->number_formatting_done)  parent::unformat_all_fields();
  }

  function save($check_notify = FALSE) {
    
    if( empty( $_REQUEST['duplicateSave'] ) || $_REQUEST['duplicateSave'] === 'false' ){
      $this->calculateTotal();
    }

    // Guardamos el año, en función de la fecha.
    global $timedate;
    
    if( !is_numeric($this->year) ){
      $cierre = $timedate->to_db($this->date_closed);
      $fecha_partes = explode('-', $timedate->to_db($this->date_closed));
      
      if (is_numeric($fecha_partes[0])) {
        $this->year = $fecha_partes[0];
      } else {
        $this->year = '0000';
      }
    }
    

    // Si se marca el checkbox de auto-generar y la factura está emitida o pagada
    // calculamos el número.
    if ( !empty($_POST['number_autogen']) && $_POST['number_autogen'] == 1 &&
         ( $this->reg_invoices_type !='invoice' || ($this->state == 'invoice_emitted' || $this->state == 'invoice_paid') )
       ) {
      $this->calculateNumber();
    }

    // echo "Antes de guardar - Si no esta formateado, lo hacemos<br/>";
    if(!$this->number_formatting_done) $this->format_all_fields();
    
    if( empty($this->number) ) $this->number = '';

    // No usamos el campo 'amount_usdollar' sin embargo, su definición por defecto puede dar problemas
    $this->field_defs['amount_usdollar']['disable_num_format']=0; // corrige un comportamiento inadecuado
        
    parent::save($check_notify);
    
    // If you are duplicating an invoice
    if( !empty( $_REQUEST['duplicateSave'] ) && $_REQUEST['duplicateSave'] !== 'false' ){
      $this->duplicateItemsFromInvoiceId( $_REQUEST['duplicateId'] );
      
      $_REQUEST['duplicateSave'] = 'false'; // Prevent loop
      $this->save();
    }
  
  }



  /**
   *
   * Asigna el siguiente número disponible para facturas
   * Cada.type.tiene su propia numeración.
   *
   */
  function calculateNumber() {
    global $sugar_config;

    // Para el caso en el que la facturación se reinicie anualmente
    $anual = ($sugar_config['fact_restart_number']) ? " AND year = $this->year" : '';
    
    $sql = " select MAX(number) number".
      " from reg_invoices ".
      " where deleted=0 AND reg_invoices_type = '$this->reg_invoices_type' AND id <> '$this->id' $anual";
      
    $result = $this->db->query($sql);
    $row = $this->db->fetchByAssoc($result);
    if ($row['number']) {
      $this->number = $row['number'] + 1;
    } else {
      $this->number = 1;
    }
  }



  /**
   *
   * Hace todos los calculos necesarios para obtener la base imponible
   * y el total a pagar después de decuentos, IVA y retenciónes.
   *
   */
  function calculateTotal() {

    // Por si el usuario introduce campos negativos
    if ($this->output_tax < 0) $this->output_tax = -$this->output_tax;
    if ($this->retention < 0) $this->retention = -$this->retention;

    $this->total_base = 0;
    $this->total_tax = 0;
    $this->total_retention = 0;
    $this->total_discount = 0;
    $this->unique_tax = true;
    $this->unique_retention = true;

    // Obtenemos la lista de Items con sus precios para procesarlos.
    $sql = " select total_base, tax_type, tax, total_tax, total_discount, retention, total_retention ".
      " from reg_items ".
      " where deleted=0 AND invoice_id = '$this->id' ";
    $result = $this->db->query($sql);

    // Procesamos las filas de Items para calcular los totales.
    while ($row = $this->db->fetchByAssoc($result)) {
      $this->total_base += $row['total_base'];
      $this->total_discount -= $row['total_discount'];

      // Si no tiene impuesto por item, aplicamos el general
      if ($row['tax'] > 0 && ($row['tax_type'] != $this->tax_type || $row['tax'] != $this->output_tax)) {
        $this->total_tax += $row['total_tax'];
        $this->unique_tax = false;
      } else {
        $this->total_tax += $row['total_base'] * $this->output_tax / 100;
      }

      // Si no tiene.retention.por item, aplicamos el general
      if ($row['retention'] > 0 && ($row['retention'] != $this->retention)) {
        $this->total_retention -= $row['total_retention'];
        $this->unique_retention = false;
      } else {
        $this->total_retention -= $row['total_base'] * $this->retention / 100;
      }
    }
    $this->amount = $this->total_base + $this->total_tax + $this->total_retention;
    $this->total_items = $this->total_base; // Porque ya no hay descuento general
  }



  /**
   *
   * Cambiamos el comportamiento al borrar una factura.
   * Hay que borrar también todos sus Items asociados
   *
   */
  function mark_deleted($id) {

    // Obtenemos la lista de Items, y los marcamos como borrados.
    $bean = new reg_invoices();
    $bean->retrieve($id);
    $items = $bean->get_linked_beans('items', 'reg_items');
    foreach ($items as $item) {
      $item->mark_deleted($item->id);
    }

    // Después ejecutamos el borrado normal.
    parent::mark_deleted($id);
  }



  /**
   *
   * Formatea algunos campos para su visualizacion en los listados
   *
   */
  function fill_in_additional_list_fields() {
    global $sugar_config;
    if ($sugar_config['fact_restart_number'] && $this->year && $this->number) {
      $this->number = "{$this->year}/{$this->number}";
    }
  }
  
  protected function duplicateItemsFromInvoiceId( $id ){
        
    $previousInvoice = new reg_invoices();
    $previousInvoice->retrieve($id);
    
    $previousInvoice->load_relationship('items');
    $this->load_relationship('items');

    foreach ($previousInvoice->items->getBeans() as $item) {
      $item->id = null; // This creates a copy of the item
      $item->relate_id = null; // Prevent múltiple total calculation
      $item->save();
      $this->items->add( $item->id );
    }
  }
  
  public static function correctFilterOptionsFromChart(){
    
  }
  
  public function attachPdf( $name = null ){
    global $sugar_config;
    require_once('modules/reg_invoices/views/view.pdf.php');
    require_once('modules/Notes/Note.php');
    
    if( empty($name) ) $name = 'Invoice';
    
    // We need a note
    $note=new Note();
    $note->name = $name;
    $note->parent_type="reg_invoices";
    $note->parent_id=$this->id;
    $note->file_mime_type="application/pdf";
    $note->filename="$name.pdf";
    $note->save();
    
    $view = new reg_invoicesViewPdf();
    $view->bean = $this;
    $view->preDisplay();
    
    $saveToFile = trim($sugar_config['upload_dir'], ' /') . "/$note->id";
    $view->display( "$saveToFile.pdf" );
    rename( "$saveToFile.pdf", $saveToFile );
  }

}
