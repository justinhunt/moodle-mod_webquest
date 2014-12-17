<?php  // $Id: teams.php,v 1.3 2007/09/09 09:00:20 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
	require_once("forms.php");
    require_once("locallib.php");
	
	global $DB;
		
    define("MAX_USERS_PER_PAGE", 5000);
    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $teamid = optional_param('teamid',0,PARAM_INT);
    $cancel = optional_param('cancel',0,PARAM_INT);
    $add            = optional_param('add', 0, PARAM_BOOL);
    $remove         = optional_param('remove', 0, PARAM_BOOL);
    $showall        = optional_param('showall', 0, PARAM_BOOL);
    $searchtext     = optional_param('searchtext', '', PARAM_RAW); // search string
    $previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);
    $previoussearch = ($searchtext != '') or ($previoussearch) ? 1:0;


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

    $strteams    = get_string("teams", "webquest");
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests=  get_string("modulenameplural", "webquest");

	$context = context_module::instance($cm->id);
	$modulecontext = $context;
	$coursecontext = context_course::instance($course->id);
	
	$PAGE->set_url('/mod/webquest/view.php', array('id' => $cm->instance));
	$PAGE->set_title($course->shortname . ": " . $webquest->name);
	$PAGE->set_heading($course->fullname);
	$PAGE->set_pagelayout('course');
	$PAGE->set_context($modulecontext);
	
	$renderer = $PAGE->get_renderer('mod_webquest');
	//echo $renderer->header();


  	//Diverge logging logic at Moodle 2.7
	if($CFG->version<2014051200){
		// add_to_log($course->id, "webquest", "update teams", "view.php?id=$cm->id", "$webquest->id");
		   add_to_log($course->id, "webquest", "update teams", "view.php?id=$cm->id",$webquest->name, $cm->id);
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


	$isteacher = mod_webquest_isteacher($cm->course);
	
    if ($action == 'editteam') {
        if (!$isteacher){
            error("Only teachers can look at this page");
        }
		$theform = new mod_webquest_teams_form();
        $team= $DB->get_record("webquest_teams",array("id"=>$teamid,"webquestid"=>$webquest->id) );
		$form_data = new stdClass();
		if($team){
			$form_data->name = $team->name;
			$form_data->description = $team->description;
			$form_data->teamid = $team->id;
			$heading  =get_string("editteam", "webquest");
		}else{
			$form_data->name ="";
			$form_data->description = "";
			$heading  =get_string("insertteam", "webquest");
		}
		$form_data->id = $cm->id;
		$form_data->a = $webquest->id;
		
		$theform->set_data($form_data);
		echo $renderer->header();
		echo $renderer->show_form($theform,$heading);
		echo $renderer->footer();
		return;

    }

    if ($action == 'doeditteam'){
        if (!$isteacher){
            error("Only teachers can look at this page");
        }
        $theform = new mod_webquest_teams_form();
		if ($theform->is_cancelled()){
            redirect("view.php?id=$cm->id&amp;action=teams");
        }
		$form_data = $theform->get_data();
        $team = new stdClass();
		$team->name = $form_data->name;
         $team->description = $form_data->description;
        if ($DB->record_exists("webquest_teams",array("id"=>$form_data->teamid))){   
            $team->id = $teamid;
            if (!$DB->update_record("webquest_teams",$team)){
                error("Could not update webquest team!");
                redirect("view.php?id=$cm->id&amp;action=teams");
            }
            redirect("view.php?id=$cm->id&amp;action=teams", get_string("wellsaved","webquest"));
        }else{
			$team->webquestid = $webquest->id;
			if (!$team->id = $DB->insert_record("webquest_teams",$team)){
				error("Could not insert webquest team!");
				redirect("view.php?id=$cm->id&amp;action=teams");
			}
			redirect("view.php?id=$cm->id&amp;action=teams", get_string("wellsaved","webquest"));
		}
	}

    if ($action == 'confirmdeleteteam'){
        if (!mod_webquest_isteacher($course->id)){
            error("Only teachers can look at this page");
        }
		echo $renderer->header();
		echo $renderer->confirm(get_string("suretodelteam","webquest"), 
			new moodle_url('teams.php', array('action'=>'dodeleteteam','id'=>$cm->id,'teamid'=>$teamid)), 
			new moodle_url('view.php', array('id'=>$cm->id,'action'=>'teams')));
			echo $renderer->footer();
			return;
		/*	
        notice_yesno(get_string("suretodelteam","webquest"),
             "teams.php?action=deleteyesteam&amp;id=$id&amp;teamid=$teamid", "view.php?id=$id&amp;action=teams");
			 */
    }

    if ($action == 'dodeleteteam'){
        if(!$isteacher){
            error("Only teachers can look at this page");
        }
        if($DB->delete_records("webquest_team_members", array("teamid"=>$teamid))){
            if($DB->delete_records("webquest_teams", array("id"=>$teamid))){
                $DB->delete_records("webquest_submissions",array("webquestid"=>$webquest->id,"userid"=>$teamid));
                redirect("view.php?id=$cm->id&amp;action=teams", get_string("deleted","webquest"));
            }else{
                error("Could not delete this webquest team!");
                redirect("view.php?id=$cm->id&amp;action=teams");
            }
        }else{
            error("Could not delete this team's members!");
            redirect("view.php?id=$cm->id&amp;action=teams");
        }
    }

