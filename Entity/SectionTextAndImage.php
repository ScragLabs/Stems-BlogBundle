<?php
namespace Stems\BlogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;

use Stems\BlogBundle\Form\SectionTextAndImageType;
use Stems\BlogBundle\Definition\SectionInstanceInterface;


/** 
 * @ORM\Entity
 * @ORM\Table(name="stm_blog_section_textandimage")
 */
class SectionTextAndImage implements SectionInstanceInterface
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $image;

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $position = 'right';

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $caption;

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $link;

    /** 
     * @ORM\Column(type="boolean")
     */
    protected $wrap = false;

    /**
     * Build the html for rendering in the front end, using any nessary custom code
     */
    public function render($services, $link)
    {
        // render the twig template
        return $services->getTwig()->render('StemsBlogBundle:Section:textAndImage.html.twig', array(
            'section'   => $this,
            'link'      => $link,
        ));
    }

    /**
     * Build the html for admin editor form
     */
    public function editor($services, $link)
    {
        // build the section from using the generic builder method
        $form = $services->createSectionForm($link, $this);

        // render the admin form html
        return $services->getTwig()->render('StemsBlogBundle:Section:textAndImageForm.html.twig', array(
            'form'      => $form->createView(),
            'section'   => $this,
            'link'      => $link,
        ));
    }

    /**
     * Update the section from posted data
     */
    public function save($services, $parameters, $request, $link)
    {
        // save the values
        $this->setContent(stripslashes($parameters['content']));
        $this->setImage($parameters['image']);
        $this->setCaption(stripslashes($parameters['caption']));
        $this->setPosition($parameters['position']);
        $this->setLink($parameters['link']);

        $services->getManager()->persist($this);
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return SectionTextAndImage
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return SectionTextAndImage
     */
    public function setImage($image)
    {
        $this->image = $image;
    
        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return SectionTextAndImage
     */
    public function setPosition($position)
    {
        $this->position = $position;
    
        return $this;
    }

    /**
     * Get position
     *
     * @return string 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set caption
     *
     * @param string $caption
     * @return SectionTextAndImage
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
    
        return $this;
    }

    /**
     * Get caption
     *
     * @return string 
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return SectionTextAndImage
     */
    public function setLink($link)
    {
        $this->link = $link;
    
        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set wrap
     *
     * @param string $wrap
     * @return SectionTextAndImage
     */
    public function setWrap($wrap)
    {
        $this->wrap = $wrap;
    
        return $this;
    }

    /**
     * Get wrap
     *
     * @return string 
     */
    public function getWrap()
    {
        return $this->wrap;
    }
}