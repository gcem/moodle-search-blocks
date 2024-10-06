<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block search_google_free is defined here.
 *
 * @package     block_search_google_free
 * @copyright   2024, Cem Gündoğdu <cemGündoğdudev@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_search_google_free extends block_base
{
    /**
     * @var Google_Service_Customsearch
     */
    protected $service = null;

    /**
     * Initializes class member variables.
     */
    public function init()
    {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_search_google_free');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content()
    {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $apikey = get_config('block_search_google_free', 'apikey');
        $search_engine_id = get_config('block_search_google_free', 'psearchengineid');

        $errors = array();
        if (!$apikey) {
            $errors[] = get_string('apikey_notset', 'block_search_google_free');
        }
        if (!$search_engine_id) {
            $errors[] = get_string('psearchengineid_notset', 'block_search_google_free');
        }

        if (!empty($errors)) {
            $this->content->text = $this->render_errors($errors);
            return $this->content;
        }

        // the argument fixes the "nocourseid" error by creating hidden fields
        $mform = new \block_search_google_free\form\search_bar_form($PAGE->url);

        $max_queries_per_user_per_day = get_config('block_search_google_free', 'maxqueriesperuserperday');

        $this->content->text = $mform->render();
        if ($formdata = $mform->get_data()) {
            $usage_count = $this->get_and_increment_usage_count();
            if ($max_queries_per_user_per_day != -1) {
                $this->content->text .= $this->render_usage_count($usage_count, $max_queries_per_user_per_day);
                if ($usage_count > $max_queries_per_user_per_day) {
                    return $this->content;
                }
            }

            // restore search string
            $mform->set_data($formdata);

            $query = $formdata->query;
            $service = $this->get_service($apikey);

            try {
                $response = $service->cse->listCse($query, array('cx' => $search_engine_id));
            } catch (Google_Service_Exception $e) {
                debugging('Google service exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $errors[] = get_string('requesterror', 'block_search_google_free');
                $this->content->text = $this->render_errors($errors);
                return $this->content;
            }

            $items = $response->items;
            $display_type = $this->config->displaytype;
            if ($display_type == 'json') {
                $this->content->text .= $this->render_items_as_json($items, $query);
            } else {
                // the default is the list
                $this->content->text .= $this->render_items_as_list($items, $query);
            }
        }

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization()
    {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_search_google_free');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Allow multiple instances in a single course?
     *
     * @return bool True if multiple instances are allowed, false otherwise.
     */
    public function instance_allow_multiple()
    {
        return true;
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config()
    {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats()
    {
        return array(
            'all' => true,
        );
    }

    /**
     * Renders the given list of errors.
     */
    protected function render_errors($errors)
    {
        $error_text = html_writer::start_tag('ul', array('class' => 'unlist'));
        foreach ($errors as $error) {
            $error_text .= html_writer::tag('li', $error);
        }
        $error_text .= html_writer::end_tag('ul');
        return $error_text;
    }

    /**
     * Renders the usage count.
     */
    protected function render_usage_count($usage_count, $max_queries_per_user_per_day)
    {
        global $OUTPUT;

        if ($usage_count > $max_queries_per_user_per_day) {
            return $OUTPUT->render_from_template("block_search_google_free/daily-limit-reached", array());
        } else {
            return $OUTPUT->render_from_template("block_search_google_free/daily-searches-remaining", array('remaining' => $max_queries_per_user_per_day - $usage_count));
        }
    }

    /**
     * Renders the (items array in) API response as HTML to display raw JSON text.
     */
    protected function render_items_as_json($items, $query)
    {
        global $OUTPUT;
        $json = json_encode($items);
        return $OUTPUT->render_from_template("block_search_google_free/result-json", array('json-text' => $json, 'query' => $query));
    }

    /**
     *Renders the items as HTML to display a list.
     */
    protected function render_items_as_list($items, $query)
    {
        global $OUTPUT;
        return $OUTPUT->render_from_template("block_search_google_free/result-list", array('items' => $items, 'query' => $query));
    }

    /**
     * Gets the custom search service object.
     *
     * @return Google_Service_Customsearch
     */
    protected function get_service($apikey)
    {
        global $CFG;

        if (!isset($this->service)) {
            require_once($CFG->libdir . '/google/lib.php');
            $client = get_google_client();
            $client->setDeveloperKey($apikey);
            $this->service = new Google_Service_Customsearch($client);
        }

        return $this->service;
    }

    /**
     * Increments usage count for current user.
     * 
     * @return int The new usage count.
     */
    protected function get_and_increment_usage_count()
    {
        global $USER, $DB;

        $table = 'block_search_google_free_usage_count';
        $date = $this->get_date_string();

        $db_obj = $DB->get_record($table, array('id' => $USER->id));
        error_log('db_obj: ' . print_r($db_obj, true));
        if (!$db_obj || $db_obj->date != $date) {
            $count = 0;
        } else {
            $count = $db_obj->count;
        }
        $count++;
        $new_obj = array('id' => $USER->id, 'date' => $date, 'count' => $count);

        if (!$db_obj) {
            $DB->insert_record_raw($table, $new_obj, customsequence: true);
        } else {
            $DB->update_record($table, $new_obj);
        }

        return $count;
    }

    /**
     * Gets today's date as a string (Ymd) in server time.
     * 
     * @return string
     */
    protected function get_date_string()
    {
        $time = new DateTime('now', core_date::get_server_timezone_object());
        return $time->format('Ymd');
    }
}
