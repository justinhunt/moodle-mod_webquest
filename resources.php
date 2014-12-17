<?php  // $Id: resources.php,v 1.4 2007/09/09 09:00:19 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
	require_once("forms.php");
    require_once("locallib.php");


    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $resid  = optional_param('resid', 0, PARAM_INT);
    $cancel = optional_param('cancel', 0, PARAM_INT);

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

    $strresources = get_string("resources", "webquest");
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests =  get_string("modulenameplural", "webquest");

	$context = context_module::instance($cm->id);
	$modulecontext = $context;
	$coursecontext = context_course::instance($course->id);
	
	//only edting teachers can edit tasks
	require_capability('mod/webquest:addinstance',$coursecontext);
	
	
	$PAGE->set_url('/mod/webquest/view.php', array('id' => $cm->instance));
	$PAGE->set_title($course->shortname . ": " . $webquest->name);
	$PAGE->set_heading($course->fullname);
	$PAGE->set_pagelayout('course');

		/*
    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strresources",
                  "", "", true);
*/
				  
    //add_to_log($course->id, "webquest", "update resource", "view.php?id=$cm->id", "$webquest->id");
    if($CFG->version<2014051200){
		add_to_log($course->id, 'webquest', "update resource", "view.php?id={$cm->id}", $webquest->name, $webquest->id);
	}else{
		// Trigger module viewed event.
		$modulecontext = context_module::instance($cm->id);
		$event = \mod_webquest\event\course_module_viewed::create(array(
		   'objectid' => $webquest->id,
		   'context' => $modulecontext
		));
		$event->add_record_snapshot('course_modules', $cm);
		$event->add_record_snapshot('course', $course);
		$event->add_record_snapshot('webquest', $webquest);
		$event->trigger();
	} 


    ///////////////Edit Resources /////////////////////////////////////////////
