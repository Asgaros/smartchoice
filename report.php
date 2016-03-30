<?php

require_once('../../config.php');
require_once('lib.php');

$id         = required_param('id', PARAM_INT);   //moduleid
$download   = optional_param('download', '', PARAM_ALPHA);
$action     = optional_param('action', '', PARAM_ALPHA);

$url = new moodle_url('/mod/smartchoice/report.php', array('id' => $id));
$url->param('format', 0);

if ($download !== '') {
    $url->param('download', $download);
}

if ($action !== '') {
    $url->param('action', $action);
}

$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('smartchoice', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/smartchoice:readresponses', $context);

if (!$choice = smartchoice_get_choice($cm->instance)) {
    print_error('invalidcoursemodule');
}

$strresponses = get_string('responses', 'smartchoice');

if (!$download) {
    $PAGE->navbar->add($strresponses);
    $PAGE->set_title(format_string($choice->name).': $strresponses');
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($choice->name, 2, null);
}

$response_data = smartchoice_get_response_data($choice);

if ($download == "ods" && has_capability('mod/smartchoice:downloadresponses', $context)) {
    require_once("$CFG->libdir/odslib.class.php");

    $filename = clean_filename("$course->shortname ".strip_tags(format_string($choice->name,true))).'.ods';
    $workbook = new MoodleODSWorkbook("-");
    $workbook->send($filename);
    $myxls = $workbook->add_worksheet($strresponses);

    // Print names of all the fields
    $myxls->write_string(0,0,get_string("smartchoice","smartchoice"));

    $row=1;
    if ($response_data) {
        $results = prepare_smartchoice_show_results($choice, $course, $cm, $response_data);

        if ($results) {
            $myxls->write_string($row,0,format_string($results->name,true));
            $row++;
            $myxls->write_string($row,0,format_string(get_string('numberofvotes', 'smartchoice').': '.$results->numberofvotes, true));
            $row++;
            $row++;

            $summary = get_smartchoiche_results_summary($results);

            if ($summary) {
                foreach ($summary as $option) {
                    $myxls->write_string($row, 0, format_string($option['text'], true));
                    $myxls->write_string($row, 1, format_string(get_string('numberofvotes', 'smartchoice').': '.$option['votes'], true));
                    $myxls->write_string($row, 2, format_string(get_string('numberofvotesinpercentage', 'smartchoice').': '.$option['percentage'], true));
                    $row++;
                }
            }
        }
    }

    $workbook->close();

    exit;
} else if ($download == "xls" && has_capability('mod/smartchoice:downloadresponses', $context)) {
    require_once($CFG->libdir.'/excellib.class.php');

    $choicename = strip_tags(format_string($choice->name, true));
    $filename = clean_filename($course->shortname.' '.$choicename).'.xls';
    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);
    $myxls = $workbook->add_worksheet($strresponses);

    // Print names of all the fields
    $myxls->write_string(0, 0, get_string('smartchoice', 'smartchoice'));

    $row=1;
    if ($response_data) {
        $results = prepare_smartchoice_show_results($choice, $course, $cm, $response_data);

        if ($results) {
            $myxls->write_string($row,0,format_string($results->name,true));
            $row++;
            $myxls->write_string($row,0,format_string(get_string('numberofvotes', 'smartchoice').': '.$results->numberofvotes, true));
            $row++;
            $row++;

            $summary = get_smartchoiche_results_summary($results);

            if ($summary) {
                foreach ($summary as $option) {
                    $myxls->write_string($row, 0, format_string($option['text'], true));
                    $myxls->write_string($row, 1, format_string(get_string('numberofvotes', 'smartchoice').': '.$option['votes'], true));
                    $myxls->write_string($row, 2, format_string(get_string('numberofvotesinpercentage', 'smartchoice').': '.$option['percentage'], true));
                    $row++;
                }
            }
        }
    }

    // Close the workbook
    $workbook->close();
    exit;
} else if ($download == "txt" && has_capability('mod/smartchoice:downloadresponses', $context)) {
    $filename = clean_filename("$course->shortname ".strip_tags(format_string($choice->name,true))).'.txt';

    header("Content-Type: application/octet-stream\n");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");

    echo get_string("smartchoice","smartchoice"). "\r\n\r\n";

    if ($response_data) {
        $results = prepare_smartchoice_show_results($choice, $course, $cm, $response_data);

        if ($results) {
            echo $results->name."\r\n";
            echo get_string('numberofvotes', 'smartchoice').": ".$results->numberofvotes."\r\n\r\n";

            $summary = get_smartchoiche_results_summary($results);

            if ($summary) {
                foreach ($summary as $option) {
                    echo $option['text'].":\r\n";
                    echo get_string('numberofvotes', 'smartchoice').": ".$option['votes']."\r\n";
                    echo get_string('numberofvotesinpercentage', 'smartchoice').": ".$option['percentage']."\r\n\r\n";
                }
            }
        }
    }
    exit;
}

$results = prepare_smartchoice_show_results($choice, $course, $cm, $response_data);
$renderer = $PAGE->get_renderer('mod_smartchoice');
echo $renderer->display_publish_anonymous_vertical($results);

// Generate download links
if (!empty($response_data) && has_capability('mod/smartchoice:downloadresponses',$context)) {
    $downloadoptions = array();
    $options = array();
    $options["id"] = "$cm->id";
    $options["download"] = "ods";
    $button =  $OUTPUT->single_button(new moodle_url("report.php", $options), get_string("downloadods"));
    $downloadoptions[] = html_writer::tag('li', $button, array('class'=>'reportoption'));

    $options["download"] = "xls";
    $button = $OUTPUT->single_button(new moodle_url("report.php", $options), get_string("downloadexcel"));
    $downloadoptions[] = html_writer::tag('li', $button, array('class'=>'reportoption'));

    $options["download"] = "txt";
    $button = $OUTPUT->single_button(new moodle_url("report.php", $options), get_string("downloadtext"));
    $downloadoptions[] = html_writer::tag('li', $button, array('class'=>'reportoption'));

    $downloadlist = html_writer::tag('ul', implode('', $downloadoptions));
    $downloadlist .= html_writer::tag('div', '', array('class'=>'clearfloat'));
    echo html_writer::tag('div',$downloadlist, array('class'=>'downloadreport'));
}
echo $OUTPUT->footer();
