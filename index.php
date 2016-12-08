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

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
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
    $tthref = '<a href="view.php?id='.$choice->coursemodule.'">'.format_string($choice->name, true).'</a>';
    $table->data[] = array($tthref);
}

echo html_writer::table($table);
echo $OUTPUT->footer();
