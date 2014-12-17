<?php  //  // $Id: locallib.php,v 1.6 2007/09/09 09:00:19 stronk7 Exp $

	define('WEBQUEST_GS_NONE',0);
	define('WEBQUEST_GS_ACCUMULATIVE',1);
	define('WEBQUEST_GS_ERRORBANDED',2);
	define('WEBQUEST_GS_CRITERION',3);
	define('WEBQUEST_GS_RUBRIC',4);

/// Library of extra functions and module webquest
function mod_webquest_strategies(){
	$strategies = array (WEBQUEST_GS_NONE => get_string('notgraded', 'webquest'),
                          WEBQUEST_GS_ACCUMULATIVE => get_string('accumulative', 'webquest'),
                          WEBQUEST_GS_ERRORBANDED => get_string('errorbanded', 'webquest'),
                          WEBQUEST_GS_CRITERION => get_string('criterion', 'webquest'),
                          WEBQUEST_GS_RUBRIC => get_string('rubric', 'webquest') );
	return $strategies;
}

function mod_webquest_isteacher($courseid=false){
	global $COURSE;
	if(!$courseid){$courseid=$COURSE->id;}
	$context = context_course::instance($courseid);
	return has_capability('mod/webquest:addinstance',$context);
}

function mod_webquest_isstudent($courseid, $userid){
	$context = context_course::instance($courseid);
	//echo $userid . ":" . has_capability('moodle/course:view',$context,$userid) . "<br />";
	//return !isguestuser() && has_capability('moodle/course:view',$context,$userid);
	return !isguestuser() && is_enrolled($context,$userid,'',true);
}
function mod_webquest_isguest(){
	return isguestuser();
}

function mod_webquest_showgrades(){
	$showgrades = array (0 => get_string('dontshowgrades', 'webquest'),
                          1 => get_string('showgrades', 'webquest') );
	return $showgrades;
}

function mod_webquest_scales(){
	$scales = array(
                    0 => array( 'name' => get_string('scaleyes', 'webquest'), 'type' => 'radio',
                        'size' => 2, 'start' => get_string('yes'), 'end' => get_string('no')),
                    1 => array( 'name' => get_string('scalepresent', 'webquest'), 'type' => 'radio',
                        'size' => 2, 'start' => get_string('present', 'webquest'),
                        'end' => get_string('absent', 'webquest')),
                    2 => array( 'name' => get_string('scalecorrect', 'webquest'), 'type' => 'radio',
                        'size' => 2, 'start' => get_string('correct', 'webquest'),
                        'end' => get_string('incorrect', 'webquest')),
                    3 => array( 'name' => get_string('scalegood3', 'webquest'), 'type' => 'radio',
                        'size' => 3, 'start' => get_string('good', 'webquest'),
                        'end' => get_string('poor', 'webquest')),
                    4 => array( 'name' => get_string('scaleexcellent4', 'webquest'), 'type' => 'radio',
                        'size' => 4, 'start' => get_string('excellent', 'webquest'),
                        'end' => get_string('verypoor', 'webquest')),
                    5 => array( 'name' => get_string('scaleexcellent5', 'webquest'), 'type' => 'radio',
                        'size' => 5, 'start' => get_string('excellent', 'webquest'),
                        'end' => get_string('verypoor', 'webquest')),
                    6 => array( 'name' => get_string('scaleexcellent7', 'webquest'), 'type' => 'radio',
                        'size' => 7, 'start' => get_string('excellent', 'webquest'),
                        'end' => get_string('verypoor', 'webquest')),
                    7 => array( 'name' => get_string('scale10', 'webquest'), 'type' => 'selection',
                        'size' => 10),
                    8 => array( 'name' => get_string('scale20', 'webquest'), 'type' => 'selection',
                            'size' => 20),
                    9 => array( 'name' => get_string('scale100', 'webquest'), 'type' => 'selection',
                            'size' => 100));
	return $scales;
}

function mod_webquest_eweights(){
	$eweights = array(  0 => -4.0, 1 => -2.0, 2 => -1.5, 3 => -1.0, 4 => -0.75, 5 => -0.5,  6 => -0.25,
                             7 => 0.0, 8 => 0.25, 9 => 0.5, 10 => 0.75, 11=> 1.0, 12 => 1.5, 13=> 2.0,
                             14 => 4.0);
	return $eweights;
}

function mod_webquest_fweights(){
	$fweights = array(  0 => 0, 1 => 0.1, 2 => 0.25, 3 => 0.5, 4 => 0.75, 5 => 1.0,  6 => 1.5,
                             7 => 2.0, 8 => 3.0, 9 => 5.0, 10 => 7.5, 11=> 10.0, 12=>50.0);
	return $fweights;
}

function mod_webquest_assessmentcomps(){
	$assessmentcomps = array (
                          0 => array('name' => get_string('verylax', 'webquest'), 'value' => 1),
                          1 => array('name' => get_string('lax', 'webquest'), 'value' => 0.6),
                          2 => array('name' => get_string('fair', 'webquest'), 'value' => 0.4),
                          3 => array('name' => get_string('strict', 'webquest'), 'value' => 0.33),
                          4 => array('name' => get_string('verystrict', 'webquest'), 'value' => 0.2) );
	return $assessmentcomps;
}

//////////////////////////////////////////////////////////////////////////////////////
///WevQuest's Functions

function webquest_fetch_int_array($start=0, $stop=100){
		$return=array();
		for ($i=$stop; $i>=$start; $i--) {
            $return[$i] = $i;
        }	
		return $return;
}

