<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseRestController;
use Symfony\Component\HttpFoundation\Request;
use Stems\BlogBundle\Entity\Section;
use Stems\BlogBundle\Entity\SectionProductGalleryProduct;
use Stems\BlogBundle\Form\SectionProductGalleryProductType;
use Stems\MediaBundle\Entity\Image;
use Stems\MediaBundle\Form\ImageType;
use Stems\BlogBundle\Entity\Comment;

class RestController extends BaseRestController
{
	/**
	 * Returns form html for the requested section type
	 *
	 * @param  integer 	$offset 	The amount of posts already loaded, and therefore the query offset
	 * @param  integer 	$chunk 		The maximum amount of posts to get
	 * @return JsonResponse
	 */
	public function getMorePostsAction($offset, $chunk=3)
	{
		// Get more of the blog posts for the view
		$em    = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'), $chunk, $offset);

		// Render the html for the posts
		$html = '';

		foreach ($posts as $post) {

			// Prerender the sections, as referencing twig within itself causes a circular reference
			$sections = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSections($post);

			$html .= $this->renderView('StemsBlogBundle:Rest:post.html.twig', array(
				'post' 		=> $post,
				'sections' 	=> $sections,
			));
		}
		
		// Let the ajax response know when there's no more additional posts to load
		count($posts) < $chunk and $this->setCallback('stopLoading');

		return $this->addHtml($html)->success()->sendResponse();
	}

	/**
	 * Processes the submission of an add comment form
	 *
	 * @param  integer 		$post 		The ID of the post to add the comment to
	 * @param  Request 		$request
	 * @return JsonResponse
	 */
	public function addCommentAction($post, Request $request)
	{
		// Get the post
		$em   = $this->getDoctrine()->getManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->find($post);

		// Build the comment form
		$comment 	  = new Comment();
		$form         = $this->createForm('blog_comment', $comment);

		// Process the submission
		if ($request->getMethod() == 'POST') {

			// Validate the submitted values
			$form->bindRequest($request);

			if ($form->isValid()) {

				// Set the user ID if we require login for commenting
				if $this->container->getParameter('stems.blog.comments.require_login')) {
					if ($this->get('security.context')->isGranted('ROLE_USER')) {
						$comment->setAuthor($this->getUser()->getId());
					} else {
						return $this->error('You need to be logged in to post a comment.', true)->sendResponse();
					}
				}

				// Attach to the post and save
				$comment->setPost($post);
				$em->persist($comment);
				$em->flush();

				// Return the rendered comment
				$html = $this->renderView('StemsBlogBundle:Rest:comment.html.twig', array(
					'image'		 => $image,
					'created'	 => true,
					'moderation' => $this->container->getParameter('stems.blog.comments.moderated'),
				));

				return $this->addHtml($html)->setCallback('commentAdded')->success()->sendResponse();
			} else {
				var_dump($form); die();
				// Add the validation errors to the response
				$this->addValidationErrors($form);
				
				return $this->setCallback('commentNotAdded')->error()->sendResponse();
			}
		}

