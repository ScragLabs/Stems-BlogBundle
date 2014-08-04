<?php

namespace Stems\BlogBundle\Controller;

// Symfony Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\RedirectResponse,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\Request;

class WidgetController extends Controller
{
	/**
	 * Renders the latest blog post
	 */
	public function latestPostAction()
	{
		// get the latest blog post
		$em = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published', 'hideFromWidgets' => false), array('created' => 'DESC'), 1);

		return $this->render('StemsBlogBundle:Widget:latestPost.html.twig', array(
			'post' 	=> reset($posts),
		));
	}

	/**
	 * Renders a (unpaginated) list of the most recent posts, defaulting to 4 if no limit is set
	 *
	 * @param  integer 	$limit 	A custom limit for the amount of posts the render
	 */
	public function latestPostsSidebarAction($limit=4)
	{
		// get the latest blog post
		$em = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published', 'hideFromWidgets' => false), array('created' => 'DESC'), $limit);

		return $this->render('StemsBlogBundle:Widget:latestPostsSidebar.html.twig', array(
			'posts' 	=> $posts,
		));
	}

	/**
	 * Renders a specific blog post
	 *
	 * @param  integer 	$id 	The ID of the blog post
	 */
	public function featurePostAction($id)
	{
		// get the blog post
		$em = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->find($id);

		return $this->render('StemsBlogBundle:Widget:latestPost.html.twig', array(
			'post' 	=> $post,
		));
	}

	/**
	 * Renders a blog post that features a product
	 *
	 * @param  integer 	$id 	The ID of the blog post
	 */
	public function featuredInAction($id)
	{
		// get the blog post
		$em = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->find($id);

		return $this->render('StemsBlogBundle:Widget:featuredIn.html.twig', array(
			'post' 	=> $post,
		));
	}

	/**
	 * Renders the latest blog posts in a feature block
	 */
	public function homepageFeatureAction()
	{
		// get the latest blog post
		$em = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published', 'hideFromWidgets' => false), array('created' => 'DESC'), 5);

		return $this->render('StemsBlogBundle:Widget:homepageFeature.html.twig', array(
			'posts' 	=> $posts,
		));
	}
}