function webquest_choose_from_menu ($options, $name, $selected="", $nothing="choose", $script="",
        $nothingvalue="0", $return=false) {
/// Given an array of value, creates a popup menu to be part of a form
/// $options["value"]["label"]

    if ($nothing == "choose") {
        $nothing = get_string("choose")."...";
    }

    if ($script) {
        $javascript = "onChange=\"$script\"";
    } else {
        $javascript = "";
    }

    $output = "<select name=\"$name\" $javascript>\n";
    if ($nothing) {
        $output .= "   <option value=\"$nothingvalue\"\n";
        if ($nothingvalue == $selected) {
            $output .= " selected=\"selected\"";
        }
        $output .= ">$nothing</option>\n";
    }
    if (!empty($options)) {
        foreach ($options as $value => $label) {
            $output .= "   <option value=\"$value\"";
            if ($value == $selected) {
                $output .= " selected=\"selected\"";
            }
            $output .= ">$label</option>\n";
        }
    }
    $output .= "</select>\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_resource_is_url($path) {
    if (strpos($path, '://')) {     // eg http:// https:// ftp://  etc
        return true;
    }
    if (strpos($path, '/') === 0) { // Starts with slash
        return true;
    }
    return false;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_fetch_upload_urls($modulecontext,$filearea,$itemid){
    global $CFG;
	$fs = get_file_storage();
	$files = $fs->get_area_files($modulecontext->id,'mod_webquest',$filearea,$itemid);
	//echo $modulecontext->id . "/mod_webquest/$filearea/$itemid/";
	$urls = array();
	foreach($files as $file){
		$filename =$file->get_filename();
		//echo "filename:" . $filename;
		if($filename!="."){
			$urls[$filename] = $CFG->wwwroot ."/pluginfile.php/" . $modulecontext->id . "/mod_webquest/$filearea/$itemid/$filename";
		}
	}
	//print_r($urls);
	return $urls;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_resource_url($resource,$modulecontext){
    global $CFG;
	
	$urls = webquest_fetch_upload_urls($modulecontext,'resourcefiles',$resource->id);
	if(count($urls)>0){
		foreach($urls as $filename=>$url){
			return $url;
		}
	}
	return '';
	
}

////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_resources($webquest, $modulecontext){
	global $CFG,$DB, $OUTPUT;

	$return = "";
    $return .=  $OUTPUT->heading_with_help(get_string("resources", "webquest"), "resources", "webquest");
    if ($resourcesraw = $DB->get_records("webquest_resources",array("webquestid"=>$webquest->id),"resno ASC ")){
        foreach ($resourcesraw as $resource){
            $resources[] = $resource; //renumber index 0,1,2,3....
        }
        $nres = count($resources);
		$table = new html_table();
        $table->head = array(format_text(get_string("resource","webquest"),format_text(get_string("description"))));
        //$table->wrap[0]  = 'nowrap';
        //$table->wrap[1]  = '';
        for ($i = 0;$i<$nres;$i++){
            $url = webquest_resource_url($resources[$i],$modulecontext);
            $rawes[0] =  "<b><a href=".$url." target = '_blank'>".$resources[$i]->name."</a></b>";
            $rawes[1] = format_text($resources[$i]->description);
            $table->data[$i] =$rawes;
        }
        return html_writer::table($table);
    }else{
        $return.=   $OUTPUT->box_start('center','70%');
        $return.=   '<div align="center">';
        print_string("noresourcesteacher","webquest");
        $return.=   '</div>';
        $return.=   $OUTPUT->box_end();
    }
    
    return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_editresources($webquest,$cm){
global $CFG, $DB, $OUTPUT;
	$modulecontext = context_module::instance($cm->id);
	$return = "";
    $return.=   $OUTPUT->heading_with_help(get_string("resources", "webquest"), "resources", "webquest");
    if ($resourcesraw = $DB->get_records("webquest_resources",array("webquestid"=>$webquest->id),"resno ASC ")){
        foreach ($resourcesraw as $resource){
            $resources[] = $resource; //renumber index 0,1,2,3....
        }
        $nres = count($resources); // así no vuelvo a pedir en la db
		$table = new html_table();
		$table->head=array();
		$table->wrap=array();
        $table->head[0] = format_text(get_string("resource","webquest"));
        $table->head[1] = format_text(get_string("description"));
        $table->wrap[0]  = 'nowrap';
        $table->wrap[1]  = '';
        require_once($CFG->libdir.'/filelib.php');
        for ($i = 0;$i<$nres;$i++){
            $resid = $resources[$i]->id;
            $url = webquest_resource_url($resources[$i], $modulecontext);
			$deleteicon = $OUTPUT->pix_url('t/delete');
			$editicon = $OUTPUT->pix_url('t/edit');
            $rawes[0] =  "<a href=\"resources.php?id=$cm->id&amp;resid=$resid&amp;action=editres\"><img src=\"$editicon\" alt = 'edit' border = '0'></a>"." "."<a href=\"resources.php?id=$cm->id&amp;resid=$resid&amp;action=deleteres\"><img src=\"$deleteicon\" alt = 'delete' border = '0'></a>"." "."<b><a href=".$url." target = '_blank'>".$resources[$i]->name."</a></b>";
            $rawes[1] = format_text($resources[$i]->description);
            $table->data[$i] =$rawes;
        }
        $return.=   html_writer::table($table);
    }else{
        $return.=   $OUTPUT->box_start('center','70%');
        $return.=   '<div align="center">';
        print_string("noresourcesteacher","webquest");
        $return.=   '</div>';
        $return.=   $OUTPUT->box_end();
    }
    
    return $return;
}

function webquest_fetch_editor_options($course, $modulecontext){
	$maxfiles=99;
	$maxbytes=$course->maxbytes;
	return  array('trusttext'=>true, 'subdirs'=>true, 'maxfiles'=>$maxfiles,
                          'maxbytes'=>$maxbytes, 'context'=>$modulecontext);
}

function webquest_fetch_filemanager_options($course, $maxfiles=1){
	$maxbytes=$course->maxbytes;
	return array('subdirs'=>true, 'maxfiles'=>$maxfiles,'maxbytes'=>$maxbytes,'accepted_types' => array('document,audio,video,image'));
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_intro($webquest,$context){
	global $OUTPUT;
	
	$return = "";
    $return.=   $OUTPUT->heading_with_help(get_string("intro", "webquest"), "introduction", "webquest");
    if (!empty($webquest->intro)){
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
		$display_intro = file_rewrite_pluginfile_urls($webquest->intro,'pluginfile.php',
			$context->id, 'mod_webquest', 'intro', null);
			$return.=   $OUTPUT->box(format_text($display_intro,FORMAT_HTML,$formatoptions), 'center', '80%', '', 5, 'generalbox', 'intro');
    }else{
        $return.=   $OUTPUT->box(format_text(get_string("writeintro","webquest")), 'center', '80%', '', 5, 'generalbox', 'intro');
    }
    return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_tasks($webquest,$cm, $context){
    global $DB, $OUTPUT;
    $return = "";
    if ($tasksraw = $DB->get_records("webquest_tasks", array("webquestid"=>$webquest->id), "taskno ASC" )) {
        foreach ($tasksraw as $task) {
            $tasks[] = $task;   // to renumber index 0,1,2...
        }
       
		$table = new html_table();
		$table->head=array();
		$table->wrap=array();
        $table->head[0] = format_text(get_string("task","webquest"));
        $table->head[1] = format_text(get_string("description"));
        $table->head[2] = format_text(get_string("taskweight","webquest"));
        $table->wrap[0] = 'nowrap';
        $table->wrap[1] = '';
        $table->size[0] = 75;
        $table->size[2] = 92;
        $table->align[2]= 'center';
		$eweights = mod_webquest_eweights();
        for ($i = 0;$i<count($tasks);$i++){
            $iplus1 = $i+1;
            $rawes[0] =  "<b>". (get_string("task","webquest"))." $iplus1:</b>";
            $rawes[1] = $tasks[$i]->description;
            $rawes[2] = $eweights[$tasks[$i]->weight];
            $table->data[$i] =$rawes;
        }
    }
    $return.=   $OUTPUT->heading_with_help(get_string("tasks", "webquest"), "tasks", "webquest");
    if (!empty($webquest->taskdesc)){
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
		$display_taskdesc = file_rewrite_pluginfile_urls($webquest->taskdesc,'pluginfile.php',
			$context->id, 'mod_webquest', 'taskdesc', $webquest->id);
        $return.=   $OUTPUT->box(format_text($display_taskdesc,FORMAT_HTML,$formatoptions), 'center', '80%', '', 5, 'generalbox', 'taskdesc');
    }else{
        $return.=   $OUTPUT->box(format_text(get_string("writetaskdesc","webquest")), 'center', '80%', '', 5, 'generalbox', 'taskdesc');
    }
    if (mod_webquest_isteacher($webquest->course)){
        $return.=   ("<b><a href=\"editpages.php?id=$cm->id&amp;action=taskdesc\">".get_string("edittaskdesc", 'webquest')."</a></b><br />");
    }
    if(!empty($table->data)){
        $return.=   html_writer::table($table);
    }
    if ((mod_webquest_isteacher($webquest->course)) and ($webquest->ntasks)){
        $return.=   ("<b><a href=\"tasks.php?id=$cm->id&amp;action=edittasks\">".get_string("goedittasks", 'webquest')."</a></b>");
    }
    
    return $return; 
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_process($webquest,$context){
global $OUTPUT;
	$return = "";
    $return.=   $OUTPUT->heading_with_help(get_string("process", "webquest"), "process", "webquest");
    if (!empty($webquest->process)){
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
		$display_process = file_rewrite_pluginfile_urls($webquest->process,'pluginfile.php',
			$context->id, 'mod_webquest', 'process', $webquest->id);
        $return.=   $OUTPUT->box(format_text($display_process,FORMAT_HTML,$formatoptions), 'center', '80%', '', 5, 'generalbox', 'process');
    }else{
        $return.=   $OUTPUT->box(format_text(get_string("writeprocess","webquest")), 'center', '80%', '', 5, 'generalbox', 'process');
    }
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_conclussion($webquest, $context){
global $OUTPUT;
	$return = "";
    $return.=   $OUTPUT->heading_with_help(get_string("conclussion", "webquest"), "conclussion", "webquest");
    if (!empty($webquest->conclussion)){
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
		$display_conclussion = file_rewrite_pluginfile_urls($webquest->conclussion,'pluginfile.php',
			$context->id, 'mod_webquest', 'conclussion', $webquest->id);
        $return.=   $OUTPUT->box(format_text($display_conclussion,FORMAT_HTML,$formatoptions), 'center', '80%', '', 5, 'generalbox', 'conclussion');
    }else{
        $return.=   $OUTPUT->box(format_text(get_string("writeconclussion","webquest")), 'center', '80%', '', 5, 'generalbox', 'conclussion');
    }
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_evaluation($webquest,$userid,$cm){
global $OUTPUT; 
$return = "";
 $return.=   $OUTPUT->heading_with_help(get_string("evaluation", "webquest"), "evaluation", "webquest");
    if (mod_webquest_isteacher($cm->course)){
      $return .=  webquest_evaluation_teacher($webquest,$cm);
    }else {
        $return .= webquest_evaluation_student($webquest,$userid,$cm);
    }
    return $return;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_teams($webquest,$cm,$userid){
global $OUTPUT; 
	$return = ""; 
  if (mod_webquest_isteacher($cm->course)) {
        $return.=   $OUTPUT->heading_with_help(get_string("teams", "webquest"), "teams", "webquest");
        if ($webquest->teamsmode == 0) {
            $return.=   $OUTPUT->box_start('center','70%');
            $return.=   '<div align="center">';
            print_string("teamsnotifyteacher","webquest");
            $return.=   '</div>';
            $return.=   $OUTPUT->box_end();
        }else {
          $return .=  webquest_print_teams_forteacher($webquest,$cm);
        }
    }else {
        $return.=   $OUTPUT->heading_with_help(get_string("yourteam", "webquest"), "teams", "webquest");
        if ($webquest->teamsmode == 0) {
            $return.=   $OUTPUT->box_start('center','70%');
            $return.=   '<div align="center">';
            print_string("teamsnotifystudent","webquest");
            $return.=   '</div>';
            $return.=   $OUTPUT->box_end();
        }else {
           $return .= webquest_print_teams_forstudent($webquest,$userid);
        }
    }
    return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_teams_forteacher($webquest,$cm){
    global $CFG,$DB,$OUTPUT;
    $return = "";
    $countteams = $DB->count_records("webquest_teams",array("webquestid"=>$webquest->id));

    if ($countteams == 0){
        $return.=   $OUTPUT->box_start('center','70%');
        $return.=   '<div align="center">';
        print_string("teamsnotifynoteams","webquest");
        $return.=   '</div>';
        $return.=   $OUTPUT->box_end();
    }
    else {
        if ($teamsraw = $DB->get_records("webquest_teams",array("webquestid"=>$webquest->id))){
            webquest_check_teamsmembers($webquest);
            foreach ($teamsraw as $team){
                $teams[] = $team; //renumber index 0,1,2,3....
            }
            $nteams = count($teams);
			$table = new html_table();
			$table->head=array();
			$table->wrap=array();
            $table->head[0] = format_text(get_string("teams","webquest"));
            $table->head[1] = format_text(get_string("description"));
            $table->wrap[0]  = 'nowrap';
            $table->wrap[1]  = '';
			$deleteicon = $OUTPUT->pix_url('t/delete');
			$editicon = $OUTPUT->pix_url('t/edit');
            for ($i = 0;$i<$nteams;$i++){
                $teamid = $teams[$i]->id;
                $rawes[0] =  "<a href=\"teams.php?id=$cm->id&amp;teamid=$teamid&amp;action=editteam\"><img src=\"$editicon\" alt = 'edit' border = '0'></a>".
					" ".
					"<a href=\"teams.php?id=$cm->id&amp;teamid=$teamid&amp;action=confirmdeleteteam\"><img src=\"$deleteicon\" alt = 'delete' border = '0'></a>".
					" ".
					"<b><a href=\"members.php?id=$cm->id&amp;teamid=$teamid&amp;action=members\">".$teams[$i]->name."</a></b>";
                $rawes[1] = format_text($teams[$i]->description);
                $table->data[$i] =$rawes;
            }
            $return.=   html_writer::table($table);
        }
        else {
            error('Could not get teams from Database');
        }
    }

    $return.=   ("<b><a href=\"teams.php?id=$cm->id&amp;action=editteam\">".
        get_string("insertteam", 'webquest')."</a></b>");
        
    return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_check_teamsmembers($webquest){
	global $DB;
    if ($membersraw = $DB->get_records("webquest_team_members",array("webquestid"=>$webquest->id))){
        foreach ($membersraw as $member){
         if (!mod_webquest_isstudent($webquest->course,$member->userid)){
				//echo "not student:" . $member->userid . ":" . $webquest->course . ":<br />" ;
              if (!$DB->delete_records("webquest_team_members",array("userid"=>$member->userid))){
                    error("could not check members database");
                }
            }
        }
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_teams_forstudent($webquest,$userid){
    global $CFG, $DB;
    $return = "";
    if($membersid = webquest_get_studentteam($webquest->id,$userid)){
		$table = new html_table();
        $table->head  = array ("", get_string("name"), get_string("email"),"");
        $table->align = array ("right", "left", "left", "center");
        $table->size  = array ("35", "", "", "15");
        $table->wrap  = array ("","nowrap","nowrap","");
        $coordinator  = true;
        foreach ($membersid as $memberid){
            if ($coordinator){
                $img = "<img src='team.jpg' alt = '".get_string("teammanager","webquest")."' border = '1'>";
            } else{
                $img = "";
            }
            $member = $DB->get_record("user",array("id"=>$memberid));
            if (!$member->maildisplay == 0){
                $email = $member->email;
            }
            $picture = print_user_picture($memberid, $webquest->course, $member->picture, false, true);
            $fullname = fullname($member, false);
            $table->data[] = array ($picture,"<a href=\"$CFG->wwwroot/user/view.php?id=$member->id&amp;course=$webquest->course\">$fullname</a>",$email, $img);
            $coordinator = false;
        }
        unset($coordinator);
        $return.=   html_writer::table($table);
    }
    return $return;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_get_studentteam($webquestid,$userid){
	global $DB;
	$return = "";
    if(!$team = $DB->get_record("webquest_team_members",array("webquestid"=>$webquestid,"userid"=>$userid))){
        $return.=   $OUTPUT->box_start('center','70%');
        $return.=   '<div align="center">';
        print_string("notinteam","webquest");
        $return.=   '</div>';
        $return.=   $OUTPUT->box_end();
        return false;
    }else{
        return webquest_get_team_members($team->teamid);
    }
    return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// this function returns member's ids given a team id.
function webquest_get_team_members($teamid){
	global $DB;
    if($teamsraw = $DB->get_records("webquest_team_members",array("teamid"=>$teamid),"id ASC ")){
        foreach ($teamsraw as $teams){
            $membersid[] = $teams->userid;
        }
        return $membersid;
    }else{
        return array();
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_evaluation_student($webquest,$userid,$cm){
    global $CFG, $DB;
    $return = "";
    if ($webquest->teamsmode){
        $team = $DB->get_record("webquest_team_members",array("webquestid"=>$webquest->id,"userid"=>$userid));
        $submission = $DB->get_record("webquest_submissions",array("webquestid"=>$webquest->id,"userid"=>$team->teamid));
        if ($membersid = webquest_get_studentteam($webquest->id,$userid)){
            // user is a team manager ...
            if ($userid == $membersid[0]){
                $timenow = time();
                // is no submission then submit your answer
                if (!$submission){
                    if ($timenow > $webquest->submissionstart){
                        if ($timenow < $webquest->submissionend) {
							$addurl = new moodle_url( '/mod/webquest/submissions.php', array('id'=>$cm->id,'action'=>'addsubmission','a'=>$webquest->id));
							$addlink = html_writer::link($addurl, get_string('addsubmission', 'webquest'));  
							$table = new html_table();
							$table->head  = array (get_string("addsubmission","webquest"));
							$table->align = array ("center");
							$table->size  = array ("*");
							$table->wrap  = array ("","nowrap","");
							$table->data[]= array ($addlink);
							$return .=   html_writer::table($table);
                            //webquest_print_upload_form($webquest);
                        }else{
                            notice(get_string("submissiontimeend","webquest"));
                        }
                    }else{
                        notice(get_string("nocanpostyet","webquest"));
                    }
                }else{
                // show your submission or the allowed actions
					$table = new html_table();
                    $table->head  = array (get_string("title","webquest"), get_string("action"), get_string("submitted","webquest"), get_string("grade"));
                    $table->align = array ("left", "center", "left", "center");
                    $table->size  = array ("*", "*", "*", "*");
                    $table->wrap  = array ("","nowrap","nowrap","");
					if ($submission->timecreated > ($timenow - $CFG->maxeditingtime)) {

							$saction = "<a href=\"submissions.php?action=editsubmission&amp;id=$cm->id&amp;sid=$submission->id\">".
                            get_string("edit")."</a> | ".
                            "<a href=\"submissions.php?action=confirmdelete&amp;id=$cm->id&amp;sid=$submission->id\">".
                            get_string("delete")."</a>";
                    }else{
                        $saction = '';
                    }
                    if ($DB->count_records("webquest_grades",array("sid"=>$submission->id))){
                        $grade = number_format($submission->grade * $webquest->grade / 100, 2);
                    }else{
                        $grade = null;
                    }
                    $table->data[]= array ("<a href=\"submissions.php?id=$cm->id&amp;sid=$submission->id&amp;action=showsubmission\">$submission->title</a>",$saction,userdate($submission->timecreated),"<a href=\"assessments.php?id=$cm->id&amp;sid=$submission->id&amp;action=viewassesment\">".$grade."</a>");
                    $return.=   html_writer::table($table);
                }
            }else{
            // the user isn't a team manager show indications
                if (!$submission){
                    $return.=   $OUTPUT->box_start('center','70%');
                    $return.=   '<div align="left">';
                    print_string("teamcoord","webquest");
                    $return.=   '</div>';
                    $return.=   $OUTPUT->box_end();
					$table = new html_table();
                    $table->head  = array ("", get_string("name"), get_string("email"),"");
                    $table->align = array ("right", "left", "left", "center");
                    $table->size  = array ("35", "", "", "15");
                    $table->wrap  = array ("","nowrap","nowrap","");
                    $img = "<img src='team.jpg' alt = '".get_string("teammanager","webquest")."' border = '1'>";
                    $member = $DB->get_record("user",array("id"=>$membersid[0]));
                    $picture = print_user_picture($membersid[0], $webquest->course, $member->picture, false, true);
                    if (!($member->maildisplay == 0)){
                        $email = $member->email;
                    }
                    $fullname = fullname($member, false);
                    $table->data[] = array ($picture, "<a href=\"$CFG->wwwroot/user/view.php?id=$member->id&amp;course=$webquest->course\">$fullname</a>",$email, $img);
                    $return.=   html_writer::table($table);
                    $return.=   $OUTPUT->box_start('center','70%');
                    $return.=   '<div align="left">';
                    print_string("teamcoorddo","webquest");
                    $return.=   '</div>';
                    $return.=   $OUTPUT->box_end();
                }else{
                // show the submission sent by the team manager
					$table = new html_table();
                    $table->head  = array (get_string("title","webquest"),get_string("submitted","webquest"), get_string("grade"));
                    $table->align = array ("left", "left", "center");
                    $table->size  = array ("*", "*", "*");
                    $table->wrap  = array ("","nowrap","");
                    $saction = '';
                    if ($DB->count_records("webquest_grades",array("sid",$submission->id))){
                        $grade = number_format($submission->grade * $webquest->grade / 100, 2);
                    }else{
                        $grade = null;
                    }
                    $table->data[]= array ("<a href=\"submissions.php?id=$cm->id&amp;sid=$submission->id&amp;action=showsubmission\">$submission->title</a>",userdate($submission->timecreated),"<a href=\"assessments.php?id=$cm->id&amp;sid=$submission->id&amp;action=viewassesment\">".$grade."</a>");
                    $return.=   html_writer::table($table);
                }
            }
        }
    }else{
    // no teams mode
        $timenow = time();
        $submission = $DB->get_record("webquest_submissions",array("webquestid"=>$webquest->id,"userid"=>$userid));
        if (!$submission){
        // if no submission then send your answer
            if ($timenow > $webquest->submissionstart){
                if ($timenow < $webquest->submissionend){
					$addurl = new moodle_url( '/mod/webquest/submissions.php', array('id'=>$cm->id,'action'=>'addsubmission','a'=>$webquest->id));
					$addlink = html_writer::link($addurl, get_string('addsubmission', 'webquest'));
				  
				  $table = new html_table();
                    $table->head  = array (get_string("addsubmission","webquest"));
                    $table->align = array ("center");
                    $table->size  = array ("*");
                    $table->wrap  = array ("","nowrap","");
                    $table->data[]= array ($addlink);
                    $return .=   html_writer::table($table);

				  //$return .= webquest_print_upload_form($webquest);
                }else{
                    notice(get_string("submissiontimeend","webquest"));
                }
            }else{
                notice(get_string("nocanpostyet","webquest"));
            }
        }else{
        // show the submission
			$table = new html_table();
            $table->head  = array (get_string("title","webquest"), get_string("action"), get_string("submitted","webquest"), get_string("grade"));
            $table->align = array ("left", "center", "left", "center");
            $table->size  = array ("*", "*", "*", "*");
            $table->wrap  = array ("","nowrap","nowrap","");
            if ($submission->timecreated > ($timenow - $CFG->maxeditingtime)) {
                $saction = "<a href=\"submissions.php?action=editsubmission&amp;id=$cm->id&amp;sid=$submission->id\">".
                    get_string("edit")."</a> | ".
                    "<a href=\"submissions.php?action=confirmdelete&amp;id=$cm->id&amp;sid=$submission->id\">".
                    get_string("delete")."</a>";
            }else {
                $saction = '';
            }

            if ($DB->count_records("webquest_grades",array("sid"=>$submission->id))){
                $grade = number_format($submission->grade * $webquest->grade / 100, 2);
            }else{
                $grade = null;
            }
            $table->data[]= array ("<a href=\"submissions.php?id=$cm->id&amp;sid=$submission->id&amp;action=showsubmission\">$submission->title</a>",$saction,userdate($submission->timecreated),"<a href=\"assessments.php?id=$cm->id&amp;sid=$submission->id&amp;action=viewassesment\">".$grade."</a>");
            $return.=   html_writer::table($table);
        }
    }
    $timenow = time();
    if ($timenow > $webquest->submissionend){
        if($submissions = webquest_get_students_submissions($webquest)){
            $sidtemp = $submission->id;
			$table = new html_table();
            $table->head  = array (get_string("title","webquest"), get_string("by","webquest"), get_string("submitted","webquest"), get_string("grade"));
            $table->align = array ("left", "left", "left", "center");
            $table->size  = array ("*", "*", "*", "*");
            $table->wrap  = array ("", "nowrap", "nowrap","nowrap");
            $table->data = null;
            foreach($submissions as $submission){
                if (!($submission->id==$sidtemp)){
                    if ($webquest->teamsmode == 0){
                        $user = $DB->get_record("user",array("id"=>$submission->userid));
                        $name = fullname($user, false);
                        $by = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$webquest->course\">$name</a>";
                    }else{
                        $team = $DB->get_record("webquest_teams",array("id"=>$submission->userid));
                        $name = $team->name;
                        $by = get_string("team","webquest").": ".$name;
                    }
                    if ($DB->count_records("webquest_grades",array("sid",$submission->id))){
                        $grade = number_format($submission->grade * $webquest->grade / 100, 2);
                    }else{
                        $grade = null;
                    }
                    $table->data[] = array("<a href=\"submissions.php?id=$cm->id&amp;sid=$submission->id&amp;action=showsubmission\">$submission->title</a>",$by,userdate($submission->timecreated),"<a href=\"assessments.php?id=$cm->id&amp;sid=$submission->id&amp;action=viewassesment\">".$grade."</a>");
                }
            }
            if ($table->data){
                print_heading(get_string("othersubmits","webquest"));
                $return.=   html_writer::table($table);
            }
        }
    }
    return $return;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_submission($webquest,$submission){
    global $CFG,$OUTPUT;
	$return = "";
    if (! $cm = get_coursemodule_from_instance("webquest", $webquest->id, $webquest->course)) {
        error("Course Module ID was incorrect");
    }
	$modulecontext = context_module::instance($cm->id);
    $formatoptions = new stdClass;
    $formatoptions->noclean = true;
	$display_submission = file_rewrite_pluginfile_urls($submission->submission,'pluginfile.php',
			$modulecontext->id, 'mod_webquest', 'submission', $submission->id);
    $return.=   $OUTPUT->box(format_text($display_submission,FORMAT_HTML,$formatoptions), 'center');

	  if ($webquest->nattachments>0) {
			$table = new html_table();
			$table->head  = array (get_string("attachments","webquest"));
			$table->align = array ("left", "left");
			$table->size  = array ("*", "*");
			
			$urls = webquest_fetch_upload_urls($modulecontext,'attachments',$submission->id);
			if(count($urls)>0){
				$n=0;
				foreach($urls as $filename=>$url){
					$n++;
					$itemname = get_string("attachment","webquest") . ": " . $n;
					$itemicon = $OUTPUT->pix_icon('icon', '', 'mod_page', array('class' => 'icon'));
					$itemlink = html_writer::link($url, $itemicon . $filename);
					$table->data[] = array($itemname, $itemlink);
				}
			}
			$return .= html_writer::table($table);
	}
	return $return;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_evaluation_teacher($webquest,$cm){
    global $CFG, $DB, $OUTPUT;
    $return = "";
    if($submissions = webquest_get_students_submissions($webquest)){
		$table = new html_table();
        $table->head  = array (get_string("title","webquest"), get_string("by","webquest"), get_string("submitted","webquest"), get_string("grade"));
        $table->align = array ("left", "left", "left", "center");
        $table->size  = array ("*", "*", "*", "*");
        $table->wrap  = array ("", "nowrap", "nowrap","nowrap");
		
		//get icons
		$deleteicon = $OUTPUT->pix_url('t/delete');
		$editicon = $OUTPUT->pix_url('t/edit');

		//do each submission
        foreach($submissions as $submission){
            if ($grade = $DB->get_records("webquest_grades",array("sid"=>$submission->id))){
                $saction = "<a href=\"submissions.php?action=assess&amp;id=$cm->id&amp;sid=$submission->id\">" .  
					"<img src=\"" . $editicon   . "\" alt = 'edit' border = '0'>" .
					"</a>"." "."<a href=\"assessments.php?action=confirmdeletegrade&amp;id=$cm->id&amp;sid=$submission->id\">".
					"<img src=\"" . $deleteicon   . "\" alt = 'delete' border = '0'>" . 
					"</a>";
            }else{
                $saction = "<a href=\"submissions.php?action=assess&amp;id=$cm->id&amp;sid=$submission->id\">".
                    get_string("grade")."</a>";
            }
            if ($webquest->teamsmode == 0){
                $user = $DB->get_record("user",array("id"=>$submission->userid));
                $name = fullname($user, false);
                $by = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$webquest->course\">$name</a>";
            }else{
                $team = $DB->get_record("webquest_teams",array("id"=>$submission->userid));
				//if somehow the user is not in a team, lets just show their submission: Justin 20140912
				if($team){
					$name = $team->name;
					$by = get_string("team","webquest")." :<a href=\"teams.php?id=$cm->id&amp;teamid=$team->id&amp;action=members\">$name</a>";
				}else{
					$user = $DB->get_record("user",array("id"=>$submission->userid));
					$name = fullname($user, false);
					$by = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$webquest->course\">$name</a>";
				}
            }
            if ($DB->count_records("webquest_grades",array("sid"=>$submission->id))){
                $grade = number_format($submission->grade * $webquest->grade / 100, 2);
            }else{
                $grade = null;
            }
            $table->data[] = array("<a href=\"submissions.php?id=$cm->id&amp;sid=$submission->id&amp;action=showsubmission\">$submission->title</a>",$by,userdate($submission->timecreated),"<a href=\"assessments.php?id=$cm->id&amp;sid=$submission->id&amp;action=viewassesment\">".$grade."</a>"." ".$saction);
        }
        $return.=   html_writer::table($table);
    }else{
        notify(get_string("nosubmissionsteacher","webquest"));
    }
    return $return;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// this function returns empty if no submissions found or return an array of submissions
function webquest_get_students_submissions($webquest){
global $DB;
    if(!$submissionsraw = $DB->get_records("webquest_submissions",array("webquestid"=>$webquest->id))){
        return array();
    }else{
        return $submissionsraw;
    }
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function webquest_print_assessment($webquest, $graded = false, $allowchanges = false, $showcommentlinks = false, $returnto = '',$sid) {
    global $CFG, $USER, $WEBQUEST_SCALES, $WEBQUEST_EWEIGHTS, $DB;

    if (! $course = get_course($webquest->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("webquest", $webquest->id, $course->id)) {
        error("Course Module ID was incorrect");
    }

    if (!$submission = $DB->get_record("webquest_submissions", array("id"=>$sid))) {
            error ("Submission record not found");
    }
    if ($graded) {
        print_heading(get_string('assessmentof', 'webquest',
            "<a href=\"submissions.php?id=$cm->id&amp;action=showsubmission&amp;sid=$submission->id\" target=\"submission\">".
            $submission->title.'</a>'));
    }

    $timenow = time();
    $showgrades = true;
    // now print the grading form with the grading grade if any
    // FORM is needed for Mozilla browsers, else radio bttons are not checked
        ?>
    <form name="assessmentform" method="post" action="assessments.php">
    <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
    <input type="hidden" name="sid" value="<?php echo $submission->id ?>" />
    <input type="hidden" name="action" value="updateassessment" />
    <input type="hidden" name="returnto" value="<?php echo $returnto ?>" />
    <input type="hidden" name="taskno" value="" />
    <center>
    <table cellpadding="2" border="1">
    <?php
    echo "<tr valign=\"top\">\n";
    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\"><center><b>";
    print_string('submitted', 'webquest');

    echo '</b><br />'.userdate($submission->timecreated)."</center></td>\n";
    echo "</tr>\n";

    // only show the grade if grading strategy > 0 and the grade is positive
    if ($webquest->gradingstrategy and $submission->grade >= 0) {
        echo "<tr valign=\"top\">\n
            <td colspan=\"2\" align=\"center\">
            <b>".get_string("thegradeis", "webquest").": ".
            number_format($submission->grade * $webquest->grade / 100, 2)." (".
            get_string("maximumgrade")." ".number_format($webquest->grade, 0).")</b>
            </td></tr><tr><td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td></tr>\n";
    }

    // get the assignment elements...
    $tasksraw = $DB->get_records("webquest_tasks", array("webquestid"=>$webquest->id), "taskno ASC" );
    if (count($tasksraw) < $webquest->ntasks) {
        print_string("noteonassignmentelements", "webquest");
    }
    if ($tasksraw) {
        foreach ($tasksraw as $task) {
            $tasks[] = $task;   // to renumber index 0,1,2...
        }
    } else {
        $tasks = null;
    }
    if ($graded) {
        // get any previous grades...
        //if ($gradesraw = $DB->get_records_select("webquest_grades", "sid = $submission->id", "taskno")) {
		if ($gradesraw = $DB->get_records_select("webquest_grades", "sid = $submission->id",null,"taskno")) {
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
    // determine what sort of grading
    switch ($webquest->gradingstrategy) {
        case 0:  // no grading
            // now print the form
            for ($i=0; $i < count($tasks); $i++) {
                $iplus1 = $i+1;
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("task","webquest")." $iplus1:</b></p></td>\n";
                echo "  <td>".format_text($tasks[$i]->description);
                echo "</td></tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("feedback").":</b></p></td>\n";
                echo "  <td>\n";
                if ($allowchanges) {
                    echo "      <textarea name=\"feedback_$i\" rows=\"3\" cols=\"75\" >\n";
                    if (isset($grades[$i]->feedback)) {
                        echo $grades[$i]->feedback;
                    }
                    echo "</textarea>\n";
                } else {
                    echo format_text($grades[$i]->feedback);
                }
                echo "  </td>\n";
                echo "</tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                echo "</tr>\n";
            }
            break;

        case 1: // accumulative grading
            // now print the form
            for ($i=0; $i < count($tasks); $i++) {
                $iplus1 = $i+1;
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("task","webquest")." $iplus1:</b></p></td>\n";
                echo "  <td>".format_text($tasks[$i]->description);
                echo "<p align=\"right\"><font size=\"1\">".get_string("taskweight", "webquest").": ".
                    number_format($WEBQUEST_EWEIGHTS[$tasks[$i]->weight], 2)."</font></p>\n";
                echo "</td></tr>\n";
                if ($showgrades) {
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><p><b>". get_string("grade"). ":</b></p></td>\n";
                    echo "  <td valign=\"top\">\n";

                    // get the appropriate scale
                    $scalenumber=$tasks[$i]->scale;
                    $SCALE = (object)$WEBQUEST_SCALES[$scalenumber];
                    switch ($SCALE->type) {
                        case 'radio' :
                                // show selections highest first
                                echo "<center><b>$SCALE->start</b>&nbsp;&nbsp;&nbsp;";
                                for ($j = $SCALE->size - 1; $j >= 0 ; $j--) {
                                    $checked = false;
                                    if (isset($grades[$i]->grade)) {
                                        if ($j == $grades[$i]->grade) {
                                            $checked = true;
                                        }
                                    } else { // there's no previous grade so check the lowest option
                                        if ($j == 0) {
                                            $checked = true;
                                        }
                                    }
                                    if ($checked) {
                                        echo " <input type=\"radio\" name=\"grade[$i]\" value=\"$j\" checked=\"checked\" alt=\"$j\" /> &nbsp;&nbsp;&nbsp;\n";
                                    }else {
                                        if(!$allowchanges){
                                            echo " <input type=\"radio\" name=\"grade[$i]\" value=\"$j\" alt=\"$j\" disabled=true/> &nbsp;&nbsp;&nbsp;\n";
                                        }else{
                                            echo " <input type=\"radio\" name=\"grade[$i]\" value=\"$j\" alt=\"$j\" /> &nbsp;&nbsp;&nbsp;\n";
                                        }
                                    }
                                }
                                echo "&nbsp;&nbsp;&nbsp;<b>$SCALE->end</b></center>\n";
                                break;
                        case 'selection' :
                                unset($numbers);
                                for ($j = $SCALE->size; $j >= 0; $j--) {
                                    $numbers[$j] = $j;
                                }
                                if (isset($grades[$i]->grade)) {
                                    choose_from_menu($numbers, "grade[$i]", $grades[$i]->grade, "");
                                }else {
                                    choose_from_menu($numbers, "grade[$i]", 0, "");
                                }
                                break;
                    }
                    echo "  </td>\n";
                    echo "</tr>\n";
                }
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("feedback").":</b></p></td>\n";
                echo "  <td>\n";
                if ($allowchanges) {
                    echo "      <textarea name=\"feedback_$i\" rows=\"3\" cols=\"75\" >\n";
                    if (isset($grades[$i]->feedback)) {
                        echo $grades[$i]->feedback;
                    }
                    echo "</textarea>\n";
                } else {
                    echo format_text($grades[$i]->feedback);
                }
                echo "  </td>\n";
                echo "</tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                echo "</tr>\n";
            }
            break;

        case 2: // error banded grading
            // now run through the elements
            $negativecount = 0;
            for ($i=0; $i < count($tasks) - 1; $i++) {
                $iplus1 = $i+1;
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("task","webquest")." $iplus1:</b></p></td>\n";
                echo "  <td>".format_text($tasks[$i]->description);
                echo "<p align=\"right\"><font size=\"1\">".get_string("taskweight", "webquest").": ".
                    number_format($WEBQUEST_EWEIGHTS[$tasks[$i]->weight], 2)."</font>\n";
                echo "</td></tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("grade"). ":</b></p></td>\n";
                echo "  <td valign=\"top\">\n";
                // get the appropriate scale - yes/no scale (0)
                $SCALE = (object) $WEBQUEST_SCALES[0];
                switch ($SCALE->type) {
                    case 'radio' :
                            // show selections highest first
                            echo "<center><b>$SCALE->start</b>&nbsp;&nbsp;&nbsp;";
                            for ($j = $SCALE->size - 1; $j >= 0 ; $j--) {
                                $checked = false;
                                if (isset($grades[$i]->grade)) {
                                    if ($j == $grades[$i]->grade) {
                                        $checked = true;
                                    }
                                } else { // there's no previous grade so check the lowest option
                                    if ($j == 0) {
                                        $checked = true;
                                    }
                                }
                                if ($checked) {
                                    echo " <input type=\"radio\" name=\"grade[$i]\" value=\"$j\" checked=\"checked\" alt=\"$j\" /> &nbsp;&nbsp;&nbsp;\n";
                                } else {
                                    if(!$allowchanges){
                                            echo " <input type=\"radio\" name=\"grade[$i]\" value=\"$j\" alt=\"$j\" disabled=true/> &nbsp;&nbsp;&nbsp;\n";
                                        }else{
                                            echo " <input type=\"radio\" name=\"grade[$i]\" value=\"$j\" alt=\"$j\" /> &nbsp;&nbsp;&nbsp;\n";
                                        }
                                }
                            }
                            echo "&nbsp;&nbsp;&nbsp;<b>$SCALE->end</b></center>\n";
                            break;
                    case 'selection' :
                            unset($numbers);
                            for ($j = $SCALE->size; $j >= 0; $j--) {
                                $numbers[$j] = $j;
                            }
                            if (isset($grades[$i]->grade)) {
                                choose_from_menu($numbers, "grade[$i]", $grades[$i]->grade, "");
                            } else {
                                choose_from_menu($numbers, "grade[$i]", 0, "");
                            }
                            break;
                }
                echo "  </td>\n";
                echo "</tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td align=\"right\"><p><b>". get_string("feedback").":</b></p></td>\n";
                echo "  <td>\n";
                if ($allowchanges) {
                    echo "      <textarea name=\"feedback_$i\" rows=\"3\" cols=\"75\" >\n";
                    if (isset($grades[$i]->feedback)) {
                        echo $grades[$i]->feedback;
                    }
                    echo "</textarea>\n";
                } else {
                    if (isset($grades[$i]->feedback)) {
                        echo format_text($grades[$i]->feedback);
                    }
                }
                echo "&nbsp;</td>\n";
                echo "</tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                echo "</tr>\n";
                if (empty($grades[$i]->grade)) {
                    $negativecount++;
                }
            }
            echo "</table></center>\n";
            // now print the grade table
            echo "<p><center><b>".get_string("gradetable","webquest")."</b></center>\n";
            echo "<center><table cellpadding=\"5\" border=\"1\"><tr><td align=\"CENTER\">".
                get_string("numberofnegativeresponses", "webquest");
            echo "</td><td>". get_string("suggestedgrade", "webquest")."</td></tr>\n";
            for ($j = 100; $j >= 0; $j--) {
                $numbers[$j] = $j;
            }
            for ($i=0; $i<=$webquest->ntasks; $i++) {
                if ($i == $negativecount) {
                    echo "<tr><td align=\"CENTER\"><img src=\"$CFG->pixpath/t/right.gif\" alt=\"\" /> $i</td><td align=\"center\">{$tasks[$i]->maxscore}</td></tr>\n";
                }else {
                    echo "<tr><td align=\"CENTER\">$i</td><td align=\"CENTER\">{$tasks[$i]->maxscore}</td></tr>\n";
                }
            }
            echo "</table></center>\n";
            echo "<p><center><table cellpadding=\"5\" border=\"1\"><tr><td><b>".get_string("optionaladjustment","webquest")."</b></td><td>\n";
            unset($numbers);
            for ($j = 20; $j >= -20; $j--) {
                $numbers[$j] = $j;
            }
            if (isset($grades[$webquest->ntasks]->grade)) {
                choose_from_menu($numbers, "grade[$webquest->ntasks]", $grades[$webquest->ntasks]->grade, "");
            }else {
                choose_from_menu($numbers, "grade[$webquest->ntasks]", 0, "");
            }
            echo "</td></tr>\n";
            break;

        case 3: // criteria grading
            echo "<tr valign=\"top\">\n";
            echo "  <td class=\"webquestassessmentheading\">&nbsp;</td>\n";
            echo "  <td class=\"webquestassessmentheading\"><b>". get_string("criterion","webquest")."</b></td>\n";
            echo "  <td class=\"webquestassessmentheading\"><b>".get_string("select", "webquest")."</b></td>\n";
            echo "  <td class=\"webquestassessmentheading\"><b>".get_string("suggestedgrade", "webquest")."</b></td>\n";
            // find which criteria has been selected (saved in the zero element), if any
            if (isset($grades[0]->grade)) {
                $selection = $grades[0]->grade;
            } else {
                $selection = 0;
            }
            // now run through the elements
            for ($i=0; $i < count($tasks); $i++) {
                $iplus1 = $i+1;
                echo "<tr valign=\"top\">\n";
                echo "  <td>$iplus1</td><td>".format_text($tasks[$i]->description)."</td>\n";
                if ($selection == $i) {
                    echo "  <td align=\"center\"><input type=\"radio\" name=\"grade[0]\" value=\"$i\" checked=\"checked\" alt=\"$i\" /></td>\n";
                } else {
                    if ($allowchanges){
                        echo "  <td align=\"center\"><input type=\"radio\" name=\"grade[0]\" value=\"$i\" alt=\"$i\" /></td>\n";
                    }else{
                        echo "  <td align=\"center\"><input type=\"radio\" name=\"grade[0]\" value=\"$i\" alt=\"$i\"  disabled=true/></td>\n";
                    }
                }
                echo "<td align=\"center\">{$tasks[$i]->maxscore}</td></tr>\n";
            }
            echo "</table></center>\n";
            echo "<p><center><table cellpadding=\"5\" border=\"1\"><tr><td><b>".get_string("optionaladjustment",
                    "webquest")."</b></td><td>\n";
            unset($numbers);
            for ($j = 20; $j >= -20; $j--) {
                $numbers[$j] = $j;
            }
            if (isset($grades[1]->grade)) {
                choose_from_menu($numbers, "grade[1]", $grades[1]->grade, "");
            } else {
                choose_from_menu($numbers, "grade[1]", 0, "");
            }
            echo "</td></tr>\n";
            break;

        case 4: // rubric grading
            // now run through the elements...
            for ($i=0; $i < count($tasks); $i++) {
                $iplus1 = $i+1;
                echo "<tr valign=\"top\">\n";
                echo "<td align=\"right\"><b>".get_string("task", "webquest")." $iplus1:</b></td>\n";
                echo "<td>".format_text($tasks[$i]->description).
                     "<p align=\"right\"><font size=\"1\">".get_string("taskweight", "webquest").": ".
                    number_format($WEBQUEST_EWEIGHTS[$tasks[$i]->weight], 2)."</font></td></tr>\n";
                echo "<tr valign=\"top\">\n";
                echo "  <td class=\"webquestassessmentheading\" align=\"center\"><b>".get_string("select", "webquest").
                    "</b></td>\n";
                echo "  <td class=\"webquestassessmentheading\"><b>". get_string("criterion","webquest").
                    "</b></td></tr>\n";
                if (isset($grades[$i])) {
                    $selection = $grades[$i]->grade;
                } else {
                    $selection = 0;
                }
                // ...and the rubrics 
                if ($rubricsraw = $DB->get_records_select("webquest_rubrics", "webquestid = $webquest->id AND taskno = $i", null,"rubricno ASC")) {
                    unset($rubrics);
                    foreach ($rubricsraw as $rubic) {
                        $rubrics[] = $rubic;   // to renumber index 0,1,2...
                    }
                    for ($j=0; $j<5; $j++) {
                        if (empty($rubrics[$j]->description)) {
                            break; // out of inner for loop
                        }
                        echo "<tr valign=\"top\">\n";
                        if ($selection == $j) {
                            echo "  <td align=\"center\"><input type=\"radio\" name=\"grade[$i]\" value=\"$j\"
                                checked=\"checked\" alt=\"$j\" /></td>\n";
                        } else {
                            if ($allowchanges){
                                echo "  <td align=\"center\"><input type=\"radio\" name=\"grade[$i]\" value=\"$j\"
                                    alt=\"$j\" /></td>\n";
                            }else{
                                echo "  <td align=\"center\"><input type=\"radio\" name=\"grade[$i]\" value=\"$j\"
                                    alt=\"$j\" disabled=true/></td>\n";
                            }
                        }
                        echo "<td>".format_text($rubrics[$j]->description)."</td>\n";
                    }
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><p><b>". get_string("feedback").":</b></p></td>\n";
                    echo "  <td>\n";
                    if ($allowchanges) {
                        echo "      <textarea name=\"feedback_$i\" rows=\"3\" cols=\"75\" >\n";
                        if (isset($grades[$i]->feedback)) {
                            echo $grades[$i]->feedback;
                        }
                        echo "</textarea>\n";
                    } else {
                        echo format_text($grades[$i]->feedback);
                    }
                    echo "  </td>\n";
                    echo "</tr>\n";
                    echo "<tr valign=\"top\">\n";
                    echo "  <td colspan=\"2\" class=\"webquestpassessmentheading\">&nbsp;</td>\n";
                    echo "</tr>\n";
                }
            }
            break;
    } // end of outer switch

        // now get the general comment (present in all types)
        echo "<tr valign=\"top\">\n";
        switch ($webquest->gradingstrategy) {
            case 0:
            case 1:
            case 4 : // no grading, accumulative and rubic
                echo "  <td align=\"right\"><p><b>". get_string("generalcomment", "webquest").":</b></p></td>\n";
                break;
            default :
                echo "  <td align=\"right\"><p><b>".get_string("generalcomment", "webquest")."/<br />".
                    get_string("reasonforadjustment", "webquest").":</b></p></td>\n";
        }
        echo "  <td>\n";
        if ($allowchanges) {
            echo "      <textarea name=\"generalcomment\" rows=\"5\" cols=\"75\" >\n";
            if (isset($submission->gradecomment)) {
                echo $submission->gradecomment;
            }
            echo "</textarea>\n";
        } else {
            if ($graded) {
                if (isset($submission->gradecomment)) {
                    echo format_text($submission->gradecomment);
                }
            } else {
                print_string("yourfeedbackgoeshere", "webquest");
            }
        }
    echo "&nbsp;</td>\n";
    echo "</tr>\n";
    $timenow = time();
    echo "<tr valign=\"top\">\n";
    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
    echo "</tr>\n";

    // ...and close the table, show submit button if needed...
    echo "</table>\n";
    if ($allowchanges) {
        echo "<input type=\"submit\" value=\"".get_string("savechanges")."\" />\n";
    }
    echo "</center>";
    echo "</form>\n";
}


?>