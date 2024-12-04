<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class CategoryController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category', name: 'admin.category', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);
        $this->userHasPermission(PermissionType::CATEGORY_DELETE);
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $category = new Category($this->configuration, [], false);
        $category->buildCategoryTree();
        $categoryInfo = $category->getAllCategories();

        $session = $this->container->get('session');
        $categoryOrder = $this->container->get('phpmyfaq.category.order');
        $orderedCategories = $categoryOrder->getAllCategories();
        $categoryTree = $categoryOrder->getCategoryTree($orderedCategories);

        if (empty($categoryTree)) {
            // Fallback if no category order is available
            $categoryTree = $category->buildAdminCategoryTree($categoryInfo);
        }

        return $this->render(
            '@admin/content/category.overview.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'csrfTokenInput' => Token::getInstance($session)->getTokenInput('category'),
                'categoryTree' => $categoryTree,
                'categoryInfo' => $categoryInfo,
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/add', name: 'admin.category/add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);


        return $this->render(
            '@admin/content/category.add.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
            ]
        );
    }
}
