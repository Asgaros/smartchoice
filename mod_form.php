<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_smartchoice_mod_form extends moodleform_mod {
    function definition() {
        global $DB;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('choicequestion', 'smartchoice'), array('size'=>'64'));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'smartchoice'), 'wrap="virtual" rows="6" cols="66"');

        $mform->addElement('header', 'optionhdr', get_string('options', 'smartchoice'));

        $mform->addElement('selectyesno', 'allowmoodlevoting', get_string('allowmoodlevoting', 'smartchoice'));
        $mform->addElement('selectyesno', 'allowmultiple', get_string('allowmultiple', 'smartchoice'));

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('text', 'option', get_string('optionno', 'smartchoice'));
        $repeatarray[] = $mform->createElement('hidden', 'optionid', 0);

        if ($this->_instance){
            $repeatno = $DB->count_records('smartchoice_options', array('choiceid'=>$this->_instance));
            $repeatno += 2;
        } else {
            $repeatno = 5;
        }

        $repeateloptions = array();
        $repeateloptions['option']['helpbutton'] = array('choiceoptions', 'smartchoice');
        $mform->setType('option', PARAM_NOTAGS);
        $mform->setType('optionid', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'option_repeats', 'option_add_fields', 3, null, true);

        // Make the first option required
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

    function data_preprocessing(&$default_values){
        global $DB;
        if (!empty($this->_instance) && ($options = $DB->get_records_menu('smartchoice_options',array('choiceid'=>$this->_instance), 'id', 'id,text'))) {
            $choiceids=array_keys($options);
            $options=array_values($options);

            foreach (array_keys($options) as $key){
                $default_values['option['.$key.']'] = $options[$key];
                $default_values['optionid['.$key.']'] = $choiceids[$key];
            }

        }
        if (empty($default_values['timeopen'])) {
            $default_values['timerestrict'] = 0;
        } else {
            $default_values['timerestrict'] = 1;
        }

    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        return $data;
    }
}