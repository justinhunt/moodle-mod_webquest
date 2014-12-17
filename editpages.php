<?php  // $Id: editpages.php,v 1.4 2007/09/09 09:00:17 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
    require_once("forms.php");
    require_once("locallib.php");

global $DB;
	
    $id     = required_param('id', PARAM_INT);
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $cancel = optional_param('cancel',0,PARAM_INT);

    $timenow = time();
	if ($id) {
		$cm             = get_coursemodule_from_id('webquest', $id, 0, false, MUST_EXIST);
		$course         = get_course($cm->course);
		$webquest = $DB->get_record('webquest', array('id' => $cm->instance), '*', MUST_EXIST);
	} else {
		$webquest = $DB->get_record('webquest', array('id' => $a), '*', MUST_EXIST);
		$course         = get_course($webquest->course);
		$cm             = get_coursemodule_from_instance('webquest', $webquest->id, $course->id, false, MUST_EXIST);
	}

    require_login($course->id, false, $cm);

    if (($action == 'intro') or ($action == 'dointro')){
        $strpage = get_string("intro", "webquest");
    }else if (($action == 'process') or ($action == 'doprocess')){
        $strpage = get_string("process", "webquest");
    }else if (($action == 'conclussion') or ($action == 'doconclussion')){
        $strpage = get_string("conclussion", "webquest");
    }else if (($action == 'taskdesc') or ($action == 'dotaskdesc')){
        $strpage = get_string("task", "webquest");
    }else{
        $strpage = "missed action";
    }
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests = get_string("modulenameplural", "webquest");

	$modulecontext = context_module::instance($cm->id);
    $coursecontext = context_course::instance($course->id);
		
//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, 'webquest', "update ".$strpage, "view.php?id={$cm->id}", $webquest->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_webquest\event\course_module_viewed::create(array(
	   'objectid' => $webquest->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('webquest', $webquest);
	$event->trigger();
}    

//set up our page
$PAGE->set_url('/mod/webquest/editpages.php', array('id' => $cm->instance));
$PAGE->set_title($course->shortname . ": " . $webquest->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');

require_capability('mod/webquest:addinstance',$coursecontext);
	//we no longer need this, .... I think
	$isteacher = mod_webquest_isteacher($cm->course);
	
   //IF DO.. , we are processing a formsubmission
   if(strpos($action,'do')===0){
		//remove the "do" since everything is keyed on the action name
		$action = substr($action,2);
		/*
		if (!$isteacher) {
            error("Only teachers can look at this page");
        }
		*/
		
		$editoroptions = webquest_fetch_editor_options($course,$modulecontext);
		$itemid = $webquest->id; //intro file area is handled by core, and expects 0 itemid
		switch($action){
			case 'intro': $theform = new mod_webquest_intro_form(); $viewaction = "introduction";$itemid=0; break;
			case 'process': $theform = new mod_webquest_process_form(); $viewaction = "process"; break;
			case 'conclussion': $theform = new mod_webquest_conclussion_form(); $viewaction = "conclussion"; break;
			case 'taskdesc': $theform = new mod_webquest_taskdesc_form(); $viewaction = "tasks"; break;
			default:  redirect("view.php?id=$cm->id&amp;action=introduction");
		}
		
        if ( $theform->is_cancelled()){
            redirect("view.php?id=$cm->id&amp;action=$action");
        }
       
	    $form_data = $theform->get_data();
	    $form_data = file_postupdate_standard_editor( $form_data, $action, $editoroptions, $modulecontext,
                                        'mod_webquest', $action, $itemid);

		$formarray = get_object_vars($form_data);
        if (!$DB->set_field("webquest", $action, trim($formarray[$action]), array("id"=>$webquest->id)) && 
			$DB->set_field("webquest", $action ."format",$formarray[$action . 'format'], array("id"=>$webquest->id))){
            error("Could not update webquest $action!");
            redirect("view.php?id=$cm->id&amp;action=$viewaction");
        }else{
            redirect("view.php?id=$cm->id&amp;action=$viewaction", get_string("wellsaved","webquest"));
        }
	}//end of if do action

 //Prepare to output relevant form for the passed in action  
$renderer = $PAGE->get_renderer('mod_webquest');
echo $renderer->header();

	
//****************************************Forms******************************************************//
 /*
everything is linked to action name : editor element name, target action, filearea for files, display string
 */
$editoroptions = webquest_fetch_editor_options($course,$modulecontext);
$itemid = $webquest->id;				  
						
switch($action){
	case 'intro': $theform = new mod_webquest_intro_form(null,array('editoroptions'=>$editoroptions));
					$itemid=0; //intro file area is handled by core, and expects 0 itemid
					break;
	case 'process': $theform = new mod_webquest_process_form(null,array('editoroptions'=>$editoroptions));
					break;
	case 'conclussion': $theform = new mod_webquest_conclussion_form(null,array('editoroptions'=>$editoroptions));
					break;
	case 'taskdesc': $theform = new mod_webquest_taskdesc_form(null,array('editoroptions'=>$editoroptions));
					break;
	default:
}

$form_data = file_prepare_standard_editor($webquest, $action, $editoroptions, $modulecontext,
							 'mod_webquest',$action, $itemid);
		
/*
if (!$isteacher) {
	error("Only teachers can look at this page");
}
*/

$form_data->a = $webquest->id ;
$form_data->id = $cm->id ;
$theform->set_data($form_data);
echo $renderer->show_form($theform, get_string($action,"webquest"));
echo $renderer->footer();
?>