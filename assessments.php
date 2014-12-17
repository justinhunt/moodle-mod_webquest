<?php  // $Id: assessments.php,v 1.3 2007/09/09 09:00:16 stronk7 Exp $
    require("../../config.php");
    require("lib.php");
    require("locallib.php");
	require("forms.php");

global $DB;

    $action         = required_param('action', PARAM_ALPHA);
    $id             = optional_param('id', 0, PARAM_INT);    // Course Module ID
    $a      = optional_param('a', '', PARAM_ALPHA);
    $userid         = optional_param('userid', 0, PARAM_INT);
    $sid            = optional_param('sid', 0, PARAM_INT); // submission id
	$graded      = optional_param('graded', false, PARAM_BOOL);
    $taskno      = optional_param('taskno', -1, PARAM_INT);



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
	$modulecontext = context_module::instance($cm->id);
	$context =$modulecontext;
    $coursecontext = context_course::instance($course->id);

	//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	   add_to_log($course->id, "webquest", "assessments ".$action, "view.php?id=$cm->id",$webquest->name, $cm->id);
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


    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");
    $strassessments = get_string("assessments", "webquest");
    
   	$context = context_module::instance($cm->id);
	$modulecontext = $context;
	$coursecontext = context_course::instance($course->id);
	
	//only edting teachers can edit tasks
	//require_capability('mod/webquest:addinstance',$coursecontext);

    // ... print the header and...
	$PAGE->set_url('/mod/webquest/assessments.php', array('id' => $cm->instance));
	$PAGE->set_title($course->shortname . ": " . $webquest->name);
	$PAGE->set_heading($course->fullname);
	$PAGE->set_pagelayout('course');
	$PAGE->set_context($modulecontext);
	
	$renderer = $PAGE->get_renderer('mod_webquest');

	if($sid){
		$submission = $DB->get_record('webquest_submissions', array('id'=>$sid));
	}

    // ... print the header and...
	/*
    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strassessments",
                "", "", true);
	*/		
	
	
	if ($action=="assessment" && $sid){
		$formheading = "";
		$formheading .=  $renderer->heading(get_string('submitted', 'webquest'),5,'main');
		$formheading .= $renderer->heading(userdate($submission->timecreated),3,'main');

		// only show the grade if grading strategy > 0 and the grade is positive
			
		if ($webquest->gradingstrategy and $submission->grade >= 0) {
			$gradenotes =get_string("thegradeis", "webquest").": ".
				number_format($submission->grade * $webquest->grade / 100, 2)." (".
				get_string("maximumgrade")." ".number_format($webquest->grade, 0).")" ;
			
			$formheading .= $renderer->heading($gradenotes,3,'main');
		}

		// get the assignment elements...
		$tasksraw = $DB->get_records("webquest_tasks", array("webquestid"=>$webquest->id), "taskno ASC" );
		
		/*
		if (count($tasksraw) < count($webquest->tasks)) {
			$message = print_string("noteonassignmentelements", "webquest");
			$formheading .= $renderer->heading($message,3,'main');
		}
		*/
		if ($tasksraw) {
			$tasks =array();
			foreach ($tasksraw as $task) {
				$tasks[] = $task;   // to renumber index 0,1,2...
			}
		} else {
			$tasks = null;
		}
		
		$grades=array();
		if ($graded) {
			// get any previous grades...
			//if ($gradesraw = $DB->get_records_select("webquest_grades", "sid = $submission->id", "taskno")) {
			if ($gradesraw = $DB->get_records("webquest_grades", array("sid"=> $submission->id,"webquestid"=>$webquest->id),"taskno ASC")) {
				foreach ($gradesraw as $grade) {
					$grades[] = $grade;   // to renumber index 0,1,2...
				}
			}
		} else {
			// setup dummy grades array
			for($i = 0; $i < count($tasksraw); $i++) { // gives a suitable sized loop
				$grades[$i] = new stdClass();
				$grades[$i]->feedback = get_string("writeafeedback", "webquest");
				$grades[$i]->grade = 0;
			}

		}

		//standard form data
		$form_data=new stdClass();
		$form_data->a = $webquest->id ;
		$form_data->id = $cm->id ;
		$form_data->sid = $sid ;
		$form_data->feedback=array();
		$form_data->grade=array();
		
		//existing feedback and grades
		for($i=0;$i<count($grades);$i++){
			//feedback
			if(isset($grades[$i]->feedback)){
				$form_data->feedback[] = $grades[$i]->feedback;
			}else{
				$form_data->feedback[] =  '';
			}
			
			//grade
			$form_data->grade[]= $grades[$i]->grade;
		}
		
		//Do we have a general comment?
		if ($generalcomment =$DB->get_field("webquest_submissions", "gradecomment", array('id'=>$sid))){
			$form_data->generalcomment= $generalcomment;
        }

		
	//	print_r($form_data);
		//
		//$form_data->returnto = $cm->id ;
		//$form_data->taskno = $webquest->id ;

			switch($webquest->gradingstrategy){
				
				case WEBQUEST_GS_NONE:
				default:
					$theform = new mod_webquest_assessment_nograde_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 
					
					$theform->set_data($form_data);
					echo $renderer->header();
					echo $formheading;
					echo $renderer->show_form($theform,get_string('submitted', 'webquest'));
					echo $renderer->footer();
					return;
				
				case WEBQUEST_GS_CRITERION:
					$theform = new mod_webquest_assessment_criteria_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 
/*
					for($i=0;$i<count($grades);$i++){
						if(isset($grades[$i]->feedback)){
							$form_data->grade[] = $grades[$i]->grade;
						}else{
							$form_data->grade[] =  0;
						}
					}
*/
					$theform->set_data($form_data);
					echo $renderer->header();
					echo $formheading;
					echo $renderer->show_form($theform,'');
					echo $renderer->footer();
					return;
					
				case WEBQUEST_GS_RUBRIC:
					$theform = new mod_webquest_assessment_rubric_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 	
/*
					for($i=0;$i<count($grades);$i++){
						if(isset($grades[$i]->feedback)){
							$form_data->grade[] = $grades[$i]->grade;
						}else{
							$form_data->grade[] =  0;
						}
					}
*/
					$theform->set_data($form_data);
					echo $renderer->header();
					echo $formheading;
					echo $renderer->show_form($theform,'');
					echo $renderer->footer();
					return;
				
				
				case WEBQUEST_GS_ERRORBANDED:
					$theform = new mod_webquest_assessment_errorbanded_form(null,array('tasks'=>$tasks,'webquestid'=>$webquest->id)); 	
/*
					for($i=0;$i<count($grades);$i++){
						if(isset($grades[$i]->feedback)){
							$form_data->grade[] = $grades[$i]->grade;
						}else{
							$form_data->grade[] =  0;
						}
					}
	*/
					$theform->set_data($form_data);
					echo $renderer->header();
					echo $formheading;
					echo $renderer->show_form($theform,'');
					echo $renderer->footer();
					return;
				
			
				case WEBQUEST_GS_ACCUMULATIVE:
					$theform = new mod_webquest_assessment_accumulative_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 	
/*
					for($i=0;$i<count($grades);$i++){
						if(isset($grades[$i]->feedback)){
							$form_data->grade[] = $grades[$i]->grade;
						}else{
							$form_data->grade[] =  0;
						}
					}
*/
					$theform->set_data($form_data);
					echo $renderer->header();
					echo $formheading;
					echo $renderer->show_form($theform,'');
					echo $renderer->footer();
					return;
			
			}//end of switch
		
	}	
	
	
    elseif ($action == 'doassessment') {
        if (!mod_webquest_isteacher($course->id)){
            error("Only teachers can look at this page");
        }
        if (empty($sid)) {
            error("Webquest Submission id missing");
        }else {
            if (!$submission = $DB->get_record("webquest_submissions", array("id"=>$sid)) ){
                error ("Submission record not found");
            }
        }


        $tasksraw = $DB->get_records("webquest_tasks", array("webquestid"=>$webquest->id), "taskno ASC");
        /*
		if (count($tasksraw) < count($webquest->tasks)) {
            print_string("noteonassignmenttasks", "webquest");
        }
		*/
        if ($tasksraw) {
            $tasks=array();
			foreach ($tasksraw as $task) {
                $tasks[] = $task;
            }
        } else {
            $tasks = null;
        }
        $DB->delete_records("webquest_grades", array( "sid"=>$submission->id));


        switch ($webquest->gradingstrategy) {
            case WEBQUEST_GS_NONE:
				$theform = new mod_webquest_assessment_nograde_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 	
				if ($theform->is_cancelled()){
					redirect("view.php?id=$cm->id&amp;action=evaluation");
				}
				
				$form_data = $theform->get_data();
				
                for ($i = 0; $i < count($form_data->feedback); $i++) {
                    $task = new stdClass();
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $i;
                    $task->feedback = $form_data->feedback[$i];
                    if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                    }
                }
                $grade = 0;
                break;

            case WEBQUEST_GS_ACCUMULATIVE:
				$theform = new mod_webquest_assessment_accumulative_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 
				if ($theform->is_cancelled()){
					redirect("view.php?id=$cm->id&amp;action=evaluation");
				}
				
				$form_data = $theform->get_data();
				$weight_options = mod_webquest_eweights();
			
                foreach ($form_data->grade as $key => $thegrade) {
                    $task = new stdClass();
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $key;
                    $task->feedback   = $form_data->feedback[$key];
                    $task->grade = $thegrade;
                    if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                        }
                    }
                $rawgrade=0;
                $totalweight=0;
                foreach ($form_data->grade as $key => $grade) {
                    $maxscore = $tasks[$key]->maxscore;
                    $weight = $weight_options[$tasks[$key]->weight];
                    if ($weight > 0) {
                        $totalweight += $weight;
                    }
                    $rawgrade += ($grade / $maxscore) * $weight;
                }
                $grade = 100.0 * ($rawgrade / $totalweight);
                break;

            case WEBQUEST_GS_ERRORBANDED:
				$theform = new mod_webquest_assessment_errorbanded_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 
				if ($theform->is_cancelled()){
					redirect("view.php?id=$cm->id&amp;action=evaluation");
				}
				
				$form_data = $theform->get_data();
				$weight_options = mod_webquest_eweights();
                $error = 0.0;
                
				for ($i =0; $i < count($form_data->feedback); $i++) {
                    $task = new stdClass();
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $i;
                    $task->feedback   = $form_data->feedback[$i];
                    $task->grade = clean_param($form_data->grade[$i], PARAM_CLEAN);
                    if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                    }
                    if (empty($form_data->grade[$i])){
						$weight_options = mod_webquest_eweights();
                        $error += $weight_options[$tasks[$i]->weight];
                    }
                }
		
				$task = new stdClass();
                $i = count($form_data->grade)-1;
                $task->webquestid = $webquest->id;
                $task->sid = $submission->id;
                $task->taskno = $i;
                $task->grade = $form_data->grade[$i];
                if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                    error("Could not insert webquest grade!");
                }
                $grade = ($tasks[intval($error + 0.5)]->maxscore + $form_data->grade[$i]) * 100 / $webquest->grade;
                if ($grade < 0) {
                    $grade = 0;
                } elseif ($grade > 100) {
                    $grade = 100;
                }
                echo "<b>".get_string("weightederrorcount", "webquest", intval($error + 0.5))."</b>\n";
                break;

            case WEBQUEST_GS_CRITERION:
				$theform = new mod_webquest_assessment_criteria_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 
				if ($theform->is_cancelled()){
					redirect("view.php?id=$cm->id&amp;action=evaluation");
				}
				$form_data = $theform->get_data();
				
                $task = new stdClass();
                $task->webquestid = $webquest->id;
                $task->sid = $submission->id;
                $task->taskno = 0;
                $task->grade = $form_data->grade[0];
                if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                    error("Could not insert webquest grade!");
                }
                
				$task = new stdClass();
                $task->webquestid = $webquest->id;
                $task->sid = $submission->id;
                $task->taskno = 1;
                $task->grade = $form_data->grade[1];
                if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                    error("Could not insert webquest grade!");
                }
                $grade = ($tasks[$form_data->grade[0]]->maxscore + $form_data->grade[1]);
                break;

            case WEBQUEST_GS_RUBRIC:
				$theform = new mod_webquest_assessment_rubric_form(null,array('tasks'=>$tasks, 'webquestid'=>$webquest->id)); 
				if ($theform->is_cancelled()){
					redirect("view.php?id=$cm->id&amp;action=evaluation");
				}
				
				$form_data = $theform->get_data();
				$weight_options = mod_webquest_eweights();
				
                foreach ($form_data->grade as $key => $thegrade) {
                   $task = new stdClass();
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $key;
                    $task->feedback = $form_data->feedback[$key];
                    $task->grade = $thegrade;
                    if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                        error("Could not insert webquest grade!");
                    }
                }
                $rawgrade=0;
                $totalweight=0;
                foreach ($form_data->grade as $key => $grade) {
                    $maxscore = $tasks[$key]->maxscore;
                    $weight = $weight_options[$tasks[$key]->weight];
                    if ($weight > 0) {
                        $totalweight += $weight;
                    }
                    $rawgrade += ($grade / $maxscore) * $weight;
                }
                $grade = 100.0 * ($rawgrade / $totalweight);
                break;

        }

		$setcondition = array("id"=>$submission->id);
        $DB->set_field("webquest_submissions", "timegraded", $timenow, $setcondition);


        $DB->set_field("webquest_submissions", "grade", $grade, $setcondition);

        if (!empty($form_data->generalcomment)) {
            $DB->set_field("webquest_submissions", "gradecomment", $form_data->generalcomment, $setcondition);
        }


        if (!$returnto = $form_data->returnto) {
            $returnto = "view.php?id=$cm->id";
        }

        if ($webquest->gradingstrategy) {
            redirect($returnto, get_string("thegradeis", "webquest").": ".
                    number_format($grade * $webquest->grade / 100, 2).
                    " (".get_string("maximumgrade")." ".number_format($webquest->grade).")");
        }
        else {
            redirect($returnto);
        }
    }

    else if($action == 'confirmdeletegrade'){
        if (!mod_webquest_isteacher($course->id)){
            error("Only teachers can look at this page");
        }
		echo $renderer->header();
		echo $renderer->confirm(get_string("suretodelgrade","webquest"), 
			new moodle_url('assessments.php', array('action'=>'dodeletegrade','id'=>$cm->id,'sid'=>$sid)), 
			new moodle_url('view.php', array('id'=>$cm->id,'sid'=>$sid,'action'=>'evaluation')));
			echo $renderer->footer();
			return;
       // notice_yesno(get_string("suretodelgrade","webquest"),
       //      "assessments.php?action=confirmdelgrade&amp;id=$id&amp;sid=$sid", "view.php?id=$id&amp;action=evaluation");
    }

    else if($action == 'dodeletegrade'){
        if (empty($sid)) {
            error("Webquest Submission id missing");
        }else {
            if (!$submission = $DB->get_record("webquest_submissions", array("id"=>$sid))) {
                error ("Submission record not found");
            }
        }
        if(!$DB->delete_records("webquest_grades",array("sid"=>$submission->id))){
            error("could not delete assessment");
        }else {
            $submission->gradecomment = '';
            $submission->timegraded = 0;
            $submission->grade = 0 ;
            if (!$DB->update_record("webquest_submissions",$submission)){
                error("Could not delete assessment");
            }
        }
        unset($submission);
        redirect("view.php?id=$cm->id&amp;action=evaluation");
    }

    elseif ($action == 'viewassesment'){
        $redirect = "view.php?id=$cm->id&amp;action=evaluation";
        webquest_print_assessment($webquest, true, false, false, $redirect,$sid);
        print_continue($redirect);
    }

   // echo $renderer->footer();

?>