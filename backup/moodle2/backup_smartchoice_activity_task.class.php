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

// Defines backup_smartchoice_activity_task class
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/smartchoice/backup/moodle2/backup_smartchoice_stepslib.php');
require_once($CFG->dirroot . '/mod/smartchoice/backup/moodle2/backup_smartchoice_settingslib.php');

// Provides the steps to perform one complete backup of the Smartchoice instance
class backup_smartchoice_activity_task extends backup_activity_task {
    // No specific settings for this activity
    protected function define_my_settings() {}

    // Defines a backup step to store the instance data in the smartchoice.xml file
    protected function define_my_steps() {
        $this->add_step(new backup_smartchoice_activity_structure_step('smartchoice_structure', 'smartchoice.xml'));
    }

    // Encodes URLs to the index.php and view.php scripts
    static public function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of choices
        $search="/(".$base."\/mod\/smartchoice\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHOICEINDEX*$2@$', $content);

        // Link to choice view by moduleid
        $search="/(".$base."\/mod\/smartchoice\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHOICEVIEWBYID*$2@$', $content);

        return $content;
    }
}
