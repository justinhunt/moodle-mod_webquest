<?php // $Id: submissions.php,v 1.4 2007/09/09 09:00:20 stronk7 Exp $
    require("../../config.php");
    require("lib.php");
    require("locallib.php");
	require("forms.php");

global $DB;

    $id          = required_param('id', PARAM_INT);    // Course Module ID
    $action      = optional_param('action', '', PARAM_ALPHA);
    $sid         = optional_param('sid', 0, PARAM_INT); //submission id
	$a       	 = optional_param('a', '', PARAM_ALPHA);//webquestid
    //$order       = optional_param('order', 'name', PARAM_ALPHA);
    $title       = optional_param('title', '', PARAM_CLEAN);
    //$nentries    = optional_param('nentries', '', PARAM_ALPHANUM);
    $description = optional_param('description', '', PARAM_CLEAN);

    $timenow = time();

    // get some useful stuff...
    if (! $cm = get_coursemodule_from_id('webquest', $id)) {
        error("Course Module ID was incorrect");
    }
    if (! $course = get_course($cm->course)) {
        error("Course is misconfigured");
    }
    if (! $webquest = $DB->get_record("webquest", array("id"=>$cm->instance))) {
        error("Course module is incorrect");
    }
    require_login($course->id, false, $cm);

    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");
    $strsubmission = get_string("submission", "webquest");
    
   	$context = context_module::instance($cm->id);
	$modulecontext = $context;
	$coursecontext = context_course::instance($course->id);
	
	//only edting teachers can edit tasks
	//require_capability('mod/webquest:addinstance',$coursecontext);

    // ... print the header and...
	$PAGE->set_url('/mod/webquest/view.php', array('id' => $cm->instance));
	$PAGE->set_title($course->shortname . ": " . $webquest->name);
	$PAGE->set_heading($course->fullname);
	$PAGE->set_pagelayout('course');
	$PAGE->set_context($modulecontext);
	
	$renderer = $PAGE->get_renderer('mod_webquest');

	
	/*
    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strsubmission",
                  "", "", true);
*/

