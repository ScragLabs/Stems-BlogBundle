<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseRestController,
	Symfony\Component\HttpFoundation\Request,
	Stems\BlogBundle\Entity\SectionProductGalleryProduct,
	Stems\BlogBundle\Form\SectionProductGalleryProductType,
	Stems\MediaBundle\Entity\Image,
	Stems\MediaBundle\Form\ImageType;


class PopupController extends BaseRestController
{
	/**
	 * Build a popup to manually add a product to a product gallery section, created a skeleton entity in the first place.
	 *
	 * @param  integer 		$id 	The ID of the Product Gallery Section to add the image to
	 * @param  Request
	 * @return JsonResponse
	 */
	public function addProductGalleryProductAction($id, Request $request)
	{
		// Get the section for the field id
		$em      = $this->getDoctrine()->getManager();
		$section = $em->getRepository('StemsBlogBundle:SectionProductGallery')->find($id);

		// Create the product
		$image = new SectionProductGalleryProduct();
		$image->setSectionProductGallery($section);
		$image->setHeading('New Product');
		$image->setThumbnail('image.jpg');
		$image->setImage('image.jpg');

		$em->persist($image);
		$em->flush();

		// Build the form 
		$form = $this->createForm(new SectionProductGalleryProductType(), $image);

		// Get the html for the popup
		$html = $this->renderView('StemsBlogBundle:Popup:updateProductGalleryProduct.html.twig', array(
			'product'	=> $image,
			'title'		=> 'Add a New Product Manually',
			'form'		=> $form->createView(),
		));

		return $this->addHtml($html)->success('The popup was successfully created.')->sendResponse();
	}

	/**
	 * Build a popup to edit a product gallery product
	 *
	 * @param  integer 		$id 	The ID of the Product Gallery Product
	 * @param  Request
	 * @return JsonResponse
	 */
	public function updateProductGalleryProductAction($id, Request $request)
	{
		// Get the product
		$em    = $this->getDoctrine()->getManager();
		$image = $em->getRepository('StemsBlogBundle:SectionProductGalleryProduct')->find($id);

		// Build the form 
		$form = $this->createForm(new SectionProductGalleryProductType(), $image);

		// Get the html for the popup
		$html = $this->renderView('StemsBlogBundle:Popup:updateProductGalleryProduct.html.twig', array(
			'product'	=> $image,
			'title'		=> 'Edit Product '.$image->getHeading(),
			'form'		=> $form->createView(),
		));

		return $this->addHtml($html)->success('The popup was successfully created.')->sendResponse();
	}

	/**
	 * Build a popup to set the feature image of a blog post
	 *
	 * @param  integer 		$id 	The ID of the blog post
	 * @param  Request
	 * @return JsonResponse
	 */
	public function setFeatureImageAction($id, Request $request)
	{
		// Get the blog post and existing image
		$em    = $this->getDoctrine()->getManager();
		$post  = $em->getRepository('StemsBlogBundle:Post')->find($id);

		if ($post->getImage()) {
			$image = $em->getRepository('StemsMediaBundle:Image')->find($post->getImage());
		} else {
			$image = new Image();
			$image->setCategory('blog');
		}

		// Build the form 
		$form = $this->createForm(new ImageType(), $image);

		// Get the html for the popup
		$html = $this->renderView('StemsBlogBundle:Popup:setFeatureImage.html.twig', array(
			'post'		=> $post,
			'existing'	=> rawurldecode($request->query->get('existing')),
			'title'		=> $post->getImage() ? 'Change Feature Image' : 'Add Feature Image',
			'form'		=> $form->createView(),
		));

		return $this->addHtml($html)->success('The popup was successfully created.')->sendResponse();
	}

	/**
	 * Build a popup to set the image of an image section
	 *
	 * @param  integer 		$id 	The ID of the section
	 * @param  Request
	 * @return JsonResponse
	 */
	public function setImageSectionImageAction($id, Request $request)
	{
		// Get the blog post and existing image
		$em      = $this->getDoctrine()->getManager();
		$section = $em->getRepository('StemsBlogBundle:SectionImage')->find($id);

		if ($section->getImage()) {
			$image = $em->getRepository('StemsMediaBundle:Image')->find($section->getImage());
		} else {
			$image = new Image();
		}

		// Build the form 
		$form = $this->createForm(new ImageType(), $image);

		// Get the html for the popup
		$html = $this->renderView('StemsBlogBundle:Popup:setImageSectionImage.html.twig', array(
			'section'	=> $section,
			'existing'	=> rawurldecode($request->query->get('existing')),
			'title'		=> $section->getImage() ? 'Change Image' : 'Add Image',
			'form'		=> $form->createView(),
		));

		return $this->addHtml($html)->success('The popup was successfully created.')->sendResponse();
	}
}