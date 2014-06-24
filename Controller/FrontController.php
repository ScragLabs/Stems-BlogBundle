<?php

namespace Stems\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\RedirectResponse,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\Request;


class FrontController extends Controller
{
	/**
	 * Overview of the blog articles
	 * @param $request Request
	 */
	public function listAction(Request $request)
	{
		// get all of the blog posts for the view
		$em = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'));

		// paginate the result
		$data = $this->get('stems.core.pagination')->paginate($posts, $request, array('maxPerPage' => 6));

		// load the page object from the CMS
		$page = $em->getRepository('StemsPageBundle:Page')->load('magazine');

		return $this->render('StemsBlogBundle:Front:list.html.twig', array(
			'posts' 		=> $data,
			'page'			=> $page,
		));
	}

	/**
	 * Loads the blog articles sequentially
	 * @param $request Request
	 */
	public function sequentialAction(Request $request)
	{
		// get all of the blog posts for the view
		$em = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'));

		// paginate the result
		$data = $this->get('stems.core.pagination')->paginate($posts, $request, array('maxPerPage' => 3));

		// load the page object from the CMS
		$page = $em->getRepository('StemsPageBundle:Page')->load('magazine');

		// create a two level array containing the sections for each post
		$postSections = array();

		foreach ($posts as &$post) {
				
			// prerender the sections, as referencing twig within itself causes a circular reference (grrrr)
			$sections = array();

			foreach ($post->getSections() as $link) {
				$sections[] = $this->get('stems.blog.sections')->renderSection($link);
			}

			$postSections[] = $sections; 
		}

		return $this->render('StemsBlogBundle:Front:sequential.html.twig', array(
			'posts' 		=> $data,
			'postSections' 	=> $postSections,
			'page'			=> $page,
		));
	}

	/**
	 * Display a blog post
	 * @param $slug string 		The slug of the requested blog post
	 */
	public function postAction($slug)
	{
		// get the requested post
		$em = $this->getDoctrine()->getManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBySlug($slug);

		// load the page object from the CMS
		$page = $em->getRepository('StemsPageBundle:Page')->load('magazine/{post}', array(
			'title' 			=> $post->getTitle(),
			'windowTitle' 		=> $post->getTitle().' - '.$post->getExcerpt(),
			'metaKeywords' 		=> $post->getMetaKeywords(),
			'metaDescription' 	=> $post->getMetaDescription(),
		));

		// prerender the sections, as referencing twig within itself causes a circular reference
		$sections = array();

		foreach ($post->getSections() as $link) {
			$sections[] = $this->get('stems.blog.sections')->renderSection($link);
		}

		return $this->render('StemsBlogBundle:Front:post.html.twig', array(
			'page'		=> $page,
			'post' 		=> $post,
			'sections' 	=> $sections,
		));
	}

	/**
	 * Preview a blog post that isn't published yet
	 * @param $slug string 		The slug of the requested blog post
	 */
	public function previewAction($slug)
	{
		// redirect if the user isn't at least an admin
		if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
			return $this->redirect('/magazine');
		}

		// get the requested post
		$em = $this->getDoctrine()->getManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBySlug($slug);

		// load the page object from the CMS
		$page = $em->getRepository('StemsPageBundle:Page')->load('magazine/{post}', array(
			'title' 			=> $post->getTitle(),
			'windowTitle' 		=> $post->getTitle(),
			'metaKeywords' 		=> $post->getMetaKeywords(),
			'metaDescription' 	=> $post->getMetaDescription(),
			'disableAnalytics'	=> true,
		));

		// prerender the sections, as referencing twig within itself causes a circular reference
		$sections = array();

		foreach ($post->getSections() as $link) {
			$sections[] = $this->get('stems.blog.sections')->renderSection($link);
		}

		return $this->render('StemsBlogBundle:Front:post.html.twig', array(
			'page'		=> $page,
			'post' 		=> $post,
			'sections' 	=> $sections,
		));
	}

	/**
	 * Serves the blog as an rss feed
	 */
	public function rssAction()
	{
		// get all of the blog posts for the feed
		$em = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'));

		// doctype
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';

		// rss header
		$xml .= '
			<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
			   <channel>
			      <title><![CDATA['.$this->container->getParameter('stems.site.name').']]></title>
			      <link>'.$this->container->getParameter('stems.site.url').'</link>
			      <description>'.$this->container->getParameter('stems.site.description').'</description>
			      <lastBuildDate>'.$posts[0]->getPublished()->format('r').'</lastBuildDate>
			      <language>en-us</language>
			      <webMaster>'.$this->container->getParameter('stems.site.email').' Webmaster</webMaster>
			      <copyright>Copyright '.date('Y').'</copyright>
			      <ttl>3600</ttl>
		';

		// add the posts
		foreach ($posts as &$post) {

			if ($post->getExcerpt()) {
				$title = $post->getTitle().' - '.$post->getExcerpt();
			} else {
				$title = $post->getTitle();
			}

			$xml .= '<item>';
			$xml .= '<title><![CDATA['.$title.']]></title>';
         	$xml .= '<author><![CDATA['.$this->container->getParameter('stems.site.name').']]></author>';
         	$xml .= '<link>'.$this->container->getParameter('stems.site.url').'/magazine/'.$post->getSlug().'</link>';
         	$xml .= '<guid>'.$this->container->getParameter('stems.site.url').'/magazine/'.$post->getSlug().'</guid>';
         	$xml .= '<category>fashion</category>';
         	$xml .= '<pubDate>'.$post->getPublished()->format('r').'</pubDate>';
         	$xml .= '<description><![CDATA['.$post->getMetaDescription().']]></description>';
         	$xml .= '<media:thumbnail url="http://www.threadandmirror.com/'.$post->getImage().'" />';

			$xml .= '</item>';
		}

		// rss closure
		$xml .= '</channel></rss>';

		// create the response and set the type as xml
		$response = new Response($xml);
		$response->headers->set('Content-Type', 'text/xml');

		return $response;
	}
}
