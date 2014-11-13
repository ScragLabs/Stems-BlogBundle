<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseAdminController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Stems\BlogBundle\Form\AdminPostType;
use Stems\BlogBundle\Entity\Post;
use Stems\BlogBundle\Entity\Section;
use Doctrine\ORM\NoResultException;

class AdminController extends BaseAdminController
{
	protected $home = 'stems_admin_blog_overview';

	/**
	 * Render the dialogue for the module's dashboard entry in the admin panel
	 */
	public function dashboardAction()
	{
		$em = $this->getDoctrine()->getEntityManager();

		// Get the number of unmoderated comments
		$comments = $em->getRepository('StemsBlogBundle:Comment')->findBy(array('moderated' => false, 'deleted' => false));
		$comments = count($comments); 

		return $this->render('StemsBlogBundle:Admin:dashboard.html.twig', array(
			'comments' => $comments,
		));
	}

	/**
	 * Build the sitemap entries for the bundle
	 */
	public function sitemapAction()
	{
		// The slug used for the blog (eg. news, blog or magazine)
		// @todo: properly integrate this site-wide via config
		$slug = 'blog';

		// Get the posts
		$em    = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('created' => 'DESC'));

		return $this->render('StemsBlogBundle:Admin:sitemap.html.twig', array(
			'slug' 		=> $slug,
			'posts'		=> $posts,
		));
	}

	/**
	 * Blog overview
	 */
	public function indexAction()
	{		
		// Get all undeleted articles
		$em    = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false), array('created' => 'DESC'));

		return $this->render('StemsBlogBundle:Admin:index.html.twig', array(
			'posts' 	=> $posts,
		));
	}

	/**
	 * Create a post, using a template if defined
	 *
	 * @param  Request 
	 */
	public function createAction(Request $request)
	{
		$em = $this->getDoctrine()->getEntityManager();

		// Create a new post for persisting, so we already have an id for adding sections etc.
		$post = new Post();
		$post->setAuthor($this->getUser()->getId());
		$em->persist($post);
		
		// If a title was posted then use it
		$request->get('title') and $post->setTitle($request->get('title'));
		$em->flush();

		// Add the blog template sections as defined in the config
		$position = 1;
		$availableSections = $this->container->getParameter('stems.core.sections.available')['blog'];

		foreach ($this->container->getParameter('stems.blog.template_sections') as $sectionName) {

			// Create a new section of the specified type
			$sectionClass = $availableSections[$sectionName]['class'];
			$section = new $sectionClass();
			$em->persist($section);
			$em->flush();

			// Create the section linkage
			$link = new Section();
			$link->setType($sectionName);
			$link->setPost($post);
			$link->setPosition($position);
			$link->setEntity($section->getId());

			$em->persist($link);
			$em->flush();
			$position++;
		}

		// Save all the things
		$em->flush();

		// Redirect to the edit page for the new post
		return $this->redirect($this->generateUrl('stems_admin_blog_edit', array('id' => $post->getId())));
	}

	/**
	 * Edit a blog post
	 *
	 * @param  integer 	$id  	The ID of the blog post
	 * @param  Request 
	 */
	public function editAction(Request $request, $id)
	{
		// Get the blog post requested
		$em   = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		// Load the section management service
		$sectionHandler = $this->get('stems.core.sections.manager')->setBundle('blog');

		// Throw an error if the post could not be found
		if (!$post) {
			$request->getSession()->getFlashBag()->set('error', 'The requested post could not be found.');
			return $this->redirect($this->generateUrl($this->home));
		}

		// Get the available section types
		$types = $this->container->getParameter('stems.sections.available');

		// Create the edit form and forms for the sections
		$form = $this->createForm(new AdminPostType(), $post);
		$sectionForms = $sectionHandler->getEditors($post->getSections());

		// Handle the form submission
		if ($request->getMethod() == 'POST') {

			// Validate the submitted values
			$form->bind($request);

			//if ($form->isValid()) {

				// Update the post in the database
				$post->setNew(false);
				$post->setTitle(stripslashes($post->getTitle()));
				$post->setExcerpt(stripslashes($post->getExcerpt()));
				$post->setContent(stripslashes($post->getContent()));
				$post->setAuthor($this->getUser()->getId());

				// Order the sections, attached to the page and save their values
				$position = 1;

				foreach ($request->get('sections') as $section) {
					
					// Attach and update order
					$sectionEntity = $em->getRepository('StemsBlogBundle:Section')->find($section);
					$sectionEntity->setPost($post);
					$sectionEntity->setPosition($position);

					// Get all form fields relevant to the section...
					foreach ($request->request->all() as $parameter => $value) {
						// Strip the section id from the parameter group and save if it matches
						$explode = explode('_', $parameter);
						$parameterId = reset($explode);
						$parameterId == $sectionEntity->getId() and $sectionParameters = $value;
					}

					// ...then process and update the entity
					$sectionHandler->saveSection($sectionEntity, $sectionParameters, $request);
					$em->persist($sectionEntity);

					$position++;
				}

				// If there were no errors then save the entity, otherwise display the save errors
				// if ($sectionHandler->getSaveErrors()) {
					
					$em->persist($post);
					$em->flush();
					$request->getSession()->getFlashBag()->set('success', 'The post "'.$post->getTitle().'" has been updated.');

					return $this->redirect($this->generateUrl($this->home));

				// } else {
				// 	$request->getSession()->getFlashBag()->set('error', 'Your request was not processed as errors were found.');
				// 	$request->getSession()->getFlashBag()->set('debug', '');
				// }
			//}
		}

		return $this->render('StemsBlogBundle:Admin:edit.html.twig', array(
			'form'			=> $form->createView(),
			'sectionForms'	=> $sectionForms,
			'types'			=> $types['blog'],
			'post' 			=> $post,
		));
	}

	/**
	 * Delete a post
	 *
	 * @param  integer 	$id  	The ID of the blog post
	 * @param  Request 
	 */
	public function deleteAction(Request $request, $id)
	{
		// Get the post
		$em   = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($post) {

			// Delete the post if was found
			$name = $post->getTitle();
			$post->setDeleted(true);
			$em->persist($post);
			$em->flush();

			// Return the success message
			$request->getSession()->getFlashBag()->set('success', 'The post "'.$name.'" was successfully deleted!');

		} else {
			$request->getSession()->getFlashBag()->set('error', 'The requested post could not be deleted as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}

	/**
	 * Publish/unpublish a post
	 *
	 * @param  integer 	$id  	The ID of the blog post
	 * @param  Request 
	 */
	public function publishAction(Request $request, $id)
	{
		// Get the post
		$em   = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($post) {

			// Set the post to published/unpublished 
			if ($post->getStatus() == 'Draft') {	
				$post->setStatus('Published');
				$post->setPublished(new \DateTime());
				$request->getSession()->getFlashBag()->set('success', 'The post "'.$post->getTitle().'" was successfully published!');
			} else {
				$post->setStatus('Draft');
				$request->getSession()->getFlashBag()->set('success', 'The post "'.$post->getTitle().'" was successfully unpublished!');
			}

			$em->persist($post);
			$em->flush();

		} else {
			$request->getSession()->getFlashBag()->set('error', 'The requested post could not be published as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}

	/**
	 * A listing of all unmoderated comments
	 */
	public function commentsAction()
	{		
		// Get all unmoderated comments
		$em       = $this->getDoctrine()->getEntityManager();
		$comments = $em->getRepository('StemsBlogBundle:Comment')->findBy(array('deleted' => false, 'moderated' => false), array('created' => 'DESC'));

		return $this->render('StemsBlogBundle:Admin:comments.html.twig', array(
			'comments' 	=> $comments,
		));
	}

	/**
	 * Moderate a comment
	 *
	 * @param  integer 	$id  	The ID of the comment
	 * @param  Request 
	 */
	public function moderateCommentAction(Request $request, $id)
	{
		// Get the comment
		$em      = $this->getDoctrine()->getEntityManager();
		$comment = $em->getRepository('StemsBlogBundle:Comment')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($comment) {

			// Set the comment to moderated
			$comment->setModerated(true);
			$request->getSession()->getFlashBag()->set('success', 'The comment was successfully authorised!');

			$em->persist($comment);
			$em->flush();

		} else {
			$request->getSession()->getFlashBag()->set('error', 'The requested comment could not be moderated as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}

	/**
	 * Delete a comment
	 *
	 * @param  integer 	$id  	The ID of the post comment
	 * @param  Request 
	 */
	public function deleteCommentAction(Request $request, $id)
	{
		// Get the comment
		$em   = $this->getDoctrine()->getEntityManager();
		$comment = $em->getRepository('StemsBlogBundle:Comment')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($comment) {

			// Delete the comment if was found
			$comment->setDeleted(true);
			$em->persist($comment);
			$em->flush();

			// Return the success message
			$request->getSession()->getFlashBag()->set('success', 'The comment was successfully deleted!');

		} else {
			$request->getSession()->getFlashBag()->set('error', 'The requested comment could not be deleted as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}
}
