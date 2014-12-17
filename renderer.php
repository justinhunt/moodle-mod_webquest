<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Webquest renderer.
 * @package   mod_webquest
 * @copyright 2014 Justin Hunt (poodllsupport@gmail.com)
 * @author    Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_webquest_renderer extends plugin_renderer_base {


	public function topmenu($action, $isteacher, $cm, $webquest){
		global $USER, $CFG;
	
		//create the main table (inner)
		$navtable= new html_table();
		$data = array();
		$navtable->head = array(0=>format_text(get_string("pages","webquest")));
		//$navtable->wrap[0] = 'nowrap';

		if($action == 'introduction') {
			$data[0] = "<b>".get_string("intro","webquest")."</b>";
		}else{
			$data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=introduction\">".get_string("intro","webquest")."</b>";
		}
		$navtable->data[0]= $data;

		if($action =='tasks'){
			$data[0] = "<b>".get_string("tasks","webquest")."</b>";
		}else{
			$data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=tasks\">".get_string("tasks","webquest")."</b>";
		}
		$navtable->data[1]= $data;

		if ($action == 'process'){
			$data[0] = "<b>".get_string("process","webquest")."</b>";
		}else{
			$data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=process\">".get_string("process","webquest")."</b>";
		}
		$navtable->data[2] = $data;

		if ($action == 'conclussion'){
			$data[0] = "<b>".get_string("conclussion","webquest")."</b>";
		}else{
			$data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=conclussion\">".get_string("conclussion","webquest")."</b>";
		}
		$navtable->data[3] = $data;

		if ($action == 'evaluation'){
			$data[0] = "<b>".get_string("evaluation","webquest")."</b>";
		}else{
			$data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=evaluation\">".get_string("evaluation","webquest")."</b>";
		}$navtable->data[4] = $data;

		if($action == 'teams'){
			$data[0] = "<b>".get_string("teams","webquest")."</b>";
		}else{
			$data[0] = "<b><a href=\"view.php?id=$cm->id&amp;action=teams\">".get_string("teams","webquest")."</b>";
		}
		$navtable->data[5] = $data;

  	//create the outer table
  	$containertable= new html_table();
	
	//create the navigation cell
	$navcell = new html_table_cell(html_writer::table($navtable));
	$navcell->attributes = array('class'=>'mod_webquest_navcell','width'=>'16%','valign'=>'top');
	
	//determine the actioncell contents based on the action
	$actioncellcontent="";
	$modulecontext = context_module::instance($cm->id);
	switch($action){
		case 'introduction':
			$actioncellcontent.= webquest_print_intro($webquest,$modulecontext);
			if ($isteacher){
				$actioncellcontent.= ("<b><a href=\"editpages.php?id=$cm->id&amp;action=intro\">".get_string("editintro", 'webquest')."</a></b>");
			}
			break;
		
		case 'tasks':
			$actioncellcontent.= webquest_print_tasks($webquest,$cm, $modulecontext);
			break;
			
		case 'process':
			$actioncellcontent.= webquest_print_process($webquest,$modulecontext);
			if ($isteacher){
				$actioncellcontent .= ("<b><a href=\"editpages.php?id=$cm->id&amp;action=process\">".get_string("editprocess", 'webquest')."</a></b>");
				$actioncellcontent .= webquest_print_editresources($webquest,$cm);
				$actioncellcontent.= ("<b><a href=\"resources.php?id=$cm->id&amp;action=editres\">".
					get_string("insertresources", 'webquest')."</a></b>");
			}else{
				$actioncellcontent.= webquest_print_resources($webquest,$modulecontext);
			}
			break;
			
		case 'conclussion':
			 $actioncellcontent .= webquest_print_conclussion($webquest,$modulecontext);
			if ($isteacher){
				$actioncellcontent .=  ("<b><a href=\"editpages.php?id=$cm->id&amp;action=conclussion\">".get_string("editconclussion", 'webquest')."</a></b>");
			}
			break;
			
		case 'evaluation':
			$actioncellcontent .=  webquest_print_evaluation($webquest,$USER->id,$cm);
			break;
			
		case 'teams':
			$actioncellcontent .= webquest_print_teams($webquest,$cm,$USER->id);
			break;
	
	}
	
	//create the action cell
	$actioncell = new html_table_cell($actioncellcontent);
	$actioncell->attributes = array('class'=>'mod_webquest_actioncell','valign'=>'top');
	
	//create the solitary table row from navcell and actioncell
	$row = new html_table_row();
	$row->cells = array($navcell, $actioncell);
	
	//set data to the table and return the html
	$containertable->data[] = $row;
	return html_writer::table($containertable);
	
	
	
	}

	/**
	 * Show a form
	 * @param mform $showform the form to display
	 * @param string $heading the title of the form
	 * @param string $message any status messages from previous actions
	 */
	function show_form($showform,$heading, $message=''){
		global $OUTPUT;
	
		//if we have a status message, display it.
		if($message){
			echo $this->output->heading($message,5,'main');
		}
		echo $this->output->heading($heading, 3, 'main');
		$showform->display();
	}
	


}
