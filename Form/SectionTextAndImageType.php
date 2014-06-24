<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SectionTextAndImageType extends AbstractType
{
	protected $id;

	public function __construct($link)
	{
		$this->id = $link->getId();
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('content', 'textarea', array(
			'label'     		=> 'Text Content',
			'required'			=> false,
			'error_bubbling' 	=> true,
			'attr'				=> array('class' => 'markitup'),
		));	

		$builder->add('image', 'text', array(
			'label'     		=> 'Image',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('position', 'choice', array(
			'label'     		=> 'Image Position',
			'empty_value' 		=> false,
			'choices'			=> array('right' => 'Right', 'left' => 'Left'),
			'required'			=> false,
			'error_bubbling' 	=> true,
			'attr'				=> array('class' => 'section_textandimage_type_position'),
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

		$builder->add('wrap', 'checkbox', array(
			'label'     		=> 'Wrap Content Around Image?',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	
	}

	public function getName()
	{
		return $this->id.'_section_textandimage_type';
	}
}
