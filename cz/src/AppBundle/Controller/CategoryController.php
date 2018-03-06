<?php

namespace AppBundle\Controller;

use Biz\Taxonomy\Service\CategoryService;
use Biz\Taxonomy\Service\TagService;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends BaseController
{
    public function allAction()
    {
        $categories = $this->getCategoryService()->findCategories(1);

        $data = array();
        foreach ($categories as $category) {
            $data[$category['id']] = array($category['name'], $category['parentId']);
        }

        return $this->createJsonResponse($data);
    }

    protected function makeCategories($group)
    {
        $group = $this->getCategoryService()->getGroupByCode($group);

        if (empty($group)) {
            $categories = array();
        } else {
            $categories = $this->getCategoryService()->getCategoryTree($group['id']);

            foreach ($categories as $id => $category) {
                if ($categories[$id]['parentId'] != '0') {
                    unset($categories[$id]);
                }
            }
        }

        return $categories;
    }

    protected function makeTags()
    {
        $tagGroups = $this->getTagService()->findTagGroups();

        foreach ($tagGroups as $key => $tagGroup) {
            $allTags = $this->getTagService()->findTagsByGroupId($tagGroup['id']);
            $tagGroups[$key]['subs'] = $allTags;
        }

        return $tagGroups;
    }

    protected function makeSubCategories($category)
    {
        $subCategories = array();

        if (empty($category['category'])) {
            return $subCategories;
        }

        $categoryArray = $this->getCategoryService()->getCategoryByCode($category['category']);

        if (!empty($categoryArray) && $categoryArray['parentId'] == 0) {
            $subCategories = $this->getCategoryService()->findAllCategoriesByParentId($categoryArray['id']);
        }

        if (!empty($categoryArray) && $categoryArray['parentId'] != 0) {
            $subCategories = $this->getCategoryService()->findAllCategoriesByParentId($categoryArray['parentId']);
        }

        return $subCategories;
    }

    protected function makeThirdCategories($category)
    {
        $thirdCategories = array();

        if (empty($category['subCategory'])) {
            return $thirdCategories;
        }

        $parentCategory = $this->getCategoryService()->getCategoryByCode($category['subCategory']);

        if (empty($parentCategory)) {
            return $thirdCategories;
        }

        return $this->getCategoryService()->findAllCategoriesByParentId($parentCategory['id']);
    }

    public function treeNavAction(Request $request, $category, $tags, $path, $filter = array('price' => 'all', 'type' => 'all', 'currentLevelId' => 'all'), $orderBy = 'latest', $group = 'course')
    {
        $categories = $this->makeCategories($group);
        $tagGroups = $this->makeTags();

        $subCategories = $this->makeSubCategories($category);

        $thirdLevelCategories = $this->makeThirdCategories($category);

        return $this->render('category/explore-nav.html.twig', array(
            'selectedCategory' => $category['category'],
            'selectedSubCategory' => $category['subCategory'],
            'selectedthirdLevelCategory' => $category['thirdLevelCategory'],
            'thirdLevelCategories' => $thirdLevelCategories,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'path' => $path,
            'filter' => $filter,
            'orderBy' => $orderBy,
            'tagGroups' => $tagGroups,
            'tags' => $tags,
            'group' => $group,
        ));
    }

    /**
     * @return TagService
     */
    protected function getTagService()
    {
        return $this->getBiz()->service('Taxonomy:TagService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->getBiz()->service('Taxonomy:CategoryService');
    }
}
