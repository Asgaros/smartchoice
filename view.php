<?php

require_once("../../config.php");
require_once("lib.php");

$id         = required_param('id', PARAM_INT);                 // Course Module ID
$action     = optional_param('action', '', PARAM_ALPHA);
$notify     = optional_param('notify', '', PARAM_ALPHA);

$url = new moodle_url('/mod/smartchoice/view.php', array('id' => $id));

if (!empty($action)) {
    $url->param('action', $action);
}

$PAGE->set_url($url);

// Fehlermeldung, wenn Kursmodul nicht existiert.
if (!$cm = get_coursemodule_from_id('smartchoice', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

if (!$choice = smartchoice_get_choice($cm->instance)) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

list($choiceavailable, $warnings) = smartchoice_get_availability_status($choice);

$PAGE->set_title($choice->name);
$PAGE->set_heading($course->fullname);

// Delete some responses
if (has_capability('mod/smartchoice:deleteresponses', $context) && $action == 'delete') {
    smartchoice_delete_responses($choice); //delete responses.
    redirect('view.php?id='.$cm->id);
}

// Submit any new data if there is any
if (data_submitted() && confirm_sesskey()) {
    // Check if multiple answers are activated and set answers
    if ($choice->allowmultiple) {
        $answer = optional_param_array('answer', array(), PARAM_INT);
    } else {
        $answer = optional_param('answer', '', PARAM_INT);
    }

    if (!$choiceavailable) {
        $reason = current(array_keys($warnings));
        throw new moodle_exception($reason, 'smartchoice', '', $warnings[$reason]);
    }

    if ($answer) {
        smartchoice_user_submit_response($answer, $choice, $cm);
        redirect(new moodle_url('/mod/smartchoice/view.php', array('id' => $cm->id, 'notify' => 'choicesaved', 'sesskey' => sesskey())));
    } else if (empty($answer) and $action === 'makechoice') {
        redirect(new moodle_url('/mod/smartchoice/view.php', array('id' => $cm->id, 'notify' => 'mustchooseone', 'sesskey' => sesskey())));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($choice->name), 2, null);

// Show notifications
if ($notify and confirm_sesskey()) {
    if ($notify === 'choicesaved') {
        echo $OUTPUT->notification(get_string('choicesaved', 'smartchoice'), 'notifysuccess');
    } else if ($notify === 'mustchooseone') {
        echo $OUTPUT->notification(get_string('mustchooseone', 'smartchoice'), 'notifyproblem');
    }
}

// Generate results link
$response_data = smartchoice_get_response_data($choice);

if (has_capability('mod/smartchoice:readresponses', $context)) {
    $responsecount = 0;
    foreach($response_data as $optionid) {
        if ($optionid) {
            $responsecount += count($optionid);
        }
    }

    echo '<div class="reportlink"><a href="report.php?id='.$cm->id.'">'.get_string('viewallresponses', 'smartchoice', $responsecount).'</a></div>';
}

echo '<div class="clearer"></div>';

if ($choice->description) {
    $choice->intro = $choice->description;
    $choice->introformat = FORMAT_MOODLE;
    echo $OUTPUT->box(format_module_intro('smartchoice', $choice, $cm->id), 'generalbox', 'description');
}

$timenow = time();

// Show error message when smartchoice is expired or not opened yet.
$choiceopen = true;
if ($choice->timeclose != 0) {
    if ($choice->timeopen > $timenow ) {
        echo $OUTPUT->box(get_string('notopenyet', 'smartchoice', userdate($choice->timeopen)), 'generalbox notopenyet');
        echo $OUTPUT->footer();
        exit;
    } else if ($timenow > $choice->timeclose) {
        echo $OUTPUT->box(get_string('expired', 'smartchoice', userdate($choice->timeclose)), 'generalbox expired');
        $choiceopen = false;
    }
}

// Choice is open
if ($choiceopen && $choice->allowmoodlevoting) {
    $options = smartchoice_prepare_options($choice, $response_data);
    $renderer = $PAGE->get_renderer('mod_smartchoice');
    echo $renderer->display_options($options, $cm->id, $choice->allowmultiple);
} else {
    echo $OUTPUT->box(get_string('youcantvote', 'smartchoice'), 'generalbox notopenyet');
}

echo $OUTPUT->footer();
