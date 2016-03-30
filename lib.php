<?php

// This function will create a new smartchoice instance and return the id number of it.
function smartchoice_add_instance($choice) {
    global $DB;

    if (empty($choice->timerestrict)) {
        $choice->timeopen = 0;
        $choice->timeclose = 0;
    }

    $choice->id = $DB->insert_record('smartchoice', $choice);

    // Insert answers
    foreach ($choice->option as $key => $value) {
        $value = trim($value);

        if (!empty($value)) {
            $option = new stdClass();
            $option->text = $value;
            $option->choiceid = $choice->id;
            $DB->insert_record('smartchoice_options', $option);
        }
    }

    return $choice->id;
}

// This function will update an existing smartchoice instance.
function smartchoice_update_instance($choice) {
    global $DB;

    $choice->id = $choice->instance;

    if (empty($choice->timerestrict)) {
        $choice->timeopen = 0;
        $choice->timeclose = 0;
    }

    // Update, delete or insert answers
    foreach ($choice->option as $key => $value) {
        $value = trim($value);
        $option = new stdClass();
        $option->text = $value;
        $option->choiceid = $choice->id;

        if (!empty($choice->optionid[$key])) {
            $option->id = $choice->optionid[$key];

            if (!empty($value)) {
                $DB->update_record('smartchoice_options', $option);
            } else {
                $DB->delete_records('smartchoice_options', array('id' => $option->id));
            }
        } else {
            if (!empty($value)) {
                $DB->insert_record('smartchoice_options', $option);
            }
        }
    }

    return $DB->update_record('smartchoice', $choice);
}

function smartchoice_prepare_options($choice, $allresponses) {
    $cdisplay = array('options' => array());

    foreach ($choice->option as $optionid => $text) {
        if (isset($text)) { // Make sure there are no entries in the db with blank text values.
            $option = new stdClass;
            $option->attributes = new stdClass;
            $option->attributes->value = $optionid;
            $option->text = format_string($text);

            if (isset($allresponses[$optionid])) {
                $option->countanswers = count($allresponses[$optionid]);
            } else {
                $option->countanswers = 0;
            }

            $cdisplay['options'][] = $option;
        }
    }

    return $cdisplay;
}

// Process user submitted answers for a choice.
function smartchoice_user_submit_response($formanswer, $choice, $cm) {
    global $DB;

    $continueurl = new moodle_url('/mod/smartchoice/view.php', array('id' => $cm->id));

    // No answers selected
    if (empty($formanswer)) {
        print_error('atleastoneoption', 'smartchoice', $continueurl);
    }

    if (is_array($formanswer)) {
        if (!$choice->allowmultiple) {
            print_error('multiplenotallowederror', 'smartchoice', $continueurl);
        }
        $formanswers = $formanswer;
    } else {
        $formanswers = array($formanswer);
    }

    $options = $DB->get_records('smartchoice_options', array('choiceid' => $choice->id), '', 'id');
    foreach ($formanswers as $key => $val) {
        if (!isset($options[$val])) {
            print_error('cannotsubmit', 'smartchoice', $continueurl);
        }
    }

    // Add new answer.
    foreach ($formanswers as $answer) {
        $newanswer = new stdClass();
        $newanswer->choiceid = $choice->id;
        $newanswer->optionid = $answer;
        $DB->insert_record('smartchoice_answers', $newanswer);
    }
}

function prepare_smartchoice_show_results($choice, $course, $cm, $allresponses) {
    global $OUTPUT;

    $display = clone($choice);
    $display->coursemoduleid = $cm->id;
    $display->courseid = $course->id;

    // Overwrite options value;
    $display->options = array();
    $totaluser = 0;
    foreach ($choice->option as $optionid => $optiontext) {
        $display->options[$optionid] = new stdClass;
        $display->options[$optionid]->text = $optiontext;

        if (array_key_exists($optionid, $allresponses)) {
            $display->options[$optionid]->user = $allresponses[$optionid];
            $totaluser += count($allresponses[$optionid]);
        }
    }
    unset($display->option);

    $display->numberofvotes = $totaluser;

    if (empty($allresponses)) {
        echo $OUTPUT->heading(get_string("nousersyet"), 3, null);
        return false;
    }

    return $display;
}

function get_smartchoiche_results_summary($results) {
    if (empty($results)) {
        return;
    }

    $summary = array();

    foreach ($results->options as $optionid => $option) {
        $percentageamount = 0;
        $numberofvotes = 0;
        $usernumber = $userpercentage = '';

        if (!empty($option->user)) {
           $numberofvotes = count($option->user);
        }

        if($results->numberofvotes > 0) {
           $percentageamount = ((float)$numberofvotes / (float)$results->numberofvotes) * 100.0;
        }

        $summary[$optionid]['text'] = $option->text;
        $summary[$optionid]['percentage'] = $percentageamount;
        $summary[$optionid]['votes'] = $numberofvotes;
    }

    return $summary;
}

function smartchoice_delete_responses($choice) {
    global $DB;
    $DB->delete_records('smartchoice_answers', array('choiceid' => $choice->id));
    return true;
}


