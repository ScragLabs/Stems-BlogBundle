<?php
namespace Stems\BlogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;


/** 
 * @ORM\Entity
 * @ORM\Table(name="stm_blog_section_scrapbookimage")
 */
class SectionScrapbookImage
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
    protected $image;

    /** 
     * @ORM\Column(type="integer")
     */
    protected $x;

    /** 
     * @ORM\Column(type="integer")
     */
    protected $y;

    /** 
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $width;

    /**
     * @ORM\ManyToOne(targetEntity="SectionScrapbook", inversedBy="images")
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
     * Set image
     *
     * @param string $image
     * @return Section
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
     * Set x
     *
     * @param integer $x
     * @return SectionScrapbookImage
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
     * @return SectionScrapbookImage
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
     * @return SectionScrapbookImage
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
     * @return SectionScrapbookImage
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