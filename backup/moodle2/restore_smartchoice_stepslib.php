<?php

/**
 * Define all the restore steps that will be used by the restore_smartchoice_activity_task
 */

// Structure step to restore one choice activity
class restore_smartchoice_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('smartchoice', '/activity/smartchoice');
        $paths[] = new restore_path_element('smartchoice_option', '/activity/smartchoice/options/option');
        if ($userinfo) {
            $paths[] = new restore_path_element('smartchoice_answer', '/activity/smartchoice/answers/answer');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_smartchoice($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        // insert the choice record
        $newitemid = $DB->insert_record('smartchoice', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_smartchoice_option($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->choiceid = $this->get_new_parentid('smartchoice');
        $newitemid = $DB->insert_record('smartchoice_options', $data);
        $this->set_mapping('smartchoice_option', $oldid, $newitemid);
    }

    protected function process_smartchoice_answer($data) {
        global $DB;
        $data = (object)$data;
        $data->choiceid = $this->get_new_parentid('smartchoice');
        $data->optionid = $this->get_mappingid('smartchoice_option', $data->optionid);
        $newitemid = $DB->insert_record('smartchoice_answers', $data);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_smartchoice', 'description', null);
    }
}
