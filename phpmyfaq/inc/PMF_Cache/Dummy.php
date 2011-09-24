<?php

class PMF_Cache_Dummy extends PMF_Cache_Service
{

	public function __construct(array $config)
	{

	}

	public function clearArticle($id)
	{

	}

	public function clearAll()
	{
		$this->instance->banUrl(".*");
	}
}
