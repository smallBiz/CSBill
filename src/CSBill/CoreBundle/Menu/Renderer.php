<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\Renderer\ListRenderer;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Silex\Voter\RouteVoter;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony\Bundle\TwigBundle\TwigEngine;

class Renderer extends ListRenderer
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Symfony\Bundle\TwigBundle\TwigEngine
     */
    protected $templating;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @param FactoryInterface   $factory
     */
    public function __construct(ContainerInterface $container, FactoryInterface $factory)
    {
        $this->container = $container;
        $this->factory = $factory;

        $matcher = new Matcher();

        try {
            $request = $this->container->get('request');

            $voter = new RouteVoter($request->get('_route'));

            $voter->setRequest($request);
            $matcher->addVoter($voter);
        } catch (InactiveScopeException $e) {
            // We are most probably running from the command line, which means there is no 'request' service.
            // We just gracefully continue
        }

        parent::__construct($matcher, array('allow_safe_labels' => true, 'currentClass' => 'active'));
    }

    /**
     * Renders all of the children of this menu.
     *
     * This calls ->renderItem() on each menu item, which instructs each
     * menu item to render themselves as an <li> tag (with nested ul if it
     * has children).
     * This method updates the depth for the children.
     *
     * @param ItemInterface $item
     * @param array         $options The options to render the item.
     *
     * @return string
     */
    protected function renderChildren(ItemInterface $item, array $options)
    {
        // render children with a depth - 1
        if (null !== $options['depth']) {
            $options['depth'] = $options['depth'] - 1;
        }

        $html = '';
        foreach ($item->getChildren() as $child) {
            /** @var \CSBill\CoreBundle\Menu\MenuItem $child */
            if ($child->isDivider()) {
                $html .= $this->renderDivider($child, $options);
            } else {
                $html .= $this->renderItem($child, $options);
            }
        }

        return $html;
    }

    /**
     * Renders the menu label
     *
     * @param  ItemInterface $item
     * @param  array         $options
     * @return string
     */
    protected function renderLabel(ItemInterface $item, array $options)
    {
        $icon = '';
        if ($item->getExtra('icon')) {
            $icon = $this->renderIcon($item->getExtra('icon'));
        }

        if ($options['allow_safe_labels'] && $item->getExtra('safe_label', false)) {
            return $icon . $item->getLabel();
        }

        return $icon . $this->escape($item->getLabel());
    }

    /**
     * Renders an icon in the menu
     *
     * @param  string $icon
     * @return string
     */
    protected function renderIcon($icon)
    {
        return $this->container->get('templating')->render(sprintf('{{ icon("%s") }} ', $icon));
    }

    /**
     * @param  ItemInterface $item
     * @param  array         $options
     * @return string
     */
    protected function renderDivider(ItemInterface $item, array $options = array())
    {
        return $this->format(
            '<li' . $this->renderHtmlAttributes(
                array(
                    'class' => 'divider' . $item->getExtra('divider'))
            ) . '>',
            'li',
            $item->getLevel(),
            $options
        );
    }

    /**
     * Renders a menu at a specific location
     *
     * @param  \SplObjectStorage $storage
     * @param  array             $options
     * @return string
     */
    public function build(\SplObjectStorage $storage, array $options = array())
    {
        $menu = $this->factory->createItem('root');

        if (isset($options['attr'])) {
            $menu->setChildrenAttributes($options['attr']);
        } else {
            // TODO : this should be set per menu, instead of globally
            $menu->setChildrenAttributes(array('class' => 'nav nav-pills nav-stacked'));
        }

        foreach ($storage as $builder) {
            /** @var \CSBill\CoreBundle\Menu\Builder\MenuBuilder $builder */
            $builder->setContainer($this->container);
            $builder->invoke($menu, $options);
        }

        return $this->render($menu, $options);
    }
}
