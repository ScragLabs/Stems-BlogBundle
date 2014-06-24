<?php
namespace Stems\BlogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;


/** 
 * @ORM\Entity
 * @ORM\Table(name="stm_blog_section_scrapbooktext")
 */
class SectionScrapbookText
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
     * @ORM\Column(type="integer")
     */
    protected $x;

    /** 
     * @ORM\Column(type="integer")
     */
    protected $y;

    /** 
     * @ORM\Column(type="integer")
     */
    protected $width;

    /**
     * @ORM\ManyToOne(targetEntity="SectionScrapbook", inversedBy="texts")
     * @ORM\JoinColumn(name="sectionScrapbook_id", referencedColumnName="id")
     */
    protected $sectionScrapbook;

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
     * @return Section
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
     * Set x
     *
     * @param integer $x
     * @return SectionScrapbookText
     */
    public function setX($x)
    {
        $this->x = $x;
    
        return $this;
    }

    /**
     * Get x
     *
     * @return integer 
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Set y
     *
     * @param integer $y
     * @return SectionScrapbookText
     */
    public function setY($y)
    {
        $this->y = $y;
    
        return $this;
    }

    /**
     * Get y
     *
     * @return integer 
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return SectionScrapbookText
     */
    public function setWidth($width)
    {
        $this->width = $width;
    
        return $this;
    }

    /**
     * Get width
     *
     * @return integer 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set sectionScrapbook
     *
     * @param \Stems\BlogBundle\Entity\SectionScrapbook $sectionScrapbook
     * @return SectionScrapbookText
     */
    public function setSectionScrapbook(\Stems\BlogBundle\Entity\SectionScrapbook $sectionScrapbook = null)
    {
        $this->sectionScrapbook = $sectionScrapbook;
    
        return $this;
    }

    /**
     * Get sectionScrapbook
     *
     * @return \Stems\BlogBundle\Entity\SectionScrapbook 
     */
    public function getSectionScrapbook()
    {
        return $this->sectionScrapbook;
    }
}