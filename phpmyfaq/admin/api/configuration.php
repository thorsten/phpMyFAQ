<?php

/**
 * Private phpMyFAQ Admin API: handling of REST configuration calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Entity\TemplateMetaDataEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TemplateMetaData;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);


switch ($ajaxAction) {

    case 'add-template-metadata':
        $postData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('add-metadata', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $meta = new TemplateMetaData($faqConfig);
        $entity = new TemplateMetaDataEntity();

        $entity
            ->setPageId(Filter::filterVar($postData->pageId, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setType(Filter::filterVar($postData->type, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setContent(Filter::filterVar($postData->content, FILTER_SANITIZE_SPECIAL_CHARS));

        $metaId = $meta->add($entity);

        if (0 !== $metaId) {
            $payload = ['added' => $metaId];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $metaId];
        }

        $response->setData($payload);
        $response->send();
        break;

    case 'delete-template-metadata':
        $json = file_get_contents('php://input', true);
        $deleteData = json_decode($json);

        if (!Token::getInstance()->verifyToken('delete-meta-data', $deleteData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $meta = new TemplateMetaData($faqConfig);
        $metaId = Filter::filterVar($deleteData->metaId, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($meta->delete((int)$metaId)) {
            $payload = ['deleted' => $metaId];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $metaId];
        }

        $response->setData($payload);
        $response->send();
        break;
}
