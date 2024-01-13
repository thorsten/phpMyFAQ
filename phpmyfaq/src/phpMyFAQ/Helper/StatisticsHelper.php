<?php

/**
 * The statistics helper class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

namespace phpMyFAQ\Helper;

use phpDocumentor\Reflection\Types\This;
use phpMyFAQ\Date;
use phpMyFAQ\Session;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

readonly class StatisticsHelper
{
    public function __construct(
        private Session $session,
        private Visits $visits,
        private Date $date
    ) {
    }

    public function getTrackingFilesStatistics(): object
    {
        $numberOfDays = 0;
        $first = PHP_INT_MAX;
        $last = 0;
        $dir = opendir(PMF_ROOT_DIR . '/content/core/data');
        while ($dat = readdir($dir)) {
            if ($dat != '.' && $dat != '..') {
                ++$numberOfDays;
            }
            if (Date::getTrackingFileDate($dat) > $last) {
                $last = Date::getTrackingFileDate($dat);
            }
            if (Date::getTrackingFileDate($dat) < $first && Date::getTrackingFileDate($dat) > 0) {
                $first = Date::getTrackingFileDate($dat);
            }
        }
        closedir($dir);

        $result = new stdClass();
        $result->numberOfDays = $numberOfDays;
        $result->firstDate = $first;
        $result->lastDate = $last;

        return $result;
    }

    public function getFirstTrackingDate(int $firstDate): string
    {
        $request = Request::createFromGlobals();
        $date = 0;

        if (is_file(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $firstDate))) {
            $fp = @fopen(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $firstDate), 'r');
            while (($data = fgetcsv($fp, 1024, ';')) !== false) {
                $date = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $request->server->get('REQUEST_TIME');
            }
            fclose($fp);
            return $this->date->format(date('Y-m-d H:i', $date));
        } else {
            return Translation::get('ad_sess_noentry');
        }
    }

    public function getLastTrackingDate(int $lastDate): string
    {
        $request = Request::createFromGlobals();

        if (is_file(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $lastDate))) {
            $fp = fopen(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $lastDate), 'r');

            while (($data = fgetcsv($fp, 1024, ';')) !== false) {
                $date = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $request->server->get('REQUEST_TIME');
            }
            fclose($fp);

            if (empty($date)) {
                $date = $request->server->get('REQUEST_TIME');
            }

            return $this->date->format(date('Y-m-d H:i', $date));
        } else {
            return Translation::get('ad_sess_noentry');
        }
    }

    /**
     * Returns all tracking dates.
     *
     * @return string[]
     */
    public function getAllTrackingDates(): array
    {
        $dir = opendir(PMF_ROOT_DIR . '/content/core/data');
        $trackingDates = [];
        while (false !== ($dat = readdir($dir))) {
            if ($dat != '.' && $dat != '..' && strlen($dat) == 16 && !is_dir($dat)) {
                $trackingDates[] = Date::getTrackingFileDate($dat);
            }
        }
        closedir($dir);
        sort($trackingDates);

        return $trackingDates;
    }

    public function deleteTrackingFiles(string $month): bool
    {
        $dir = opendir(PMF_ROOT_DIR . '/content/core/data');
        $first = PHP_INT_MAX;
        $last = 0;
        while ($trackingFile = readdir($dir)) {
            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            if (($trackingFile != '.') && ($trackingFile != '..') && (10 == strpos($trackingFile, $month))) {
                $candidateFirst = Date::getTrackingFileDate($trackingFile);
                $candidateLast = Date::getTrackingFileDate($trackingFile, true);
                if (($candidateLast > 0) && ($candidateLast > $last)) {
                    $last = $candidateLast;
                }
                if (($candidateFirst > 0) && ($candidateFirst < $first)) {
                    $first = $candidateFirst;
                }
                unlink(PMF_ROOT_DIR . '/data/' . $trackingFile);
            }
        }
        closedir($dir);

        return $this->session->deleteSessions($first, $last);
    }

    public function clearAllVisits(): bool
    {
        $this->visits->resetAll();

        // Delete logfiles
        $files = glob(PMF_ROOT_DIR . '/data/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Delete sessions
        return $this->session->deleteAllSessions();
    }

    public function renderMonthSelector(): string
    {
        $oldValue = mktime(0, 0, 0, 1, 1, 1970);
        $renderedHtml = sprintf('<option value="" selected>%s</option>', Translation::get('ad_stat_choose'));
        foreach ($this->getAllTrackingDates() as $trackingDate) {
            if (date('Y-m', $oldValue) != date('Y-m', $trackingDate)) {
                // The filename format is: trackingDDMMYYYY
                // e.g.: tracking02042006
                $renderedHtml .= sprintf(
                    '<option value="%s">%s</option>',
                    date('mY', $trackingDate),
                    date('Y-m', $trackingDate)
                );
                $oldValue = $trackingDate;
            }
        }

        return $renderedHtml;
    }

    public function renderDaySelector(): string
    {
        $request = Request::createFromGlobals();
        $renderedHtml = '';
        foreach ($this->getAllTrackingDates() as $trackingDate) {
            $renderedHtml .= sprintf('<option value="%d"', $trackingDate);
            if (date('Y-m-d', $trackingDate) == date('Y-m-d', $request->server->get('REQUEST_TIME'))) {
                $renderedHtml .= ' selected';
            }
            $renderedHtml .= '>';
            $renderedHtml .= $this->date->format(date('Y-m-d H:i', $trackingDate));
            $renderedHtml .= "</option>\n";
        }

        return $renderedHtml;
    }
}
