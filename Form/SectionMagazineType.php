<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SectionMagazineType extends AbstractType
{
	protected $id;

	public function __construct($link)
	{
		$this->id = $link->getId();
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('contentA', 'textarea', array(
			'label'     		=> 'Column 1',
			'required'			=> false,
			'error_bubbling' 	=> true,
			'attr'				=> array('class' => 'markitup'),
		));	

		$builder->add('contentB', 'textarea', array(
			'label'     		=> 'Column 2',
			'required'			=> false,
			'error_bubbling' 	=> true,
			'attr'				=> array('class' => 'markitup'),
		));	

		$builder->add('contentC', 'textarea', array(
			'label'     		=> 'Column 3',
			'required'			=> false,
			'error_bubbling' 	=> true,
			'attr'				=> array('class' => 'markitup'),
		));	

		$builder->add('imageA', 'text', array(
			'label'     		=> 'Image 1',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('imageB', 'text', array(
			'label'     		=> 'Image 2',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('imageC', 'text', array(
			'label'     		=> 'Image 3',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('positionA', 'choice', array(
			'label'     		=> 'Image 1 Position',
			'empty_value' 		=> false,
			'choices'			=> array('top' => 'Top', 'bottom' => 'Bottom'),
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('positionB', 'choice', array(
			'label'     		=> 'Image 2 Position',
			'empty_value' 		=> false,
			'choices'			=> array('top' => 'Top', 'bottom' => 'Bottom'),
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('positionC', 'choice', array(
			'label'     		=> 'Image 3 Position',
			'empty_value' 		=> false,
			'choices'			=> array('top' => 'Top', 'bottom' => 'Bottom'),
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('captionA', 'text', array(
			'label'     		=> 'Image 1 Caption',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('captionB', 'text', array(
			'label'     		=> 'Image 2 Caption',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('captionC', 'text', array(
			'label'     		=> 'Image 3 Caption',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('linkA', 'text', array(
			'label'     		=> 'Image 1 Link',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('linkB', 'text', array(
			'label'     		=> 'Image 2 Link',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('linkC', 'text', array(
			'label'     		=> 'Image 3 Link',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	
	}

	public function getName()
	{
		return $this->id.'_section_magazine_type';
	}
}
