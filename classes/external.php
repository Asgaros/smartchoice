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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/mod/smartchoice/lib.php');

// Choice module external functions
class mod_smartchoice_external extends external_api {
    // Returns results for a specific choice.
    public static function get_choice_results($choiceid) {
        $params = self::validate_parameters(self::get_choice_results_parameters(), array('choiceid' => $choiceid));

        if (!$choice = smartchoice_get_choice($params['choiceid'])) {
            throw new moodle_exception('invalidcoursemodule', 'error');
        }

        list($course, $cm) = get_course_and_cm_from_instance($choice, 'smartchoice');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $response_data = smartchoice_get_response_data($choice);
        $results = prepare_smartchoice_show_results($choice, $course, $cm, $response_data);

        $options = array();
        foreach ($results->options as $optionid => $option) {
            $numberofvotes = 0;
            $percentageamount = 0;

            if (property_exists($option, 'user') and has_capability('mod/smartchoice:readresponses', $context)) {
                $numberofvotes = count($option->user);
                $percentageamount = ((float)$numberofvotes / (float)$results->numberofvotes) * 100.0;
            }

            $options[] = array('id'               => $optionid,
                               'text'             => external_format_string($option->text, $context->id),
                               'numberofvotes'     => $numberofvotes,
                               'percentageamount' => $percentageamount
                              );
        }

        $warnings = array();

        return array(
            'options' => $options,
            'warnings' => $warnings
        );
    }

    public static function get_choice_results_parameters() {
        return new external_function_parameters (array('choiceid' => new external_value(PARAM_INT, 'choice instance id')));
    }

