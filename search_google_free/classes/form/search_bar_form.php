<?php

namespace block_search_google_free\form;

defined('MOODLE_INTERNAL') || die;

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class search_bar_form extends \moodleform
{
    // Add elements to form.
    public function definition()
    {
        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!

        // Don't ask for confirmation when the user leaves the page.
        $mform->disable_form_change_checker();

        // Add elements to your form.
        $mform->addElement('text', 'query', get_string('searchquery', 'block_search_google_free'));
        // minlenght doesn't work with 1 (it always accepts empty fields).
        // so I disable the search button below. ('required' rule is ugly!)
        $mform->addRule('query', get_string('searchquery_enter', 'block_search_google_free', 100), 'minlength', 1, 'client');
        $mform->addRule('query', get_string('searchquery_toolong', 'block_search_google_free', 100), 'maxlength', 100, 'server');
        $mform->setType('query', PARAM_TEXT);

        // Submit button.
        $this->add_action_buttons($cancel = false, $submitlabel = get_string('search'));
        $mform->disabledIf('submitbutton', 'query', 'eq', '');
    }

    // Custom validation should be added here.
    function validation($data, $files)
    {
        return [];
    }
}