<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class GlossaryController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/glossary')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GLOSSARY_ADD);
        $this->userHasPermission(PermissionType::GLOSSARY_EDIT);
        $this->userHasPermission(PermissionType::GLOSSARY_DELETE);

        $session = $this->container->get('session');
        $glossary = $this->container->get('phpmyfaq.glossary');
        $glossary->setLanguage($this->configuration->getLanguage()->getLanguage());

        return $this->render(
            '@admin/content/glossary.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'adminHeaderGlossary' => Translation::get('ad_menu_glossary'),
                'msgAddGlossary' => Translation::get('ad_glossary_add'),
                'msgGlossaryItem' => Translation::get('ad_glossary_item'),
                'msgGlossaryDefinition' => Translation::get('ad_glossary_definition'),
                'glossaryItems' => $glossary->fetchAll(),
                'buttonDelete' => Translation::get('msgDelete'),
                'csrfTokenDelete' => Token::getInstance($session)->getTokenString('delete-glossary'),
                'currentLanguage' => $this->configuration->getLanguage()->getLanguage(),
                'addGlossaryTitle' => Translation::get('ad_glossary_add'),
                'addGlossaryCsrfTokenInput' => Token::getInstance($session)->getTokenInput('add-glossary'),
                'closeModal' => Translation::get('ad_att_close'),
                'saveModal' => Translation::get('ad_gen_save'),
                'updateGlossaryTitle' => Translation::get('ad_glossary_edit'),
                'updateGlossaryCsrfToken' => Token::getInstance($session)->getTokenString('update-glossary'),
            ]
        );
    }
}
