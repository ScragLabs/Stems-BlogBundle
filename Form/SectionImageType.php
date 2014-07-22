<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SectionImageType extends AbstractType
{
	protected $id;

	public function __construct($link)
	{
		$this->id = $link->getId();
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('image', 'text', array(
			'label'     		=> 'Image',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('position', 'choice', array(
			'label'     		=> 'Image Position',
			'empty_value' 		=> false,
			'choices'			=> array('center' => 'Center', 'right' => 'Right', 'left' => 'Left'),
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('caption', 'text', array(
			'label'     		=> 'Image Caption',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('link', 'text', array(
			'label'     		=> 'Image Link',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	
	}

	public function getName()
	{
		return $this->id.'_section_image_type';
	}
}
