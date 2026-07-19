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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Administration\Session;
use phpMyFAQ\Date;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

readonly class StatisticsHelper
{
    public function __construct(
        private Session $session,
        private Visits $visits,
        private Date $date,
    ) {
    }

    public function getTrackingFilesStatistics(): stdClass
    {
        $numberOfDays = 0;
        $first = PHP_INT_MAX;
        $last = 0;
        $dir = opendir((string) PMF_ROOT_DIR . '/content/core/data');
        if ($dir === false) {
            $result = new stdClass();
            $result->numberOfDays = 0;
            $result->firstDate = $first;
            $result->lastDate = $last;
            return $result;
        }

        while (true) {
            $dat = readdir($dir);
            if ($dat === false) {
                break;
            }

            if ($dat !== '.' && $dat !== '..') {
                ++$numberOfDays;
            }

            if ($this->date->getTrackingFileDateStart($dat) > $last) {
                $last = $this->date->getTrackingFileDateStart($dat);
            }

            if (
                $this->date->getTrackingFileDateStart($dat) < $first
                && $this->date->getTrackingFileDateStart($dat) > 0
            ) {
                $first = $this->date->getTrackingFileDateStart($dat);
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
        $requestTime = $request->server->get('REQUEST_TIME');
        $date = 0;

        $trackingFile =
            (string) PMF_ROOT_DIR . '/content/core/data/tracking' . date(format: 'dmY', timestamp: $firstDate);
        if (is_file($trackingFile)) {
            $fp = fopen(filename: $trackingFile, mode: 'r');
            if ($fp === false) {
                return Translation::getString('ad_sess_noentry');
            }

            while (($data = fgetcsv($fp, length: 1024, separator: ';', enclosure: '"', escape: '\\')) !== false) {
                $field = $data[7] ?? null;
                $date = is_string($field) && 10 === strlen($field) ? $field : $requestTime;
            }

            fclose($fp);
            return $this->date->format(date(format: 'Y-m-d H:i', timestamp: (int) $date));
        }

        return Translation::getString('ad_sess_noentry');
    }

    public function getLastTrackingDate(int $lastDate): string
    {
        $request = Request::createFromGlobals();
        $requestTime = $request->server->get('REQUEST_TIME');

        $trackingFile =
            (string) PMF_ROOT_DIR . '/content/core/data/tracking' . date(format: 'dmY', timestamp: $lastDate);
        if (is_file($trackingFile)) {
            $fp = fopen(filename: $trackingFile, mode: 'r');
            if ($fp === false) {
                return Translation::getString('ad_sess_noentry');
            }

            $date = null;
            while (($data = fgetcsv($fp, length: 1024, separator: ';', enclosure: '"', escape: '\\')) !== false) {
                $field = $data[7] ?? null;
                $date = is_string($field) && 10 === strlen($field) ? $field : $requestTime;
            }

            fclose($fp);

            if ($date === null || $date === 0) {
                $date = $request->server->get('REQUEST_TIME');
            }

            return $this->date->format(date(format: 'Y-m-d H:i', timestamp: (int) $date));
        }

        return Translation::getString('ad_sess_noentry');
    }

    /**
     * Returns all tracking dates.
     *
     * @return int[]
     */
    public function getAllTrackingDates(): array
    {
        $dir = opendir((string) PMF_ROOT_DIR . '/content/core/data');
        $trackingDates = [];
        if ($dir === false) {
            return $trackingDates;
        }

        while (false !== ($dat = readdir($dir))) {
            if (!($dat !== '.' && $dat !== '..' && strlen($dat) === 16 && !is_dir($dat))) {
                continue;
            }

            $trackingDates[] = $this->date->getTrackingFileDateStart($dat);
        }

        closedir($dir);
        sort($trackingDates);

        return $trackingDates;
    }

    public function deleteTrackingFiles(string $month): bool
    {
        $dir = opendir((string) PMF_ROOT_DIR . '/content/core/data');
        $first = PHP_INT_MAX;
        $last = 0;
        if ($dir === false) {
            return false;
        }

        while (true) {
            $trackingFile = readdir($dir);
            if ($trackingFile === false) {
                break;
            }

            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            if (!($trackingFile !== '.' && $trackingFile !== '..' && 10 === strpos($trackingFile, $month))) {
                continue;
            }

            $candidateFirst = $this->date->getTrackingFileDateStart($trackingFile);
            $candidateLast = $this->date->getTrackingFileDateEnd($trackingFile);
            if ($candidateLast > 0 && $candidateLast > $last) {
                $last = $candidateLast;
            }

            if ($candidateFirst > 0 && $candidateFirst < $first) {
                $first = $candidateFirst;
            }

            unlink(PMF_CONTENT_DIR . '/core/data/' . $trackingFile);
        }

        closedir($dir);

        return $this->session->deleteSessions($first, $last);
    }

    public function clearAllVisits(): bool
    {
        $this->visits->resetAll();

        $files = glob(PMF_CONTENT_DIR . '/core/data/*');
        if ($files === false) {
            $files = [];
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            unlink($file);
        }

        return $this->session->deleteAllSessions();
    }

    public function renderMonthSelector(): string
    {
        $oldValue = (int) mktime(hour: 0, minute: 0, second: 0, month: 1, day: 1, year: 1970);
        $renderedHtml = sprintf('<option value="" selected>%s</option>', Translation::getString('ad_stat_choose'));

        $trackingDates = $this->getAllTrackingDates();
        foreach ($trackingDates as $trackingDate) {
            if (date(format: 'Y-m', timestamp: $oldValue) === date(format: 'Y-m', timestamp: (int) $trackingDate)) {
                continue;
            }

            $renderedHtml .= sprintf(
                '<option value="%s">%s</option>',
                date(format: 'mY', timestamp: (int) $trackingDate),
                date(format: 'Y-m', timestamp: (int) $trackingDate),
            );
            $oldValue = $trackingDate;
        }

        return $renderedHtml;
    }

    public function renderDaySelector(): string
    {
        $request = Request::createFromGlobals();
        $trackingDates = $this->getAllTrackingDates();
        $renderedHtml = '';

        if ($trackingDates === []) {
            return sprintf(
                '%s<option value="" selected>%s</option>',
                $renderedHtml,
                Translation::getString('ad_stat_choose'),
            );
        }

        foreach ($trackingDates as $trackingDate) {
            $renderedHtml .= sprintf('<option value="%d"', $trackingDate);
            if (
                date(format: 'Y-m-d', timestamp: (int) $trackingDate) === date(
                    format: 'Y-m-d',
                    timestamp: (int) $request->server->get('REQUEST_TIME'),
                )
            ) {
                $renderedHtml .= ' selected';
            }

            $renderedHtml .= '>';
            $renderedHtml .= $this->date->format(date(format: 'Y-m-d H:i', timestamp: (int) $trackingDate));
            $renderedHtml .= "</option>\n";
        }

        return $renderedHtml;
    }
}
