<?php

namespace FSC\HateoasBundle\Factory;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Util\PropertyPath;
use Metadata\MetadataFactoryInterface;
use Pagerfanta\PagerfantaInterface;

use FSC\HateoasBundle\Model\Link;
use FSC\HateoasBundle\Metadata\ClassMetadataInterface;

class LinkFactory implements LinkFactoryInterface, PagerLinkFactoryInterface
{
    protected $urlGenerator;
    protected $metadataFactory;
    protected $parametersFactory;

    public function __construct(UrlGeneratorInterface $urlGenerator, MetadataFactoryInterface $metadataFactory,
                                ParametersFactoryInterface $parametersFactory)
    {
        $this->urlGenerator = $urlGenerator;
        $this->metadataFactory = $metadataFactory;
        $this->parametersFactory = $parametersFactory;
    }

    public function createLinks($object)
    {
        if ($object instanceof Link) {
            return;
        }

        if (null === ($classMetadata = $this->metadataFactory->getMetadataForClass(get_class($object)))) {
            return;
        }

        return $this->createLinksFromMetadata($classMetadata, $object);
    }

    public function createPagerLinks(PagerfantaInterface $pager, $route, $routeParameters)
    {
        $routeParameters = array_merge($routeParameters, array(
            'page' => $pager->getCurrentPage(),
            'limit' => $pager->getMaxPerPage(),
        ));

        $links = array();
        $links[] = $this->createLink('self', $this->urlGenerator->generate($route, $routeParameters, true));
        $links[] = $this->createLink('first', $this->urlGenerator->generate(
            $route,
            array_merge($routeParameters, array('page' => '1')),
            true
        ));
        $links[] = $this->createLink('last', $this->urlGenerator->generate(
            $route,
            array_merge($routeParameters, array('page' => $pager->getNbPages())),
                true
        ));

        if ($pager->hasPreviousPage()) {
            $links[] = $this->createLink('next', $this->urlGenerator->generate(
                $route,
                array_merge($routeParameters, array('page' => $pager->getPreviousPage())),
                true
            ));
        }

        if ($pager->hasNextPage()) {
            $links[] = $this->createLink('next', $this->urlGenerator->generate(
                $route,
                array_merge($routeParameters, array('page' => $pager->getNextPage())),
                true
            ));
        }

        return $links;
    }

    public function createLinksFromMetadata(ClassMetadataInterface $classMetadata, $object)
    {
        $links = array();

        foreach ($classMetadata->getRelations() as $relationMeta) {
            $href = $this->urlGenerator->generate($relationMeta['route'], $this->parametersFactory->createParameters($object, $relationMeta['params']), true);
            $links[] = $this->createLink($relationMeta['rel'], $href);
        }

        return $links;
    }

    public static function createLink($rel, $href)
    {
        $link = new Link();
        $link->setRel($rel);
        $link->setHref($href);

        return $link;
    }
}
