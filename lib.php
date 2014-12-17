<?php  // $Id: lib.php,v 1.5 2007/09/09 09:00:19 stronk7 Exp $

/// Library of functions and constants for module webquest

require_once($CFG->libdir.'/filelib.php');

/**
 * List of features supported in Page module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function webquest_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

function webquest_add_instance($webquest) {
global $DB;
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will create a new instance and return the id number
/// of the new instance.


    $webquest->timemodified = time();
    //Encode password if necessary
    if (!empty($webquest->password)){
       $webquest->password = md5($webquest->password);
    }  else unset($webquest->password);
/*
    $webquest->submissionstart = make_timestamp($webquest->submissionstartyear,
            $webquest->submissionstartmonth, $webquest->submissionstartday, $webquest->submissionstarthour,
            $webquest->submissionstartminute);
    $webquest->submissionend = make_timestamp($webquest->submissionendyear,
            $webquest->submissionendmonth, $webquest->submissionendday, $webquest->submissionendhour,
            $webquest->submissionendminute);
*/
    if (!webquest_check_dates($webquest)) {
        throw new moodle_exception( get_string('invaliddates', 'webquest'));
    }else{
        $id = $DB->insert_record("webquest", $webquest);
		return $id;
    }
	
}


function webquest_update_instance($webquest) {
	global $DB;
    $webquest->timemodified = time();
    $webquest->id = $webquest->instance;

    //Encode password if necessary
    if (!empty($webquest->password)){
       $webquest->password = md5($webquest->password);
    } else unset($webquest->password);
	/*
    $webquest->submissionstart = make_timestamp($webquest->submissionstartyear,
            $webquest->submissionstartmonth, $webquest->submissionstartday, $webquest->submissionstarthour,
            $webquest->submissionstartminute);
    $webquest->submissionend = make_timestamp($webquest->submissionendyear,
            $webquest->submissionendmonth, $webquest->submissionendday, $webquest->submissionendhour,
            $webquest->submissionendminute);
			*/
     if (!webquest_check_dates($webquest)){
       return get_string('invaliddates', 'webquest');
     }

    return $DB->update_record("webquest", $webquest);
}


function webquest_delete_instance($id) {
/// Given an ID of an instance of this module,
/// delete the instance and any data that depends on it.
    global $CFG;

    if (! $webquest = get_record("webquest", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("webquest", "id", "$webquest->id")) {
        $result = false;
    }
    if (!delete_records("webquest_resources", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_tasks", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_rubrics", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_grades", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_teams", "webquestid", "$webquest->id")){
        $result = false;
    }
    if (!delete_records("webquest_team_members", "webquestid", "$webquest->id")){
        $result = false;
    }
    if ($submissions = get_records("webquest_submissions", "webquestid", "$webquest->id")){
        foreach ($submissions as $submission){
            $dirpath = "$CFG->dataroot/$webquest->course/$CFG->moddata/webquest/$submission->id";
            fulldelete($dirpath);
        }
    }
    if (!delete_records("webquest_submissions", "webquestid", "$webquest->id")){
        $result = false;
    }
    return $result;
}

function webquest_user_outline($course, $user, $mod, $webquest) {
/// Return a small object with summary information about what a
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function webquest_user_complete($course, $user, $mod, $webquest) {
/// Print a detailed representation of what a  user has done with
/// a given particular instance of this module, for user activity reports.

    return true;
}

function webquest_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity
/// that has occurred in webquest activities and print it out.
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false
}

function webquest_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such
/// as sending out mail, toggling flags etc ...

    global $CFG;

    return true;
}

function webquest_grade_item_update($modinstance, $grades=NULL){
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($webquest->courseid)) {
        $webquest->courseid = $webquest->course;
    }

    $params = array('itemname'=>$webquest->name, 'idnumber'=>$webquest->cmidnumber);

        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $webquest->grade;
        $params['grademin']  = 0;

		//could do this if using feedback .. see mod_assign for how to 
        //$params['gradetype'] = GRADE_TYPE_TEXT;


    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/webquest',
                        $webquest->course,
                        'mod',
                        'webquest',
                        $webquest->id,
                        0,
                        $grades,
                        $params);

}
function webquest_update_grades($modinstance, $userid=0, $nullifnone=true){
  global $CFG;
    require_once($CFG->libdir.'/gradelib.php');


    if ($grades = webquest_get_user_grades($scorm, $userid)) {
        webquest_grade_item_update($webquest, $grades);
    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        webquest_grade_item_update($webquest, $grade);
    } else {
        webquest_grade_item_update($webquest);
    }
}


function webquest_get_user_grades($webquest,$userid=0) {

    $grades = array();
        if ($webquest->gradingstrategy > 0){
            if(!$webquest->teamsmode){
                if ($students = get_course_students($webquest->course)){
                    foreach ($students as $student) {
						//quick dodge to get single student grades
						if($userid!=0 && $student->id!=$userid){continue;}
						
						$grade = new stdClass();
                        $grade->id = $student->id;
						$grade->userid = $student->id; 
						$submission = get_record("webquest_submissions","webquestid",$webquest->id,"userid",$student->id);
                        if (count_records("webquest_grades","sid",$submission->id)){
                            $grade->rawgrade = number_format($submission->grade * $webquest->grade / 100);
                        }else{
                            $grade->rawgrade = null;
                        }
                        $grades[$student->id] = $grade;
                    }
                }
            }else{
                if ($students = get_course_students($webquest->course)){
                    if($submissionsraw = get_records("webquest_submissions","webquestid",$webquest->id)){
                        require_once("locallib.php");
                        foreach($submissionsraw as $submission){
                            if (count_records("webquest_grades","sid",$submission->id)){
                                $gradescore = number_format($submission->grade * $webquest->grade / 100);
                            }else{
                                $gradescore = null;
                            }
                            if($membersid = webquest_get_team_members($submission->userid)){
                                foreach($membersid as $memberid){
									//quick dodge to get single student grades
									if($userid!=0 && $memberid!=$userid){continue;}
									
									$grade = new stdClass();
									$grade->id = $memberid;
									$grade->userid = $memberid;
									$grade->rawgrade = $gradescore;
                                    $grades[$memberid] = $grade;
                                }
                            }
                        }
                    }
                }
            }
        }

    return $grades;
}

function webquest_get_participants($webquestid) {
//Must return an array of user records (all data) who are participants
//for a given instance of webquest. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function webquest_scale_used ($webquestid,$scaleid) {
//This function returns if a scale is being used by one webquest
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.

    $return = false;

    //$rec = get_record("webquest","id","$WebQuestid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other WebQuest functions go here.
function webquest_check_dates($webquest) {
    // allow submission and assessment to start on the same date and to end on the same date
    // but enforce non-empty submission period and non-empty assessment period.
    return ($webquest->submissionstart < $webquest->submissionend);
}

/**
 * Serves the web files.
 *
 * @package  mod_page
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function webquest_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
	
	$itemid = (int)array_shift($args);

    require_course_login($course, true, $cm);

    if (!has_capability('mod/webquest:view', $context)) {
        return false;
    }

    // $arg could be revision number or index.html
   // $arg = array_shift($args);
   //$itemid = (int)array_shift($args);

        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_webquest/$filearea/$itemid/$relativepath";
		//error_log($fullpath);
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
		/*
            $page = $DB->get_record('webquest', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($page->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_webquest', $filearea, $itemid)) {
                return false;
            }
			*/
          return false;
        }

        // finally send the file
        send_stored_file($file, null, 0, $forcedownload, $options);

}



?>