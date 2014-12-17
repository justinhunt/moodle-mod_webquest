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
 *
 * @package    mod_webquest
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Module settings form for Workshop instances
 */
class mod_webquest_mod_form extends moodleform_mod {

    /** @var object the course this instance is part of */
    protected $course = null;

    /**
     * Constructor
     */
    public function __construct($current, $section, $cm, $course) {
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Defines the workshop instance configuration form
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $webquestconfig = get_config('webquest');
        $mform = $this->_form;

        // General --------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Workshop name
        $label = get_string('webquestname', 'webquest');
        $mform->addElement('text', 'name', $label, array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Introduction
        $this->add_intro_editor(false, get_string('intro', 'webquest'));

        // Grading settings -----------------------------------------------------------
        //$mform->addElement('header', 'gradingsettings', get_string('gradingsettings', 'workshop'));
        //$mform->setExpanded('gradingsettings');
		
		//grades
		$grades=webquest_fetch_int_array(0,100);		
        $label = get_string('grade', 'webquest');
		$mform->addElement('select', 'grade', $label, $grades);
		$mform->setDefault('grade', $webquestconfig->grade);
		
		//tasks
		$ntasks=webquest_fetch_int_array(0,20);		
        $label = get_string('numbertasks', 'webquest');
		$mform->addElement('select', 'ntasks', $label, $ntasks);
		$mform->setDefault('ntasks', 1);
		//$mform->setDefault('ntasks', $webquestconfig->ntasks);
		
		//attachments
		$nattachments=webquest_fetch_int_array(0,5);		
        $label = get_string('numberofattachments', 'webquest');
		$mform->addElement('select', 'nattachments', $label, $nattachments);
		$mform->setDefault('nattachments', 0);
		//$mform->setDefault('ntasks', $webquestconfig->nattachments);
		
		//teamsmode
		$label = get_string('teamsmode', 'webquest');
		$mform->addElement('selectyesno', 'teamsmode', $label);
		
		
		//grading strategy
        $label = get_string('gradingstrategy', 'webquest');
		$options = mod_webquest_strategies();
		$mform->addElement('select', 'gradingstrategy', $label, $options);
		$mform->setDefault('gradingstrategy', 0);
		
		//just hardcoding this (bad sorry)
		$mform->addElement('hidden','processformat',1);
		$mform->addElement('hidden','taskdescformat', 1);
		$mform->addElement('hidden','conclussionformat', 1);
		$mform->setType('processformat',PARAM_INT);
		$mform->setType('taskdescformat',PARAM_INT);
		$mform->setType('conclussionformat',PARAM_INT);
		
		//submission start
		$label = get_string('submissionstart', 'webquest');
		$mform->addElement('date_time_selector', 'submissionstart', $label);
		$label = get_string('submissionend', 'webquest');
		$mform->addElement('date_time_selector', 'submissionend', $label);
		
		/*
		$gradecategories = grade_get_categories_menu($this->course->id);
        $mform->addGroup(array(
            $mform->createElement('select', 'grade', '', $grades),
            $mform->createElement('select', 'gradecategory', '', $gradecategories),
            ), 'submissiongradegroup', $label, ' ', false);
        $mform->setDefault('grade', $workshopconfig->grade);
        $mform->addHelpButton('submissiongradegroup', 'submissiongrade', 'workshop');
		*/
		
        $options = get_max_upload_sizes($CFG->maxbytes, $this->course->maxbytes, 0, $webquestconfig->maxbytes);
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'webquest'), $options);
        $mform->setDefault('maxbytes', $webquestconfig->maxbytes);

        // Availability ---------------------------------------------------------------
        // $mform->addElement('header', 'accesscontrol', get_string('availability', 'core'));



        $coursecontext = context_course::instance($this->course->id);
        plagiarism_get_form_elements_module($mform, $coursecontext, 'mod_webquest');

        // Common module settings, Restrict availability, Activity completion etc. ----
        $features = array('groups'=>true, 'groupings'=>true, 'groupmembersonly'=>true,
                'outcomes'=>true, 'gradecat'=>false, 'idnumber'=>false);

        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules ------------------------------------
        $this->add_action_buttons();
    }

}
