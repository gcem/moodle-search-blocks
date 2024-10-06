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
 * Plugin strings are defined here.
 *
 * @package     block_search_google_free
 * @category    string
 * @copyright   2024, Cem Gündoğdu <cemGündoğdudev@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['displaytype'] = 'Display type';
$string['pluginname'] = 'Google Search (Free Query)';
$string['listdisplay'] = 'Show results as a list';
$string['jsondisplay'] = 'Show raw JSON';
$string['privacy:metadata'] = 'Google Search (Free Query) does not store any personal data';
$string['search_google_free:addinstance'] = 'Add a new pluginname block';
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Get a <a href="https://developers.google.com/custom-search/v1/overview">Google Custom Search API</a> key to use with this block';
$string['apikey_notset'] = 'Plugin setting "API key" is not set.';
$string['psearchengineid'] = 'Programmable Search Engine ID';
$string['psearchengineid_desc'] = 'Create a <a href="https://programmablesearchengine.google.com/">Google Programmable Search Engine</a> and set its ID here';
$string['psearchengineid_notset'] = 'Plugin setting "Programmable Search Engine ID" is not set.';
$string['maxqueriesperuserperday'] = 'Maximum daily searches per user';
$string['maxqueriesperuserperday_desc'] = 'Limit the number of daily searches a user can make in the whole site. -1 for no limit.';
$string['search'] = 'Search';
$string['searchquery'] = 'Search query';
$string['searchquery_enter'] = 'Enter text to search';
$string['searchquery_toolong'] = 'The search query is too long (max. {$a} characters)';
$string['requesterror'] = 'Request error';
$string['apiresponsefor'] = 'API response for the search query "{$a}"';
$string['searchresultsfor'] = 'Search results for "{$a}"';
$string['dailylimitreached'] = 'Daily search limit reached';
$string['dailysearchesremaining'] = 'Daily searches remaining: {$a}';