$isteacher = mod_webquest_isteacher($course->id);

    if ($action == 'showsubmission' ) {

        if (empty($sid)) {
            error("submission id missing");
        }

        $submission = $DB->get_record("webquest_submissions", array("id"=> $sid));
        $title = '"'.$submission->title.'" ';
        if ($isteacher) {
            if ($webquest->teamsmode == 0){
                $user = $DB->get_record("user",array("id"=>$submission->userid));
                $name = fullname($user, false);
                $by = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$webquest->course\">$name</a>";
            }else{
                $team = $DB->get_record("webquest_teams",array("id"=>$submission->userid));
				if($team){
					$name = $team->name;
					$by = get_string("team","webquest")." :<a href=\"teams.php?id=$cm->id&amp;teamid=$team->id&amp;action=members\">$name</a>";
				}else{
					$by = get_string("invalidteam","webquest");
				}
            }
           $title .= get_string("by","webquest")." ".$by;
        }
		echo $renderer->header();
        echo $renderer->heading($title,5,"main");
        echo '<center>'.get_string('submitted', 'webquest').': '.userdate($submission->timecreated).'</center><br />';
		echo webquest_print_submission($webquest,$submission);
		echo $renderer->continue_button($_SERVER['HTTP_REFERER'].'#sid='.$submission->id) ;
		echo $renderer->footer();
		return;
        //print_continue($_SERVER['HTTP_REFERER'].'#sid='.$submission->id);
    }
	
	elseif ($action == 'addsubmission'){
		$maxfiles = $webquest->nattachments;
		$filemanageroptions = webquest_fetch_filemanager_options($course,$maxfiles);
		$editoroptions = webquest_fetch_editor_options($course, $modulecontext);
		
		//prepare form data
		$target="submission";// this is the form action, string source, and filearea
		$submission = new stdClass();
		$submissionid=null;
		$submission = file_prepare_standard_editor($submission, $target, $editoroptions, $modulecontext,
							 'mod_webquest',$target, $submissionid);
		
		//prepare file area
		$draftitemid = file_get_submitted_draft_itemid('attachments');
		file_prepare_draft_area($draftitemid, $modulecontext->id, 'mod_webquest', 'attachments', $submissionid,
								$filemanageroptions);
		$submission->attachments = $draftitemid;
		
		//prepare id's in hidden variables
		$submission->a = $webquest->id ;
		$submission->id = $cm->id ;
		$submission->sid = $submissionid;
		
		$theform = new mod_webquest_multi_form(null,
			array('target'=>$target,
			'editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions)
		);
		$theform->set_data($submission);
		echo $renderer->header();
		echo $renderer->show_form($theform, get_string("addsubmission", "webquest"));
		echo $renderer->footer();
		return;
	
	}
	
    elseif ($action == 'editsubmission' ) {
        if (empty($sid)) {
            error("Submission id missing");
        }

        $submission = $DB->get_record("webquest_submissions", array("id"=>$sid));
        //print_heading(get_string("editsubmission", "webquest"));
        if ($webquest->teamsmode){
            $userid = $DB->get_record("webquest_team_members",array("teamid"=>$submission->userid,"userid"=>$USER->id));
            if ($submission->userid <> $userid->teamid) {
                error("Wrong user id");
            }
        }else{
            if ($submission->userid <> $USER->id) {
                error("Wrong user id");
            }
        }
       if ($submission->timecreated < ($timenow - $CFG->maxeditingtime)) {
			error(get_string('notallowed', 'webquest'));
        }
		
		$maxfiles = $webquest->nattachments;
		$filemanageroptions = webquest_fetch_filemanager_options($course,$maxfiles);
		$editoroptions = webquest_fetch_editor_options($course, $modulecontext);
		
		//prepare form data
		$target="submission";// this is the form action, string source, and filearea
		$submissionid=$submission->id;
		$submission = file_prepare_standard_editor($submission, $target, $editoroptions, $modulecontext,
							 'mod_webquest',$target, $submissionid);
		
		//prepare file area
		$draftitemid = file_get_submitted_draft_itemid('attachments');
		file_prepare_draft_area($draftitemid, $modulecontext->id, 'mod_webquest', 'attachments', $submissionid,
								$filemanageroptions);
		$submission->attachments = $draftitemid;
		
		//prepare id's in hidden variables
		$submission->a = $webquest->id ;
		$submission->id = $cm->id ;
		$submission->sid = $submissionid;
		
		$theform = new mod_webquest_multi_form(null,
			array('target'=>$target,
			'editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions)
		);
		$theform->set_data($submission);
		

		echo $renderer->header();
		echo $renderer->show_form($theform, get_string("editsubmission", "webquest"));
		echo $renderer->footer();
		return;
		

    }
	
	elseif ($action == 'dosubmission') {

        if (!empty($sid)) {
			$submission = $DB->get_record("webquest_submissions", array("id"=>$sid));
			if ($webquest->teamsmode){
				$userid = $DB->get_record("webquest_team_members",array("teamid"=>$submission->userid,"userid"=>$USER->id));
				$submissionuserid = $userid->teamid;
			}else{
				$submissionuserid = $USER->id;
			}

			// students are only allowed to update their own submission and only up to the deadline
			if (!(($isteacher)or
				   (($userid == $submission->userid) and ($timenow < $webquest->submissionend)
					   and ($timenow < ($submission->timecreated + $CFG->maxeditingtime))))) {
				error("You are not authorized to update your submission");
			}
			
		}else{
			if ($webquest->teamsmode){
				$userid = $USER->id;
				if ($team = $DB->get_record("webquest_team_members",array("webquestid"=>$webquest->id,"userid"=>$userid))){
					$submissionuserid = $team->teamid;
				}else{
					error("You are not a member of a team");
				}
			}else {
				$submissionuserid = $USER->id;
			}
		
		}
		
		$maxfiles = $webquest->nattachments;
		$filemanageroptions = webquest_fetch_filemanager_options($course,$maxfiles);
		$editoroptions = webquest_fetch_editor_options($course,$modulecontext);
		$itemid = $sid; 
		$target='submission';
		$theform = new mod_webquest_multi_form(null,
			array('target'=>$target,
			'editoroptions'=>$editoroptions, 
			'filemanageroptions'=>$filemanageroptions)
		);
		
        if ( $theform->is_cancelled()){
            redirect("view.php?id=$cm->id&amp;action=evaluation");
        }
       
	    $form_data = $theform->get_data();
	   

		$newsubmission = new stdClass();
		if ($sid && $DB->record_exists("webquest_submissions",array("id"=>$sid,'userid'=>$submissionuserid))){
			
			//get our embedded media etc in the right place
			 $form_data = file_postupdate_standard_editor( $form_data, $target, $editoroptions, $modulecontext,
                                        'mod_webquest', $target, $sid);
		
			$newsubmission->id = $sid;
            $newsubmission->title = $form_data->title;
            $newsubmission->submission = $form_data->submission;
			$newsubmission->submissionformat = $form_data->submissionformat;
			$newsubmission->userid = $submissionuserid;

            if ($DB->update_record("webquest_submissions",$newsubmission)){
                error("Could not update webquest submission!");
                redirect("view.php?id=$cm->id&amp;action=evaluation");
            }
			//save files
			file_save_draft_area_files($form_data->attachments, $modulecontext->id, 'mod_webquest', 'attachments',
                   $sid, $filemanageroptions);
            redirect("view.php?id=$cm->id&amp;action=evaluation", get_string("wellsaved","webquest"));
       
	   }else{

            $newsubmission->title = $form_data->title;
			$newsubmission->webquestid = $form_data->a;
            $newsubmission->submission = '';//not available yet
			$newsubmission->submissionformat = 1;//not available yet
			$newsubmission->userid = $submissionuserid;
			$newsubmission->timecreated = time();
			$newsubmission->gradecomment = '';

            if (!$sid = $DB->insert_record("webquest_submissions",$newsubmission)){
                error("Could not insert webquest submission!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
			
			//get our embedded media etc in the right place, now that we have a item id
			//ala we have to resave our submission data with the correct 
			 $form_data = file_postupdate_standard_editor( $form_data, $target, $editoroptions, $modulecontext,
                                        'mod_webquest', $target, $sid);
			$newsubmission->id = $sid;
			$newsubmission->submission = $form_data->submission;
			$newsubmission->submissionformat = $form_data->submissionformat;
			if (!$DB->update_record("webquest_submissions",$newsubmission)){
                error("Could not insert/update webquest submission!");
                redirect("view.php?id=$cm->id&amp;action=evaluation");
            }
		
			//save files
			file_save_draft_area_files($form_data->attachments, $modulecontext->id, 'mod_webquest', 'attachments',
                   $sid, $filemanageroptions);
            redirect("view.php?id=$cm->id&amp;action=evaluation", get_string("wellsaved","webquest"));

        }
    }

	  ///////////Delete Resource////////////////////////////
    elseif($action == 'confirmdelete'){

		echo $renderer->header();
		echo $renderer->confirm(get_string("confirmsubmissiondelete","webquest"), 
			new moodle_url('submissions.php', array('action'=>'delete','id'=>$cm->id,'sid'=>$sid)), 
			new moodle_url('view.php', array('id'=>$cm->id,'action'=>'evaluation')));
		echo $renderer->footer();
		return;

    }

    /////// Delete Resource NOW////////
    if ($action == 'delete'){

		 $submission = $DB->get_record("webquest_submissions", array("id"=>$sid));
        if ($webquest->teamsmode){
            $userid = $DB->get_record("webquest_team_members",array("teamid"=>$submission->userid,"userid"=>$USER->id));
            $userid = $userid->teamid;
        }else{
            $userid = $USER->id;
        }
        if (!(($isteacher)or
               (($userid = $submission->userid) and ($timenow < $webquest->submissionend)
                   and ($timenow < ($submission->timecreated + $CFG->maxeditingtime))))) {
            error("You are not authorized to delete submission");
        }
        if ($DB->count_records("webquest_grades",array("sid"=>$submission->id))){
            if(!$DB->delete_records("webquest_grades",array("sid"=>$submission->id))){
                error("Could not delete grades for this submission");
            }
        }

        if (!$DB->delete_records("webquest_submissions", array("id"=>$sid))){
            error("Could not delete submission");
        }
		//remove files
		$fs= get_file_storage();
		$fs->delete_area_files($modulecontext->id,'mod_webquest','submission',$sid);
        redirect("view.php?id=$cm->id&amp;action=evaluation");
	
    }

    elseif ($action == 'assess') {
	
        $submission = $DB->get_record("webquest_submissions", array("id"=> $sid));
        // there can be an assessment record (for teacher submissions), if there isn't...
        if (!$assessments = $DB->get_records("webquest_grades", array("sid"=> $submission->id))) {
                $graded = false;
                $submission->grade = -1; // set impossible grade
                $submission->timegraded = 0;
                if (!$DB->update_record("webquest_submissions", $submission)) {
                    error("Could not insert webquest assessment!");
                }
                // if it's the teacher and the webquest is error banded set all the elements to Yes
                if ($isteacher and ($webquest->gradingstrategy == 2)) {
                    $graded = true;
					
                    for ($i =0; $i < $webquest->ntasks; $i++) {
                        $task = new stdClass();
                        $task->webquestid = $webquest->id;
                        $task->sid = $submission->id;
                        $task->taskno = $i;
                        $task->feedback = '';
                        $task->grade = 1;
                        if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                            error("Could not insert Webquest grade!");
                        }
                    }
                    // now set the adjustment
                    $task = new stdClass();
                    $i = $webquest->ntasks;
                    $task->webquestid = $webquest->id;
                    $task->sid = $submission->id;
                    $task->taskno = $i;
                    $task->grade = 0;
                    if (!$task->id = $DB->insert_record("webquest_grades", $task)) {
                        error("Could not insert Webquest grade!");
                    }
                }
        }else {
            $graded = true;
        }
		
		//Modify Justin 20140829 . This is the beginnning of the assessments forms
		//for now we just show the grading commentsform. basically look at webquest_print_assessment
		//and remake the forms in mforms. Then add logic to save the same.
		//to make it look good, for now, we disable this, and enable the old logic
		redirect("assessments.php?id=$cm->id&amp;action=assessment&amp;sid=$submission->id&amp;a=$webquest->id&amp;graded=$graded");
		return;
		
		echo $renderer->header();
		echo $renderer->heading_with_help(get_string("assessthissubmission", "webquest"), "grading", "webquest");
        $redirect = "view.php?id=$cm->id&amp;action=evaluation";
        // show assessment and allow changes
        webquest_print_assessment($webquest, $graded, true, true, $redirect,$sid);
		echo $renderer->footer();
    }

?>