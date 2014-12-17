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
 * Forms for WebQuest Module
 *
 * @package    mod_webquest
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');
require_once('locallib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/user_bulk_forms.php');

//Assessment base form
abstract class mod_webquest_base_form extends moodleform {

	function tablify($elarray, $colcount, $id, $haveheader=true){
		$mform = & $this->_form;
		
		$starttable =  html_writer::start_tag('table',array('class'=>'webquest_form_table'));
		//$startheadrow=html_writer::start_tag('th'); 
		//$endheadrow=html_writer::end_tag('th'); 
		$startrow=html_writer::start_tag('tr'); 
		$startcell = html_writer::start_tag('td',array('class'=>'webquest_form_cell webquest_' . $id .'_col_@@'));
		$startheadcell = html_writer::start_tag('th',array('class'=>'webquest_form_cell webquest_' . $id .'_col_@@'));
		$endcell=html_writer::end_tag('td');
		$endheadcell=html_writer::end_tag('th');
		$endrow=html_writer::end_tag('tr');
		$endtable = html_writer::end_tag('table');
		
		//start the table 
		$tabledelements = array();
		$tabledelements[]=& $mform->createElement('static', 'table_start_' . $id, '', $starttable);
	
		
		//loop through rows
		for($row=0;$row<count($elarray);$row= $row+$colcount){
			//loop through cols
			for($col=0;$col<$colcount;$col++){
				//addrowstart
				if($col==0){
					$tabledelements[]=& $mform->createElement('static', 'tablerow_start_' . $id . '_' . $row, '', $startrow);
				}
				//add a th cell if this is first row, otherwise a td
				if($row==0 && $haveheader){
					$thestartcell = str_replace('@@', $col,$startheadcell);
					$theendcell = $endheadcell;
				}else{
					$thestartcell = str_replace('@@', $col,$startcell);
					$theendcell = $endcell;
				}
				$tabledelements[]=& $mform->createElement('static', 'tablecell_start_' . $id . '_' . $row .'_'. $col, '', $thestartcell);
				$tabledelements[]=& $elarray[$row+$col];
				$tabledelements[]=& $mform->createElement('static', 'tablecell_end_' . $id . '_' . $row .'_'. $col, '', $theendcell);

				//add row end
				if($col==$colcount-1){
					$tabledelements[]=& $mform->createElement('static', 'tablerow_end_' . $id . '_' . $row, '', $endrow);
				}
			}//end of col loop	
		}//end of row loop
		
		//close out our table and return it
		$tabledelements[]=& $mform->createElement('static', 'table_end_' . $id, '', $endtable);
		return $tabledelements;
	}
}

class mod_webquest_editoronly_form extends mod_webquest_base_form {
	
	protected $target = 'replacethis';
	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
        $mform = & $this->_form;
        
		$editoroptions = $this->_customdata['editoroptions'];
		$editorname= $this->target . '_editor';
        $mform->addElement('editor', $editorname, get_string($this->target, 'webquest'),null,$editoroptions);
        $mform->setType($editorname, PARAM_RAW);

		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->addElement('hidden', 'action', 'do' . $this->target);
        $mform->setType('action', PARAM_TEXT);
		 $this->add_action_buttons(true,get_string('savechanges'));

    }

}

class mod_webquest_intro_form extends mod_webquest_editoronly_form {

	protected $target = 'intro';

}//end of class

class mod_webquest_process_form extends mod_webquest_editoronly_form {

	protected $target = 'process';

}//end of class

class mod_webquest_conclussion_form extends mod_webquest_editoronly_form {

	protected $target = 'conclussion';

}//end of class

class mod_webquest_taskdesc_form extends mod_webquest_editoronly_form {

	protected $target = 'taskdesc';

}//end of class

class mod_webquest_task_form extends mod_webquest_base_form {
		
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
        $mform = & $this->_form;
        
