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
 * Plugin administration pages are defined here.
 *
 * @package     block_search_google_free
 * @category    admin
 * @copyright   2024, Cem Gündoğdu <cemGündoğdudev@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'block_search_google_free/apikey',
        get_string('apikey', 'block_search_google_free'),
        get_string('apikey_desc', 'block_search_google_free'),
        '',
        PARAM_RAW_TRIMMED,
        40
    ));

    // programmable search engine id
    $settings->add(new admin_setting_configtext(
        'block_search_google_free/psearchengineid',
        get_string('psearchengineid', 'block_search_google_free'),
        get_string('psearchengineid_desc', 'block_search_google_free'),
        '',
        PARAM_RAW_TRIMMED,
        40
    ));

    $settings->add(new admin_setting_configtext(
        'block_search_google_free/maxqueriesperuserperday',
        get_string('maxqueriesperuserperday', 'block_search_google_free'),
        get_string('maxqueriesperuserperday_desc', 'block_search_google_free'),
        -1,
        PARAM_INT,
        40
    ));

}