    public static function get_choice_results_returns() {
        return new external_single_structure(
            array(
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'choice option instance id'),
                            'text' => new external_value(PARAM_RAW, 'text of the choice'),
                            'numberofvotes' => new external_value(PARAM_INT, 'number of votes answers'),
                            'percentageamount' => new external_value(PARAM_FLOAT, 'percentage of answers')
                        ), 'Options'
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    // Returns options for a specific choice
    public static function get_choice_options($choiceid) {
        $warnings = array();
        $params = self::validate_parameters(self::get_choice_options_parameters(), array('choiceid' => $choiceid));

        if (!$choice = smartchoice_get_choice($params['choiceid'])) {
            throw new moodle_exception("invalidcoursemodule", "error");
        }
        list($course, $cm) = get_course_and_cm_from_instance($choice, 'smartchoice');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $response_data = smartchoice_get_response_data($choice);

        $timenow = time();
        $choiceopen = true;

        if ($choice->timeclose != 0) {
            if ($choice->timeopen > $timenow) {
                $choiceopen = false;
                $warnings[1] = get_string('notopenyet', 'smartchoice', userdate($choice->timeopen));
            }

            if ($timenow > $choice->timeclose) {
                $choiceopen = false;
                $warnings[3] = get_string('expired', 'smartchoice', userdate($choice->timeclose));
            }
        }

        $optionsarray = array();

        if ($choiceopen) {
            $options = smartchoice_prepare_options($choice, $response_data);

            foreach ($options['options'] as $option) {
                $optionarr = array();
                $optionarr['id']            = $option->attributes->value;
                $optionarr['text']          = external_format_string($option->text, $context->id);
                $optionarr['countanswers']  = $option->countanswers;
                $optionsarray[] = $optionarr;
            }
        }

        foreach ($warnings as $key => $message) {
            $warnings[$key] = array(
                'item' => 'smartchoice',
                'itemid' => $cm->id,
                'warningcode' => $key,
                'message' => $message
            );
        }

        return array(
            'options' => $optionsarray,
            'warnings' => $warnings
        );
    }

    public static function get_choice_options_parameters() {
        return new external_function_parameters (array('choiceid' => new external_value(PARAM_INT, 'choice instance id')));
    }

    public static function get_choice_options_returns() {
        return new external_single_structure(
            array(
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'option id'),
                            'text' => new external_value(PARAM_RAW, 'text of the choice'),
                            'countanswers' => new external_value(PARAM_INT, 'number of answers')
                            )
                    ), 'Options'
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    // Submit choice responses
    public static function submit_choice_response($choiceid, $responses) {
        $warnings = array();
        $params = self::validate_parameters(self::submit_choice_response_parameters(), array('choiceid' => $choiceid, 'responses' => $responses));

        if (!$choice = smartchoice_get_choice($params['choiceid'])) {
            throw new moodle_exception('invalidcoursemodule', 'error');
        }

        list($course, $cm) = get_course_and_cm_from_instance($choice, 'smartchoice');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $timenow = time();
        if ($choice->timeclose != 0) {
            if ($choice->timeopen > $timenow) {
                throw new moodle_exception('notopenyet', 'smartchoice', '', userdate($choice->timeopen));
            } else if ($timenow > $choice->timeclose) {
                throw new moodle_exception('expired', 'smartchoice', '', userdate($choice->timeclose));
            }
        }

        // When a single response is given, we convert the array to a simple variable
        // in order to avoid smartchoice_user_submit_response to check with allowmultiple even
        // for a single response.
        if (count($params['responses']) == 1) {
            $params['responses'] = reset($params['responses']);
        }

        smartchoice_user_submit_response($params['responses'], $choice, $cm);

        return array(
            'answered' => 'Answered',
            'warnings' => $warnings
        );
    }

    public static function submit_choice_response_parameters() {
        return new external_function_parameters (
            array(
                'choiceid' => new external_value(PARAM_INT, 'choice instance id'),
                'responses' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'answer id'),
                    'Array of response ids'
                ),
            )
        );
    }

    public static function submit_choice_response_returns() {
        return new external_single_structure(
            array(
                'answered' => new external_value(PARAM_RAW, 'answered flag'),
                'warnings' => new external_warnings(),
            )
        );
    }

    // Returns a list of choices in a provided list of courses, if no list is provided all choices that the user can view will be returned.
    public static function get_choices_by_courses($courseids = array()) {
        $returnedchoices = array();
        $warnings = array();

        $params = self::validate_parameters(self::get_choices_by_courses_parameters(), array('courseids' => $courseids));

        if (empty($params['courseids'])) {
            $params['courseids'] = array_keys(enrol_get_my_courses());
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {
            list($courses, $warnings) = external_util::validate_courses($params['courseids']);

            // Get the choices in this course.
            $choices = get_all_instances_in_courses('smartchoice', $courses);
            foreach ($choices as $choice) {
                $context = context_module::instance($choice->coursemodule);
                // Entry to return.
                $choicedetails = array();
                $choicedetails['id'] = $choice->id;
                $choicedetails['coursemodule'] = $choice->coursemodule;
                $choicedetails['course'] = $choice->course;
                $choicedetails['name']  = external_format_string($choice->name, $context->id);
                $choicedetails['description'] = external_format_string($choice->description, $context->id);
                $choicedetails['timeopen']  = $choice->timeopen;
                $choicedetails['timeclose']  = $choice->timeclose;
                $choicedetails['allowmultiple']  = $choice->allowmultiple;

                $returnedchoices[] = $choicedetails;
            }
        }
        $result = array();
        $result['choices'] = $returnedchoices;
        $result['warnings'] = $warnings;
        return $result;
    }

    public static function get_choices_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    public static function get_choices_by_courses_returns() {
        return new external_single_structure(
            array(
                'choices' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Choice instance id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'Choice name'),
                            'description' => new external_value(PARAM_RAW, 'The choice intro'),
                            'allowmultiple' => new external_value(PARAM_BOOL, 'Allow multiple choices', VALUE_OPTIONAL),
                            'timeopen' => new external_value(PARAM_INT, 'Date of opening validity', VALUE_OPTIONAL),
                            'timeclose' => new external_value(PARAM_INT, 'Date of closing validity', VALUE_OPTIONAL)
                        ), 'Choices'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }
}
