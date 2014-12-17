<?php  // $Id: view.php,v 1.4 2007/09/09 09:00:21 stronk7 Exp $

/// This page prints a particular instance of webquest

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
	require_once("forms.php");

	
global $DB,$CFG, $PAGE, $OUTPUT;
	
    $id      = required_param('id', PARAM_INT);    // Course Module ID, or
    $a       = optional_param('a', '', PARAM_ALPHA);
    $action  = optional_param('action', '', PARAM_ALPHA);     ///action to view the instance.

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
	
    //add_to_log($course->id, "webquest", "view ".$action, "view.php?id=$cm->id", "$webquest->id");
    //Diverge logging logic at Moodle 2.7
	if($CFG->version<2014051200){
		add_to_log($course->id, 'webquest', "view ".$action, "view.php?id={$cm->id}", $webquest->name, $cm->id);
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
    

    $straction = ($action) ? '-> '.get_string($action, 'webquest') : '';
/// Print the page header

    if ($course->category) {
		$PAGE->navbar->add($course->shortname,null,null,navigation_node::TYPE_CUSTOM, new moodle_url($CFG->wwwroot . "/course/view.php?id=$course->id"));
    } else {
        $navigation = '';
    }

    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");
/*
    print_header("$course->shortname: $webquest->name", "$course->fullname",
                "$navigation <a href=index.php?id=$course->id>$strwebquests</a> -> $webquest->name",
                "", "", true, update_module_button($cm->id, $course->id, $strwebquest),
                navmenu($course, $cm));
*/
$PAGE->set_url('/mod/webquest/view.php', array('id' => $cm->instance));
$PAGE->set_title($course->shortname . ": " . $webquest->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_context($modulecontext);


/// Print the main part of the page
	$isteacher = mod_webquest_isteacher($course->id);
    if ($isteacher){
        if (empty($action)){
            if ($DB->count_records("webquest_tasks", array("webquestid"=>$webquest->id)) >= $webquest->ntasks) {
                $action = "introduction";
            }else{
                redirect("tasks.php?action=edittasks&id=$cm->id");
            }
        }
	}

//set up our renderer	
$renderer = $PAGE->get_renderer('mod_webquest');
echo $renderer->header();
	
    if (!mod_webquest_isguest() && !$isteacher){
        if (!$cm->visible){
            notice(get_string("activityiscurrentlyhidden"));
        }else if(empty($action)){
            $action = "introduction";
        }
    }elseif (mod_webquest_isguest()){ // he is a guest. Not allowed
        $action = 'notavailable';
    }


    if($action == 'notavailable'){
        notice(get_string("notavailable"));
    }
    
	echo $renderer->topmenu($action, $isteacher, $cm, $webquest);
	
    echo $renderer->footer();
