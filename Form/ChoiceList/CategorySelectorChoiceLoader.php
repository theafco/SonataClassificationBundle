<?php

namespace Sonata\ClassificationBundle\Form\ChoiceList;

use Sonata\ClassificationBundle\Model\CategoryInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\ContextInterface;
use Sonata\CoreBundle\Model\ManagerInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class CategorySelectorChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var CategoryManagerInterface
     */
    protected $manager;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var CategoryInterface
     */
    protected $category;

    private $choiceList;

    /**
     * @param ManagerInterface $manager
     * @param ContextInterface|null $context
     * @param CategoryInterface|null $category
     */
    public function __construct(ManagerInterface $manager, $context = null, $category = null)
    {
        $this->manager = $manager;
        $this->context = $context;
        $this->category = $category;
    }


    /**
     * Loads a list of choices.
     *
     * Optionally, a callable can be passed for generating the choice values.
     * The callable receives the choice as first and the array key as the second
     * argument.
     *
     * @param null|callable $value The callable which generates the values
     *                             from choices
     *
     * @return ChoiceListInterface The loaded choice list
     */
    public function loadChoiceList($value = null)
    {
        if (!$this->choiceList) {
            $choices = $this->getChoices(array(
                'context' => $this->context,
                'category' => $this->category,
            ));

            $this->choiceList = new ArrayChoiceList(array_flip($choices), $value);
        }

        return $this->choiceList;
    }

    /**
     * Loads the choices corresponding to the given values.
     *
     * The choices are returned with the same keys and in the same order as the
     * corresponding values in the given array.
     *
     * Optionally, a callable can be passed for generating the choice values.
     * The callable receives the choice as first and the array key as the second
     * argument.
     *
     * @param string[] $values An array of choice values. Non-existing
     *                              values in this array are ignored
     * @param null|callable $value The callable generating the choice values
     *
     * @return array An array of choices
     */
    public function loadChoicesForValues(array $values, $value = null)
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * Loads the values corresponding to the given choices.
     *
     * The values are returned with the same keys and in the same order as the
     * corresponding choices in the given array.
     *
     * Optionally, a callable can be passed for generating the choice values.
     * The callable receives the choice as first and the array key as the second
     * argument.
     *
     * @param array $choices An array of choices. Non-existing choices in
     *                               this array are ignored
     * @param null|callable $value The callable generating the choice values
     *
     * @return string[] An array of choice values
     */
    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getChoices($options)
    {
        if (!$options['category'] instanceof CategoryInterface) {
            return array();
        }

        if ($options['context'] === null) {
            $categories = $this->manager->getRootCategories();
        } else {
            $categories = array($this->manager->getRootCategory($options['context']));
        }

        $choices = array();

        foreach ($categories as $category) {
            $choices[$category->getId()] = sprintf('%s (%s)', $category->getName(), $category->getContext()->getId());

            $this->childWalker($category, $options, $choices);
        }

        return $choices;
    }

    /**
     * @param CategoryInterface $category
     * @param array           $options
     * @param array             $choices
     * @param int               $level
     */
    private function childWalker(CategoryInterface $category, array $options, array &$choices, $level = 2)
    {
        if ($category->getChildren() === null) {
            return;
        }

        foreach ($category->getChildren() as $child) {
            if ($options['category'] && $options['category']->getId() == $child->getId()) {
                continue;
            }

            $choices[$child->getId()] = sprintf('%s %s', str_repeat('-', 1 * $level), $child);

            $this->childWalker($child, $options, $choices, $level + 1);
        }
    }
}