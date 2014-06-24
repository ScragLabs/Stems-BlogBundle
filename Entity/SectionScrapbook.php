<?php
namespace Stems\BlogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;

use Stems\BlogBundle\Form\SectionScrapbookType;
use Stems\BlogBundle\Definition\SectionInstanceInterface;

/** 
 * @ORM\Entity
 * @ORM\Table(name="stm_blog_section_scrapbook")
 */
class SectionScrapbook implements SectionInstanceInterface
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
    protected $title = 'title';

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /** 
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $contentX = 20;

    /** 
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $contentY = 20;

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $background;

    /** 
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $height;

    /**
     * @ORM\OneToMany(targetEntity="SectionScrapbookText", mappedBy="sectionScrapbook")
     */
    protected $texts;

    /**
     * @ORM\OneToMany(targetEntity="SectionScrapbookImage", mappedBy="sectionScrapbook")
     */
    protected $images; 

    public function __construct()
    {
        $this->texts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Build the html for rendering in the front end, using any nessary custom code
     */
    public function render($services, $link)
    {
        // render the twig template
        return $services->getTwig()->render('StemsBlogBundle:Section:scrapbook.html.twig', array(
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

        // get forms for the different scraps attached in the scrapbook
        $imageForms = array();
        $textForms = array();

        foreach ($this->getImages() as $image) {
            $imageForm = $services->createSubSectionForm($image);
            $imageForms[] = $services->getTwig()->render('StemsBlogBundle:Section:scrapbookImageForm.html.twig', array(
                'form'      => $imageForm->createView(),
                'image'     => $image,
            ));
        }

        foreach ($this->getTexts() as $text) {
            $textForm = $services->createSubSectionForm($text);
            $textForms[] = $services->getTwig()->render('StemsBlogBundle:Section:scrapbookTextForm.html.twig', array(
                'form'     => $textForm->createView(),
                'text'     => $text,
            ));
        }

        // render the admin form html
        return $services->getTwig()->render('StemsBlogBundle:Section:scrapbookForm.html.twig', array(
            'form'          => $form->createView(),
            'link'          => $link,
            'section'       => $this,
            'imageForms'    => $imageForms,
            'textForms'     => $textForms,
        ));
    }

    /**
     * Update the section from posted data
     */
    public function save($services, $parameters, $request, $link)
    {
        // save the values
        $this->setTitle($parameters['title']);
        $this->setContent($parameters['content']);
        $this->setContentX($parameters['contentX']);
        $this->setContentY($parameters['contentY']);
        $this->setBackground($parameters['height']);
        $this->setBackground($parameters['background']);
        
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
     * Set title
     *
     * @param string $title
     * @return SectionScrapbook
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return SectionScrapbook
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
     * Set contentX
     *
     * @param integer $contentX
     * @return SectionScrapbook
     */
    public function setContentX($contentX)
    {
        $this->contentX = $contentX;
    
        return $this;
    }

    /**
     * Get contentX
     *
     * @return integer 
     */
    public function getContentX()
    {
        return $this->contentX;
    }

    /**
     * Set contentY
     *
     * @param integer $contentY
     * @return SectionScrapbook
     */
    public function setContentY($contentY)
    {
        $this->contentY = $contentY;
    
        return $this;
    }

    /**
     * Get contentY
     *
     * @return integer 
     */
    public function getContentY()
    {
        return $this->contentY;
    }

    /**
     * Set background
     *
     * @param integer $background
     * @return SectionScrapbook
     */
    public function setBackground($background)
    {
        $this->background = $background;
    
        return $this;
    }

    /**
     * Get background
     *
     * @return integer 
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return SectionScrapbook
     */
    public function setHeight($height)
    {
        $this->height = $height;
    
        return $this;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Add texts
     *
     * @param \Stems\BlogBundle\Entity\SectionScrapbookText $texts
     * @return SectionScrapbook
     */
    public function addText(\Stems\BlogBundle\Entity\SectionScrapbookText $texts)
    {
        $this->texts[] = $texts;
    
        return $this;
    }

    /**
     * Remove texts
     *
     * @param \Stems\BlogBundle\Entity\SectionScrapbookText $texts
     */
    public function removeText(\Stems\BlogBundle\Entity\SectionScrapbookText $texts)
    {
        $this->texts->removeElement($texts);
    }

    /**
     * Get texts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTexts()
    {
        return $this->texts;
    }

    /**
     * Add images
     *
     * @param \Stems\BlogBundle\Entity\SectionScrapbookImage $images
     * @return SectionScrapbook
     */
    public function addImage(\Stems\BlogBundle\Entity\SectionScrapbookImage $images)
    {
        $this->images[] = $images;
    
        return $this;
    }

    /**
     * Remove images
     *
     * @param \Stems\BlogBundle\Entity\SectionScrapbookImage $images
     */
    public function removeImage(\Stems\BlogBundle\Entity\SectionScrapbookImage $images)
    {
        $this->images->removeElement($images);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getImages()
    {
        return $this->images;
    }
}