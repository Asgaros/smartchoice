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

defined('MOODLE_INTERNAL') || die();

// Define the complete choice structure for backup, with file and id annotations.
class backup_smartchoice_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $choice = new backup_nested_element(
            'smartchoice',
            array('id'),
            array('name', 'description', 'allowmultiple', 'allowmoodlevoting', 'timeopen', 'timeclose')
        );

        $options = new backup_nested_element('options');
        $option = new backup_nested_element('option', array('id'), array('text'));

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', array('id'), array('optionid'));

        // Build the tree.
        $choice->add_child($options);
        $options->add_child($option);

        $choice->add_child($answers);
        $answers->add_child($answer);

        // Define sources.
        $choice->set_source_table('smartchoice', array('id' => backup::VAR_ACTIVITYID));
        $option->set_source_table('smartchoice_options', array('choiceid' => backup::VAR_PARENTID), 'id ASC');

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $answer->set_source_table('smartchoice_answers', array('choiceid' => '../../id'));
        }

        // Define file annotations.
        $choice->annotate_files('mod_smartchoice', 'description', null);

        // Return the root element (smartchoice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($choice);
    }
}
