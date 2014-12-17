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
 * Page module admin settings and defaults
 *
 * @package mod_webquest
 * @copyright  2014 Justin Hunt (http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");
	require_once($CFG->dirroot.'/mod/webquest/locallib.php');

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('webquestmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));
/*
    $settings->add(new admin_setting_configcheckbox('page/printheading',
        get_string('printheading', 'page'), get_string('printheadingexplain', 'page'), 1));
    $settings->add(new admin_setting_configcheckbox('page/printintro',
        get_string('printintro', 'page'), get_string('printintroexplain', 'page'), 0));
    $settings->add(new admin_setting_configselect('page/display',
        get_string('displayselect', 'page'), get_string('displayselectexplain', 'page'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
		*/
	$grades=webquest_fetch_int_array(0,100);	   
	$settings->add(new admin_setting_configselect('webquest/grade',
        get_string('grade', 'webquest'), get_string('gradeexplain', 'webquest'), 100, $grades));
		
	if (isset($CFG->maxbytes)) {
			$maxbytes = get_config('webquest', 'maxbytes');
			$options = get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes);
			$settings->add(new admin_setting_configselect('webquest/maxbytes', get_string('maximumsize', 'webquest'),
								get_string('configmaxbytes', 'webquest'), 0, $options));
    }
/*		
    $settings->add(new admin_setting_configtext('page/popupheight',
        get_string('popupheight', 'page'), get_string('popupheightexplain', 'page'), 450, PARAM_INT, 7));
*/
}
