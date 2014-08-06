<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseRestController,
	Symfony\Component\HttpFoundation\Request,
	Stems\BlogBundle\Entity\Section,
	Stems\BlogBundle\Entity\SectionProductGalleryProduct,
	Stems\BlogBundle\Form\SectionProductGalleryProductType;

class RestController extends BaseRestController
{
	/**
	 * Returns form html for the requested section type
	 *
	 * @param  integer 	$offset 	The amount of posts already loaded, and therefore the query offset
	 * @param  integer 	$chunk 		The maximum amount of posts to get
	 */
	public function getMorePostsAction($offset, $chunk=3)
	{
		// get more of the blog posts for the view
		$em = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'), $chunk, $offset);

		// render the html for the posts
		$html = '';

		foreach ($posts as &$post) {
			// prerender the sections, as referencing twig within itself causes a circular reference
			$sections = array();

			foreach ($post->getSections() as $link) {
				$sections[] = $this->get('stems.blog.sections')->renderSection($link);
			}

			$html .= $this->renderView('StemsBlogBundle:Rest:post.html.twig', array(
				'post' 		=> $post,
				'sections' 	=> $sections,
			));
		}
		
		// let the ajax response know when there's no more additional posts to load
		count($posts) < $chunk and $this->setCallback('stopLoading');

		return $this->addHtml($html)->success()->sendResponse();
	}

	/**
	 * Returns form html for the requested section type
	 *
	 * @param  integer 	$id 	Section type id
	 */
	public function addSectionTypeAction($id)
	{
		// get the section type
		$em = $this->getDoctrine()->getManager();
		$type = $em->getRepository('StemsBlogBundle:SectionType')->find($id);

		// create a new section of the specified type
		$class = 'Stems\\BlogBundle\\Entity\\'.$type->getClass();
		$section = new $class();

		$em->persist($section);
		$em->flush();
		
		// create the section linkage
		$link = new Section();
		$link->setType($type);
		$link->setEntity($section->getId());
		$em->persist($link);
		$em->flush();

		// get the form html
		$sectionHandler = $this->get('stems.blog.sections');
		$html = $section->editor($sectionHandler, $link);

		// store the seciton id for use in the response handler
		$meta = array('section' => $link->getId());

		return $this->addHtml($html)->addMeta($meta)->success()->sendResponse();
	}

	/**
	 * Removes the specified section and its linkage
	 *
	 * @param  integer 		$id 	Section id
	 */
	public function removeSectionAction($id)
	{
		try
		{
			// get the section linkage and the specific section
			$em = $this->getDoctrine()->getManager();
			$link = $em->getRepository('StemsBlogBundle:Section')->find($id);
			$section = $em->getRepository('StemsBlogBundle:'.$link->getType()->getClass())->find($link->getEntity());

			$em->remove($section);
			$em->remove($link);
			$em->flush();

			return $this->success('Section deleted.')->sendResponse();
		}
		catch (\Exception $e) 
		{
			return $this->error($e->getMessage())->sendResponse();
		}
	}

	/**
	 * Adds a product to a product gallery section
	 *
	 * @param  integer 		$id 	The ID of the Product Gallery Section to add the image to
	 * @param  Request
	 */
	public function addProductGalleryProductAction($id, Request $request)
	{
		// get the url from the query paramter and attempt to parse the product
		$em = $this->getDoctrine()->getManager();

		$product = $em->getRepository('ThreadAndMirrorProductsBundle:Product')->getProductFromUrl($request->get('url'));

		// get the section for the field id
		$section = $em->getRepository('StemsBlogBundle:SectionProductGallery')->findOneById($id);

		// if we manage to parse a product from the url then create the product listing for the gallery
		if (is_object($product)) {

			// save the product as it may not already exist in the database
			$em->persist($product);

			// create a pick from the product
			$image = new SectionProductGalleryProduct();
			$image->setHeading($product->getName());
			$image->setCaption($product->getShop()->getName());
			$image->setUrl($product->getFrontendUrl());
			$image->setThumbnail($product->getThumbnail());
			$image->setImage($product->getImage());
			$image->setRatio($product->getShop()->getImageRatio());
			$image->setPid($product->getId());

			$em->persist($image);
			$em->flush();

			// get the associated section linkage to tag the fields with the right id
			$link = $em->getRepository('StemsBlogBundle:Section')->findOneByEntity($section->getId());

			// get the html for the new product gallery item and to add to the page
			$html = $this->renderView('StemsBlogBundle:Rest:productGalleryProduct.html.twig', array(
				'product'	=> $image,
				'section'	=> $section,
				'link'		=> $link,
			));

			return $this->addHtml($html)->success('The product was successfully loaded from the link.')->sendResponse();
		} else {
			return $this->addHtml($html)->error('We could not load a product using that link.', true)->sendResponse();
		}
	}

	/**
	 * Build a popup to add a product to a product gallery section
	 *
	 * @param  integer 		$id 	The ID of the Product Gallery Section to add the image to
	 * @param  Request
	 */
	public function popupAddProductGalleryProductAction($id, Request $request)
	{
		// get the url from the query paramter and attempt to parse the product
		$em = $this->getDoctrine()->getManager();

		// get the section for the field id
		$section = $em->getRepository('StemsBlogBundle:SectionProductGallery')->findOneById($id);

		// create the product
		$image = new SectionProductGalleryProduct();
		$image->setHeading('New Product');
		// $image->setCaption($product->getShop()->getName());
		// $image->setUrl($product->getFrontendUrl());
		$image->setThumbnail('/image-link');
		$image->setImage('/image-link');
		// $image->setRatio($product->getShop()->getImageRatio());
		// $image->setPid($product->getId());

		$em->persist($image);
		$em->flush();

		// build the form 
		$form = $this->createForm(new SectionProductGalleryProductType(), $image);

		// get the associated section linkage to tag the fields with the right id
		$link = $em->getRepository('StemsBlogBundle:Section')->findOneByEntity($section->getId());

		// get the html for the new product gallery item and to add to the page
		$html = $this->renderView('StemsBlogBundle:Popup:addProductGalleryProduct.html.twig', array(
			'product'	=> $image,
			'form'		=> $form->createView(),
		));

		return $this->addHtml($html)->success('The popup was successfully created.')->sendResponse();
	}
}
