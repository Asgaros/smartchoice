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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_smartchoice_mod_form extends moodleform_mod {
    public function definition() {
        global $DB;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('choicequestion', 'smartchoice'), array('size' => '64'));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('description', 'smartchoice'),
            'wrap="virtual" rows="6" cols="66"'
        );

        $mform->addElement('header', 'optionhdr', get_string('options', 'smartchoice'));

        $mform->addElement('selectyesno', 'allowmoodlevoting', get_string('allowmoodlevoting', 'smartchoice'));
        $mform->addElement('selectyesno', 'allowmultiple', get_string('allowmultiple', 'smartchoice'));

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('text', 'option', get_string('optionno', 'smartchoice'));
        $repeatarray[] = $mform->createElement('hidden', 'optionid', 0);

        if ($this->_instance) {
            $repeatno = $DB->count_records('smartchoice_options', array('choiceid' => $this->_instance));
            $repeatno += 2;
        } else {
            $repeatno = 5;
        }

        $repeateloptions = array();
        $repeateloptions['option']['helpbutton'] = array('choiceoptions', 'smartchoice');
        $mform->setType('option', PARAM_NOTAGS);
        $mform->setType('optionid', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'option_repeats', 'option_add_fields', 3, null, true);

        // Make the first option required.
        if ($mform->elementExists('option[0]')) {
            $mform->addRule('option[0]', get_string('atleastoneoption', 'smartchoice'), 'required', null, 'client');
        }

        $mform->addElement('checkbox', 'timerestrict', get_string('timerestrict', 'smartchoice'));

        $mform->addElement('date_time_selector', 'timeopen', get_string("choiceopen", "smartchoice"));
        $mform->disabledIf('timeopen', 'timerestrict');
        $mform->addElement('date_time_selector', 'timeclose', get_string("choiceclose", "smartchoice"));
        $mform->disabledIf('timeclose', 'timerestrict');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultValues) {
        global $DB;
        if (!empty($this->_instance) &&
            ($options = $DB->get_records_menu('smartchoice_options', array('choiceid' => $this->_instance), 'id', 'id,text'))
        ) {
            $choiceids = array_keys($options);
            $options = array_values($options);

            foreach (array_keys($options) as $key) {
                $defaultValues['option['.$key.']'] = $options[$key];
                $defaultValues['optionid['.$key.']'] = $choiceids[$key];
            }

        }
        if (empty($defaultValues['timeopen'])) {
            $defaultValues['timerestrict'] = 0;
        } else {
            $defaultValues['timerestrict'] = 1;
        }

    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        return $data;
    }
}
