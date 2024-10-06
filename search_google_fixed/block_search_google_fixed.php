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
 * Block search_google_fixed is defined here.
 *
 * @package     block_search_google_fixed
 * @copyright   2024, Cem Gündoğdu <cemGündoğdudev@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_search_google_fixed extends block_base
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
        $this->title = get_string('pluginname', 'block_search_google_fixed');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content()
    {

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
        $this->content->text = '';

        $apikey = get_config('block_search_google_fixed', 'apikey');
        $search_engine_id = get_config('block_search_google_fixed', 'psearchengineid');
        $query = $this->config->query;

        $errors = array();
        if (!$apikey) {
            $errors[] = get_string('apikey_notset', 'block_search_google_fixed');
        }
        if (!$search_engine_id) {
            $errors[] = get_string('psearchengineid_notset', 'block_search_google_fixed');
        }
        if (empty($query)) {
            $errors[] = get_string('searchquery_notset', 'block_search_google_fixed');
        }

        if (!empty($errors)) {
            $this->content->text .= $this->render_errors($errors);
            return $this->content;
        }

        $cached_text = $this->get_cached_result($query);

        if (!$cached_text) {
            $service = $this->get_service($apikey);

            try {
                $response = $service->cse->listCse($query, array('cx' => $search_engine_id));
            } catch (Google_Service_Exception $e) {
                debugging('Google service exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $errors[] = get_string('requesterror', 'block_search_google_fixed');
                $this->content->text .= $this->render_errors($errors);
                return $this->content;
            }

            $items = $response->items;
            $this->cache_result($query, json_encode($items));
            $cache_info = get_string('newresults', 'block_search_google_fixed');
        } else {
            $items = json_decode($cached_text);
            $cache_info = get_string('cachedresults', 'block_search_google_fixed');
        }


        $display_type = $this->config->displaytype;

        if ($display_type == 'json') {
            $this->content->text .= $this->render_items_as_json($items, $query, $cache_info);
        } else {
            // the default is the list
            $this->content->text .= $this->render_items_as_list($items, $query, $cache_info);
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
            $this->title = get_string('pluginname', 'block_search_google_fixed');
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
            'all' => false,
            'course-view' => true,
            'course-view-social' => false,
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
     * Renders the item list as JSON.
     */
    protected function render_items_as_json($items, $query, $cache_string)
    {
        global $OUTPUT;

        // TODO CEM maybe pretty print
        $json = json_encode($items);

        // return html_writer::tag('div', $json, array('class' => 'json-response'));
        return $OUTPUT->render_from_template("block_search_google_fixed/result-json", array('json-text' => $json, 'query' => $query, 'cache_string' => $cache_string));
    }

    /**
     * Renders the search results as HTML.
     */
    protected function render_items_as_list($items, $query, $cache_string)
    {
        global $OUTPUT;

        return $OUTPUT->render_from_template("block_search_google_fixed/result-list", array('items' => $items, 'query' => $query, 'cache_string' => $cache_string));
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
     * Gets the cached result for the given query.
     *
     * @param string $query The search query.
     * @return string|null The cached result.
     */
    protected function get_cached_result($query)
    {
        global $DB;

        $table = 'block_search_google_fixed_cached_search';

        // in hours
        $ttl = get_config('block_search_google_fixed', 'cachettl');
        if ($ttl <= 0) {
            $DB->delete_records($table);
            return null;
        }

        $hash = hash('sha256', $query);
        $select = sprintf('%s = :hash', $DB->sql_compare_text('id'));
        $db_obj = $DB->get_record_select($table, $select, array('hash' => $hash));

        if ($db_obj) {
            $min_time = new DateTime($ttl . ' hour ago', core_date::get_server_timezone_object());
            $min_time_str = $min_time->format('YmdHi');
            if ($db_obj->date >= $min_time_str) {
                return $db_obj->result;
            }
        }

        return null;
    }

    /**
     * Caches the result for the given query.
     *
     * @param string $query The search query.
     * @param string $result The result to cache.
     */
    protected function cache_result($query, $result)
    {
        global $DB;

        $table = 'block_search_google_fixed_cached_search';

        // we take our time to clean up the cache here
        $this->delete_old_cache_entries();

        $hash = hash('sha256', $query);
        $time = new DateTime("now", core_date::get_server_timezone_object());
        $time_str = $time->format('YmdHi');

        try {
            $DB->insert_record_raw($table, array('id' => $hash, 'date' => $time_str, 'result' => $result), customsequence: true);
        } catch (dml_exception $e) {
            // it may have been concurrently cached, we don't care
            debugging('Error while caching search result: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Deletes old cache entries.
     */
    protected function delete_old_cache_entries()
    {
        global $DB;

        $table = 'block_search_google_fixed_cached_search';

        $ttl = get_config('block_search_google_fixed', 'cachettl');
        if ($ttl <= 0) {
            $DB->delete_records($table);
            return;
        }

        $min_time = new DateTime($ttl . ' hour ago', core_date::get_server_timezone_object());
        $min_time_str = $min_time->format('YmdHi');
        $DB->delete_records_select($table, 'date < ?', array($min_time_str));
    }
}