$isteacher = mod_webquest_isteacher($cm->course);
//set up our renderer	
$renderer = $PAGE->get_renderer('mod_webquest');

   if ($action == 'editres'){
        if (!$isteacher){
            error("Only teachers can look at this page");
        }
		$maxfiles=1;
		$filemanageroptions = webquest_fetch_filemanager_options($course,$maxfiles);
		$theform = new mod_webquest_resource_form(null,array('filemanageroptions'=>$filemanageroptions));
		
		
		//prepare form data 
		$form_data=new stdClass();
		$form_data->a = $webquest->id ;
		$form_data->id = $cm->id ;
		
		if($resid){
			$dbdata = $DB->get_record("webquest_resources",array("id"=>$resid,"webquestid"=>$webquest->id ));
			if($dbdata){
				$form_data->resid = $resid;
				$form_data->name = $dbdata->name;
				$form_data->description = $dbdata->description;
				$form_data->path = $dbdata->path;
			}

			 
			 //prepare file area
			$draftitemid = file_get_submitted_draft_itemid('resourcefiles');
			file_prepare_draft_area($draftitemid, $modulecontext->id, 'mod_webquest', 'resourcefiles', $resid,
									$filemanageroptions);
			$form_data->resourcefiles = $draftitemid;
			
			//headertext
			$headertext = get_string("editres", "webquest");
		
		}else{
			//headertext
			$headertext = get_string("insertresources", "webquest");
		}
		
		
		$theform->set_data($form_data);
		echo $renderer->header();
		echo $renderer->show_form($theform,$headertext);
		echo $renderer->footer();
		return;
		
		/*
        $form = $DB->get_record("webquest_resources",array("id"=>$resid,"webquestid"=>$webquest->id ));
		if(!$form){$form = new stdClass();}
        if (empty($form->name)){
            $form->name = "";
        }
        if (empty($form->description)){
            $form->description = "";
        }
        if (empty($form->path)){
            $form->path = "http://";
        }
        $string = get_string('cancel');
        $strsearch = get_string("searchweb", "webquest");
        $strchooseafile = get_string("chooseafile", "webquest");
        if (!$resid){
            echo $OUTPUT->heading_with_help(get_string("insertresources", "webquest"), "insertresources", "webquest");
        }else {
            echo $OUTPUT->heading_with_help(get_string("editresource", "webquest"), "editresource", "webquest");
        }
      ?>
        <form name="form" method="post" action="resources.php">
        <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
        <input type="hidden" name="action" value="insertres" />
    <input type="hidden" name="resid" value="<?php echo $resid ?>" />
        <center><table cellpadding="5" border="1">
        <?php
    ///get the selected resource
            echo "<tr valign=\"top\">\n";
            echo "<td align=\"right\"><b>". get_string("name").": </b></td>\n";
            echo "<td align=\"left\"><input type=\"text\" name=\"name\" size=\"30\" value=$form->name></td>";
            echo "</tr>";
            echo "<tr valign=\"top\">\n";
            echo "  <td align=\"right\"><b>". get_string("description").": </b></td>\n";
            echo "<td><textarea name=\"description\" rows=\"3\" cols=\"75\">".$form->description."</textarea>\n";
            echo "  </td></tr>\n";
            echo "<tr valign =\"top\">\n";
            echo "<td align=\"right\"><b>". get_string("url","webquest")." :</b></td>\n";
            echo "<td align=\"left\"><input type=\"text\" name=\"path\" size=\"30\" value=\"$form->path\" alt=\"reference\" /><br />";
            button_to_popup_window ("/files/index.php?id=$cm->course&amp;choose=form.path", "coursefiles", $strchooseafile, 500, 750, $strchooseafile);
            echo "<input type=\"button\" name=\"searchbutton\" value=\"$strsearch ...\" ".
                "onclick=\"return window.open('$CFG->resource_websearch', 'websearch', 'menubar=1,location=1,directories=1,toolbar=1,scrollbars,resizable,width=800,height=600');\" />\n</td>";
            echo "</tr>";
            echo "<tr valign=\"top\">\n";
            echo "  <td colspan=\"2\" >&nbsp;</td>\n";
            echo "</tr>";
            echo"<td>";



 echo   "</td>";
    ?>
        </table><br />
        <input type="submit" value="<?php  print_string("savechanges") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
        </center>
        </form>
        <?php
		
		*/
   }
   if ($action == 'doinsertres'){
        $theform = new mod_webquest_resource_form();
		if ($theform->is_cancelled()){
            redirect("view.php?id=$cm->id&amp;action=process");
        }
        $form_data = $theform->get_data();
		$maxfiles=1;
		$filemanageroptions = webquest_fetch_filemanager_options($course,$maxfiles);

		$res = new stdClass();

		if ($DB->record_exists("webquest_resources",array("id"=>$resid))){
            $res->name = $form_data->name;
            $res->description = $form_data->description;
            $res->path = $form_data->path;
            $res->id = $resid;
            if (!$DB->update_record("webquest_resources",$res)){
                error("Could not update webquest resource!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
			//save files
			file_save_draft_area_files($form_data->resourcefiles, $modulecontext->id, 'mod_webquest', 'resourcefiles',
                   $res->id, $filemanageroptions);
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }else{
            $res->webquestid = $webquest->id;
            $res->name = $form_data->name;
            $res->description = $form_data->description;
            $res->path = $form_data->path;
            $res->resno =1+ ($DB->count_records("webquest_resources",array("webquestid"=>$webquest->id)));
            if (!$res->id = $DB->insert_record("webquest_resources", $res)) {
                error("Could not insert webquest resource!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
			//save files
			file_save_draft_area_files($form_data->resourcefiles, $modulecontext->id, 'mod_webquest', 'resourcefiles',
                   $res->id, $filemanageroptions);
				   
			//redirect
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }
	/*
		
		
        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=process");
        }
        if ($DB->record_exists("webquest_resources",array("id"=>$resid))){
            $res->name = $form->name;
            $res->description = $form->description;
            $res->path = $form->path;
            $res->id = $resid;
            if (!$DB->update_record("webquest_resources",$res)){
                error("Could not update webquest resource!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }else{
            unset($res);
            $res->webquestid = $webquest->id;
            $res->name = $form->name;
            $res->description = $form->description;
            $res->path = $form->path;
            $res->resno =1+ ($DB->count_records("webquest_resources",array("webquestid"=>$webquest->id)));
            if (!$res->id = $DB->insert_record("webquest_resources", $res)) {
                error("Could not insert webquest resource!");
                redirect("view.php?id=$cm->id&amp;action=process");
            }
            redirect("view.php?id=$cm->id&amp;action=process", get_string("wellsaved","webquest"));
        }
	*/
    }

    ///////////Delete Resource////////////////////////////
    if($action == 'deleteres'){
        if (!$isteacher){
            error("Only teachers can look at this page");
        }
		echo $renderer->header();
		echo $renderer->confirm(get_string("suretodelres","webquest"), 
			new moodle_url('resources.php', array('action'=>'deleteyesres','id'=>$id,'resid'=>$resid)), 
			new moodle_url('view.php', array('id'=>$id,'action'=>'process')));
		echo $renderer->footer();
		return;
      //  notice_yesno(get_string("suretodelres","webquest"),
       //      "resources.php?action=deleteyesres&amp;id=$id&amp;resid=$resid", "view.php?id=$id&amp;action=process");

    }

    /////// Delete Resource NOW////////
    if ($action == 'deleteyesres'){
        if (!$isteacher){
            error("Only teachers can look at this page");
        }
        if (! $DB->delete_records("webquest_resources", array("id"=>"$resid"))){
            redirect("view.php?id=$cm->id&amp;action=process", get_string("couldnotdelete","webquest"));
        }else {
			//remove files
			$fs= get_file_storage();
			$fs->delete_area_files($modulecontext->id,'mod_webquest','resourcefiles',$resid);
            redirect("view.php?id=$cm->id&amp;action=process", get_string("deleted","webquest"));
        }
    }
   // echo $renderer->footer();

