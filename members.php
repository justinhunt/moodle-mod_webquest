<?php  // $Id: teams.php,v 1.3 2007/09/09 09:00:20 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
	require_once("forms.php");
    require_once("locallib.php");
	require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
	//require_once($CFG->dirroot.'/'.$CFG->admin.'/user/user_bulk_forms.php');

	global $DB;
		

    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $teamid = optional_param('teamid',0,PARAM_INT);
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
	$memberteam = $DB->get_record('webquest_teams', array('id' => $teamid), '*', MUST_EXIST);

    require_login($course->id, false, $cm);

	if (!isset($SESSION->bulk_users)) {
		$SESSION->bulk_users = array();
	}
	
	// create the user filter form
	$ufiltering = new user_filtering();
	
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
		   add_to_log($course->id, "webquest", "update members", "view.php?id=$cm->id",$webquest->name, $cm->id);
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
	
//handle the membership update form
	$mod_webquest_updatemembers_form = new mod_webquest_updatemembers_form();
   if($action == 'domembers'){
        if (!$isteacher){
            error("Only teachers can look at this page");
        }
		
		if($mod_webquest_updatemembers_form->is_cancelled()){
			//clear the selections and move bac to teams page
			$SESSION->bulk_users = array();
			redirect("view.php?id=$cm->id&amp;action=teams");
		}
		$form_data = $mod_webquest_updatemembers_form->get_data();
		 if($DB->delete_records("webquest_team_members", array("teamid"=>$teamid))){
			list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
			$rs = $DB->get_recordset_select('user', "id $in", $params);
			foreach ($rs as $user) {
				$member = new stdClass();
				$member->teamid = $form_data->teamid;
				$member->webquestid = $form_data->a;
				$member->userid=$user->id;
				if(!$DB->insert_record("webquest_team_members", $member)){
					error("Could not update this webquest team members!");
					redirect("view.php?id=$cm->id&amp;action=teams");
				}
				unset($SESSION->bulk_users[$user->id]);
			}
			redirect("view.php?id=$cm->id&amp;action=teams", get_string("wellsaved","webquest"));
        }else{
            error("Could not update this team's members!");
            redirect("view.php?id=$cm->id&amp;action=teams");
        }
    }
	
	//if we are arriving from elsewhere clear the bulk users and add current team members
	if($action=='members'){
		$SESSION->bulk_users = array();
		//add existing members here
		$members = $DB->get_records("webquest_team_members", array('webquestid'=>$webquest->id,'teamid'=>$memberteam->id));
		if($members){
			foreach ($members as $member){
				$SESSION->bulk_users[] = $member->userid;
			}
		}
		
	}
	
	
//handle the select form	
$mod_webquest_members_form = new mod_webquest_members_form(null, get_selection_data($ufiltering));
if ($data = $mod_webquest_members_form->get_data()) {
    if (!empty($data->addall)) {
        add_selection_all($ufiltering);

    } else if (!empty($data->addsel)) {
        if (!empty($data->ausers)) {
            if (in_array(0, $data->ausers)) {
                add_selection_all($ufiltering);
            } else {
                foreach($data->ausers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    if (!isset($SESSION->bulk_users[$userid])) {
                        $SESSION->bulk_users[$userid] = $userid;
                    }
                }
            }
        }

    } else if (!empty($data->removeall)) {
        $SESSION->bulk_users= array();

    } else if (!empty($data->removesel)) {
        if (!empty($data->susers)) {
            if (in_array(0, $data->susers)) {
                $SESSION->bulk_users= array();
            } else {
                foreach($data->susers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    unset($SESSION->bulk_users[$userid]);
                }
            }
        }
    }

    // reset the form selections
    unset($_POST);
    $mod_webquest_members_form = new mod_webquest_members_form(null, get_selection_data($ufiltering));
}

// do output
echo $renderer->header();

//show these to show user filtering options for select list
//$ufiltering->display_add();
//$ufiltering->display_active();
$form_data = new stdClass();
$form_data->id = $cm->id;
$form_data->a = $webquest->id;
$form_data->teamid = $teamid;
$mod_webquest_members_form->set_data($form_data);
$mod_webquest_updatemembers_form->set_data($form_data);

echo $renderer->show_form($mod_webquest_members_form,get_string('editingmembers','webquest',$memberteam->name));
echo $renderer->show_form($mod_webquest_updatemembers_form,'');
echo $renderer->footer();