// Deletes an instance of this module and any data that depends on it.
function smartchoice_delete_instance($id) {
    global $DB;

    if (!$choice = $DB->get_record('smartchoice', array('id' => "$id"))) {
        return false;
    }

    $result = true;

    if (!$DB->delete_records('smartchoice_answers', array('choiceid' => "$choice->id"))) {
        $result = false;
    }

    if (!$DB->delete_records('smartchoice_options', array('choiceid' => "$choice->id"))) {
        $result = false;
    }

    if (!$DB->delete_records('smartchoice', array('id' => "$choice->id"))) {
        $result = false;
    }

    return $result;
}

// Returns text string which is the answer that matches the id.
function smartchoice_get_option_text($id) {
    global $DB;

    if ($result = $DB->get_record('smartchoice_options', array('id' => $id))) {
        return $result->text;
    } else {
        return get_string('notanswered', 'smartchoice');
    }
}

// Gets a full choice record.
function smartchoice_get_choice($choiceid) {
    global $DB;

    if ($choice = $DB->get_record('smartchoice', array('id' => $choiceid))) {
        if ($options = $DB->get_records('smartchoice_options', array('choiceid' => $choiceid), 'id')) {
            foreach ($options as $option) {
                $choice->option[$option->id] = $option->text;
            }

            return $choice;
        }
    }

    return false;
}

// Implementation of the function for printing the form elements that control whether the course reset functionality affects the choice.
function smartchoice_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'choiceheader', get_string('modulenameplural', 'smartchoice'));
    $mform->addElement('advcheckbox', 'reset_choice', get_string('removeresponses','smartchoice'));
}

// Course reset form defaults.
function smartchoice_reset_course_form_defaults($course) {
    return array('reset_choice'=>1);
}

// Implementation of the reset course functionality. Delete all the choice responses for course $data->courseid.
function smartchoice_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'smartchoice');
    $status = array();

    if (!empty($data->reset_choice)) {
        $choicessql = "SELECT ch.id FROM {choice} ch WHERE ch.course=?";
        $DB->delete_records_select('smartchoice_answers', "choiceid IN ($choicessql)", array($data->courseid));
        $status[] = array('component' => $componentstr, 'item' => get_string('removeresponses', 'smartchoice'), 'error' => false);
    }

    // Updating dates.
    if ($data->timeshift) {
        shift_course_mod_dates('smartchoice', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

function smartchoice_get_response_data($choice) {
    global $DB;
    $allresponses = array();
    $rawresponses = $DB->get_records('smartchoice_answers', array('choiceid' => $choice->id));

    if ($rawresponses) {
        foreach ($rawresponses as $response) {
            $allresponses[$response->optionid][] = $response->id;
        }
    }

    return $allresponses;
}

// Defines the supported moodle functions
function smartchoice_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                        return false;
        case FEATURE_GROUPINGS:                     return false;
        case FEATURE_MOD_INTRO:                     return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:       return false;
        case FEATURE_COMPLETION_HAS_RULES:          return false;
        case FEATURE_GRADE_HAS_GRADE:               return false;
        case FEATURE_GRADE_OUTCOMES:                return false;
        case FEATURE_ADVANCED_GRADING:              return false;
        case FEATURE_CONTROLS_GRADE_VISIBILITY:     return false;
        case FEATURE_SHOW_DESCRIPTION:              return false;
        case FEATURE_ADVANCED_GRADING:              return false;
        case FEATURE_PLAGIARISM:                    return false;
        case FEATURE_RATE:                          return false;
        case FEATURE_IDNUMBER:                      return false;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:    return false;
        case FEATURE_BACKUP_MOODLE2:                return true;
        default:                                    return null;
    }
}

// Extend activity navigation
function smartchoice_extend_settings_navigation(settings_navigation $settings, navigation_node $choicenode) {
    global $PAGE;

    if (has_capability('mod/smartchoice:readresponses', $PAGE->cm->context)) {
        $choice = smartchoice_get_choice($PAGE->cm->instance);
        $response_data = smartchoice_get_response_data($choice);
        $responsecount = 0;

        foreach($response_data as $optionid => $userlist) {
            if ($optionid) {
                $responsecount += count($userlist);
            }
        }

        $choicenode->add(get_string('viewallresponses', 'smartchoice', $responsecount), new moodle_url('/mod/smartchoice/report.php', array('id' => $PAGE->cm->id)));
    }

    // Add reset button
    if (has_capability('mod/smartchoice:deleteresponses', $PAGE->cm->context)) {
        $choicenode->add(get_string('resetchoice', 'smartchoice'), new moodle_url('/mod/smartchoice/view.php', array('id' => $PAGE->cm->id, 'action' => 'delete')));
    }
}

// Get all the responses on a given choice.
function smartchoice_get_all_responses($choice) {
    global $DB;
    return $DB->get_records('smartchoice_answers', array('choiceid' => $choice->id));
}

// Check if a choice is available.
function smartchoice_get_availability_status($choice) {
    $available = true;
    $warnings = array();

    if ($choice->timeclose != 0) {
        $timenow = time();

        if ($choice->timeopen > $timenow) {
            $available = false;
            $warnings['notopenyet'] = userdate($choice->timeopen);
        } else if ($timenow > $choice->timeclose) {
            $available = false;
            $warnings['expired'] = userdate($choice->timeclose);
        }
    }

    return array($available, $warnings);
}
