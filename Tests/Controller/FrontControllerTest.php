<?php

namespace StemsBlogBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FrontControllerTest extends WebTestCase
{
	protected $client;

	public function __construct() 
	{
		$this->client = static::createClient();
	}

    public function testList()
    {
    	// load the page
        $crawler = $this->client->request('GET', '/blog');

        // check any content loaded
        $this->assertTrue($crawler->filter('.cms-page')->count() > 0);

        // optional test cases based on restful or paginated loading
        if ($this->assertTrue($crawler->filter('.rest-load-more')->count() > 0)) {

        }
    }

    public function testPost()
    {
    	// load the page for the first ever blog post
        $crawler = $this->client->request('GET', '/blog/the-wedding-guest-edit');

        // check any content loaded
        $this->assertTrue($crawler->filter('.cms-page')->count() > 0);
    }
}
