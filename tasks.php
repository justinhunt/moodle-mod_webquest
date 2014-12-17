<?php  // $Id: tasks.php,v 1.3 2007/09/09 09:00:20 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
	require_once("forms.php");
    require_once("locallib.php");

	const WEBQUEST_DEF_TASKWEIGHT = 11;
	
	global $DB,$CFG, $PAGE, $OUTPUT;

    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $cancel = optional_param('cancel',0,PARAM_INT);  
	$add_fields = optional_param('add_fields','',PARAM_TEXT); 
	


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
	

	$context = context_module::instance($cm->id);
	$modulecontext = $context;
	$coursecontext = context_course::instance($course->id);
	
	//only edting teachers can edit tasks
	require_capability('mod/webquest:addinstance',$coursecontext);
	
	/// Print the page header
	$PAGE->set_url('/mod/webquest/tasks.php', array('id' => $id,"a"=>$a,"action"=>$action,"cancel"=>$cancel));
	$PAGE->set_title(format_string($webquest->name));
	$PAGE->set_pagelayout('course');
	//$PAGE->set_heading(format_string($course->fullname));

	$PAGE->set_context($context);

    $strtasks = get_string("tasks", "webquest");
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests =  get_string("modulenameplural", "webquest");


    /*
	print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strtasks",
                  "", "", true);

	*/

// add_to_log($course->id, "webquest", "update tasks", "view.php?id=$cm->id", "$webquest->id");
   
    //Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, 'webquest', "update tasks", "view.php?id={$cm->id}", $webquest->name, $cm->id);
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


