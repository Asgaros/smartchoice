<?php

// Choice external functions and service definitions.

defined('MOODLE_INTERNAL') || die;

$functions = array(
    'mod_smartchoice_get_choice_results' => array(
        'classname'     => 'mod_smartchoice_external',
        'methodname'    => 'get_choice_results',
        'description'   => 'Retrieve users results for a given choice.',
        'type'          => 'read',
        'capabilities'  => ''
    ),

    'mod_smartchoice_get_choice_options' => array(
        'classname'     => 'mod_smartchoice_external',
        'methodname'    => 'get_choice_options',
        'description'   => 'Retrieve options for a specific choice.',
        'type'          => 'read',
        'capabilities'  => ''
    ),

    'mod_smartchoice_submit_choice_response' => array(
        'classname'     => 'mod_smartchoice_external',
        'methodname'    => 'submit_choice_response',
        'description'   => 'Submit responses to a specific choice item.',
        'type'          => 'write',
        'capabilities'  => ''
    ),

    'mod_smartchoice_get_choices_by_courses' => array(
        'classname'     => 'mod_smartchoice_external',
        'methodname'    => 'get_choices_by_courses',
        'description'   => 'Returns a list of choice instances in a provided set of courses, if no courses are provided then all the choice instances the user has access to will be returned.',
        'type'          => 'read',
        'capabilities'  => ''
    )
);
