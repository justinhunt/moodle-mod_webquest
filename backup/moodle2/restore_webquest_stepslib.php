<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   mod_webquest
 * @copyright 2014 Justin Hunt poodllsupport@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_webquest_activity_task
 */

/**
 * Structure step to restore one webquest activity
 */
class restore_webquest_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing webquest instance
        $webquest = new restore_path_element('webquest', '/activity/webquest');
        $paths[] = $webquest;
		
		//resources
		$resources= new restore_path_element('webquest_resource',
                                            '/activity/webquest/resources/resource');
		$paths[] = $resources;

		//tasks
		 $tasks= new restore_path_element('webquest_task',
                                            '/activity/webquest/tasks/task');
		$paths[] = $tasks;
		 
		 //rubrics
		 $rubrics= new restore_path_element('webquest_rubric',
                                            '/activity/webquest/rubrics/rubric');
		 $paths[] = $rubrics;

        // End here if no-user data has been selected
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////
		//submission
		$submission = new restore_path_element('webquest_submission',
                                                   '/activity/webquest/submissions/submission');
         $paths[] = $submission;

		 //grade
         $grade = new restore_path_element('webquest_grade', '/activity/webquest/grades/grade');
         $paths[] = $grade;
		
		//team
		$team = new restore_path_element('webquest_team', '/activity/webquest/teams/team');
         $paths[] = $team;
		
		//teammember
		$teammember = new restore_path_element('webquest_teammember', '/activity/webquest/teammembers/teammember');
         $paths[] = $teammember;


        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_webquest($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->submissionstart = $this->apply_date_offset($data->submissionstart);
        $data->submissionend = $this->apply_date_offset($data->submissionend);


        // insert the webquest record
        $newitemid = $DB->insert_record('webquest', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_webquest_resource($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
        $newitemid = $DB->insert_record('webquest_resources', $data);
       $this->set_mapping('webquest_resource', $oldid, $newitemid, true); // Mapping with files
    }
	
	protected function process_webquest_task($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
        $newitemid = $DB->insert_record('webquest_tasks', $data);
       $this->set_mapping('webquest_task', $oldid, $newitemid, true); // Mapping with files
    }
	
	protected function process_webquest_rubric($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
        $newitemid = $DB->insert_record('webquest_rubrics', $data);
       $this->set_mapping('webquest_rubric', $oldid, $newitemid, true); // Mapping with files
    }

	  protected function process_webquest_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timegraded = $this->apply_date_offset($data->timegraded);

        $newitemid = $DB->insert_record('webquest_submissions', $data);
        $this->set_mapping('webquest_submission', $oldid, $newitemid, true); // Mapping with files
    }
	
	/**
     * Process a grade restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_webquest_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
        $data->sid = $this->get_mappingid('user', $data->sid);
     
		$newitemid = $DB->insert_record('webquest_grades', $data);
		$this->set_mapping('webquest_grades', $oldid, $newitemid, true); // Mapping with files
    }
	
	protected function process_webquest_team($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
        $newitemid = $DB->insert_record('webquest_teams', $data);
       $this->set_mapping('webquest_team', $oldid, $newitemid, true); // Mapping with files
    }
	
	protected function process_webquest_teammember($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->webquestid = $this->get_new_parentid('webquest');
		$data->teamid = $this->get_new_parentid('webquest_team');
		$data->userid = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('webquest_team_members', $data);
       $this->set_mapping('webquest_teammember', $oldid, $newitemid, true); // Mapping with files
    }
	
	

    protected function after_execute() {
        // Add webquest related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_webquest', 'intro', null);
		
        $this->add_related_files('mod_webquest', 'process', 'webquest');
        $this->add_related_files('mod_webquest', 'taskdesc', 'webquest');
        $this->add_related_files('mod_webquest', 'conclussion', 'webquest');

        // Add related files
        $this->add_related_files('mod_webquest', 'resourcefiles', 'webquest_resource');
        $this->add_related_files('mod_webquest', 'attachments', 'webquest_submission');
		$this->add_related_files('mod_webquest', 'submission', 'webquest_submission');
    }
}
