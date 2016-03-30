<?php

// Define the complete choice structure for backup, with file and id annotations
class backup_smartchoice_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $choice = new backup_nested_element('smartchoice', array('id'), array('name', 'description', 'allowmultiple', 'allowmoodlevoting', 'timeopen', 'timeclose'));

        $options = new backup_nested_element('options');
        $option = new backup_nested_element('option', array('id'), array('text'));

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', array('id'), array('optionid'));

        // Build the tree
        $choice->add_child($options);
        $options->add_child($option);

        $choice->add_child($answers);
        $answers->add_child($answer);

        // Define sources
        $choice->set_source_table('smartchoice', array('id' => backup::VAR_ACTIVITYID));
        $option->set_source_table('smartchoice_options', array('choiceid' => backup::VAR_PARENTID), 'id ASC');

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $answer->set_source_table('smartchoice_answers', array('choiceid' => '../../id'));
        }

        // Define file annotations
        $choice->annotate_files('mod_smartchoice', 'description', null); // This file area hasn't itemid

        // Return the root element (smartchoice), wrapped into standard activity structure
        return $this->prepare_activity_structure($choice);
    }
}
