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
 * @copyright 2024-2025 phpMyFAQ Team
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

    public function getTrackingFilesStatistics(): object
    {
        $numberOfDays = 0;
        $first = PHP_INT_MAX;
        $last = 0;
        $dir = opendir(PMF_ROOT_DIR . '/content/core/data');
        while ($dat = readdir($dir)) {
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

        if (is_file(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $firstDate))) {
            $fp = @fopen(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $firstDate), 'r');
            while (($data = fgetcsv($fp, 1024, ';', '"', '\\')) !== false) {
                $date = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $requestTime;
            }

            fclose($fp);
            return $this->date->format(date('Y-m-d H:i', (int) $date));
        }

        return Translation::get('ad_sess_noentry');
    }

    public function getLastTrackingDate(int $lastDate): string
    {
        $request = Request::createFromGlobals();
        $requestTime = $request->server->get('REQUEST_TIME');

        if (is_file(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $lastDate))) {
            $fp = fopen(PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY', $lastDate), 'r');

            while (($data = fgetcsv($fp, 1024, ';', '"', '\\')) !== false) {
                $date = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $requestTime;
            }

            fclose($fp);

            if (empty($date)) {
                $date = $request->server->get('REQUEST_TIME');
            }

            return $this->date->format(date('Y-m-d H:i', (int) $date));
        }

        return Translation::get('ad_sess_noentry');
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
            if ($dat !== '.' && $dat !== '..' && strlen($dat) === 16 && !is_dir($dat)) {
                $trackingDates[] = $this->date->getTrackingFileDateStart($dat);
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
            if ($trackingFile !== '.' && $trackingFile !== '..' && 10 === strpos($trackingFile, $month)) {
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
        }

        closedir($dir);

        return $this->session->deleteSessions($first, $last);
    }

    public function clearAllVisits(): bool
    {
        $this->visits->resetAll();

        $files = glob(PMF_CONTENT_DIR . '/core/data/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return $this->session->deleteAllSessions();
    }

    public function renderMonthSelector(): string
    {
        $oldValue = mktime(0, 0, 0, 1, 1, 1970);
        $renderedHtml = sprintf('<option value="" selected>%s</option>', Translation::get('ad_stat_choose'));

        $trackingDates = $this->getAllTrackingDates();
        foreach ($trackingDates as $trackingDate) {
            if (date('Y-m', $oldValue) !== date('Y-m', (int) $trackingDate)) {
                // The filename format is: trackingDDMMYYYY
                // e.g.: tracking02042006
                $renderedHtml .= sprintf(
                    '<option value="%s">%s</option>',
                    date('mY', (int) $trackingDate),
                    date('Y-m', (int) $trackingDate),
                );
                $oldValue = $trackingDate;
            }
        }

        return $renderedHtml;
    }

    public function renderDaySelector(): string
    {
        $request = Request::createFromGlobals();
        $trackingDates = $this->getAllTrackingDates();
        $renderedHtml = '';

        if ($trackingDates === []) {
            return $renderedHtml . sprintf('<option value="" selected>%s</option>', Translation::get('ad_stat_choose'));
        }

        foreach ($trackingDates as $trackingDate) {
            $renderedHtml .= sprintf('<option value="%d"', $trackingDate);
            if (date('Y-m-d', (int) $trackingDate) === date('Y-m-d', $request->server->get('REQUEST_TIME'))) {
                $renderedHtml .= ' selected';
            }

            $renderedHtml .= '>';
            $renderedHtml .= $this->date->format(date('Y-m-d H:i', (int) $trackingDate));
            $renderedHtml .= "</option>\n";
        }

        return $renderedHtml;
    }
}
