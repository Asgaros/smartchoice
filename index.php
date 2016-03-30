<?php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // Course-ID
$PAGE->set_url('/mod/smartchoice/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$strchoices = get_string('modulenameplural', 'smartchoice');
$PAGE->set_title($strchoices);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strchoices);
echo $OUTPUT->header();

if (!$choices = get_all_instances_in_course('smartchoice', $course)) {
    notice(get_string('thereareno', 'moodle', $strchoices), '../../course/view.php?id='.$course->id);
}

$table = new html_table();
$table->head  = array(get_string('question'));
$table->align = array('left');

foreach ($choices as $choice) {
    $tt_href = '<a href="view.php?id='.$choice->coursemodule.'">'.format_string($choice->name, true).'</a>';
    $table->data[] = array($tt_href);
}

echo html_writer::table($table);
echo $OUTPUT->footer();