//set up our renderer	
$renderer = $PAGE->get_renderer('mod_webquest');


    //*********************************************edit tasks**********************
    if ($action == 'edittasks' || ($action=='doedittasks' && $add_fields!='')){
        if (!mod_webquest_isteacher($course->id)) {
            error("Only teachers can look at this page"); /// is trying to get access but not allowed jejejeje
        }
        $count = $DB->count_records("webquest_grades", array("webquestid"=>$webquest->id));
        if ($count) {
            notify(get_string("warningtask", "webquest"));
        }
     ///// setup a form to edit tasks
	// echo $renderer->heading_with_help(get_string("edittasks", "webquest"), 'tasks', 'webquest');
     // print_heading_with_help(get_string("edittasks", "webquest"), "tasks", "webquest");
    

        // get existing tasks, if none set up appropriate default ones
		$tasks=array();
        if ($tasksraw = $DB->get_records("webquest_tasks", array("webquestid"=>$webquest->id), "taskno ASC" )) {
            foreach ($tasksraw as $task) {
                $tasks[] = $task;   // to renumber index 0,1,2...
            }
        }
        // check for missing tasks (this happens either the first time round or when the number of tasks is increased)
            if (count($tasks)<1 || $add_fields !='') {
				$task = new stdClass;
                $task->description = '';
                $task->scale =0;
                $task->maxscore = 0;
                $task->weight = WEBQUEST_DEF_TASKWEIGHT;
				$tasks[]=$task;
         }

		
		//prepare form data : simple elements
		$form_data=new stdClass();
		$form_data->a = $webquest->id ;
		$form_data->id = $cm->id ;
		
		//prepare form data: repeated elements
		$descriptions = array();
		$scales = array();
		$weights = array();
		$maxscore = array();
		foreach($tasks as $task){
			$descriptions[] = $task->description;
			$scales[] = $task->scale;
			$weights[] = $task->weight;
			$maxscore[] = $task->maxscore;
		}
		$form_data->description=$descriptions;
		$form_data->scale=$scales;
		$form_data->weight=$weights;
		$form_data->maxscore=$maxscore;
		
		//prepare form data: repeated elements(rubrics)
		if ($rubricsraw = $DB->get_records("webquest_rubrics", array("webquestid"=>$webquest->id))) {
			foreach ($rubricsraw as $rubric) {
				switch($rubric->rubricno){
					case 0: $rubric1s[$rubric->taskno] = $rubric->description;break;
					case 1: $rubric2s[$rubric->taskno] = $rubric->description;break;
					case 2: $rubric3s[$rubric->taskno] = $rubric->description;break;
					case 3: $rubric4s[$rubric->taskno] = $rubric->description;break;
					case 4: $rubric5s[$rubric->taskno] = $rubric->description;break;
					
				}
			}
			$form_data->rubric1 = $rubric1s;
			$form_data->rubric2 = $rubric2s;
			$form_data->rubric3 = $rubric3s;
			$form_data->rubric4 = $rubric4s;
			$form_data->rubric5 = $rubric5s;
         }
		$theform = new mod_webquest_task_form(null,array('fieldcount'=>count($tasks),'webquestgrade'=>$webquest->grade,'gradingstrategy'=>$webquest->gradingstrategy));
		$theform->set_data($form_data);
		echo $renderer->header();
		echo $renderer->show_form($theform,get_string("edittasks", "webquest"));
		echo $renderer->footer();
		return;
	
 ///////////Insert tasks////////////////////////////
   } elseif ($action == 'doedittasks') {

        if (!mod_webquest_isteacher($course->id)) {
            error("Only teachers can look at this page"); ///not allowed if isn't a teacher
        }

		//we need to supply config to form to get data from it, but the fieldcount is bogus
		$theform = new mod_webquest_task_form(null,array('fieldcount'=>1,'webquestgrade'=>$webquest->grade,'gradingstrategy'=>$webquest->gradingstrategy));
		if ($theform->is_cancelled()){
            redirect("view.php?id=$cm->id&amp;action=tasks");
        }
        $form_data = $theform->get_data();

          // delete all rubrics and re-add (this was the original 1.9 system, unchanged)
        $DB->delete_records("webquest_tasks", array("webquestid"=>$webquest->id));

        // determine which type of grading
        switch ($webquest->gradingstrategy) {
            case WEBQUEST_GS_NONE : // no grading
                // Insert all the tasks that contain something
				$taskno=0;
                foreach ($form_data->description as $key => $description) {
                    if ($description) {
						 $task = new stdClass();
                        $task->description   = $description;
                        $task->webquestid = $webquest->id;
                        $task->taskno = $taskno=0;;
						$taskno++;
                        if (!$task->id = $DB->insert_record("webquest_tasks", $task)) {
                            error("Could not insert webquest task!");
                        }
                    }
                }
                break;

            case WEBQUEST_GS_ACCUMULATIVE : // accumulative grading
                // Insert all the tasks that contain something
				$taskno=0;
                for ($key=0;$key<count($form_data->description);$key++) {
                    if ($form_data->description[$key]) {
                        $task = new stdClass();
                        $task->description = $form_data->description[$key];
                        $task->webquestid = $webquest->id;
                        $task->taskno = $taskno;
						$taskno++;
                        if (isset($form_data->scale[$key])) {
                            $task->scale = $form_data->scale[$key];
							$scales=mod_webquest_scales();
                            switch ($scales[$form_data->scale[$key]]['type']) {
                                case 'radio' :  $task->maxscore = $scales[$form_data->scale[$key]]['size'] - 1;
                                                        break;
                                case 'selection' :  $task->maxscore = $scales[$form_data->scale[$key]]['size'];
                                                        break;
                            }
                        }
                        if (isset($form_data->weight[$key])) {
                            $task->weight = $form_data->weight[$key];
                        }
						
                        if (!$task->id = $DB->insert_record("webquest_tasks", $task)) {
                            error("Could not insert webquest task!");
                        }
                    }
                }
                break;

            case WEBQUEST_GS_ERRORBANDED : // error banded grading...
            case WEBQUEST_GS_CRITERION : // ...and criterion grading
                // Insert all the elements that contain something, the number of descriptions is one less than the number of grades
				$taskno = 0;
				foreach ($form_data->maxscore as $key => $themaxscore) {
                     $task = new stdClass();
                    $task->webquestid = $webquest->id;
                    $task->taskno = $taskno;
					$taskno++;
                    $task->maxscore = $themaxscore;
                    if (isset($form_data->description[$key])) {
                        $task->description   = $form_data->description[$key];
                    }else{
						$task->description = '';
					}
					
					//Scale. For errorbanded this must be yes/no. I think criterion doesn't care
					$task->scale = 0; //this is hardcoded to yes/no radio
					
                    if (isset($form_data->weight[$key])) {
                        $task->weight = $form_data->weight[$key];
                    }else{
						$task->weight = WEBQUEST_DEF_TASKWEIGHT;
					}
                    if (!$task->id = $DB->insert_record("webquest_tasks", $task)) {
                        error("Could not insert webquest task!");
                    }
					//Justin 2014.09.12 there appears to be a bug in moodle here, only the repeat elements are counted
					//but this array will be one more than the repeats if we are on the error margin screen
					////so tried this, but it blew everything up, so I just made errorcounts same as taskcounts on form....
					/*
					$postmaxscores = $_POST['maxscore'];
					if(count($postmaxscores)>$taskno){
						 $task = new stdClass();
						$task->webquestid = $webquest->id;
						$task->taskno = $taskno;
						$task->description = '';
						$task->weight=WEBQUEST_DEF_TASKWEIGHT;
						$task->maxscore = intval($postmaxscores[$taskno]);
						 if (!$task->id = $DB->insert_record("webquest_tasks", $task)) {
							error("Could not insert webquest task!");
						}
					}
					*/
					
					
                }
                break;

            case WEBQUEST_GS_RUBRIC: // ...and criteria grading
                // Insert all the elements that contain something
				$taskno = 0;
                foreach ($form_data->description as $key => $description) {
					  if ($form_data->description[$key]) {
						$task = new stdClass();
						$task->webquestid = $webquest->id;
						$task->taskno = $taskno;
						$taskno++;
						$task->description   = $description;
						$task->weight = $form_data->weight[$key];
						for ($j=0;$j<5;$j++) {
							if (empty($form_data->rubric[$key][$j]))
								break;
						}
						$task->maxscore = $j - 1;
						if (!$task->id = $DB->insert_record("webquest_tasks", $task)) {
							error("Could not insert webquest task!");
						}
					}//end of if have task descr
                }//end of fore each
                // delete all rubrics and re-add (this was the original 1.9 system way of doing it)
                $DB->delete_records("webquest_rubrics", array("webquestid"=>$webquest->id));
               // for ($i=0;$i<$webquest->ntasks;$i++) {
			   $taskno=0;
			   for($i=0;$i<$form_data->repeats;$i++){
					if(!$form_data->description[$i]){
							continue;
					}else{
						$taskno++;
					}

                    for ($j=0;$j<5;$j++) {						
                        $rubric = new stdClass();
						switch($j){
							case 0: $rubric->description = $form_data->rubric1[$i];break;
							case 1: $rubric->description = $form_data->rubric2[$i];break;
							case 2: $rubric->description = $form_data->rubric3[$i];break;
							case 3: $rubric->description = $form_data->rubric4[$i];break;
							case 4: $rubric->description = $form_data->rubric5[$i];break;
						
						}
                        if (empty($rubric->description)) {  // OK to have an element with fewer than 5 items
                            break;
                        }
                        $rubric->webquestid = $webquest->id;
                        $rubric->taskno = $taskno-1;
                        $rubric->rubricno = $j;
                        if (!$rubric->id = $DB->insert_record("webquest_rubrics", $rubric)) {
                            error("Could not insert webquest rubric!");
                        }
                    }
                }
                break;
        } // end of switch
        if (!$DB->count_records("webquest_resources",array("webquestid"=>$webquest->id))){
            redirect("view.php?id=$cm->id&amp;action=process",get_string("wellsaved","webquest"));
        }
        else {
            redirect("view.php?id=$cm->id&amp;action=tasks", get_string("wellsaved","webquest"));
        }
    }
?>