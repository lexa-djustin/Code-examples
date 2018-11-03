<?php

/**
 * 1
 *
 * Class Catalog_Model_Service_Helper_Category_WithoutActiveItems
 */
class Catalog_Model_Service_Helper_Category_WithoutActiveItems
{
    /**
     * @var Catalog_Model_Collection_Category
     */
    protected $categories;

    /**
     * @var int[]
     */
    protected $categoriesWithActiveItemsIds;

    /**
     * @var int[]
     */
    protected $withActiveItems = [];

    /**
     * @var Catalog_Model_Object_Category[]
     */
    protected $withoutActiveItems = [];

    /**
     * Catalog_Model_Service_Helper_Category_CategoryDeactivator constructor.
     *
     * @param Catalog_Model_Collection_Category $categories
     * @param int[] $categoriesWithActiveItemsIds
     */
    public function __construct(Catalog_Model_Collection_Category $categories, array $categoriesWithActiveItemsIds)
    {
        $this->categories = $categories;
        $this->categoriesWithActiveItemsIds = $categoriesWithActiveItemsIds;
    }

    /**
     * @param Callable $filter
     *
     * @return Catalog_Model_Object_Category[]
     */
    public function categoriesWithoutActiveItems(Callable $filter = null)
    {
        /** @var Catalog_Model_Object_Category $category */
        foreach ($this->categories->findByElement('tree_level', 1) as $category) {
            $this->checkChildren($category);
        }

        $result = [];

        foreach ($this->withoutActiveItems as $category) {
            if (is_callable($filter)) {
                if ($filter($category)) {
                    $result[] = $category;
                }
            } else {
                $result[] = $category;
            }
        }

        return $result;
    }

    /**
     * @param Catalog_Model_Object_Category $category
     */
    protected function checkChildren(Catalog_Model_Object_Category $category)
    {
        /** @var array|bool $childrenCategories */
        $childrenCategories = $this->categories->findByElement('parent_id', $category->tree_id);

        if ($childrenCategories === false) {
            if (in_array($category->id, $this->categoriesWithActiveItemsIds, true)) {
                $this->withActiveItems[] = $category->id;
            } else {
                $this->withoutActiveItems[] = $category;
            }

            return;
        }

        $haveActiveItems = false;
        /** @var Catalog_Model_Object_Category $childCategory */
        foreach ($childrenCategories as $childCategory) {
            $this->checkChildren($childCategory);

            if (!$haveActiveItems && in_array($childCategory->id, $this->withActiveItems, true)) {
                $this->withActiveItems[] = $category->id;
                $haveActiveItems = true;
            }
        }

        if ($haveActiveItems === false) {
            if (in_array($category->id, $this->categoriesWithActiveItemsIds, true)) {
                $this->withActiveItems[] = $category->id;
            } else {
                $this->withoutActiveItems[] = $category;
            }
        }
    }
}