		return $this->error('Unauthorised Method.')->sendResponse();
	}

	/**
	 * Returns form html for the requested section type
	 *
	 * @param  integer 	$type 	Section type id
	 * @return JsonResponse
	 */
	public function addSectionTypeAction($type)
	{
		// Get the section type
		$em    	   = $this->getDoctrine()->getManager();
		$available = $this->container->getParameter('stems.sections.available')

		// Create a new section of the specified type
		$class = $available['blog'][$type]['class'];
		$section = new $class();

		$em->persist($section);
		$em->flush();
		
		// Create the section linkage
		$link = new Section();
		$link->setType($type);
		$link->setEntity($section->getId());
		$em->persist($link);
		$em->flush();

		// Get the form html
		$sectionHandler = $this->get('stems.core.sections.manager')->setBundle('blog');
		$html = $section->editor($sectionHandler, $link);

		// Store the section id for use in the response handler
		$meta = array('section' => $link->getId());

		return $this->addHtml($html)->addMeta($meta)->success()->sendResponse();
	}

	/**
	 * Removes the specified section and its linkage
	 *
	 * @param  integer 		$id 	Section id
	 * @return JsonResponse
	 */
	public function removeSectionAction($id)
	{
		try
		{
			// Get the section linkage and the specific section
			$em      = $this->getDoctrine()->getManager();
			$link    = $em->getRepository('StemsBlogBundle:Section')->find($id);
			$types   = $this->container->getParameter('stems.sections.available');
			$section = $em->getRepository($types['blog'][$link->getType()]['entity'])->find($link->getEntity());

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
	 * Updates the feature image for a blog post
	 *
	 * @param  integer 		$id 		The ID of the Product Gallery Section to add the image to
	 * @param  Request 		$request
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

		// Build the form and handle the request
		$form = $this->createForm('media_image', $image);

		if ($form->bindRequest($request)->isValid()) {

			// Upload the file and save the entity
			$image->doUpload();
			$em->persist($image);
			$em->flush();

			// Get the html for updating the feature image
			$html = $this->renderView('StemsBlogBundle:Rest:setFeatureImage.html.twig', array(
				'post'	=> $post,
				'image'	=> $image,
			));

			return $this->addHtml($html)->setCallback('updateFeatureImage')->success('Image updated.')->sendResponse();
		} else {
			return $this->error('Please choose an image to upload.', true)->sendResponse();
		}
	}

	/**
	 * Updates the image for an image section
	 *
	 * @param  integer 		$id 	The ID of the image section
	 * @param  Request
	 * @return JsonResponse
	 */
	public function setImageSectionImageAction($id, Request $request)
	{
		// Get the section and existing image
		$em      = $this->getDoctrine()->getManager();
		$section = $em->getRepository('StemsBlogBundle:SectionImage')->find($id);

		if ($section->getImage()) {
			$image = $em->getRepository('StemsMediaBundle:Image')->find($section->getImage());
		} else {
			$image = new Image();
		}

		// Build the form and handle the request
		$form = $this->createForm('media_image', $image);

		if ($form->bindRequest($request)->isValid()) {

			// Upload the file and save the entity
			$image->doUpload();
			$em->persist($image);
			$em->flush();

			// Get the html for updating the feature image
			$html = $this->renderView('StemsBlogBundle:Rest:setImageSectionImage.html.twig', array(
				'section'	=> $section,
				'image'		=> $image,
			));

			// Set the meta data for the update callback
			$meta = array(
				'imageType' => 'imageGalleryImage',
				'section'	=> $section->getId(),
			);

			return $this->addHtml($html)->addMeta($meta)->setCallback('updateSectionImage')->success('Image updated.')->sendResponse();
		} else {
			return $this->error('Please choose an image to upload.', true)->sendResponse();
		}
	}

	/**
	 * Adds a product to a product gallery section
	 *
	 * @param  integer 		$id 	The ID of the Product Gallery Section to add the image to
	 * @param  Request
	 * @return JsonResponse
	 */
	public function parseProductGalleryProductAction($id, Request $request)
	{
		// Get the url from the query paramter and attempt to parse the product
		$em = $this->getDoctrine()->getManager();

		$product = $em->getRepository('ThreadAndMirrorProductsBundle:Product')->getProductFromUrl($request->get('url'));

		// Get the section for the field id
		$section = $em->getRepository('StemsBlogBundle:SectionProductGallery')->findOneById($id);

		// If we manage to parse a product from the url then create the product listing for the gallery
		if (is_object($product)) {

			// Save the product as it may not already exist in the database
			$em->persist($product);

			// Create a pick from the product
			$image = new SectionProductGalleryProduct();
			$image->setHeading($product->getName());
			$image->setCaption($product->getShop()->getName());
			$image->setUrl($this->generateUrl('thread_products_front_product_buy', array('slug' => $product->getslug())));
			$image->setThumbnail($product->getThumbnail());
			$image->setImage($product->getImage());
			$image->setRatio($product->getShop()->getImageRatio());
			$image->setPid($product->getId());

			$em->persist($image);
			$em->flush();

			// Get the associated section linkage to tag the fields with the right id
			$link = $em->getRepository('StemsBlogBundle:Section')->findOneByEntity($section->getId());

			// Get the html for the new product gallery item and to add to the page
			$html = $this->renderView('StemsBlogBundle:Rest:productGalleryProduct.html.twig', array(
				'product'	=> $image,
				'section'	=> $section,
				'link'		=> $link,
			));

			// Store the section id for use in the response handler
			$this->addMeta(array('section' => $link->getId()));

			return $this->addHtml($html)->success('The product was successfully updated.')->sendResponse();
		} else {
			return $this->error('We could not load a product using that link.', true)->sendResponse();
		}
	}

	/**
	 * Update a product gallery product, both generated and manually added
	 *
	 * @param  integer 		$id 	The ID of the Product Gallery Section to update
	 * @param  Request
	 * @return JsonResponse
	 */
	public function updateProductGalleryProductAction($id, Request $request)
	{
		// Get the url from the query parameter and attempt to parse the product
		$em    = $this->getDoctrine()->getManager();
		$image = $em->getRepository('StemsBlogBundle:SectionProductGalleryProduct')->find($id);

		$data = json_decode($request->getContent());

		// If the product exists, then handle the request
		if (is_object($image)) {

			// Update the product
			$image->setHeading($request->request->get('section_productgalleryproduct_type')['heading']);
			$image->setCaption($request->request->get('section_productgalleryproduct_type')['caption']);
			$image->setUrl($request->request->get('section_productgalleryproduct_type')['url']);
			$image->setThumbnail($request->request->get('section_productgalleryproduct_type')['thumbnail']);
			$image->setImage($request->request->get('section_productgalleryproduct_type')['image']);

			$em->persist($image);
			$em->flush();

			// Get the associated section linkage to tag the fields with the right id
			$link = $em->getRepository('StemsBlogBundle:Section')->findOneByEntity($image->getSectionProductGallery()->getId());

			// Get the html for the product gallery item and to add to the page
			$html = $this->renderView('StemsBlogBundle:Rest:productGalleryProduct.html.twig', array(
				'product'	=> $image,
				'section'	=> $image->getSectionProductGallery(),
				'link'		=> $link,
			));

			// Store the section and product id for use in the response handler
			$this->addMeta(array(
				'section' => $link->getId(),
				'product' => $image->getId(),
			));

			return $this->addHtml($html)->setCallback('insertProductGalleryProduct')->success('The product was successfully updated.')->sendResponse();
		} else {
			return $this->addHtml($html)->error('There was a problem updating the product.', true)->sendResponse();
		}
	}
}
