<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Newsletter\NewsletterBundle\Block;

use Symfony\Component\HttpFoundation\Response;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;

class TemplateBlockService extends BaseBlockService
{
     public function getName()
    {
        return 'My Newsletter';
    }

    public function getDefaultSettings()
    {
        return array();
    }

    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
    }

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper
               ->add('title', 'text', array('label' => 'Title'))               
               ->add('body','textarea'
                    ) ;
    }

    public function execute(BlockContextInterface $block, Response $response = null)
    {

        // merge settings
        $settings = array_merge($this->getDefaultSettings(), $block->getSettings());
        
        return $this->renderResponse('NewsletterNewsletterBundle:Block:template.html.twig', array(
            'block'     => $block->getBlock(),
            'settings'  =>  $settings
        ), $response);
    }
    
     
    
}