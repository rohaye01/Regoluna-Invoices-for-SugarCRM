<?php
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
class reg_invoicesController extends SugarController{

  // returns a simple view of detail view to be loaded via Ajax.
  function action_AjaxMain(){
    $this->view = 'ajaxmain';
  }

  // returns xml view in "facturae" format (spanish invoices).
  function action_XmlView(){
    $this->view = 'xml';
  }

  // returns invoice in PDF format.
  function action_PdfView(){
    $this->view = 'pdf';
  }

  // returns invoice in PDF format.
  function action_PrintPdf(){
    $this->view = 'pdf';
  }

  // Signs XML using CryptoApplet.
  function action_SignXml(){
    $this->view = 'SignXml';
  }

  // Signs PDF using CryptoApplet.
  function action_SignPdf(){
    $this->view = 'SignXml';
  }
  
  function action_listview(){
    if($_REQUEST['state_in_chart']) $this->correctStateFilterFromChart();
    parent::action_listview();
  }
  
  function correctStateFilterFromChart(){
    global $app_list_strings;
    $_REQUEST['state_advanced'] = $_REQUEST['state_in_chart'];
    foreach($app_list_strings['reg_invoice_state_dom'] as $i=>$t){
      if($_REQUEST['state_in_chart']==$t){
        $_REQUEST['state_advanced']=$i;
        break;
      }
    }
  }

  // Adds a note with attachment from signed XML or PDF.
  function action_AddNote(){
    global $sugar_config;

    // $this->view = 'AddNote';

    // Creamos una nota
    require_once "modules/Notes/Note.php";
    $nota=new Note();
    $nota->name="Signed Invoice ".date();
    $nota->parent_type="reg_invoices";
    $nota->parent_id=$_POST['record'];

    // Guardamos la nota y obtenemos el id
    $id=$nota->save();

    // Guardamos el archivo ($_POST['doc'])
    if($_POST['type']=='XML'){
      file_put_contents(trim($sugar_config['upload_dir']," /")."/$id" , trim(html_entity_decode($_POST['doc'])));
      $nota->file_mime_type="text/xml";
      $nota->filename="Factura.xml";
      $nota->save();
    }else if($_POST['type']=='PDF'){
      file_put_contents(trim($sugar_config['upload_dir']," /")."/$id" , base64_decode($_POST['doc']) );
      $nota->file_mime_type="application/pdf";
      $nota->filename="Factura.pdf";
      $nota->save();
    }
    die();
  }

}