		$fieldcount = $this->_customdata['fieldcount'];
		$gradingstrategy = $this->_customdata['gradingstrategy'];
		$webquestgrade = $this->_customdata['webquestgrade'];
		if(!$fieldcount){$fieldcount=1;}
		if(!$gradingstrategy){$gradingstrategy=WEBQUEST_GS_NONE;}
		if(!$webquestgrade){$webquestgrade=100;}
		
		$repeatel = array();
		$repeateloptions = array();
		
		//task header
		$taskheader_element = $mform->createElement('static','taskheader',get_string("taskheader","webquest"));
        
		//description
		$description_element = $mform->createElement('textarea', 'description', get_string("criterion","webquest"),
			array('wrap'=>"virtual", 'rows'=>2, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
		$repeateloptions['description']['type'] = PARAM_TEXT;
			
		//scale
		$scale_options = array();
		$rawscales = mod_webquest_scales(); 
		foreach ($rawscales as $key => $scale) {
                    $scale_options[] = $scale['name'];
       }
	  $scale_element  = $mform->createElement('select', 'scale', get_string("typeofscale", "webquest"),$scale_options);
	  $repeateloptions['scale']['type'] = PARAM_INT;

	   
	   //weight
	   $weight_options = mod_webquest_eweights();
	   $weight_element = $mform->createElement('select', 'weight', get_string("taskweight", "webquest"),$weight_options);
	   $repeateloptions['weight']['type'] = PARAM_INT;


		//maxscore
		$maxscore_options = webquest_fetch_int_array(0,$webquestgrade);
		$maxscore_element = $mform->createElement('select', 'maxscore', get_string('suggestedgrade','webquest'),$maxscore_options);
		$repeateloptions['maxscore']['type'] = PARAM_INT;

	   
	   //Criterion (ie max score)
	   /*
	    $criterion_options = webquest_fetch_int_array(0,100);
		$criterion_element = $mform->createElement('select', 'maxscore', get_string('suggestedgrade','webquest'),$criterion_options);
		*/
		
		//rubrics
		$rubric1_element = $mform->createElement('textarea', 'rubric1', get_string("grade1","webquest"),
			array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
		
		$rubric2_element = $mform->createElement('textarea', 'rubric2', get_string("grade2","webquest"),
			array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
			
		$rubric3_element = $mform->createElement('textarea', 'rubric3', get_string("grade3","webquest"),
			array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
			
		$rubric4_element = $mform->createElement('textarea', 'rubric4', get_string("grade4","webquest"),
			array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
			
		$rubric5_element = $mform->createElement('textarea', 'rubric5', get_string("grade5","webquest"),
			array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
		$repeateloptions['rubric1']['type'] = PARAM_TEXT;
		$repeateloptions['rubric2']['type'] = PARAM_TEXT;
		$repeateloptions['rubric3']['type'] = PARAM_TEXT;
		$repeateloptions['rubric4']['type'] = PARAM_TEXT;
		$repeateloptions['rubric5']['type'] = PARAM_TEXT;
		
		
		//load up the header element per task for repitition
		$repeatel[] = $taskheader_element;
		
		
		//only load up the form elements we require for repetition
		switch ($gradingstrategy){
			case WEBQUEST_GS_NONE:
				$repeatel[]=$description_element;
				break;
				
			
			case WEBQUEST_GS_ACCUMULATIVE:
				$repeatel[]=$description_element;
				$repeatel[]=$scale_element;
				$repeatel[]=$weight_element;
				break;
			
			case WEBQUEST_GS_ERRORBANDED:
				$repeatel[]=$description_element;
				$repeatel[]=$weight_element;
				//$repeatel[]=$maxscore_element;
				break;
			
			case WEBQUEST_GS_CRITERION:
				$repeatel[]=$description_element;
				$repeatel[]=$maxscore_element;
				break;
			
			case WEBQUEST_GS_RUBRIC:
				$repeatel[]=$description_element;
				$repeatel[]=$weight_element;
				$repeatel[]=$rubric1_element;
				$repeatel[]=$rubric2_element;
				$repeatel[]=$rubric3_element;
				$repeatel[]=$rubric4_element;
				$repeatel[]=$rubric5_element;
				break;
		}
		
		//set the repeatable form elements to the form
		$this->repeat_elements($repeatel, $fieldcount,
                    $repeateloptions, 'repeats', 'add_fields', 1, get_string('addtasksbutton','webquest','{no}'), true);
					
		//Error Margin Grade Table	
		if($gradingstrategy==WEBQUEST_GS_ERRORBANDED){
			$gradeel = array();
			$gradeel[] =& $mform->createElement('static', "rheader_errorreponses", '', get_string('numberofnegativeresponses','webquest'));
			$gradeel[] =& $mform->createElement('static', "rheader_maxscore", '',get_string('suggestedgrade','webquest'));

			for ($i=0;$i<$fieldcount+1;$i++){
				$gradeel[] =& $mform->createElement('static', "errorreponses[$i]", '', $i);
				$gradeel[] =& $mform->createElement('select', "maxscore[$i]", '',$maxscore_options);	
			}
			$tablegradeel = $this->tablify($gradeel,2,'errormargin');
			$mform->addGroup($tablegradeel, 'errormargin_table', '', array(' '), false);

		}
		

		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->addElement('hidden', 'action', 'doedittasks');
        $mform->setType('action', PARAM_TEXT);
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

class mod_webquest_teams_form extends mod_webquest_base_form {

   /**
     * Defines the form.  Just adds a filemanager, editor and title
     */
    public function definition() {
        $mform = $this->_form;

		$mform->addElement('text', 'name', get_string("name"));
		$mform->addElement('textarea', 'description', get_string("description"),
			array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_tasktext', 'cols'=>75));
		
		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'action','doeditteam');
		$mform->addElement('hidden', 'a',0);
		$mform->addElement('hidden', 'teamid',0);			
		$mform->setType('name', PARAM_TEXT);	
	    $mform->setType('action', PARAM_TEXT);
		$mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->setType('teamid', PARAM_INT);
		
		$this->add_action_buttons(true,get_string('savechanges'));

    }

}

/**
 *
 *
 * Just displays a filepicker field.
 *
 */
class mod_webquest_resource_form extends mod_webquest_base_form {

    /**
     * Defines the form.  Just adds a filepicker and submit button
     */
    public function definition() {
        $mform = $this->_form;
		$filemanageroptions = $this->_customdata['filemanageroptions'];
		
		 $mform->addElement('text', 'name', get_string("name"));
		
		 $mform->addElement('textarea', 'description', get_string("description"),array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_resourcetext', 'cols'=>75));
		
        $mform->addElement('filemanager',
                           'resourcefiles',
                           get_string('resources', 'webquest'),
                           null,$filemanageroptions
                           );
		
		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
		$mform->addElement('hidden', 'resid',0);
		$mform->addElement('hidden', 'path','');
        $mform->setType('resid', PARAM_INT);
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->setType('name', PARAM_TEXT);
		$mform->setType('description', PARAM_TEXT);
		$mform->setType('path', PARAM_TEXT);
		$mform->addElement('hidden', 'action', 'doinsertres');
        $mform->setType('action', PARAM_TEXT);
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

/**
 *
 *
 * Just displays an html area and a attachments field
 *
 */
class mod_webquest_multi_form extends mod_webquest_base_form {

    /**
     * Defines the form.  Just adds a filemanager, editor and title
     */
    public function definition() {
        $mform = $this->_form;
		$filemanageroptions = $this->_customdata['filemanageroptions'];
		$editoroptions = $this->_customdata['editoroptions'];
		$target = $this->_customdata['target'];
		
		
		$mform->addElement('text', 'title', get_string("title",'webquest'));
		
		$editorname= $target . '_editor';
        $mform->addElement('editor', $editorname, get_string($target, 'webquest'),null,$editoroptions);
        $mform->setType($editorname, PARAM_RAW);
		
		$mform->addElement('filemanager',
                           'attachments',
                           get_string('attachments','webquest'),
                           null,$filemanageroptions
                           );

		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
		$mform->addElement('hidden', 'sid',0);
		$mform->setType('title', PARAM_TEXT);
        $mform->setType('sid', PARAM_INT);
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->addElement('hidden', 'action', 'do' . $target);
        $mform->setType('action', PARAM_TEXT);
		$this->add_action_buttons(true,get_string('savechanges'));

    }
}

//Assessment base form
abstract class mod_webquest_assessment_base_form extends mod_webquest_base_form {


	function add_hidden(){
		$mform = & $this->_form;
		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
		$mform->addElement('hidden', 'sid',0);
		$mform->addElement('hidden', 'returnto','');
		$mform->addElement('hidden', 'taskno',0);
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->setType('sid', PARAM_INT);
		$mform->setType('returnto', PARAM_TEXT);
		$mform->setType('taskno', PARAM_INT);
		$mform->addElement('hidden', 'action', 'doassessment');
        $mform->setType('action', PARAM_TEXT);
	}
	
	function add_feedback($tasks, $addgrades=false, $addrubrics=false){
		global $DB;

		
		$mform = & $this->_form;
		$webquestid = $this->_customdata['webquestid'];
		
		for($i=0;$i<count($tasks);$i++){
			
			//add task descriptions
			$elname = "desk_task_$i";
			$mform->addelement('static',"desc_" . $elname ,get_string('tasks_i','webquest',$i+1) ,$tasks[$i]->description);

			
			//if addgrades
			if($addgrades){
				$this->add_grades($tasks[$i],$i);
			}

			//add rubrics
			if($addrubrics){
				if ($rubricsraw = $DB->get_records_select("webquest_rubrics", "webquestid = $webquestid AND taskno = $i", null,"rubricno ASC")) {
                    unset($rubrics);
                    foreach ($rubricsraw as $rubic) {
                        $rubrics[] = $rubic;   // to renumber index 0,1,2...
                    }
					$this->add_rubrics($rubrics,$i);
				}
			}
			
			
			
			//add feedbacks
			$elname="feedback[$i]";
			$mform->addElement('textarea', 
				$elname, 
				get_string("feedback","webquest"),
				array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_resourcetext', 'cols'=>75));
			$mform->setType($elname, PARAM_TEXT);
		}
	}
	
	function add_grades($task,$i){
			$eweights = mod_webquest_eweights();
			$escales = mod_webquest_scales();
			$mform = & $this->_form;
			//static elements
		//	$mform->addelement('static',"desc_tasks_" . $i ,get_string('tasks_i','webquest',$i+1) ,$task->description);
			$mform->addelement('static',"weight_tasks_" . $i ,get_string("taskweight", "webquest") ,number_format($eweights[$task->weight], 2));
			
			//grade controls
			$scalenumber=$task->scale;
            $scale = (object)$escales[$scalenumber];
			$grade_options = webquest_fetch_int_array(0,$scale->size);
			$grade_options = array_reverse($grade_options);
			$radioattributes=array('class'=>'webquest_horizontalradio');
			$default=0;
			$elname="grade[$i]";
			switch ($scale->type) {
	
                        case 'radio' :						
							//testing if using scales has -ve effect on non error banded assessments
							//justin 201409/10
							if(true){
								$graderadios=array();
								foreach($grade_options as $label=>$value){
									$graderadios[] =& $mform->createElement('radio', $elname, '', '&nbsp;', $value, $radioattributes);
								}
								$graderadios[] =& $mform->createElement('static', 'scaleend_' . $i, '', $scale->end);
								array_unshift($graderadios, $mform->createElement('static', 'scalestart_' . $i, '', $scale->start));
								$mform->addGroup($graderadios, 'graderadios', get_string("grade"), array(' '), false);
								break;				
							}else{
								$graderadios=array();
								foreach($grade_options as $label=>$value){
									$graderadios[] =& $mform->createElement('radio', $elname, '', $label, $value, $radioattributes);
								}
								$mform->addGroup($graderadios, 'graderadios', get_string("grade"), array(' '), false);
								break;
							}
						case 'selection':
						default:
							$mform->addelement('select', $elname, get_string("grade"),$grade_options);
			}
			//setting a default here seems to screw it
			//$mform->setDefault($elname,0);
			$mform->setType($elname, PARAM_INT);
	}
	
	function add_grade_table($tasks){
		$ret =  '<h3>' . get_string("gradetable","webquest") . '</h3>';
		$table = new html_table();
		$table->id = 'mod_webquest_gradetable';
	
		//table header
		$table->head = array(
			get_string("numberofnegativeresponses", "webquest"),
			get_string("suggestedgrade", "webquest")
		);
		//$table->headspan = array(1,1,2);
		
		//columns
		$table->colclasses = array(
			'mod_webquest_gradeindex', 'mod_webquest_grademaxscore');
	
		//add a row per tasks
		$i=0;
		foreach($tasks as $task){
			$i++;
			$row = new html_table_row();
			$indexcell = new html_table_cell($i);
			$maxscorecell = new html_table_cell($task->maxscore);
			$row->cells = array($indexcell, $maxscorecell);
			$table->data[] = $row;
		}

		$ret= html_writer::table($table);
		return $ret;
	}
	
	function add_criteria_table($tasks){
		$mform = & $this->_form;
		
		$i=0;
		$criteria = array();
		//header cells
		$criteria[] =& $mform->createElement('static', 'header_taskname_', '', '');
		$criteria[] =& $mform->createElement('static', 'header_taskdesc_', '', get_string("criterion","webquest"));
		$criteria[] =& $mform->createElement('static', 'header_grade_', '', get_string("select","webquest"));
		$criteria[] =& $mform->createElement('static', 'header_taskmax_', '', get_string("suggestedgrade","webquest"));
		foreach($tasks as $task){
			$i++;
			$criteria[]=& $mform->createElement('static', 'criterion_taskname_' . $i, '', get_string("task", 'webquest') . ' ' . $i );
			$criteria[]=& $mform->createElement('static', 'criterion_taskdesc_' . $i, '', format_text($task->description));
			$criteria[] =& $mform->createElement('radio', 'grade[0]', '', '',$i-1);
			$criteria[] =& $mform->createElement('static', 'criterion_taskmax_' . $i, '', $task->maxscore);
		}
		$criteria_els = $this->tablify($criteria,4,'criteria');
		$mform->addGroup($criteria_els, 'criteria_table_group' , get_string("tasks", 'webquest'), array(' '), false);
	}
	
	function add_rubrics($rubrics,$i){
		$mform = & $this->_form; 

		$rubric_controls = array();
		
		//loop through the rubrics
		 for ($j=0; $j<5; $j++) {
			if (empty($rubrics[$j]->description)) {
				break; // out of inner for loop
			}
			$rubric_controls[] =& $mform->createElement('radio', "grade[$i]", '', '',$j);
			$rubric_controls[]=& $mform->createElement('static', "rtd_3_$i:$j", '', format_text($rubrics[$j]->description));	
		}
		$rubric_els = $this->tablify($rubric_controls,2,'rubric',false);
		$mform->addGroup($rubric_els, "rubrics__table", '', array(' '), false);
	}
	
	function add_optional_adjust($gradeindex){
		$mform = & $this->_form;
		$elname="grade[$gradeindex]";
		$adjust_options = webquest_fetch_int_array(-20,20);
		$mform->addelement('select', $elname, get_string("optionaladjustment","webquest"),$adjust_options);
		//setting this seems to screw it, so we don't
		//$mform->setDefault($elname,0);
		$mform->setType($elname, PARAM_INT);

	
	}
	
	function add_general_comment($adjustreason=false){
		$mform = & $this->_form;
	
		//textarea
		$areatitle = get_string("generalcomment", "webquest");
		if($adjustreason){$areatitle.= '/' . get_string("reasonforadjustment", "webquest") ;}
		$elname="generalcomment";
		$mform->addElement('textarea', 
				$elname, 
				$areatitle,
				array('wrap'=>"virtual", 'rows'=>3, 'class'=>'mod_webquest_resourcetext', 'cols'=>75));
			$mform->setType($elname, PARAM_TEXT);
	
	}
	
}


//Assessment Nograde form
class mod_webquest_assessment_nograde_form extends mod_webquest_assessment_base_form {

	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
        $mform = & $this->_form;
        
		//$formheading = $this->_customdata['formheading'];
		$tasks = $this->_customdata['tasks'];
		
		//add feedback fields
		$addgrades =false;
		$addrubrics =false;
		$this->add_feedback($tasks, $addgrades, $addrubrics);
		
		//add hidden fields
		$this->add_hidden();
		
		//add buttons
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

//Assessment Accumulative form
class mod_webquest_assessment_accumulative_form extends mod_webquest_assessment_base_form {
	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');

		
        $mform = & $this->_form;
        
		//$formheading = $this->_customdata['formheading'];
		$tasks = $this->_customdata['tasks'];
		
		//add feedback fields and grades
		$addgrades =true;
		$addrubrics =false;
		$this->add_feedback($tasks, $addgrades, $addrubrics);
		
		//this add general feedback
		$adjustmessage=false;
		$this->add_general_comment($adjustmessage);

		//add hidden fields
		$this->add_hidden();
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

//Assessment Errorbandedform
//this needs to be reviewed it will not work like the original
class mod_webquest_assessment_errorbanded_form extends mod_webquest_assessment_base_form {

	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
		
        $mform = & $this->_form;
        
		//$formheading = $this->_customdata['formheading'];
		$tasks = $this->_customdata['tasks'];
		
		//add feedback fields
		$addgrades =true;
		$addrubrics =false;
		$this->add_feedback($tasks, $addgrades, $addrubrics);
		
		//this add grade table
		$gtable = $this->add_grade_table($tasks);
		$mform->addElement('static','gradetable','',$gtable);
		
		//add optional adjust
		$gradeindex = count($tasks);
		$this->add_optional_adjust($gradeindex);
		
		//this add general feedback
		$adjustmessage=true;
		$this->add_general_comment($adjustmessage);
		
		$this->add_hidden();
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

//Assessment Criterion
class mod_webquest_assessment_criteria_form extends mod_webquest_assessment_base_form {
	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
		
        $mform = & $this->_form;
        
		//$formheading = $this->_customdata['formheading'];
		$tasks = $this->_customdata['tasks'];

		
		//this add grade table
		$this->add_criteria_table($tasks);
		
		//add optional adjust
		$gradeindex = 1;
		$this->add_optional_adjust($gradeindex);
		
		//this add general feedback
		$adjustmessage=true;
		$this->add_general_comment($adjustmessage);
		
		$this->add_hidden();
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

//Assessment Criterion
class mod_webquest_assessment_rubric_form extends mod_webquest_assessment_base_form {
	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
		
        $mform = & $this->_form;
        
		//$formheading = $this->_customdata['formheading'];
		$tasks = $this->_customdata['tasks'];


		//add feedback fields and grades
		$addgrades =false;
		$addrubrics =true;
		$this->add_feedback($tasks, $addgrades, $addrubrics);
		
		
		//this add general feedback
		$adjustmessage=false;
		$this->add_general_comment($adjustmessage);
		
		$this->add_hidden();
		$this->add_action_buttons(true,get_string('savechanges'));
    }
}

class mod_webquest_members_form extends user_bulk_form {

	function definition() {
		$mform = & $this->_form;
		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
		$mform->addElement('hidden', 'teamid',0);
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->setType('teamid', PARAM_INT);
		parent::definition();
	}

}//end of class

class mod_webquest_updatemembers_form extends user_bulk_form {
	function definition() {
		$mform = & $this->_form;
		$mform->addElement('hidden', 'id',0);
		$mform->addElement('hidden', 'a',0);
		$mform->addElement('hidden', 'teamid',0);
		$mform->addElement('hidden', 'action','domembers');
        $mform->setType('id', PARAM_INT);
		$mform->setType('a', PARAM_INT);
		$mform->setType('teamid', PARAM_INT);
		$mform->setType('action', PARAM_TEXT);
		$this->add_action_buttons(true,get_string('savechanges'));
	}

}//end of class

