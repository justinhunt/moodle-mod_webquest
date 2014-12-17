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
 * Defines all the backup steps that will be used by {@link backup_webquest_activity_task}
 *
 * @package     mod_webquest
 * @category    backup
 * @copyright   2014 Justin Hunt <poodllsupport@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_webquest_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the 'webquest' element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing webquest instance
        $webquest = new backup_nested_element('webquest', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'process', 'processformat', 'conclussion', 'conclussionformat', 'taskdesc', 'taskdescformat', 'nattachments', 'gradingstrategy', 'maxbytes', 'submissionstart', 'submissionend', 'grade', 'teamsmode', 'timemodified', 'ntasks', 'usepassword', 'password'
			));

		// resources
        $resources = new backup_nested_element('resources');
        $resource = new backup_nested_element('resource', array('id'),array(
		 'webquestid', 'name', 'description', 'path', 'resno'
		));
		
		//tasks
        $tasks = new backup_nested_element('tasks');
        $task = new backup_nested_element('task', array('id'),array(
		 'webquestid', 'taskno', 'description', 'scale', 'maxscore', 'weight', 'stddev', 'totalassessments'
		));
		
		//rubrics
        $rubrics = new backup_nested_element('rubrics');
        $rubric = new backup_nested_element('rubric', array('id'),array(
			 'webquestid', 'description', 'taskno', 'rubricno'
		));
		
		//teams
        $teams = new backup_nested_element('teams');
        $team = new backup_nested_element('team', array('id'),array(
			'webquestid', 'name', 'description' 
		));
		
		//teammembers
        $teammembers = new backup_nested_element('teammembers');
        $teammember = new backup_nested_element('teammember', array('id'),array(
		 'teamid', 'webquestid', 'userid'
		));
		
		//submissions
        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', array('id'),array(
		 'webquestid', 'title', 'submission', 'submissionformat', 'userid', 'timecreated', 'mailed', 'timegraded', 'grade', 'gradecomment'
		));
		
		//grades
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'),array(
			 'webquestid', 'sid', 'taskno', 'feedback', 'grade' 
		));
		
		
		  // Build the tree.
        $webquest->add_child($resources);
        $resources->add_child($resource);
        $webquest->add_child($tasks);
        $tasks->add_child($task);
		$webquest->add_child($rubrics);
        $rubrics->add_child($rubric);
		$webquest->add_child($teams);
        $teams->add_child($team);
		$webquest->add_child($teammembers);
        $teammembers->add_child($teammember);
		$webquest->add_child($submissions);
        $submissions->add_child($submission);
        $webquest->add_child($grades);
        $grades->add_child($grade);


        // Define sources.
        $webquest->set_source_table('webquest', array('id' => backup::VAR_ACTIVITYID));
        $resource->set_source_table('webquest_resources',
                                        array('webquestid' => backup::VAR_PARENTID));
		$task->set_source_table('webquest_tasks',
                                        array('webquestid' => backup::VAR_PARENTID));
		$rubric->set_source_table('webquest_rubrics',
                                        array('webquestid' => backup::VAR_PARENTID));

        if ($userinfo) {
            $team->set_source_table('webquest_teams',
                                     array('webquestid' => backup::VAR_PARENTID));
			
			 $teammember->set_source_table('webquest_team_members',
                                     array('webquestid' => backup::VAR_PARENTID));
			
			 $submission->set_source_table('webquest_submissions',
                                     array('webquestid' => backup::VAR_PARENTID));
			
			 $grade->set_source_table('webquest_grades',
                                     array('webquestid' => backup::VAR_PARENTID));

           
        }

        // Define id annotations.
        $submission->annotate_ids('user', 'userid');
		$teammember->annotate_ids('user', 'userid');
		$grade->annotate_ids('user', 'sid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $webquest->annotate_files('mod_webquest', 'intro', null);
		
		//other file areas use webquestid
		$webquest->annotate_files('mod_webquest', 'process', 'id');
		$webquest->annotate_files('mod_webquest', 'taskdesc', 'id');
		$webquest->annotate_files('mod_webquest', 'conclussion', 'id');
		
		$submission->annotate_files('mod_webquest_', 'submission', 'id');
		$submission->annotate_files('mod_webquest', 'attachments', 'id');
        $resource->annotate_files('mod_webquest', 'resourcefiles', 'id');


        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($webquest);
		

    }
}
