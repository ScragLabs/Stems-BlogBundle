<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseRestController,
	Symfony\Component\HttpFoundation\Request,
	Stems\BlogBundle\Entity\SectionProductGalleryProduct,
	Stems\BlogBundle\Form\SectionProductGalleryProductType;


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
		// get the section for the field id
		$em      = $this->getDoctrine()->getManager();
		$section = $em->getRepository('StemsBlogBundle:SectionProductGallery')->find($id);

		// create the product
		$image = new SectionProductGalleryProduct();
		$image->setSectionProductGallery($section);
		$image->setHeading('New Product');
		$image->setThumbnail('image.jpg');
		$image->setImage('image.jpg');

		$em->persist($image);
		$em->flush();

		// build the form 
		$form = $this->createForm(new SectionProductGalleryProductType(), $image);

		// get the html for the new product gallery item and to add to the page
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
		// get the url from the query parameter and attempt to parse the product
		$em    = $this->getDoctrine()->getManager();
		$image = $em->getRepository('StemsBlogBundle:SectionProductGalleryProduct')->find($id);

		// build the form 
		$form = $this->createForm(new SectionProductGalleryProductType(), $image);

		// get the html for the new product gallery item and to add to the page
		$html = $this->renderView('StemsBlogBundle:Popup:updateProductGalleryProduct.html.twig', array(
			'product'	=> $image,
			'title'		=> 'Edit Product '.$image->getHeading(),
			'form'		=> $form->createView(),
		));

		return $this->addHtml($html)->success('The popup was successfully created.')->sendResponse();
	}
}
